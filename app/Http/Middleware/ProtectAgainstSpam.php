<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Filter bot untuk form publik (login, register, lupa/reset password).
 *
 * Tiga lapis, semuanya tidak terlihat oleh user normal:
 *  1. Honeypot  — field tersembunyi yang hanya diisi bot.
 *  2. Time-trap — form yang dikirim < N detik setelah dirender ditolak.
 *  3. Cloudflare Turnstile — opsional, hanya bila TURNSTILE_ENABLED=true dan key terisi.
 *
 * Pasangannya: komponen <x-honeypot /> di dalam form.
 */
class ProtectAgainstSpam
{
    public const HONEYPOT_FIELD = 'website';

    public const TIME_FIELD = '_hp_time';

    /** Minimal jeda (detik) antara form dirender dan dikirim. */
    public const MIN_SECONDS = 3;

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('POST')) {
            return $next($request);
        }

        $reason = $this->detectSpam($request);

        if ($reason === null) {
            return $next($request);
        }

        Log::channel('security')->notice('Form publik ditolak oleh filter anti-spam', [
            'reason' => $reason,
            'ip' => $request->ip(),
            'path' => $request->path(),
        ]);

        // Ditolak "diam-diam": pesan generik, tanpa memberi tahu bot alasan penolakan.
        $message = 'Permintaan tidak dapat diproses. Silakan muat ulang halaman dan coba lagi.';

        if ($request->expectsJson()) {
            return response()->json(['status' => 'error', 'message' => $message, 'errors' => ['email' => [$message]]], 422);
        }

        return back()->withInput($request->except(['password', 'password_confirmation', self::HONEYPOT_FIELD]))
            ->withErrors(['email' => $message]);
    }

    private function detectSpam(Request $request): ?string
    {
        if (filled($request->input(self::HONEYPOT_FIELD))) {
            return 'honeypot';
        }

        try {
            $renderedAt = (int) Crypt::decryptString((string) $request->input(self::TIME_FIELD));
        } catch (DecryptException) {
            return 'time-trap-missing';
        }

        if (time() - $renderedAt < self::MIN_SECONDS) {
            return 'time-trap-too-fast';
        }

        if (config('services.turnstile.enabled') && ! $this->turnstilePassed($request)) {
            return 'turnstile';
        }

        return null;
    }

    /** Verifikasi token Turnstile ke Cloudflare (timeout pendek agar login tidak macet). */
    private function turnstilePassed(Request $request): bool
    {
        $token = (string) $request->input('cf-turnstile-response');

        if ($token === '') {
            return false;
        }

        try {
            return (bool) Http::asForm()->timeout(5)
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => config('services.turnstile.secret_key'),
                    'response' => $token,
                    'remoteip' => $request->ip(),
                ])->json('success', false);
        } catch (\Throwable) {
            // Cloudflare tidak bisa dihubungi: jangan kunci user keluar.
            return true;
        }
    }
}
