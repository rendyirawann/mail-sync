@extends('auth.app')

@section('title', 'Masuk — '.$team->name)
@section('meta_description', 'Masuk ke ruang kerja tim '.$team->name.' di '.config('app.name').'.')
@section('robots', 'noindex, nofollow')

@section('content')
    @php
        $firstError = $errors->first();
    @endphp

    <div class="auth-card">

        {{-- Brand block — only shown when the story panel is hidden (mobile). --}}
        <div class="auth-card__brand">
            <img src="{{ $brand['logo_url'] }}" alt="" width="56" height="56" />
            <div>
                <div class="auth-card__title mb-0">{{ $team->name }}</div>
                <div class="auth-card__subtitle mb-0">{{ config('app.name') }}</div>
            </div>
        </div>

        <h1 class="auth-card__title">Masuk ke {{ $team->name }}</h1>
        <p class="auth-card__subtitle">Gunakan akun yang diberikan owner tim Anda.</p>

        @if (session('error'))
            <div class="auth-alert" role="alert"><i class="ki-outline ki-information-5 fs-4 mt-1" aria-hidden="true"></i><span>{{ session('error') }}</span></div>
        @endif

        {{-- Accounts previously used on THIS device. Only the identifier is
             kept (in localStorage) — never a password. Populated and rendered
             by app-auth.js; hidden while empty. --}}
        <div class="auth-accounts" id="auth-accounts" hidden>
            <div class="auth-accounts__label">
                <span>Masuk sebagai</span>
                <button type="button" class="auth-accounts__clear" data-accounts-clear>Hapus semua</button>
            </div>
            <ul class="auth-accounts__list" data-accounts-list></ul>
        </div>

        {{-- Errors from a non-JS (full page) submit land here; the fetch flow
             reuses the same box. --}}
        <div class="auth-alert" id="auth-alert" role="alert" @if (! $firstError) hidden @endif>
            <i class="ki-outline ki-shield-cross fs-4 mt-1" aria-hidden="true"></i>
            <span data-alert-text>{{ $firstError }}</span>
        </div>

        <form method="POST" action="{{ route('team.login.store') }}" id="auth-signin-form" novalidate
            data-redirect="{{ route('team.dashboard') }}">
            @csrf
            <x-honeypot />

            <div class="auth-field">
                <label class="auth-label" for="auth-identifier">Email / No. WhatsApp / Username</label>
                <div class="auth-input-wrap">
                    <input type="text" name="email" id="auth-identifier" class="auth-input"
                        value="{{ old('email') }}" placeholder="nama@perusahaan.com"
                        autocomplete="username" autocapitalize="none" spellcheck="false" required
                        @if ($errors->has('email')) aria-invalid="true" aria-describedby="auth-identifier-error" @endif />
                </div>
                @error('email')
                    <span class="auth-error" id="auth-identifier-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="auth-field">
                <label class="auth-label" for="auth-password">Password</label>
                <div class="auth-input-wrap">
                    <input type="password" name="password" id="auth-password"
                        class="auth-input auth-input--password" placeholder="••••••••"
                        autocomplete="current-password" required
                        @if ($errors->has('password')) aria-invalid="true" aria-describedby="auth-password-error" @endif />

                    <button type="button" class="auth-toggle" data-auth-toggle-password="auth-password"
                        aria-label="Tampilkan password" aria-pressed="false">
                        <i class="ki-outline ki-eye fs-4" data-eye aria-hidden="true"></i>
                        <i class="ki-outline ki-eye-slash fs-4" data-eye-off style="display:none" aria-hidden="true"></i>
                    </button>
                </div>
                @error('password')
                    <span class="auth-error" id="auth-password-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="auth-options">
                <label class="auth-check" for="auth-remember">
                    <input type="checkbox" name="remember" id="auth-remember" value="1"
                        @checked(old('remember')) />
                    <span>Ingat saya</span>
                </label>
                <span class="auth-hint">Tetap masuk di perangkat ini</span>
            </div>

            <button type="submit" class="auth-submit" data-auth-submit data-label-idle="Masuk">
                <span class="auth-submit__spinner" aria-hidden="true"></span>
                <span data-label>Masuk</span>
            </button>
        </form>


        <p class="auth-foot">
            Lupa password? Minta owner tim untuk mengatur ulang dari menu Anggota.<br />
            Owner lupa password? Hubungi admin {{ config('app.name') }}.
        </p>
    </div>
@endsection
