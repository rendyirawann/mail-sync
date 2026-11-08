<?php

namespace App\Http\Controllers\Backend\Saas;

use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Saas\AdminCreateTeamRequest;
use App\Http\Requests\Saas\ProofRequest;
use App\Models\Plan;
use App\Models\Registration;
use App\Services\Saas\SubscriptionService;
use App\Support\SaasSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Superadmin: memproses pendaftaran tim (setujui dengan bukti transfer / tolak)
 * dan mendaftarkan tim secara langsung.
 */
class RegistrationAdminController extends Controller
{
    public function __construct(private readonly SubscriptionService $subscriptions) {}

    public function index(Request $request): View
    {
        $status = $request->query('status', RegistrationStatus::Pending->value);
        $search = trim((string) $request->query('q', ''));

        $registrations = Registration::query()
            ->with('plan', 'team')
            ->when(in_array($status, array_column(RegistrationStatus::cases(), 'value'), true), fn ($q) => $q->where('status', $status))
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $search).'%';
                $q->where('code', 'like', $like)->orWhere('team_name', 'like', $like)
                    ->orWhere('owner_name', 'like', $like)->orWhere('owner_email', 'like', $like)
                    ->orWhere('owner_whatsapp', 'like', $like);
            }))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('backend.saas.registrations.index', [
            'registrations' => $registrations,
            'status' => $status,
            'search' => $search,
            'counts' => Registration::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function show(Registration $registration): View
    {
        $registration->load('plan', 'team', 'processor');

        return view('backend.saas.registrations.show', [
            'registration' => $registration,
            'customerWa' => SaasSettings::customerLink($registration->owner_whatsapp, $this->loginMessage($registration)),
        ]);
    }

    /** Setujui: buat tim + slug + akun owner dari isian pendaftar. */
    public function approve(ProofRequest $request, Registration $registration): RedirectResponse
    {
        $team = $this->subscriptions->approve($registration, $request->user(), $request->file('proof'), $request->validated('note'));

        return redirect()->route('admin.saas.registrations.show', $registration)
            ->with('success', "Tim {$team->name} aktif. Tautan login: ".route('team.login', $team));
    }

    public function reject(Request $request, Registration $registration): RedirectResponse
    {
        abort_unless($registration->status === RegistrationStatus::Pending, 422, 'Pendaftaran ini sudah diproses.');

        $data = $request->validate(['note' => ['required', 'string', 'max:255']], [], ['note' => 'alasan penolakan']);
        $this->subscriptions->reject($registration, $request->user(), $data['note']);

        return redirect()->route('admin.saas.registrations.index')->with('success', "Pendaftaran {$registration->code} ditolak.");
    }

    /** Form Superadmin mendaftarkan tim langsung. */
    public function create(): View
    {
        return view('backend.saas.registrations.create', ['plans' => Plan::active()->get()]);
    }

    public function store(AdminCreateTeamRequest $request): RedirectResponse
    {
        $registration = $this->subscriptions->register($request->validated(), 'superadmin', $request->ip());
        $this->subscriptions->approve($registration, $request->user(), $request->file('proof'), $request->validated('note'));

        return redirect()->route('admin.saas.registrations.show', $registration)
            ->with('success', 'Tim berhasil didaftarkan dan langsung aktif.');
    }

    private function loginMessage(Registration $registration): string
    {
        if ($registration->team === null) {
            return "Halo {$registration->owner_name}, terima kasih telah mendaftar di ".config('app.name').'.';
        }

        return "Halo {$registration->owner_name}, akun tim *{$registration->team->name}* di ".config('app.name')." sudah aktif. 🎉\n\n"
            .'Login: '.route('team.login', $registration->team)."\n"
            ."Email: {$registration->owner_email}\n"
            ."Password: sesuai yang Anda isi saat mendaftar.\n\n"
            .'Simpan tautan login di atas — setiap tim punya alamat login sendiri.';
    }
}
