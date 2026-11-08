{{--
    Layout halaman error — SENGAJA mandiri:
    CSS inline, logo SVG inline, tanpa @vite, tanpa query DB, tanpa auth()/session,
    supaya halaman error tetap tampil walau database, build aset, atau sesi bermasalah.

    Setiap halaman errors/{kode}.blade.php mengisi: code, title, message (default).
    - 4xx: pesan dari abort(4xx, 'pesan') ditampilkan bila diisi developer.
    - 5xx: TIDAK pernah menampilkan detail exception.
    - 429: sisa detik diambil dari header Retry-After.
--}}
@php
    $code = (int) trim($__env->yieldContent('code'));
    $appName = (string) config('app.name', 'Mail Sync');
    $message = trim($__env->yieldContent('message'));

    $custom = isset($exception) && $exception instanceof \Throwable ? trim((string) $exception->getMessage()) : '';
    // Pesan bawaan framework (Inggris, kadang membocorkan nama model/route) tidak ditampilkan.
    if (preg_match('/^(This action is unauthorized|The route|No query results|Unauthenticated|The \S+ method is not supported|CSRF token mismatch|Too Many Attempts|Not Found|Forbidden|Service Unavailable|Invalid signature|The given data was invalid)/i', $custom)) {
        $custom = '';
    }
    if ($code >= 400 && $code < 500 && $code !== 429 && $code !== 419 && $custom !== '') {
        $message = $custom;
    }

    if ($code === 429) {
        $retry = 60;
        if (isset($exception) && method_exists($exception, 'getHeaders')) {
            $retry = (int) ($exception->getHeaders()['Retry-After'] ?? 60);
        }
        $message = "Terlalu banyak permintaan. Coba lagi dalam {$retry} detik.";
    }

    $home = url('/');
@endphp
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex, nofollow" />
    <meta name="theme-color" content="#1D4ED8" />
    <title>{{ $code }} — @yield('title') | {{ $appName }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any" />
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}" />
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        :root {
            --bg: #f8fafc; --card: #ffffff; --text: #0f172a; --muted: #475569;
            --border: #e2e8f0; --brand: #1d4ed8; --brand-hover: #1e40af; --accent: #0e7490;
        }
        @media (prefers-color-scheme: dark) {
            :root { --bg: #0b1120; --card: #111827; --text: #f1f5f9; --muted: #94a3b8; --border: #1f2937; }
        }
        html, body { margin: 0; height: 100%; }
        body {
            font-family: 'Plus Jakarta Sans', 'Segoe UI', system-ui, -apple-system, Roboto, Arial, sans-serif;
            background: var(--bg); color: var(--text);
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            padding: 24px 16px; line-height: 1.6;
        }
        .card {
            width: 100%; max-width: 480px; background: var(--card); border: 1px solid var(--border);
            border-radius: 20px; padding: 40px 32px; text-align: center;
            box-shadow: 0 10px 40px -12px rgba(15, 23, 42, .18);
        }
        .brand { display: inline-flex; align-items: center; gap: 10px; margin-bottom: 28px; color: var(--text); text-decoration: none; font-weight: 800; }
        .code {
            font-size: clamp(3.5rem, 14vw, 5.5rem); font-weight: 800; line-height: 1; letter-spacing: -.04em; margin: 0 0 12px;
            background: linear-gradient(135deg, var(--brand), var(--accent)); -webkit-background-clip: text; background-clip: text; color: transparent;
        }
        h1 { font-size: 1.375rem; margin: 0 0 8px; }
        p.msg { color: var(--muted); margin: 0 0 28px; overflow-wrap: anywhere; }
        .actions { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
        .btn {
            display: inline-flex; align-items: center; justify-content: center; min-width: 120px;
            padding: 11px 18px; border-radius: 10px; font-weight: 600; font-size: .95rem; text-decoration: none;
            border: 1px solid var(--border); color: var(--text); background: transparent; cursor: pointer; font-family: inherit;
        }
        .btn:hover { border-color: var(--brand); }
        .btn-primary { background: var(--brand); border-color: var(--brand); color: #ffffff; }
        .btn-primary:hover { background: var(--brand-hover); }
        .btn:focus-visible { outline: 3px solid var(--accent); outline-offset: 2px; }
        footer { margin-top: 24px; font-size: .85rem; color: var(--muted); text-align: center; }
    </style>
</head>

<body>
    <main class="card" role="main">
        <a class="brand" href="{{ $home }}" aria-label="Beranda {{ $appName }}">
            <svg width="36" height="36" viewBox="0 0 512 512" role="img" aria-label="{{ $appName }}">
                <defs>
                    <linearGradient id="err-logo" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0" stop-color="#1D4ED8" />
                        <stop offset="1" stop-color="#0E7490" />
                    </linearGradient>
                </defs>
                <rect width="512" height="512" rx="116" fill="url(#err-logo)" />
                <path d="M96 132h320L256 268Z" fill="#ffffff" fill-opacity=".16" />
                <text x="256" y="262" text-anchor="middle" dominant-baseline="central"
                    font-family="'Segoe UI',system-ui,Arial,sans-serif" font-size="176" font-weight="800" fill="#ffffff">MS</text>
            </svg>
            <span>{{ $appName }}</span>
        </a>

        <p class="code">{{ $code }}</p>
        <h1>@yield('title')</h1>
        <p class="msg">{{ $message }}</p>

        <div class="actions">
            @if ($code === 419 || $code === 503 || $code === 502 || $code === 504 || $code === 408)
                <a class="btn btn-primary" href="{{ url()->current() }}">Muat Ulang</a>
            @else
                <a class="btn" href="javascript:history.back()" onclick="if (history.length > 1) { history.back(); return false; }">Kembali</a>
            @endif
            <a class="btn {{ in_array($code, [408, 419, 502, 503, 504], true) ? '' : 'btn-primary' }}" href="{{ $home }}">Beranda</a>
        </div>
    </main>

    <footer>&copy; {{ now()->year }} {{ $appName }}. All rights reserved.</footer>
</body>

</html>
