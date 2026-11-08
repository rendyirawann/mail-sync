<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Memasang header keamanan browser di SEMUA respon (HTML, JSON, halaman error).
 *
 * Kebijakan sengaja dibuat longgar agar tema Metronic (inline script/style,
 * plugin CDN, Google Fonts, Alpine) dan websocket Reverb tetap berjalan.
 * Perlindungan utama tetap di sisi server (validasi, auth, CSRF, policy);
 * header ini menutup clickjacking, MIME sniffing, kebocoran referrer, dan
 * pembajakan form lintas origin.
 *
 * Semua pengaturan dibaca dari config/security.php (key SECURITY_* di .env).
 */
class SecurityHeaders
{
    /** Origin CDN yang dipakai tema / view. Tambah domain lain lewat SECURITY_CSP_EXTRA_HOSTS. */
    private const CDN_HOSTS = [
        'https://cdn.jsdelivr.net',
        'https://cdnjs.cloudflare.com',
        'https://unpkg.com',
    ];

    /** Origin font. */
    private const FONT_HOSTS = [
        'https://fonts.googleapis.com',
        'https://fonts.gstatic.com',
    ];

    /** Dev server Vite (hanya saat APP_ENV=local). */
    private const VITE_DEV_HOSTS = [
        'http://localhost:5173',
        'http://127.0.0.1:5173',
        'ws://localhost:5173',
        'ws://127.0.0.1:5173',
        'http://[::1]:5173',
        'ws://[::1]:5173',
    ];

    /**
     * Teruskan request lalu tambahkan header keamanan ke respon.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Sembunyikan versi PHP walau expose_php masih On di php.ini.
        if (! headers_sent()) {
            header_remove('X-Powered-By');
        }

        $response = $next($request);

        $headers = $response->headers;
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        $headers->set('Permissions-Policy', (string) config('security.permissions_policy'));
        $headers->remove('X-Powered-By');
        $headers->remove('Server');

        if ($request->secure() && config('security.hsts', false)) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if (config('security.csp.enabled', true)) {
            $name = config('security.csp.report_only', false)
                ? 'Content-Security-Policy-Report-Only'
                : 'Content-Security-Policy';

            $headers->set($name, $this->policy());
        }

        if ($this->shouldNoindex($request, $response)) {
            $headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        // Halaman yang dirender untuk user login tidak boleh di-cache proxy/browser.
        if ($request->user() !== null && $this->isHtml($response)) {
            $headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private');
            $headers->set('Pragma', 'no-cache');
        }

        return $response;
    }

    /**
     * Noindex untuk: semua halaman selain production, halaman admin/auth, dan respon error.
     */
    private function shouldNoindex(Request $request, Response $response): bool
    {
        if (! config('seo.indexable', false) || $response->getStatusCode() >= 400) {
            return true;
        }

        return $request->is(...(array) config('seo.noindex_paths', []));
    }

    private function isHtml(Response $response): bool
    {
        return str_contains((string) $response->headers->get('Content-Type', 'text/html'), 'text/html');
    }

    /**
     * Susun nilai header Content-Security-Policy.
     *
     * Tanpa nonce/strict-dynamic: tema memakai banyak inline script, dan nonce
     * akan membuat 'unsafe-inline' diabaikan browser sehingga tema rusak.
     */
    private function policy(): string
    {
        $extra = array_values(array_filter(array_map('trim', (array) config('security.csp.extra_hosts', []))));
        $dev = app()->environment('local') ? self::VITE_DEV_HOSTS : [];

        $join = static fn (array ...$groups): string => implode(' ', array_unique(array_merge(...$groups)));

        $directives = [
            "default-src 'self'",
            // 'unsafe-eval' diperlukan Alpine.js (build CDN) dan beberapa plugin tema.
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' ".$join(self::CDN_HOSTS, $this->frameHosts(), $extra, $dev),
            "style-src 'self' 'unsafe-inline' https: ".$join(self::FONT_HOSTS, $dev),
            "img-src 'self' data: blob: https:",
            "font-src 'self' data: https:",
            "connect-src 'self' ".$join($this->websocketHosts(), self::CDN_HOSTS, $extra, $dev),
            "media-src 'self' data: blob: https:",
            "worker-src 'self' blob:",
            "frame-src 'self' ".$join($this->frameHosts(), $extra),
            "frame-ancestors 'self'",
            "form-action 'self' ".$join($this->formHosts()),
            "base-uri 'self'",
            "object-src 'none'",
        ];

        if (config('security.csp.upgrade_insecure', false)) {
            $directives[] = 'upgrade-insecure-requests';
        }

        return implode('; ', array_map('trim', $directives));
    }

    /**
     * Origin websocket Reverb dari config broadcasting (ws:// dan wss://).
     *
     * @return list<string>
     */
    private function websocketHosts(): array
    {
        $host = (string) config('broadcasting.connections.reverb.options.host', '');
        $port = (string) config('broadcasting.connections.reverb.options.port', '');

        if ($host === '') {
            return [];
        }

        $hostPort = $port !== '' ? "{$host}:{$port}" : $host;

        return ["ws://{$hostPort}", "wss://{$hostPort}", "http://{$hostPort}", "https://{$hostPort}"];
    }

    /**
     * Embed yang diizinkan (Cloudflare Turnstile bila aktif).
     *
     * @return list<string>
     */
    private function frameHosts(): array
    {
        return config('services.turnstile.enabled') ? ['https://challenges.cloudflare.com'] : [];
    }

    /**
     * Tujuan form tambahan: login sosial me-redirect ke provider OAuth.
     *
     * @return list<string>
     */
    private function formHosts(): array
    {
        $appUrl = Str::of((string) config('app.url'))->rtrim('/')->toString();

        return $appUrl !== '' ? [$appUrl] : [];
    }
}
