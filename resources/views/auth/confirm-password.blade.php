@extends('auth.app')

@section('title', 'Konfirmasi Password')

@section('content')
    <div class="auth-card">
        <div class="auth-card__brand">
            <img src="{{ $brand['logo_url'] }}" alt="{{ config('app.name') }}" width="56" height="56" />
        </div>

        <h1 class="auth-card__title">Konfirmasi password</h1>
        <p class="auth-card__subtitle">
            Ini area yang dilindungi. Masukkan password Anda untuk melanjutkan.
        </p>

        @if ($errors->any())
            <div class="auth-alert" role="alert">
                <i class="ki-outline ki-shield-cross fs-4 mt-1" aria-hidden="true"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('password.confirm') }}">
            @csrf

            <div class="auth-field">
                <label class="auth-label" for="confirm-password">Password</label>
                <div class="auth-input-wrap">
                    <input type="password" name="password" id="confirm-password"
                        class="auth-input @error('password') is-invalid @enderror"
                        autocomplete="current-password" required autofocus />
                    <x-field-error name="password" />
                </div>
            </div>

            <button type="submit" class="auth-submit">Konfirmasi</button>
        </form>
    </div>
@endsection
