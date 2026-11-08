@extends('frontend.layout')

@section('is_home', '1')
@section('meta_description', config('seo.description'))

@php
    $check = '<svg class="h-4 w-4 shrink-0 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>';
    $driveIcon = '<svg class="h-3.5 w-3.5 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>';
    $maxMembers = $plans->max('max_members') ?: 3;
@endphp

@section('content')
    {{-- ================= HERO ================= --}}
    <section class="w-full border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-[1440px] px-4 py-12 sm:px-10 lg:py-20">
            <div class="grid grid-cols-1 items-center gap-12 lg:grid-cols-12 lg:gap-10">

                <div class="space-y-7 lg:col-span-5">
                    <div class="inline-flex items-center gap-2 rounded-full border border-brand-100 bg-brand-50 px-3 py-1.5 text-xs font-bold text-brand-700">
                        <span class="h-1.5 w-1.5 rounded-full bg-brand-600" aria-hidden="true"></span>
                        <span>{{ config('seo.tagline') }}</span>
                    </div>

                    <div class="space-y-4">
                        <h1 class="text-3xl font-extrabold leading-[1.12] tracking-tight text-zinc-950 sm:text-5xl">
                            Surat masuk, disposisi, dan surat keluar — rapi di satu tempat.
                        </h1>
                        <p class="text-base leading-relaxed text-slate-600 sm:text-lg">
                            {{ config('app.name') }} membantu tim dan instansi mencatat surat masuk, mendisposisikan ke pegawai, membuat surat keluar dari template dengan nomor otomatis & QR verifikasi, lalu mengarsipkan semuanya.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 pt-1">
                        <a href="{{ route('register.team') }}" class="flex items-center gap-2 rounded-xl bg-brand-600 px-6 py-3.5 text-sm font-semibold text-white shadow-xs transition-colors hover:bg-brand-700">
                            <span>Daftarkan Tim</span>
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                        </a>
                        <a href="#harga" class="rounded-xl border border-slate-300 bg-white px-6 py-3.5 text-sm font-semibold text-zinc-950 transition-colors hover:bg-slate-50">Lihat Harga</a>
                    </div>

                    <div class="grid grid-cols-3 gap-4 border-t border-slate-200 pt-4">
                        @foreach ([['Nomor & QR Otomatis', 'Cek keaslian via scan'], ['Tautan Google Drive', 'Hemat penyimpanan'], ['Ekspor PDF & Excel', 'Rekap buku agenda']] as [$title, $sub])
                            <div>
                                <div class="flex items-center gap-1.5 text-xs font-bold text-zinc-950">{!! $check !!}<span>{{ $title }}</span></div>
                                <p class="mt-0.5 text-[11px] text-slate-500">{{ $sub }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Mockup ruang kerja interaktif (data contoh, bukan data nyata) --}}
                <div class="lg:col-span-7">
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-900/5" aria-label="Contoh tampilan aplikasi">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-slate-50 px-5 py-3.5">
                            <div class="flex items-center gap-3">
                                <div class="flex items-center gap-1.5" aria-hidden="true">
                                    <span class="h-2.5 w-2.5 rounded-full bg-slate-300"></span><span class="h-2.5 w-2.5 rounded-full bg-slate-300"></span><span class="h-2.5 w-2.5 rounded-full bg-slate-300"></span>
                                </div>
                                <div class="h-4 w-px bg-slate-200"></div>
                                <span class="rounded-md bg-zinc-950 px-2 py-0.5 text-[11px] font-bold text-white">TIM-SEKRETARIAT</span>
                                <span class="hidden text-xs font-semibold text-slate-600 sm:inline">Tahun {{ now()->year }}</span>
                            </div>
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200/70 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500" aria-hidden="true"></span>{{ $maxMembers }} Akun Tim Aktif
                            </span>
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-white px-5 py-3">
                            <div class="flex items-center gap-1.5 rounded-xl bg-slate-100 p-1" role="tablist" aria-label="Contoh alur">
                                @foreach (['masuk' => '1. Agenda Surat Masuk', 'disposisi' => '2. Disposisi Berjenjang', 'keluar' => '3. Surat Keluar & QR'] as $key => $label)
                                    <button type="button" role="tab" data-ws-tab="{{ $key }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                                        class="rounded-lg px-3.5 py-1.5 text-xs font-bold transition-all {{ $loop->first ? 'bg-white text-zinc-950 shadow-2xs' : 'text-slate-600 hover:text-zinc-950' }}">{{ $label }}</button>
                                @endforeach
                            </div>
                            <span class="text-[11px] font-semibold text-brand-600">Klik tab untuk mencoba alur</span>
                        </div>

                        {{-- Tab 1 --}}
                        <div data-ws-panel="masuk" role="tabpanel">
                            <div class="grid grid-cols-3 divide-x divide-slate-200 border-b border-slate-200 bg-slate-50/50 tabular-nums">
                                @foreach ([['Surat Masuk (Bulan Ini)', '142 Surat', 'text-zinc-950'], ['Disposisi Aktif', '18 Tindak Lanjut', 'text-brand-600'], ['Surat Keluar Terbit QR', '89 Dokumen', 'text-zinc-950']] as [$l, $v, $c])
                                    <div class="p-4"><div class="text-xs font-medium text-slate-500">{{ $l }}</div><div class="mt-0.5 text-lg font-extrabold {{ $c }}">{{ $v }}</div></div>
                                @endforeach
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full border-collapse text-left">
                                    <thead>
                                        <tr class="border-b border-slate-200 bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                            <th class="px-5 py-3">No. Agenda</th><th class="px-4 py-3">Pengirim & Perihal</th><th class="px-4 py-3">Berkas Lampiran</th><th class="px-5 py-3 text-right">Status Disposisi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200 text-xs tabular-nums sm:text-sm">
                                        @foreach ([
                                            ['0412', 'Dinas Pendidikan & Kebudayaan', 'Undangan Rapat Koordinasi Evaluasi Semester', 'Didisposisi ke Kabid →', 'border-brand-100 bg-brand-50 text-brand-700', 'disposisi'],
                                            ['0411', 'Biro Keuangan Daerah', 'Pedoman Penyusunan Laporan Anggaran Triwulan II', 'Selesai Ditindaklanjuti', 'border-emerald-200 bg-emerald-50 text-emerald-700', 'disposisi'],
                                            ['0410', 'Penyedia Layanan Jaringan', 'Pemberitahuan Pemeliharaan Jaringan Intranet Instansi', 'Arsip Terverifikasi', 'border-slate-200 bg-slate-100 text-slate-700', 'keluar'],
                                        ] as [$no, $from, $subject, $status, $badge, $goto])
                                            <tr data-ws-goto="{{ $goto }}" class="cursor-pointer transition-colors hover:bg-brand-50/40">
                                                <td class="whitespace-nowrap px-5 py-3.5 font-bold {{ $loop->first ? 'text-brand-600' : 'text-zinc-950' }}">AGD/{{ now()->year }}/{{ $no }}</td>
                                                <td class="px-4 py-3.5"><div class="font-bold text-zinc-950">{{ $from }}</div><div class="text-xs text-slate-500">{{ $subject }}</div></td>
                                                <td class="whitespace-nowrap px-4 py-3.5"><span class="inline-flex items-center gap-1.5 rounded-md border border-slate-200 bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{!! $driveIcon !!} Google Drive</span></td>
                                                <td class="whitespace-nowrap px-5 py-3.5 text-right"><span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-bold {{ $badge }}">{{ $status }}</span></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Tab 2 --}}
                        <div data-ws-panel="disposisi" role="tabpanel" class="hidden space-y-5 bg-white p-5 sm:p-6">
                            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 pb-4">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="rounded border border-brand-100 bg-brand-50 px-2 py-0.5 text-xs font-bold tabular-nums text-brand-700">AGD/{{ now()->year }}/0412</span>
                                        <span class="text-xs font-semibold text-slate-500">Tenggat: <strong class="tabular-nums text-zinc-950">14 Nov {{ now()->year }}</strong></span>
                                    </div>
                                    <h3 class="mt-1 text-sm font-extrabold text-zinc-950 sm:text-base">Undangan Rapat Koordinasi Evaluasi Semester</h3>
                                </div>
                                <span class="rounded-xl bg-zinc-950 px-3.5 py-2 text-xs font-semibold text-white">Cetak Lembar Disposisi</span>
                            </div>
                            <div class="space-y-3">
                                <div class="flex items-start justify-between gap-4 rounded-xl border border-slate-200 bg-slate-50 p-3.5">
                                    <div class="flex items-start gap-3">
                                        <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-zinc-950 text-xs font-bold text-white">1</div>
                                        <div><div class="text-xs font-bold text-zinc-950">Kepala Instansi (Pimpinan) → Sekretaris & Kabid Teknis</div>
                                            <p class="mt-0.5 text-xs text-slate-600">"Harap hadiri dan siapkan bahan paparan capaian program semester I."</p></div>
                                    </div>
                                    <span class="shrink-0 rounded border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[11px] font-bold text-emerald-700">Diteruskan</span>
                                </div>
                                <div class="flex items-start justify-between gap-4 rounded-xl border border-brand-100 bg-brand-50/60 p-3.5">
                                    <div class="flex items-start gap-3">
                                        <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-brand-600 text-xs font-bold text-white">2</div>
                                        <div><div class="text-xs font-bold text-zinc-950">Kabid Teknis → 3 Staf Pelaksana (Multi-Tujuan)</div>
                                            <p class="mt-0.5 text-xs text-slate-700">"Susun draf surat tugas dan rekapitulasi laporan sebelum Kamis pukul 14.00."</p></div>
                                    </div>
                                    <span class="shrink-0 rounded bg-brand-600 px-2 py-0.5 text-[11px] font-bold text-white">Sedang Dikerjakan</span>
                                </div>
                            </div>
                        </div>

                        {{-- Tab 3 --}}
                        <div data-ws-panel="keluar" role="tabpanel" class="hidden bg-slate-50/70 p-5 sm:p-6">
                            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-2xs">
                                <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-200 pb-4">
                                    <div>
                                        <div class="text-[11px] font-bold uppercase tracking-wider text-brand-600">Pratinjau Kertas Template Surat Keluar</div>
                                        <div class="mt-0.5 text-sm font-extrabold tabular-nums text-zinc-950 sm:text-base">Nomor Otomatis: 005/UND/XI/{{ now()->year }}</div>
                                        <div class="mt-0.5 text-xs text-slate-500">Perihal: Surat Tugas Menghadiri Rapat Koordinasi Evaluasi</div>
                                    </div>
                                    <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">✓ Disetujui Pimpinan</span>
                                </div>
                                <div class="mt-4 grid grid-cols-1 items-center gap-4 sm:grid-cols-12">
                                    <div class="space-y-2 text-xs leading-relaxed text-slate-600 sm:col-span-8">
                                        <p>Menindaklanjuti surat masuk nomor agenda <strong class="tabular-nums text-zinc-950">AGD/{{ now()->year }}/0412</strong>, dengan ini Pimpinan menugaskan pegawai terlampir untuk mengikuti kegiatan rapat koordinasi resmi.</p>
                                        <p class="text-[11px] text-slate-500">Nomor surat diterbitkan otomatis saat disetujui pimpinan dan dilengkapi QR Code verifikasi keaslian dokumen.</p>
                                    </div>
                                    <div class="flex items-center justify-end sm:col-span-4">
                                        <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3">
                                            <svg class="h-12 w-12 shrink-0 text-zinc-950" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M3 3h8v8H3V3zm2 2v4h4V5H5zm8-2h8v8h-8V3zm2 2v4h4V5h-4zM3 13h8v8H3v-8zm2 2v4h4v-4H5zm13-2h3v2h-3v-2zm-3 0h2v3h-2v-3zm3 3h3v2h-3v-2zm-3 3h2v2h-2v-2zm3 0h3v2h-3v-2z"/></svg>
                                            <div><div class="text-[11px] font-extrabold text-zinc-950">QR Verifikasi</div><div class="text-[10px] font-bold text-emerald-700">Dokumen Asli</div></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-200 bg-slate-50 px-5 py-3 text-xs text-slate-600">
                            <div class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-brand-600" aria-hidden="true"></span>
                                <span>Hak akses terpisah per peran: <strong class="text-zinc-900">Owner, Admin, Pimpinan, Staf</strong></span></div>
                            <button type="button" data-ws-goto="keluar" class="font-bold text-brand-600 hover:underline">Lihat Contoh QR Surat →</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= FITUR ================= --}}
    <section id="fitur" class="w-full scroll-mt-16 border-b border-slate-200 bg-slate-50/60 py-20" aria-labelledby="judul-fitur">
        <div class="mx-auto max-w-[1440px] px-4 sm:px-10">
            <div class="mb-14 max-w-2xl">
                <div class="mb-2 text-xs font-bold uppercase tracking-wider text-brand-600">Alur Tata Usaha Modern</div>
                <h2 id="judul-fitur" class="text-2xl font-extrabold tracking-tight text-zinc-950 sm:text-4xl">Dibuat untuk mengelola surat dengan rapi</h2>
                <p class="mt-3 text-base leading-relaxed text-slate-600">Dirancang mengikuti alur kerja tata usaha: catat, disposisikan, tindak lanjuti, terbitkan, arsipkan.</p>
            </div>

            @php
                $features = [
                    ['Agenda surat masuk', 'Nomor agenda otomatis, pengirim tersimpan untuk isian berikutnya, berkas cukup tautan Google Drive.', 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z', 'AGD/'.now()->year.'/0412', 'Saran pengirim otomatis'],
                    ['Disposisi berjenjang', 'Kirim ke banyak pegawai sekaligus dengan tenggat, pantau tindak lanjut, cetak lembar disposisi.', 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4', 'Pimpinan → Kabid → Staf', 'Lembar cetak'],
                    ['Surat keluar dari template', 'Isi formulir, lihat hasilnya langsung di kertas, ajukan ke pimpinan, unduh PDF.', 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'Pratinjau kertas A4/F4', 'Siap unduh PDF'],
                    ['Nomor & QR otomatis', 'Nomor surat terbit saat disetujui dan setiap surat punya QR untuk cek keaslian.', 'M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z', '005/UND/XI/'.now()->year, 'QR valid'],
                    ['Arsip & laporan', 'Cari surat lintas tahun, unduh buku agenda dan rekap disposisi dalam PDF atau Excel.', 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4', 'Pencarian lintas tahun', 'PDF & .XLSX'],
                    ['Aman per tim', 'Data tiap tim terpisah, hak akses per peran (owner, admin, pimpinan, staf), dan jejak audit lengkap.', 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', 'Owner · Admin · Pimpinan · Staf', 'Terisolasi'],
                ];
            @endphp

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($features as [$title, $text, $path, $meta, $tag])
                    <div class="flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-6 shadow-2xs transition-all hover:border-slate-300">
                        <div>
                            <div class="mb-5 flex h-10 w-10 items-center justify-center rounded-xl border border-brand-100 bg-brand-50 text-brand-600">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}" /></svg>
                            </div>
                            <h3 class="mb-2 text-lg font-bold text-zinc-950">{{ $title }}</h3>
                            <p class="text-sm leading-relaxed text-slate-600">{{ $text }}</p>
                        </div>
                        <div class="mt-6 flex items-center justify-between border-t border-slate-100 pt-4 text-xs">
                            <span class="font-medium tabular-nums text-slate-600">{{ $meta }}</span>
                            <span class="rounded-md bg-brand-50 px-2.5 py-1 font-bold text-brand-700">{{ $tag }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ================= CARA KERJA ================= --}}
    <section id="cara-kerja" class="w-full scroll-mt-16 border-b border-slate-200 bg-white py-20" aria-labelledby="judul-alur">
        <div class="mx-auto max-w-[1440px] px-4 sm:px-10">
            <div class="mb-12 max-w-2xl">
                <div class="mb-2 text-xs font-bold uppercase tracking-wider text-brand-600">Implementasi Instan</div>
                <h2 id="judul-alur" class="text-2xl font-extrabold tracking-tight text-zinc-950 sm:text-4xl">Mulai dalam 3 langkah</h2>
            </div>
            <ol class="grid grid-cols-1 gap-6 md:grid-cols-3">
                @foreach ([
                    ['Daftar & pilih paket', 'Isi data tim dan pilih paket bulanan atau lifetime.', 'Proses kurang dari 1 menit', 'text-brand-600'],
                    ['Transfer & konfirmasi', 'Transfer ke rekening yang tertera, lalu konfirmasi lewat WhatsApp dengan satu klik.', 'Verifikasi cepat via WhatsApp', 'text-emerald-700'],
                    ['Login & undang tim', 'Akun aktif dengan alamat khusus tim Anda. Tambahkan anggota dan mulai catat surat.', 'Siap digunakan seluruh anggota', 'text-zinc-900'],
                ] as $i => [$title, $text, $note, $noteColor])
                    <li class="flex flex-col justify-between rounded-2xl border border-slate-200 bg-slate-50/50 p-7">
                        <div>
                            <div class="mb-5 flex h-9 w-9 items-center justify-center rounded-full bg-brand-600 text-sm font-extrabold tabular-nums text-white shadow-xs">{{ $i + 1 }}</div>
                            <h3 class="mb-2 text-lg font-bold text-zinc-950">{{ $title }}</h3>
                            <p class="text-sm leading-relaxed text-slate-600">{{ $text }}</p>
                        </div>
                        <div class="mt-6 border-t border-slate-200/80 pt-4 text-xs font-semibold {{ $noteColor }}">{{ $note }}</div>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- ================= HARGA ================= --}}
    <section id="harga" class="w-full scroll-mt-16 border-b border-slate-200 bg-slate-50/60 py-20" aria-labelledby="judul-harga">
        <div class="mx-auto max-w-[1440px] px-4 sm:px-10">
            <div class="mx-auto mb-14 max-w-2xl text-center">
                <h2 id="judul-harga" class="text-2xl font-extrabold tracking-tight text-zinc-950 sm:text-4xl">Harga sederhana, tanpa biaya tersembunyi</h2>
                <p class="mt-2.5 text-base text-slate-600">Semua paket mendapat seluruh fitur. Bedanya hanya cara bayar.</p>
            </div>

            <div class="mx-auto grid max-w-4xl grid-cols-1 items-stretch gap-6 md:grid-cols-2">
                @forelse ($plans as $plan)
                    @php $best = $plan->isLifetime(); @endphp
                    <div class="relative flex flex-col justify-between rounded-2xl bg-white p-8 {{ $best ? 'border-2 border-brand-600 shadow-lg shadow-brand-600/5' : 'border border-slate-200 shadow-2xs' }}">
                        @if ($best)
                            <div class="absolute -top-3.5 right-6"><span class="rounded-full bg-brand-600 px-3 py-1 text-xs font-bold text-white shadow-xs">Paling hemat</span></div>
                        @endif
                        <div>
                            <div class="text-xs font-extrabold uppercase tracking-wider {{ $best ? 'text-brand-600' : 'text-slate-500' }}">{{ $plan->name }}</div>
                            <div class="mt-3 flex items-baseline gap-1.5 tabular-nums">
                                <span class="text-4xl font-extrabold tracking-tight text-zinc-950">{{ $plan->priceLabel() }}</span>
                                <span class="text-sm font-medium text-slate-500">/ {{ $plan->periodLabel() }}</span>
                            </div>
                            <p class="mt-2 text-xs text-slate-500">{{ $plan->description }}</p>
                            <ul class="mt-8 space-y-3.5 text-sm text-slate-700">
                                @foreach (['Maks. '.$plan->max_members.' akun (termasuk owner)', 'Pegawai & tujuan disposisi tanpa batas', 'Surat masuk, disposisi, surat keluar ber-QR', 'Template surat + pratinjau langsung', 'Laporan PDF & Excel'] as $benefit)
                                    <li class="flex items-center gap-3">{!! $check !!}<span class="{{ $loop->first && $best ? 'font-semibold text-zinc-950' : '' }}">{{ $benefit }}</span></li>
                                @endforeach
                            </ul>
                        </div>
                        <a href="{{ route('register.team', ['paket' => $plan->id]) }}"
                            class="mt-8 w-full rounded-xl px-5 py-3.5 text-center text-sm font-bold transition-colors {{ $best ? 'bg-brand-600 text-white shadow-xs hover:bg-brand-700' : 'border border-slate-300 bg-white text-brand-600 hover:bg-slate-50' }}">
                            Pilih {{ $plan->name }}
                        </a>
                    </div>
                @empty
                    <p class="col-span-2 text-center text-slate-500">Paket sedang disiapkan. Silakan kembali beberapa saat lagi.</p>
                @endforelse
            </div>
        </div>
    </section>

    {{-- ================= CTA PENUTUP ================= --}}
    <section class="w-full border-b border-slate-200 bg-brand-50/50 py-14">
        <div class="mx-auto flex max-w-[1440px] flex-col items-start justify-between gap-6 px-4 sm:px-10 md:flex-row md:items-center">
            <div>
                <h2 class="text-xl font-extrabold tracking-tight text-zinc-950 sm:text-2xl">Siap merapikan surat-menyurat tim Anda?</h2>
                <p class="mt-1 text-sm text-slate-600">Daftar sekarang, atau masuk dengan kode tim Anda.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('register.team') }}" class="rounded-xl bg-brand-600 px-5 py-3 text-sm font-semibold text-white shadow-xs transition-colors hover:bg-brand-700">Daftarkan Tim</a>
                <a href="{{ route('team.find') }}" class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-zinc-900 transition-colors hover:bg-slate-50">Masuk Tim</a>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        (function () {
            var ACTIVE = 'rounded-lg px-3.5 py-1.5 text-xs font-bold transition-all bg-white text-zinc-950 shadow-2xs';
            var IDLE = 'rounded-lg px-3.5 py-1.5 text-xs font-bold transition-all text-slate-600 hover:text-zinc-950';

            function show(tab) {
                document.querySelectorAll('[data-ws-tab]').forEach(function (btn) {
                    var on = btn.dataset.wsTab === tab;
                    btn.className = on ? ACTIVE : IDLE;
                    btn.setAttribute('aria-selected', on ? 'true' : 'false');
                });
                document.querySelectorAll('[data-ws-panel]').forEach(function (panel) {
                    panel.classList.toggle('hidden', panel.dataset.wsPanel !== tab);
                });
            }

            document.querySelectorAll('[data-ws-tab]').forEach(function (btn) {
                btn.addEventListener('click', function () { show(btn.dataset.wsTab); });
            });
            document.querySelectorAll('[data-ws-goto]').forEach(function (el) {
                el.addEventListener('click', function () { show(el.dataset.wsGoto); });
            });
        })();
    </script>
@endpush
