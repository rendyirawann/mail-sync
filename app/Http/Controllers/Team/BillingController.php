<?php

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Plan;
use App\Models\Team;
use App\Support\SaasSettings;
use Illuminate\View\View;

/**
 * Halaman langganan untuk owner: status, riwayat bayar, dan perpanjangan
 * (transfer → konfirmasi WA → admin mencatat). Tetap bisa dibuka saat tim beku.
 */
class BillingController extends Controller
{
    public function index(Team $team): View
    {
        $plans = Plan::active()->get();

        return view('team.billing.index', [
            'plans' => $plans,
            'banks' => BankAccount::active()->get(),
            'payments' => $team->payments()->with('plan')->get(),
            'renewLinks' => $plans->mapWithKeys(fn (Plan $plan) => [$plan->id => SaasSettings::renewalLink($team, $plan)]),
        ]);
    }
}
