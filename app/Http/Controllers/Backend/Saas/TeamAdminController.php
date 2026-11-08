<?php

namespace App\Http\Controllers\Backend\Saas;

use App\Enums\TeamStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Saas\ProofRequest;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Registration;
use App\Models\Team;
use App\Services\Saas\SubscriptionService;
use App\Support\SaasSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Superadmin: daftar tim pelanggan, detail, perpanjangan, dan status.
 */
class TeamAdminController extends Controller
{
    public function __construct(private readonly SubscriptionService $subscriptions) {}

    public function index(Request $request): View
    {
        $status = (string) $request->query('status', '');
        $search = trim((string) $request->query('q', ''));
        $expiring = $request->boolean('expiring');

        $teams = Team::query()
            ->with(['plan', 'owner'])
            ->withCount('users')
            ->when(TeamStatus::tryFrom($status), fn ($q, $s) => $q->where('status', $s->value))
            ->when($expiring, fn ($q) => $q->whereNotNull('expires_at')->whereBetween('expires_at', [now()->subDays(SubscriptionService::GRACE_DAYS), now()->addDays(7)]))
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $search).'%';
                $q->where('name', 'like', $like)->orWhere('slug', 'like', $like)->orWhere('email', 'like', $like)->orWhere('phone', 'like', $like);
            }))
            ->orderByRaw('expires_at is null')
            ->orderBy('expires_at')
            ->paginate(15)
            ->withQueryString();

        $monthStart = now()->startOfMonth();

        return view('backend.saas.teams.index', [
            'teams' => $teams,
            'status' => $status,
            'search' => $search,
            'expiring' => $expiring,
            'stats' => [
                'active' => Team::where('status', TeamStatus::Active->value)->count(),
                'frozen' => Team::where('status', TeamStatus::Frozen->value)->count(),
                'expiring' => Team::where('status', TeamStatus::Active->value)->whereNotNull('expires_at')->whereBetween('expires_at', [now(), now()->addDays(7)])->count(),
                'pending' => Registration::where('status', 'pending')->count(),
                'revenue_month' => (int) Payment::where('paid_at', '>=', $monthStart)->sum('amount'),
                'revenue_total' => (int) Payment::sum('amount'),
            ],
            'revenueChart' => $this->revenueByMonth(),
        ]);
    }

    public function show(Team $team): View
    {
        $team->load(['plan', 'users' => fn ($q) => $q->orderByRaw("team_role = 'owner' desc")->orderBy('name')]);
        $owner = $team->users->firstWhere('team_role', \App\Enums\TeamRole::Owner);

        return view('backend.saas.teams.show', [
            'team' => $team,
            'payments' => $team->payments()->with('plan', 'verifier')->get(),
            'plans' => Plan::active()->get(),
            'customerWa' => $owner && $owner->no_wa
                ? SaasSettings::customerLink($owner->no_wa, "Halo {$owner->name}, berikut tautan login tim *{$team->name}* di ".config('app.name').":\n".route('team.login', $team))
                : null,
        ]);
    }

    public function renew(ProofRequest $request, Team $team): RedirectResponse
    {
        $plan = Plan::findOrFail($request->validated('plan_id'));
        $payment = $this->subscriptions->renew($team, $plan, $request->user(), $request->file('proof'), $request->validated('note'));

        $until = $team->fresh()?->expires_at?->locale('id')->translatedFormat('j F Y') ?? 'selamanya (lifetime)';

        return redirect()->route('admin.saas.teams.show', $team)
            ->with('success', "{$payment->type->label()} berhasil dicatat. Aktif sampai {$until}.");
    }

    public function updateStatus(Request $request, Team $team): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::enum(TeamStatus::class)]]);
        $this->subscriptions->setStatus($team, TeamStatus::from($data['status']));

        return back()->with('success', 'Status tim diperbarui menjadi '.TeamStatus::from($data['status'])->label().'.');
    }

    /** Bukti transfer disimpan privat — hanya bisa dibuka Superadmin lewat route ini. */
    public function proof(Payment $payment): StreamedResponse
    {
        abort_unless($payment->proof_path && Storage::disk('local')->exists($payment->proof_path), 404);

        return Storage::disk('local')->response($payment->proof_path, 'bukti-'.$payment->id.'.'.pathinfo($payment->proof_path, PATHINFO_EXTENSION), [
            'Content-Type' => $payment->proof_mime ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ], 'inline');
    }

    /** @return array{labels: list<string>, values: list<int>} */
    private function revenueByMonth(): array
    {
        $labels = [];
        $values = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->startOfMonth()->subMonths($i);
            $labels[] = $month->locale('id')->translatedFormat('M Y');
            $values[] = (int) Payment::whereBetween('paid_at', [$month, $month->copy()->endOfMonth()])->sum('amount');
        }

        return ['labels' => $labels, 'values' => $values];
    }
}
