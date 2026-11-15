<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\Saas\DemoAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman publik untuk membuat akun demo sekali pakai.
 */
class DemoController extends Controller
{
    public function __construct(private readonly DemoAccountService $demo) {}

    /**
     * Tampilkan halaman demo. Bila kredensial baru dibuat (≤ 3 menit), tampilkan modal berisi akun.
     */
    public function show(Request $request): View
    {
        $token = $request->session()->get('demo_token');
        $credentials = $this->demo->credentials($token);

        if ($credentials === null) {
            $request->session()->forget('demo_token');
        }

        return view('frontend.demo', [
            'credentials' => $credentials,
            'secondsLeft' => $credentials ? $this->demo->credentialsSecondsLeft($token) : 0,
            'nextAllowedAt' => $this->demo->nextAllowedAt((string) $request->ip()),
            'lifetimeHours' => DemoAccountService::LIFETIME_HOURS,
        ]);
    }

    /**
     * Buat tim demo baru (dibatasi 1 kali per IP per 7 hari).
     */
    public function store(Request $request): RedirectResponse
    {
        $token = $this->demo->generate((string) $request->ip());
        $request->session()->put('demo_token', $token);

        return redirect()->route('demo.show')->with('demo_created', true);
    }
}
