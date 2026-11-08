<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Saas\StoreTeamRegistrationRequest;
use App\Models\BankAccount;
use App\Models\Plan;
use App\Models\Registration;
use App\Services\Saas\SubscriptionService;
use App\Support\SaasSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pendaftaran tim oleh calon pelanggan:
 * isi form → halaman tagihan (rekening + tombol konfirmasi WA) → Superadmin menyetujui.
 */
class RegistrationController extends Controller
{
    public function __construct(private readonly SubscriptionService $subscriptions) {}

    public function create(Request $request): View
    {
        $plans = Plan::active()->get();

        return view('frontend.register.create', [
            'plans' => $plans,
            'selectedPlan' => (int) $request->query('paket', ($plans->firstWhere('code', 'monthly') ?? $plans->first())?->id),
        ]);
    }

    public function store(StoreTeamRegistrationRequest $request): RedirectResponse
    {
        $registration = $this->subscriptions->register($request->validated(), 'public', $request->ip());

        // Hanya pemohon di sesi ini yang boleh melihat ringkasan lengkap tagihannya.
        $request->session()->put('registration_access.'.$registration->code, true);

        return redirect()->route('register.team.show', $registration);
    }

    public function show(Request $request, Registration $registration): View
    {
        abort_unless($request->session()->has('registration_access.'.$registration->code), 404);

        $registration->load('plan', 'team');

        return view('frontend.register.show', [
            'registration' => $registration,
            'banks' => BankAccount::active()->get(),
            'waLink' => $registration->status === RegistrationStatus::Pending ? SaasSettings::registrationLink($registration) : null,
        ]);
    }
}
