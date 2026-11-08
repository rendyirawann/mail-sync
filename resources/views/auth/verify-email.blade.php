@extends('auth.app')

@section('title', 'Verifikasi Email')

@section('content')
    <div class="auth-card">
        <div class="auth-card__brand">
            <img src="{{ $brand['logo_url'] }}" alt="{{ config('app.name') }}" width="56" height="56" />
        </div>

        <h1 class="auth-card__title">Verifikasi email Anda</h1>
        <p class="auth-card__subtitle">
            Terima kasih telah mendaftar. Klik tautan verifikasi yang kami kirim ke email Anda.
            Belum menerima email? Kirim ulang di bawah ini.
        </p>

        @if (session('status') === 'verification-link-sent')
            <div class="auth-alert" role="status"
                style="background: rgba(34,197,94,.14); border-color: rgba(74,222,128,.38); color:#bbf7d0;">
                <i class="ki-outline ki-shield-tick fs-4 mt-1" aria-hidden="true"></i>
                <span>Tautan verifikasi baru telah dikirim ke email Anda.</span>
            </div>
        @endif

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="auth-submit">Kirim ulang email verifikasi</button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="auth-foot">
            @csrf
            <button type="submit" class="btn btn-link p-0 text-decoration-none" style="color: #a5b4fc;">Keluar</button>
        </form>
    </div>
@endsection
