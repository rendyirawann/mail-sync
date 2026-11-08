<?php

namespace App\Http\Controllers\Team;

use App\Enums\DispositionStatus;
use App\Http\Controllers\Controller;
use App\Models\Disposition;
use App\Models\Employee;
use App\Models\IncomingLetter;
use App\Models\Team;
use App\Services\Letters\DispositionService;
use App\Services\Letters\LetterRenderer;
use App\Support\Tenancy\CurrentTeam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Disposisi: kirim, kotak masuk "Disposisi Saya", tindak lanjut, cetak lembar.
 */
class DispositionController extends Controller
{
    public function __construct(private readonly DispositionService $service) {}

    public function index(Request $request, Team $team): View
    {
        $user = $request->user();
        $scope = $request->query('lingkup') === 'semua' && $user->can('team.dispositions.create') ? 'semua' : 'saya';
        $status = (string) $request->query('status', 'aktif');

        $dispositions = Disposition::query()
            ->with(['letter:id,subject,sender,agenda_number,agenda_year,nature', 'recipient.division', 'sender:id,name'])
            ->when($scope === 'saya', fn ($q) => $q->whereHas('recipient', fn ($r) => $r->where('user_id', $user->id)))
            ->when($status === 'aktif', fn ($q) => $q->where('status', '!=', DispositionStatus::Selesai->value))
            ->when($status === 'selesai', fn ($q) => $q->where('status', DispositionStatus::Selesai->value))
            ->when($status === 'terlambat', fn ($q) => $q->where('status', '!=', DispositionStatus::Selesai->value)
                ->whereNotNull('due_date')->whereDate('due_date', '<', now()->toDateString()))
            ->orderByRaw('due_date is null')->orderBy('due_date')->latest()
            ->paginate(15)->withQueryString();

        return view('team.dispositions.index', [
            'dispositions' => $dispositions,
            'scope' => $scope,
            'status' => $status,
            'linkedEmployee' => Employee::where('user_id', $user->id)->exists(),
        ]);
    }

    public function store(Request $request, Team $team, IncomingLetter $incoming): RedirectResponse
    {
        $teamId = app(CurrentTeam::class)->id();

        $data = $request->validate([
            'to_employee_ids' => ['required', 'array', 'min:1', 'max:20'],
            'to_employee_ids.*' => ['uuid', Rule::exists('employees', 'id')->where('team_id', $teamId)->where('is_active', true)],
            'instruction' => ['required', 'string', 'max:2000'],
            'due_date' => ['nullable', 'date', 'after_or_equal:today'],
        ], [], ['to_employee_ids' => 'penerima disposisi', 'instruction' => 'instruksi', 'due_date' => 'tenggat']);

        $created = $this->service->dispatch($incoming, $request->user(), $data['to_employee_ids'], $data['instruction'], $data['due_date'] ?? null);

        return redirect()->route('team.incoming.show', $incoming)
            ->with('success', "Disposisi dikirim ke {$created->count()} pegawai.");
    }

    public function update(Request $request, Team $team, Disposition $disposition): RedirectResponse
    {
        abort_unless($this->service->canRespond($disposition, $request->user()), 403, 'Hanya penerima disposisi yang dapat menindaklanjuti.');

        $data = $request->validate([
            'status' => ['required', Rule::enum(DispositionStatus::class)],
            'response' => [Rule::requiredIf($request->input('status') === DispositionStatus::Selesai->value), 'nullable', 'string', 'max:2000'],
        ], ['response.required' => 'Tuliskan laporan singkat tindak lanjut sebelum menandai selesai.'], ['response' => 'laporan tindak lanjut']);

        $this->service->respond($disposition, $request->user(), DispositionStatus::from($data['status']), $data['response'] ?? null);

        return back()->with('success', 'Tindak lanjut disposisi disimpan.');
    }

    /** Lembar disposisi siap cetak (format kertas resmi). */
    public function print(Team $team, Disposition $disposition, LetterRenderer $renderer): View
    {
        $disposition->load(['letter.category', 'recipient.division', 'sender:id,name']);

        return view('team.dispositions.print', [
            'd' => $disposition,
            'letterhead' => $renderer->letterhead($team),
        ]);
    }
}
