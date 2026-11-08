<!DOCTYPE html>
<html lang="id">

<head>
    @include('partials.seo')

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700;800&display=swap" />

    @vite(['resources/css/app.css'])
</head>

<body class="min-h-screen bg-white font-sans text-slate-800 antialiased">
    <a href="#konten" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:rounded-md focus:bg-white focus:px-4 focus:py-2 focus:shadow">
        Lewati ke konten
    </a>

    <header class="border-b border-slate-100">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4 sm:px-6">
            <a href="{{ route('home') }}" class="flex items-center gap-3" aria-label="Beranda {{ config('app.name') }}">
                <img src="{{ $brand['logo_url'] }}" alt="{{ config('app.name') }}" width="36" height="36" class="h-9 w-9" />
                <span class="text-lg font-extrabold tracking-tight text-slate-900">{{ config('app.name') }}</span>
            </a>

            <nav aria-label="Navigasi utama">
                @auth
                    <a href="{{ route('dashboard') }}"
                        class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2">
                        Buka Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}"
                        class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2">
                        Masuk
                    </a>
                @endauth
            </nav>
        </div>
    </header>

    <main id="konten">
        @yield('content')
    </main>

    <footer class="border-t border-slate-100">
        <div class="mx-auto flex max-w-6xl flex-col gap-2 px-4 py-6 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <p>&copy; {{ now()->year }} {{ config('app.name') }}. All rights reserved.</p>
            <p>{{ config('seo.tagline') }}</p>
        </div>
    </footer>
</body>

</html>
