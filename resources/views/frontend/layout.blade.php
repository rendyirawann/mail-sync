<!DOCTYPE html>
<html lang="id">

<head>
    @include('partials.seo')

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" />

    @vite(['resources/css/app.css'])
</head>

<body class="min-h-screen bg-white font-sans text-zinc-950 antialiased selection:bg-brand-600 selection:text-white">
    <a href="#konten" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-white focus:px-4 focus:py-2 focus:shadow">
        Lewati ke konten
    </a>

    {{-- Navbar lengket, lebar penuh --}}
    <header class="sticky top-0 z-40 w-full border-b border-slate-200 bg-white/95 backdrop-blur-md">
        <div class="mx-auto flex h-16 max-w-[1440px] items-center justify-between px-4 sm:px-10">
            <div class="flex items-center gap-3">
                <a href="{{ route('home') }}" class="group flex items-center gap-2.5" aria-label="Beranda {{ config('app.name') }}">
                    <img src="{{ $brand['logo_url'] }}" alt="{{ config('app.name') }}" width="32" height="32" class="h-8 w-8 rounded-lg" />
                    <span class="text-lg font-extrabold tracking-tight text-zinc-950">{{ config('app.name') }}</span>
                </a>
                <span class="hidden items-center rounded-md border border-slate-200 bg-slate-100 px-2.5 py-0.5 text-[11px] font-semibold text-slate-600 sm:inline-flex">
                    Tata Usaha & Instansi
                </span>
            </div>

            <nav class="hidden items-center gap-8 text-sm font-semibold text-slate-600 md:flex" aria-label="Navigasi halaman">
                <a href="{{ route('home') }}#fitur" class="transition-colors hover:text-zinc-950">Fitur</a>
                <a href="{{ route('home') }}#cara-kerja" class="transition-colors hover:text-zinc-950">Cara Kerja</a>
                <a href="{{ route('home') }}#harga" class="transition-colors hover:text-zinc-950">Harga</a>
            </nav>

            <div class="flex items-center gap-2.5" aria-label="Akun">
                @auth
                    @php $dashboardUrl = auth()->user()->team ? route('team.dashboard', auth()->user()->team) : route('dashboard'); @endphp
                    <a href="{{ $dashboardUrl }}" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-xs transition-colors hover:bg-brand-700">
                        Buka Dashboard
                    </a>
                @else
                    <a href="{{ route('team.find') }}" class="rounded-lg px-4 py-2 text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-100 hover:text-zinc-950">Masuk</a>
                    <a href="{{ route('register.team') }}" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-xs transition-colors hover:bg-brand-700">Daftar</a>
                @endauth
            </div>
        </div>
    </header>

    <main id="konten">
        @yield('content')
    </main>

    <footer class="w-full border-t border-slate-200 bg-white py-8">
        <div class="mx-auto flex max-w-[1440px] flex-col items-center justify-between gap-4 px-4 text-xs text-slate-500 sm:flex-row sm:px-10">
            <p>&copy; {{ now()->year }} {{ config('app.name') }}. All rights reserved.</p>
            <p class="font-medium">{{ config('seo.tagline') }}</p>
        </div>
    </footer>

    @stack('scripts')
</body>

</html>
