@extends('backend.layout.app')

@section('title', 'Daftarkan Tim')

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.saas.registrations.index') }}" class="text-muted fs-7"><i class="ki-outline ki-arrow-left"></i> Pendaftaran Masuk</a>
        <h1 class="fs-2 fw-bold text-gray-900 mb-1 mt-2">Daftarkan Tim</h1>
        <p class="text-muted fs-7 mb-0">Isi data pelanggan, pilih paket, dan tempel bukti transfer. Tim langsung aktif setelah disimpan.</p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger mb-6">
            <strong>Periksa kembali isian:</strong>
            <ul class="mb-0 ps-4">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.saas.registrations.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="row g-6">
            <div class="col-lg-7">
                <div class="card mb-6">
                    <div class="card-header"><h3 class="card-title">Data tim & owner</h3></div>
                    <div class="card-body row g-5">
                        <div class="col-12">
                            <label class="form-label required" for="team_name">Nama tim / instansi</label>
                            <input id="team_name" name="team_name" class="form-control form-control-solid @error('team_name') is-invalid @enderror" value="{{ old('team_name') }}" required maxlength="120" />
                            <x-field-error name="team_name" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required" for="owner_name">Nama owner</label>
                            <input id="owner_name" name="owner_name" class="form-control form-control-solid @error('owner_name') is-invalid @enderror" value="{{ old('owner_name') }}" required maxlength="100" />
                            <x-field-error name="owner_name" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required" for="owner_whatsapp">WhatsApp owner</label>
                            <input id="owner_whatsapp" name="owner_whatsapp" type="tel" class="form-control form-control-solid @error('owner_whatsapp') is-invalid @enderror" value="{{ old('owner_whatsapp') }}" required placeholder="081234567890" />
                            <x-field-error name="owner_whatsapp" />
                        </div>
                        <div class="col-12">
                            <label class="form-label required" for="owner_email">Email owner (untuk login)</label>
                            <input id="owner_email" name="owner_email" type="email" class="form-control form-control-solid @error('owner_email') is-invalid @enderror" value="{{ old('owner_email') }}" required />
                            <x-field-error name="owner_email" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required" for="password">Password awal</label>
                            <input id="password" name="password" type="text" class="form-control form-control-solid @error('password') is-invalid @enderror" required autocomplete="new-password" />
                            <div class="form-text">Min. 8 karakter, huruf besar, kecil, angka. Kirimkan ke owner & minta diganti.</div>
                            <x-field-error name="password" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required" for="password_confirmation">Ulangi password</label>
                            <input id="password_confirmation" name="password_confirmation" type="text" class="form-control form-control-solid" required autocomplete="new-password" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card mb-6">
                    <div class="card-header"><h3 class="card-title">Paket</h3></div>
                    <div class="card-body">
                        @foreach ($plans as $plan)
                            <label class="d-flex align-items-center justify-content-between border rounded p-4 mb-3 cursor-pointer">
                                <span class="d-flex align-items-center gap-3">
                                    <input class="form-check-input" type="radio" name="plan_id" value="{{ $plan->id }}" @checked((int) old('plan_id', $plans->first()->id) === $plan->id) required />
                                    <span><span class="fw-bold">{{ $plan->name }}</span><br><span class="text-muted fs-7">{{ $plan->periodLabel() }}</span></span>
                                </span>
                                <span class="fw-bolder fs-4">{{ $plan->priceLabel() }}</span>
                            </label>
                        @endforeach
                        <x-field-error name="plan_id" />
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <x-proof-input />
                        <div class="mt-5">
                            <label class="form-label" for="note">Catatan (opsional)</label>
                            <input id="note" name="note" class="form-control form-control-solid" maxlength="255" value="{{ old('note') }}" />
                        </div>
                        <button type="submit" class="btn btn-primary w-100 mt-6">Simpan & Aktifkan Tim</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
