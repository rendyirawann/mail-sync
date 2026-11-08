@extends('frontend.layout')

@section('title', 'Daftar Tim Baru')
@section('meta_description', 'Daftarkan tim atau instansi Anda di '.config('app.name').'. Paket bulanan atau lifetime, aktif setelah pembayaran dikonfirmasi.')

@php
    $input = 'mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-900 shadow-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/30';
    $label = 'block text-sm font-semibold text-slate-700';
    $err = 'mt-1 text-sm text-red-600';
@endphp

@section('content')
    <section class="bg-slate-50">
        <div class="mx-auto max-w-5xl px-4 py-12 sm:px-6">
            <div class="text-center">
                <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Daftarkan tim Anda</h1>
                <p class="mx-auto mt-3 max-w-2xl text-slate-600">
                    Isi data di bawah, transfer sesuai paket, lalu konfirmasi lewat WhatsApp. Akun aktif begitu pembayaran kami verifikasi.
                </p>
            </div>

            {{-- Langkah --}}
            <ol class="mx-auto mt-8 grid max-w-3xl grid-cols-3 gap-3 text-center text-sm" aria-label="Langkah pendaftaran">
                @foreach (['Isi data & pilih paket', 'Transfer & konfirmasi WA', 'Akun aktif, login'] as $i => $step)
                    <li class="rounded-xl border {{ $i === 0 ? 'border-brand-600 bg-white text-brand-700' : 'border-slate-200 bg-white text-slate-500' }} px-3 py-3">
                        <span class="block text-xs font-bold uppercase tracking-wide">Langkah {{ $i + 1 }}</span>
                        <span class="font-semibold">{{ $step }}</span>
                    </li>
                @endforeach
            </ol>

            @if ($errors->any())
                <div class="mx-auto mt-8 max-w-3xl rounded-xl border border-red-200 bg-red-50 p-4 text-red-800" role="alert">
                    <p class="font-semibold">Periksa kembali isian Anda:</p>
                    <ul class="mt-1 list-inside list-disc text-sm">
                        @foreach ($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('register.team.store') }}" class="mx-auto mt-8 max-w-3xl space-y-8" novalidate>
                @csrf
                <x-honeypot />

                {{-- Paket --}}
                <fieldset>
                    <legend class="text-lg font-bold text-slate-900">1. Pilih paket</legend>
                    <div class="mt-3 grid gap-4 sm:grid-cols-{{ max(1, min(3, $plans->count())) }}">
                        @foreach ($plans as $plan)
                            @php $checked = (int) old('plan_id', $selectedPlan) === $plan->id; @endphp
                            <label class="relative flex cursor-pointer flex-col rounded-2xl border-2 bg-white p-5 shadow-sm transition has-[:checked]:border-brand-600 has-[:checked]:ring-4 has-[:checked]:ring-brand-600/15 {{ $checked ? 'border-brand-600' : 'border-slate-200' }}">
                                <input type="radio" name="plan_id" value="{{ $plan->id }}" class="sr-only" @checked($checked) required />
                                @if ($plan->isLifetime())
                                    <span class="absolute -top-3 right-4 rounded-full bg-accent-600 px-3 py-1 text-xs font-bold text-white">Paling hemat</span>
                                @endif
                                <span class="text-sm font-bold uppercase tracking-wide text-slate-500">{{ $plan->name }}</span>
                                <span class="mt-2 text-3xl font-extrabold text-slate-900">{{ $plan->priceLabel() }}</span>
                                <span class="text-sm text-slate-500">{{ $plan->periodLabel() }}</span>
                                <span class="mt-3 text-sm text-slate-600">{{ $plan->description }}</span>
                                <span class="mt-3 text-sm font-medium text-slate-700">Maks. {{ $plan->max_members }} akun (termasuk owner)</span>
                            </label>
                        @endforeach
                    </div>
                    @error('plan_id') <p class="{{ $err }}">{{ $message }}</p> @enderror
                </fieldset>

                {{-- Data tim --}}
                <fieldset class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <legend class="px-1 text-lg font-bold text-slate-900">2. Data tim / instansi</legend>
                    <div>
                        <label for="team_name" class="{{ $label }}">Nama tim / instansi</label>
                        <input id="team_name" name="team_name" type="text" value="{{ old('team_name') }}" class="{{ $input }}"
                            placeholder="Mis. Dinas Kominfo Kab. Deli Serdang" required maxlength="120" autocomplete="organization" />
                        @error('team_name') <p class="{{ $err }}">{{ $message }}</p> @enderror
                    </div>
                </fieldset>

                {{-- Owner --}}
                <fieldset class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <legend class="px-1 text-lg font-bold text-slate-900">3. Akun owner</legend>
                    <p class="text-sm text-slate-500">Owner adalah pengelola tim: bisa menambah anggota, mengatur modul, dan perpanjangan.</p>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="owner_name" class="{{ $label }}">Nama lengkap</label>
                            <input id="owner_name" name="owner_name" type="text" value="{{ old('owner_name') }}" class="{{ $input }}" required maxlength="100" autocomplete="name" />
                            @error('owner_name') <p class="{{ $err }}">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="owner_whatsapp" class="{{ $label }}">Nomor WhatsApp</label>
                            <input id="owner_whatsapp" name="owner_whatsapp" type="tel" inputmode="numeric" value="{{ old('owner_whatsapp') }}" class="{{ $input }}"
                                placeholder="081234567890" required autocomplete="tel" />
                            @error('owner_whatsapp') <p class="{{ $err }}">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="owner_email" class="{{ $label }}">Email (untuk login)</label>
                            <input id="owner_email" name="owner_email" type="email" value="{{ old('owner_email') }}" class="{{ $input }}" required autocomplete="email" />
                            @error('owner_email') <p class="{{ $err }}">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="password" class="{{ $label }}">Password</label>
                            <input id="password" name="password" type="password" class="{{ $input }}" required autocomplete="new-password" />
                            <p class="mt-1 text-xs text-slate-500">Min. 8 karakter, huruf besar, huruf kecil, dan angka.</p>
                            @error('password') <p class="{{ $err }}">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="password_confirmation" class="{{ $label }}">Ulangi password</label>
                            <input id="password_confirmation" name="password_confirmation" type="password" class="{{ $input }}" required autocomplete="new-password" />
                        </div>
                    </div>
                </fieldset>

                <div>
                    <label class="flex items-start gap-3 text-sm text-slate-700">
                        <input type="checkbox" name="agree" value="1" class="mt-1 h-4 w-4 rounded border-slate-300 text-brand-600" @checked(old('agree')) required />
                        <span>Saya menyetujui syarat layanan dan memahami bahwa akun aktif setelah pembayaran dikonfirmasi admin.</span>
                    </label>
                    @error('agree') <p class="{{ $err }}">{{ $message }}</p> @enderror
                </div>

                <button type="submit"
                    class="w-full rounded-xl bg-brand-600 px-6 py-4 text-lg font-bold text-white shadow-sm hover:bg-brand-700 focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-600/40">
                    Lanjut ke Pembayaran
                </button>

                <p class="text-center text-sm text-slate-500">
                    Sudah punya akun tim? Masuk lewat tautan <span class="font-mono">{{ url('/') }}/<em>kode-tim</em>/login</span> yang dikirim admin.
                </p>
            </form>
        </div>
    </section>
@endsection
