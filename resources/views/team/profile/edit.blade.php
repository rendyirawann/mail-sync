@extends('team.layout.app')

@section('title', 'Profil & Password')

@section('content')
    <div class="mb-6">
        <h1 class="fs-2 fw-bold text-gray-900 mb-1">Profil & Password</h1>
        <p class="text-muted fs-7 mb-0">{{ $user->role_label }} di {{ $team->name }}</p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger mb-6"><ul class="mb-0 ps-4">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <div class="row g-6">
        <div class="col-lg-6">
            <form method="POST" action="{{ route('team.profile.update') }}" class="card">
                @csrf @method('PUT')
                <input type="hidden" name="section" value="profile" />
                <div class="card-header"><h3 class="card-title fs-5">Profil</h3></div>
                <div class="card-body">
                    <label class="form-label required" for="name">Nama</label>
                    <input id="name" name="name" class="form-control form-control-solid mb-4" value="{{ old('name', $user->name) }}" required maxlength="100" />
                    <label class="form-label required" for="email">Email (untuk login)</label>
                    <input id="email" name="email" type="email" class="form-control form-control-solid mb-4" value="{{ old('email', $user->email) }}" required />
                    <label class="form-label" for="no_wa">WhatsApp</label>
                    <input id="no_wa" name="no_wa" class="form-control form-control-solid mb-4" value="{{ old('no_wa', $user->no_wa) }}" />
                    <button class="btn btn-primary">Simpan profil</button>
                </div>
            </form>
        </div>
        <div class="col-lg-6">
            <form method="POST" action="{{ route('team.profile.update') }}" class="card">
                @csrf @method('PUT')
                <input type="hidden" name="section" value="password" />
                <div class="card-header"><h3 class="card-title fs-5">Ganti password</h3></div>
                <div class="card-body">
                    <label class="form-label required" for="current_password">Password saat ini</label>
                    <input id="current_password" name="current_password" type="password" class="form-control form-control-solid mb-4" required autocomplete="current-password" />
                    <label class="form-label required" for="password">Password baru</label>
                    <input id="password" name="password" type="password" class="form-control form-control-solid mb-1" required autocomplete="new-password" />
                    <div class="form-text mb-4">Min. 8 karakter, huruf besar, huruf kecil, angka.</div>
                    <label class="form-label required" for="password_confirmation">Ulangi password baru</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" class="form-control form-control-solid mb-4" required autocomplete="new-password" />
                    <button class="btn btn-primary">Ganti password</button>
                </div>
            </form>
        </div>
    </div>
@endsection
