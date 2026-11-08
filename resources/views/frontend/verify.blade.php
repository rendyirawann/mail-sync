@extends('frontend.layout')

@section('title', 'Verifikasi Surat')
@section('robots', 'noindex, nofollow')

@section('content')
    <section class="mx-auto max-w-xl px-4 py-16 sm:px-6">
        @if ($letter)
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-6">
                <div class="flex items-center gap-3">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-emerald-600 text-white" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6 9 17l-5-5" /></svg>
                    </span>
                    <h1 class="text-2xl font-extrabold text-emerald-900">Surat terverifikasi</h1>
                </div>
                <p class="mt-3 text-emerald-900">Surat ini tercatat resmi di {{ config('app.name') }}.</p>
            </div>

            <dl class="mt-6 divide-y divide-slate-100 rounded-2xl border border-slate-200">
                @foreach ([
                    'Instansi' => $letter->team?->name,
                    'Nomor surat' => $letter->letter_number,
                    'Tanggal' => \App\Services\Letters\LetterRenderer::dateId($letter->letter_date),
                    'Perihal' => $letter->subject,
                    'Ditandatangani' => trim(($letter->signer?->name ?? '-').($letter->signer?->position ? ' — '.$letter->signer->position : '')),
                ] as $label => $value)
                    <div class="grid grid-cols-3 gap-4 px-5 py-3">
                        <dt class="text-sm text-slate-500">{{ $label }}</dt>
                        <dd class="col-span-2 font-medium text-slate-900">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
            <p class="mt-4 text-sm text-slate-500">Bandingkan data di atas dengan surat yang Anda terima. Bila berbeda, surat tersebut patut dicurigai.</p>
        @else
            <div class="rounded-2xl border border-red-200 bg-red-50 p-6">
                <h1 class="text-2xl font-extrabold text-red-900">Surat tidak ditemukan</h1>
                <p class="mt-3 text-red-900">Kode verifikasi ini tidak terdaftar atau surat belum disahkan. Hubungi instansi pengirim untuk memastikan keaslian surat.</p>
            </div>
        @endif
    </section>
@endsection
