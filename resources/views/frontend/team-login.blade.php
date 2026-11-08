@extends('frontend.layout')

@section('title', 'Masuk Tim')
@section('meta_description', 'Masuk ke ruang kerja tim Anda di '.config('app.name').' dengan kode tim.')

@section('content')
    <section class="bg-slate-50">
        <div class="mx-auto max-w-md px-4 py-16 sm:px-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Masuk ke tim Anda</h1>
                <p class="mt-2 text-slate-600">Ketik kode tim (4 karakter) yang dikirim admin saat akun diaktifkan.</p>

                <form method="GET" action="{{ route('team.find') }}" class="mt-6" novalidate>
                    <label for="kode" class="block text-sm font-semibold text-slate-700">Kode tim</label>
                    <div class="mt-1 flex items-center rounded-lg border border-slate-300 focus-within:border-brand-600 focus-within:ring-2 focus-within:ring-brand-600/30">
                        <span class="pl-3 text-slate-500">{{ parse_url(url('/'), PHP_URL_HOST) }}/</span>
                        <input id="kode" name="kode" value="{{ old('kode') }}" maxlength="10" autocomplete="off" autocapitalize="none" spellcheck="false" autofocus required
                            class="w-full rounded-r-lg border-0 py-2.5 pr-3 font-mono text-lg lowercase tracking-widest text-slate-900 focus:outline-none" placeholder="abcd" />
                    </div>
                    @error('kode') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    <button class="mt-5 w-full rounded-xl bg-brand-600 px-6 py-3 font-bold text-white hover:bg-brand-700">Lanjut ke halaman login</button>
                </form>

                <p class="mt-6 text-center text-sm text-slate-500">
                    Belum punya akun? <a href="{{ route('register.team') }}" class="font-semibold text-brand-700 hover:underline">Daftarkan tim</a>
                </p>
            </div>
        </div>
    </section>
@endsection
