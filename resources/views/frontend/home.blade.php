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
                    Surat masuk, disposisi, dan surat keluar — rapi di satu tempat.
                </h1>
                <p class="mt-6 max-w-xl text-lg leading-relaxed text-brand-100">
                    {{ config('app.name') }} membantu tim dan instansi mencatat surat masuk, mendisposisikan ke pegawai, membuat surat keluar dari template dengan nomor otomatis & QR verifikasi, lalu mengarsipkan semuanya.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('register.team') }}"
                        class="rounded-lg bg-white px-5 py-3 font-semibold text-brand-700 shadow-sm hover:bg-brand-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-brand-700">
                        Daftarkan Tim
                    </a>
                    <a href="#harga"
                        class="rounded-lg px-5 py-3 font-semibold text-white ring-1 ring-white/40 hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-white">
                        Lihat Harga
                    </a>
                </div>
            </div>

            {{-- Ilustrasi alur surat: masuk → disposisi → keluar --}}
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
            Dirancang mengikuti alur kerja tata usaha: catat, disposisikan, tindak lanjuti, terbitkan, arsipkan.
        </p>

        @php
            $features = [
                ['title' => 'Agenda surat masuk', 'text' => 'Nomor agenda otomatis, pengirim tersimpan untuk isian berikutnya, berkas cukup tautan Google Drive.'],
                ['title' => 'Disposisi berjenjang', 'text' => 'Kirim ke banyak pegawai sekaligus dengan tenggat, pantau tindak lanjut, cetak lembar disposisi.'],
                ['title' => 'Surat keluar dari template', 'text' => 'Isi formulir, lihat hasilnya langsung di kertas, ajukan ke pimpinan, unduh PDF.'],
                ['title' => 'Nomor & QR otomatis', 'text' => 'Nomor surat terbit saat disetujui dan setiap surat punya QR untuk cek keaslian.'],
                ['title' => 'Arsip & laporan', 'text' => 'Cari surat lintas tahun, unduh buku agenda dan rekap disposisi dalam PDF atau Excel.'],
                ['title' => 'Aman per tim', 'text' => 'Data tiap tim terpisah, hak akses per peran (owner, admin, pimpinan, staf), dan jejak audit lengkap.'],
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

    {{-- Cara kerja --}}
    <section class="border-y border-slate-100 bg-slate-50" aria-labelledby="judul-alur">
        <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
            <h2 id="judul-alur" class="text-3xl font-extrabold tracking-tight text-slate-900">Mulai dalam 3 langkah</h2>
            <ol class="mt-8 grid gap-6 md:grid-cols-3">
                @foreach ([
                    ['Daftar & pilih paket', 'Isi data tim dan pilih paket bulanan atau lifetime.'],
                    ['Transfer & konfirmasi', 'Transfer ke rekening yang tertera, lalu konfirmasi lewat WhatsApp dengan satu klik.'],
                    ['Login & undang tim', 'Akun aktif dengan alamat khusus tim Anda. Tambahkan anggota dan mulai catat surat.'],
                ] as $i => [$title, $text])
                    <li class="rounded-2xl border border-slate-200 bg-white p-6">
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-brand-600 font-bold text-white">{{ $i + 1 }}</span>
                        <h3 class="mt-4 text-lg font-bold text-slate-900">{{ $title }}</h3>
                        <p class="mt-1 text-slate-600">{{ $text }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- Harga --}}
    <section id="harga" class="mx-auto max-w-6xl px-4 py-20 sm:px-6" aria-labelledby="judul-harga">
        <h2 id="judul-harga" class="text-center text-3xl font-extrabold tracking-tight text-slate-900">Harga sederhana, tanpa biaya tersembunyi</h2>
        <p class="mx-auto mt-3 max-w-2xl text-center text-slate-600">Semua paket mendapat seluruh fitur. Bedanya hanya cara bayar.</p>
        <div class="mx-auto mt-10 grid max-w-3xl gap-6 sm:grid-cols-2">
            @foreach ($plans as $plan)
                <div class="relative flex flex-col rounded-2xl border-2 {{ $plan->isLifetime() ? 'border-brand-600' : 'border-slate-200' }} bg-white p-8 shadow-sm">
                    @if ($plan->isLifetime())
                        <span class="absolute -top-3 right-6 rounded-full bg-accent-600 px-3 py-1 text-xs font-bold text-white">Paling hemat</span>
                    @endif
                    <h3 class="text-sm font-bold uppercase tracking-wide text-slate-500">{{ $plan->name }}</h3>
                    <p class="mt-3"><span class="text-4xl font-extrabold text-slate-900">{{ $plan->priceLabel() }}</span>
                        <span class="text-slate-500">/ {{ $plan->periodLabel() }}</span></p>
                    <p class="mt-3 text-slate-600">{{ $plan->description }}</p>
                    <ul class="mt-6 space-y-2 text-sm text-slate-700">
                        @foreach (['Maks. '.$plan->max_members.' akun (termasuk owner)', 'Pegawai & tujuan disposisi tanpa batas', 'Surat masuk, disposisi, surat keluar ber-QR', 'Template surat + pratinjau langsung', 'Laporan PDF & Excel'] as $benefit)
                            <li class="flex gap-2"><span class="text-brand-600" aria-hidden="true">✓</span>{{ $benefit }}</li>
                        @endforeach
                    </ul>
                    <a href="{{ route('register.team', ['paket' => $plan->id]) }}"
                        class="mt-8 rounded-xl {{ $plan->isLifetime() ? 'bg-brand-600 text-white hover:bg-brand-700' : 'border border-brand-600 text-brand-700 hover:bg-brand-50' }} px-5 py-3 text-center font-bold">
                        Pilih {{ $plan->name }}
                    </a>
                </div>
            @endforeach
        </div>
    </section>

    {{-- CTA --}}
    <section class="bg-brand-50">
        <div class="mx-auto flex max-w-6xl flex-col items-start gap-6 px-4 py-16 sm:px-6 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">Siap merapikan surat-menyurat tim Anda?</h2>
                <p class="mt-2 text-slate-600">Daftar sekarang, atau masuk dengan kode tim Anda.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('register.team') }}"
                    class="rounded-lg bg-brand-600 px-5 py-3 font-semibold text-white hover:bg-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2">
                    Daftarkan Tim
                </a>
                <a href="{{ route('team.find') }}" class="rounded-lg border border-slate-300 px-5 py-3 font-semibold text-slate-700 hover:bg-white">Masuk Tim</a>
            </div>
        </div>
    </section>
@endsection
