<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Beranda publik + pintu masuk tim (ketik kode tim → diarahkan ke /{kode}/login).
 */
class HomeController extends Controller
{
    /**
     * Tampilkan beranda yang menjelaskan aplikasi beserta paket harga.
     */
    public function __invoke(): View
    {
        // Beranda tetap tampil (tanpa harga) walau database sedang bermasalah.
        try {
            $plans = Plan::active()->get();
        } catch (\Throwable) {
            $plans = collect();
        }

        return view('frontend.home', ['plans' => $plans]);
    }

    /**
     * Form "Masuk tim": pengguna cukup mengetik kode tim 4 karakter.
     */
    public function teamLogin(Request $request): View|RedirectResponse
    {
        if (! $request->filled('kode')) {
            return view('frontend.team-login');
        }

        $data = $request->validate(
            ['kode' => ['required', 'string', 'regex:/^[A-Za-z0-9]{4,10}$/']],
            ['kode.regex' => 'Kode tim terdiri dari 4 huruf/angka, mis. k7qp.'],
            ['kode' => 'kode tim'],
        );

        $team = Team::where('slug', strtolower($data['kode']))->first();

        if ($team === null) {
            return back()->withInput()->withErrors(['kode' => 'Kode tim tidak ditemukan. Periksa kembali pesan aktivasi dari admin.']);
        }

        return redirect()->route('team.login', $team);
    }
}
