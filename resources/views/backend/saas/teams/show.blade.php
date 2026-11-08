@extends('backend.layout.app')

@section('title', 'Tim '.$team->name)

@section('content')
    @php $days = $team->daysLeft(); @endphp

    <div class="d-flex flex-column flex-md-row align-items-md-start justify-content-between gap-3 mb-6">
        <div>
            <a href="{{ route('admin.saas.teams.index') }}" class="text-muted fs-7"><i class="ki-outline ki-arrow-left"></i> Tim Pelanggan</a>
            <h1 class="fs-2 fw-bold text-gray-900 mb-1 mt-2">{{ $team->name }}</h1>
            <span class="badge badge-light-{{ $team->status->badge() }}">{{ $team->status->label() }}</span>
            <span class="font-monospace text-muted ms-2">/{{ $team->slug }}</span>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if ($customerWa)
                <a href="{{ $customerWa }}" target="_blank" rel="noopener noreferrer" class="btn btn-light-success"><i class="ki-outline ki-whatsapp fs-2"></i> WA owner</a>
            @endif
            <a href="{{ route('team.login', $team) }}" target="_blank" rel="noopener" class="btn btn-light">Halaman login tim</a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger mb-6">{{ $errors->first() }}</div>
    @endif

    <div class="row g-6">
        <div class="col-lg-5">
            <div class="card mb-6">
                <div class="card-header"><h3 class="card-title">Langganan</h3></div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <tr><td class="text-muted w-125px">Paket</td><td class="fw-bold">{{ $team->plan->name }}</td></tr>
                        <tr><td class="text-muted">Mulai</td><td>{{ $team->started_at?->locale('id')->translatedFormat('j F Y') }}</td></tr>
                        <tr><td class="text-muted">Aktif sampai</td><td class="fw-bold">
                            @if ($team->isLifetime()) <span class="text-success">Selamanya (lifetime)</span>
                            @else {{ $team->expires_at->locale('id')->translatedFormat('j F Y') }}
                                <span class="fs-7 {{ $days < 0 ? 'text-danger' : 'text-muted' }}">({{ $days < 0 ? 'lewat '.abs($days).' hari' : $days.' hari lagi' }})</span>
                            @endif
                        </td></tr>
                        <tr><td class="text-muted">Akun</td><td>{{ $team->users->count() }} / {{ $team->plan->max_members }}</td></tr>
                        <tr><td class="text-muted">Login</td><td><span class="font-monospace fs-7">{{ route('team.login', $team) }}</span></td></tr>
                    </table>
                </div>
            </div>

            <div class="card mb-6">
                <div class="card-header"><h3 class="card-title">Status tim</h3></div>
                <div class="card-body">
                    <p class="text-muted fs-7">Beku: hanya bisa melihat & ekspor. Dinonaktifkan: tidak bisa login sama sekali.</p>
                    <form method="POST" action="{{ route('admin.saas.teams.status', $team->id) }}" class="d-flex gap-2"
                        onsubmit="return confirm('Ubah status tim ini?');">
                        @csrf @method('PATCH')
                        <select name="status" class="form-select form-select-solid" aria-label="Status tim">
                            @foreach (\App\Enums\TeamStatus::cases() as $case)
                                <option value="{{ $case->value }}" @selected($team->status === $case)>{{ $case->label() }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-light-primary">Simpan</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">Anggota</h3></div>
                <div class="card-body pt-2">
                    @foreach ($team->users as $member)
                        <div class="d-flex justify-content-between py-3 border-bottom border-gray-200">
                            <div>
                                <div class="fw-bold">{{ $member->name }}</div>
                                <div class="text-muted fs-7">{{ $member->email }}</div>
                            </div>
                            <span class="badge badge-light-primary align-self-center">{{ $member->team_role?->label() }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card mb-6">
                <div class="card-header"><h3 class="card-title">Catat perpanjangan / upgrade</h3></div>
                <div class="card-body">
                    <p class="text-muted fs-7">Masa aktif ditambahkan dari tanggal jatuh tempo (bila belum lewat) sehingga tidak ada hari yang hilang. Tim beku otomatis aktif kembali.</p>
                    <form method="POST" action="{{ route('admin.saas.teams.renew', $team->id) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-5">
                            <label class="form-label required">Paket</label>
                            @foreach ($plans as $plan)
                                <label class="d-flex align-items-center justify-content-between border rounded p-4 mb-3 cursor-pointer">
                                    <span class="d-flex align-items-center gap-3">
                                        <input class="form-check-input" type="radio" name="plan_id" value="{{ $plan->id }}" @checked((int) old('plan_id', $team->plan_id) === $plan->id) required />
                                        <span class="fw-bold">{{ $plan->name }}</span>
                                    </span>
                                    <span class="fw-bolder">{{ $plan->priceLabel() }}</span>
                                </label>
                            @endforeach
                        </div>
                        <x-proof-input />
                        <input type="text" name="note" class="form-control form-control-solid mt-5" maxlength="255" placeholder="Catatan (opsional)" aria-label="Catatan" />
                        <button class="btn btn-primary w-100 mt-6">Simpan Pembayaran</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">Riwayat pembayaran</h3></div>
                <div class="card-body pt-2">
                    <div class="table-responsive">
                        <table class="table table-row-dashed align-middle fs-7 gy-3">
                            <thead><tr class="text-muted fw-bold text-uppercase"><th>Tanggal</th><th>Jenis</th><th>Periode</th><th class="text-end">Nominal</th><th></th></tr></thead>
                            <tbody>
                                @forelse ($payments as $payment)
                                    <tr>
                                        <td>{{ $payment->paid_at?->locale('id')->translatedFormat('j M Y') }}<div class="text-muted">{{ $payment->verifier?->name }}</div></td>
                                        <td>{{ $payment->type->label() }}<div class="text-muted">{{ $payment->plan->name }}</div></td>
                                        <td>{{ $payment->period_start?->translatedFormat('j M Y') }} – {{ $payment->period_end?->translatedFormat('j M Y') ?? 'selamanya' }}</td>
                                        <td class="text-end fw-bold">{{ $payment->amountLabel() }}</td>
                                        <td class="text-end">
                                            @if ($payment->proof_path)
                                                <a href="{{ route('admin.saas.payments.proof', $payment) }}" target="_blank" rel="noopener" class="btn btn-sm btn-light">Bukti</a>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-6">Belum ada pembayaran.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
