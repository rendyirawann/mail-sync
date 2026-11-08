@extends('backend.layout.app')

@section('title', 'Pendaftaran '.$registration->code)

@section('content')
    @php $pending = $registration->status === \App\Enums\RegistrationStatus::Pending; @endphp

    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-6">
        <div>
            <a href="{{ route('admin.saas.registrations.index') }}" class="text-muted fs-7"><i class="ki-outline ki-arrow-left"></i> Pendaftaran Masuk</a>
            <h1 class="fs-2 fw-bold text-gray-900 mb-1 mt-2">{{ $registration->team_name }}</h1>
            <span class="badge badge-light-{{ $registration->status->badge() }}">{{ $registration->status->label() }}</span>
            <span class="font-monospace text-muted ms-2">{{ $registration->code }}</span>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger mb-6">{{ $errors->first() }}</div>
    @endif

    <div class="row g-6">
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header"><h3 class="card-title">Data pendaftar</h3></div>
                <div class="card-body">
                    <table class="table table-borderless mb-0 fs-6">
                        <tr><td class="text-muted w-125px">Tim / instansi</td><td class="fw-bold">{{ $registration->team_name }}</td></tr>
                        <tr><td class="text-muted">Owner</td><td class="fw-bold">{{ $registration->owner_name }}</td></tr>
                        <tr><td class="text-muted">Email</td><td>{{ $registration->owner_email }}</td></tr>
                        <tr><td class="text-muted">WhatsApp</td><td>
                            <a href="https://wa.me/{{ $registration->owner_whatsapp }}" target="_blank" rel="noopener noreferrer">+{{ $registration->owner_whatsapp }}</a>
                        </td></tr>
                        <tr><td class="text-muted">Paket</td><td>{{ $registration->plan->name }} ({{ $registration->plan->periodLabel() }})</td></tr>
                        <tr><td class="text-muted">Total</td><td class="fs-3 fw-bolder text-primary">{{ $registration->amountLabel() }}</td></tr>
                        <tr><td class="text-muted">Didaftarkan</td><td>{{ $registration->created_at->locale('id')->translatedFormat('j F Y H:i') }}</td></tr>
                        @if ($registration->processed_at)
                            <tr><td class="text-muted">Diproses</td><td>{{ $registration->processed_at->locale('id')->translatedFormat('j F Y H:i') }} oleh {{ $registration->processor?->name }}</td></tr>
                        @endif
                        @if ($registration->note)
                            <tr><td class="text-muted">Catatan</td><td>{{ $registration->note }}</td></tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            @if ($pending)
                <div class="card mb-6">
                    <div class="card-header"><h3 class="card-title">Setujui & aktifkan tim</h3></div>
                    <div class="card-body">
                        <ol class="text-gray-700 mb-6 ps-5">
                            <li>Buka chat WhatsApp dari pendaftar dan cocokkan kode <strong class="font-monospace">{{ $registration->code }}</strong> serta nominal <strong>{{ $registration->amountLabel() }}</strong>.</li>
                            <li>Salin gambar bukti transfer, lalu tempel (Ctrl+V) di kotak di bawah.</li>
                            <li>Klik <strong>Setujui & Aktifkan</strong>: tim, kode URL, dan akun owner dibuat otomatis.</li>
                        </ol>
                        <form method="POST" action="{{ route('admin.saas.registrations.approve', $registration) }}" enctype="multipart/form-data">
                            @csrf
                            <x-proof-input />
                            <div class="mt-5">
                                <label class="form-label" for="approve-note">Catatan (opsional)</label>
                                <input type="text" id="approve-note" name="note" class="form-control form-control-solid" maxlength="255" value="{{ old('note') }}"
                                    placeholder="Mis. transfer BCA 12/11 pukul 10.15" />
                            </div>
                            <button type="submit" class="btn btn-success w-100 mt-6">
                                <i class="ki-outline ki-check-circle fs-2"></i> Setujui & Aktifkan
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.saas.registrations.reject', $registration) }}" class="d-flex flex-column flex-md-row gap-3"
                            onsubmit="return confirm('Tolak pendaftaran ini?');">
                            @csrf
                            <input type="text" name="note" class="form-control form-control-solid" maxlength="255" required
                                placeholder="Alasan penolakan (tampil ke pendaftar)" aria-label="Alasan penolakan" />
                            <button type="submit" class="btn btn-light-danger text-nowrap">Tolak</button>
                        </form>
                    </div>
                </div>
            @elseif ($registration->team)
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-5">
                            <i class="ki-duotone ki-check-circle fs-3x text-success"><span class="path1"></span><span class="path2"></span></i>
                            <div>
                                <div class="fs-4 fw-bold">Tim aktif</div>
                                <div class="text-muted">Kode URL: <span class="font-monospace fw-bold">{{ $registration->team->slug }}</span></div>
                            </div>
                        </div>
                        <label class="form-label" for="login-url">Tautan login tim</label>
                        <div class="input-group mb-5">
                            <input id="login-url" class="form-control font-monospace" readonly value="{{ route('team.login', $registration->team) }}" />
                            <button class="btn btn-light" type="button" onclick="navigator.clipboard.writeText(document.getElementById('login-url').value); this.textContent='Tersalin';">Salin</button>
                        </div>
                        <div class="d-flex flex-wrap gap-3">
                            <a href="{{ $customerWa }}" target="_blank" rel="noopener noreferrer" class="btn btn-success">
                                <i class="ki-outline ki-whatsapp fs-2"></i> Kirim info login via WA
                            </a>
                            <a href="{{ route('admin.saas.teams.show', $registration->team->id) }}" class="btn btn-light-primary">Kelola tim</a>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
