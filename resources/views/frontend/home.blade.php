@extends('frontend.layout')

@section('is_home', '1')
@section('meta_description', config('seo.description'))

@section('content')
    {{-- Hero --}}
    <section class="relative overflow-hidden bg-gradient-to-br from-brand-900 via-brand-700 to-accent-600 text-white">
        <div class="mx-auto grid max-w-6xl items-center gap-12 px-4 py-20 sm:px-6 lg:grid-cols-2 lg:py-28">
            <div>
                <p class="mb-4 inline-flex rounded-full bg-white/10 px-3 py-1 text-sm font-medium text-brand-100 ring-1 ring-white/20">
                    {{ config('seo.tagline') }}
                </p>
                <h1 class="text-4xl font-extrabold leading-tight tracking-tight sm:text-5xl">
                    Semua kotak masuk Anda, tersinkron di satu tempat.
                </h1>
                <p class="mt-6 max-w-xl text-lg leading-relaxed text-brand-100">
                    {{ config('app.name') }} adalah aplikasi web untuk menyinkronkan, mengelola, dan memantau email
                    antar akun dari satu panel admin yang aman.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('login') }}"
                        class="rounded-lg bg-white px-5 py-3 font-semibold text-brand-700 shadow-sm hover:bg-brand-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-brand-700">
                        Masuk ke Panel
                    </a>
                    <a href="#fitur"
                        class="rounded-lg px-5 py-3 font-semibold text-white ring-1 ring-white/40 hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-white">
                        Lihat Fitur
                    </a>
                </div>
            </div>

            {{-- Ilustrasi sinkronisasi: dua kotak masuk yang saling terhubung --}}
            <div class="hidden lg:block" aria-hidden="true">
                <svg viewBox="0 0 420 300" width="420" height="300" class="mx-auto h-auto w-full max-w-md">
                    <rect x="10" y="40" width="160" height="210" rx="20" fill="#ffffff" fill-opacity=".08" stroke="#ffffff" stroke-opacity=".25" />
                    <rect x="250" y="40" width="160" height="210" rx="20" fill="#ffffff" fill-opacity=".08" stroke="#ffffff" stroke-opacity=".25" />
                    <g fill="#ffffff">
                        <rect x="30" y="70" width="120" height="34" rx="8" fill-opacity=".9" />
                        <rect x="30" y="116" width="120" height="34" rx="8" fill-opacity=".55" />
                        <rect x="30" y="162" width="120" height="34" rx="8" fill-opacity=".3" />
                        <rect x="270" y="70" width="120" height="34" rx="8" fill-opacity=".9" />
                        <rect x="270" y="116" width="120" height="34" rx="8" fill-opacity=".55" />
                        <rect x="270" y="162" width="120" height="34" rx="8" fill-opacity=".3" />
                    </g>
                    <path d="M40 78h60L70 96Z M280 78h60l-30 18Z" fill="#1d4ed8" fill-opacity=".7" />
                    <g fill="none" stroke="#22d3ee" stroke-width="4" stroke-linecap="round">
                        <path d="M178 120c20-22 44-22 64 0" />
                        <path d="M242 180c-20 22-44 22-64 0" />
                    </g>
                    <path d="M236 108l8 14-16 0Z M184 192l-8-14 16 0Z" fill="#22d3ee" />
                </svg>
            </div>
        </div>
    </section>

    {{-- Fitur --}}
    <section id="fitur" class="mx-auto max-w-6xl px-4 py-20 sm:px-6" aria-labelledby="judul-fitur">
        <h2 id="judul-fitur" class="text-3xl font-extrabold tracking-tight text-slate-900">Dibuat untuk mengelola email dengan rapi</h2>
        <p class="mt-3 max-w-2xl text-slate-600">
            Fokus pada sinkronisasi yang andal, kontrol akses yang jelas, dan jejak audit yang lengkap.
        </p>

        @php
            $features = [
                ['title' => 'Sinkronisasi antar akun', 'text' => 'Satukan beberapa akun email dan jaga isinya tetap selaras tanpa salin-tempel manual.'],
                ['title' => 'Pemantauan terpusat', 'text' => 'Pantau status sinkronisasi, aktivitas, dan sesi login dari satu dashboard.'],
                ['title' => 'Hak akses berbasis peran', 'text' => 'Atur siapa yang boleh melihat dan mengubah data dengan peran dan izin yang terpusat.'],
                ['title' => 'Jejak audit lengkap', 'text' => 'Setiap perubahan penting tercatat lengkap dengan pelaku, waktu, dan data sebelum–sesudah.'],
                ['title' => 'Keamanan berlapis', 'text' => 'Proteksi CSRF, pembatasan percobaan login, header keamanan, dan logout perangkat lain.'],
                ['title' => 'Notifikasi real-time', 'text' => 'Pemberitahuan instan lewat websocket saat ada kejadian penting pada akun Anda.'],
            ];
        @endphp

        <ul class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($features as $feature)
                <li class="rounded-2xl border border-slate-200 p-6 shadow-sm">
                    <span class="mb-4 inline-flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="5" width="18" height="14" rx="2" />
                            <path d="m3 7 9 6 9-6" />
                        </svg>
                    </span>
                    <h3 class="text-lg font-bold text-slate-900">{{ $feature['title'] }}</h3>
                    <p class="mt-2 text-slate-600">{{ $feature['text'] }}</p>
                </li>
            @endforeach
        </ul>
    </section>

    {{-- CTA --}}
    <section class="bg-brand-50">
        <div class="mx-auto flex max-w-6xl flex-col items-start gap-6 px-4 py-16 sm:px-6 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">Siap mulai menyinkronkan email?</h2>
                <p class="mt-2 text-slate-600">Masuk dengan akun yang diberikan administrator Anda.</p>
            </div>
            <a href="{{ route('login') }}"
                class="rounded-lg bg-brand-600 px-5 py-3 font-semibold text-white hover:bg-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2">
                Masuk ke {{ config('app.name') }}
            </a>
        </div>
    </section>
@endsection
