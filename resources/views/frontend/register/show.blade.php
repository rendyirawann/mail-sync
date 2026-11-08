@extends('frontend.layout')

@section('title', 'Tagihan Pendaftaran '.$registration->code)
@section('robots', 'noindex, nofollow')

@section('content')
    <section class="bg-slate-50">
        <div class="mx-auto max-w-2xl px-4 py-12 sm:px-6">
            @if ($registration->status === \App\Enums\RegistrationStatus::Approved && $registration->team)
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-6 text-center">
                    <h1 class="text-2xl font-extrabold text-emerald-900">Akun tim sudah aktif 🎉</h1>
                    <p class="mt-2 text-emerald-900">Silakan masuk menggunakan email dan password yang Anda daftarkan.</p>
                    <a href="{{ route('team.login', $registration->team) }}"
                        class="mt-5 inline-block rounded-xl bg-emerald-600 px-6 py-3 font-bold text-white hover:bg-emerald-700">
                        Masuk ke {{ $registration->team->name }}
                    </a>
                    <p class="mt-3 font-mono text-sm text-emerald-800">{{ route('team.login', $registration->team) }}</p>
                </div>
            @elseif ($registration->status === \App\Enums\RegistrationStatus::Rejected)
                <div class="rounded-2xl border border-red-200 bg-red-50 p-6">
                    <h1 class="text-2xl font-extrabold text-red-900">Pendaftaran tidak dapat diproses</h1>
                    <p class="mt-2 text-red-900">{{ $registration->note ?: 'Silakan hubungi admin untuk informasi lebih lanjut.' }}</p>
                </div>
            @else
                <div class="text-center">
                    <p class="text-sm font-bold uppercase tracking-wide text-brand-600">Langkah 2 dari 3</p>
                    <h1 class="mt-1 text-3xl font-extrabold tracking-tight text-slate-900">Selesaikan pembayaran</h1>
                    <p class="mt-2 text-slate-600">Simpan kode pendaftaran Anda: <strong class="font-mono text-slate-900">{{ $registration->code }}</strong></p>
                </div>

                <div class="mt-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <dl class="space-y-2">
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Tim / instansi</dt><dd class="text-right font-semibold">{{ $registration->team_name }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Paket</dt><dd class="text-right font-semibold">{{ $registration->plan->name }} ({{ $registration->plan->periodLabel() }})</dd></div>
                        <div class="flex justify-between gap-4 border-t border-slate-100 pt-3"><dt class="font-bold text-slate-900">Total transfer</dt><dd class="text-2xl font-extrabold text-brand-700">{{ $registration->amountLabel() }}</dd></div>
                    </dl>
                </div>

                <h2 class="mt-8 text-lg font-bold text-slate-900">Transfer ke salah satu rekening</h2>
                <div class="mt-3 space-y-3">
                    @forelse ($banks as $bank)
                        <div class="flex items-center justify-between gap-4 rounded-xl border border-slate-200 bg-white p-4">
                            <div>
                                <p class="text-sm font-bold text-slate-500">{{ $bank->bank_name }}</p>
                                <p class="font-mono text-xl font-bold tracking-wider text-slate-900" data-copy-target>{{ $bank->account_number }}</p>
                                <p class="text-sm text-slate-600">a.n. {{ $bank->account_holder }}</p>
                            </div>
                            <button type="button" data-copy="{{ $bank->account_number }}"
                                class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Salin</button>
                        </div>
                    @empty
                        <p class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-900">Informasi rekening belum tersedia. Silakan hubungi admin.</p>
                    @endforelse
                </div>

                <h2 class="mt-8 text-lg font-bold text-slate-900">Kirim bukti transfer via WhatsApp</h2>
                <p class="mt-1 text-slate-600">Tekan tombol di bawah — pesan sudah terisi otomatis. Lampirkan foto/screenshot bukti transfer di chat.</p>
                @if ($waLink)
                    <a href="{{ $waLink }}" target="_blank" rel="noopener noreferrer"
                        class="mt-4 flex w-full items-center justify-center gap-2 rounded-xl bg-[#1f9d55] px-6 py-4 text-lg font-bold text-white hover:bg-[#178048]">
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor" aria-hidden="true"><path d="M17.5 14.4c-.3-.1-1.8-.9-2-1-.3-.1-.5-.1-.7.1-.2.3-.8 1-.9 1.2-.2.2-.3.2-.6.1-.3-.1-1.3-.5-2.4-1.5-.9-.8-1.5-1.8-1.7-2.1-.2-.3 0-.5.1-.6l.4-.5.3-.5c.1-.2 0-.4 0-.5l-.9-2.2c-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.5s1.1 2.9 1.2 3.1c.1.2 2.1 3.2 5.1 4.5.7.3 1.3.5 1.7.6.7.2 1.4.2 1.9.1.6-.1 1.8-.7 2-1.4.2-.7.2-1.3.2-1.4-.1-.2-.3-.3-.6-.4zM12 21.8c-1.8 0-3.5-.5-5-1.4l-.4-.2-3.7 1 1-3.6-.2-.4A9.8 9.8 0 1 1 12 21.8zm0-21.6A11.8 11.8 0 0 0 1.8 17.9L.1 24l6.3-1.6A11.8 11.8 0 1 0 12 .2z" /></svg>
                        Konfirmasi via WhatsApp
                    </a>
                @else
                    <p class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-900">Nomor WhatsApp admin belum diatur. Simpan kode {{ $registration->code }} dan hubungi admin.</p>
                @endif

                <div class="mt-8 rounded-xl bg-brand-50 p-4 text-sm text-brand-900">
                    <strong>Selanjutnya:</strong> setelah admin memverifikasi pembayaran, Anda akan menerima tautan login khusus tim Anda
                    (mis. <span class="font-mono">{{ url('/') }}/abcd/login</span>). Halaman ini juga akan menampilkan tautan tersebut.
                </div>
            @endif
        </div>
    </section>

    <script>
        document.querySelectorAll('[data-copy]').forEach(function (button) {
            button.addEventListener('click', function () {
                navigator.clipboard.writeText(button.getAttribute('data-copy')).then(function () {
                    button.textContent = 'Tersalin';
                    setTimeout(function () { button.textContent = 'Salin'; }, 1500);
                });
            });
        });
    </script>
@endsection
