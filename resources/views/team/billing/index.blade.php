@extends('team.layout.app')

@section('title', 'Langganan')

@section('content')
    @php $days = $team->daysLeft(); @endphp

    <div class="mb-6">
        <h1 class="fs-2 fw-bold text-gray-900 mb-1">Langganan</h1>
        <p class="text-muted fs-7 mb-0">Status paket, perpanjangan, dan riwayat pembayaran tim.</p>
    </div>

    <div class="row g-6">
        <div class="col-xl-5">
            <div class="card mb-6">
                <div class="card-body">
                    <div class="text-muted fw-semibold">Paket saat ini</div>
                    <div class="fs-2x fw-bolder text-gray-900">{{ $team->plan->name }}</div>
                    <span class="badge badge-light-{{ $team->status->badge() }} fs-7">{{ $team->status->label() }}</span>
                    <div class="separator my-5"></div>
                    @if ($team->isLifetime())
                        <div class="d-flex align-items-center gap-3"><i class="ki-outline ki-infinity fs-2x text-success"></i><span class="fs-5 fw-bold text-success">Aktif selamanya</span></div>
                    @else
                        <div class="text-muted">Aktif sampai</div>
                        <div class="fs-3 fw-bold">{{ $team->expires_at->locale('id')->translatedFormat('j F Y') }}</div>
                        <div class="{{ $days < 0 ? 'text-danger' : ($days <= 7 ? 'text-warning' : 'text-muted') }}">
                            {{ $days < 0 ? 'Lewat '.abs($days).' hari' : ($days === 0 ? 'Berakhir hari ini' : 'Sisa '.$days.' hari') }}
                        </div>
                    @endif
                    <div class="text-muted fs-7 mt-4">Akun: {{ $team->seatsUsed() }} / {{ $team->plan->max_members }}</div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title fs-5">Riwayat pembayaran</h3></div>
                <div class="card-body pt-2">
                    @forelse ($payments as $p)
                        <div class="d-flex justify-content-between py-3 border-bottom border-gray-200">
                            <div><div class="fw-bold">{{ $p->type->label() }} — {{ $p->plan->name }}</div>
                                <div class="text-muted fs-7">{{ $p->paid_at?->locale('id')->translatedFormat('j M Y') }} · s.d. {{ $p->period_end?->locale('id')->translatedFormat('j M Y') ?? 'selamanya' }}</div></div>
                            <div class="fw-bold">{{ $p->amountLabel() }}</div>
                        </div>
                    @empty
                        <p class="text-muted mb-0">Belum ada riwayat.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            @unless ($team->isLifetime())
                <div class="card">
                    <div class="card-header"><h3 class="card-title fs-5">Perpanjang / upgrade</h3></div>
                    <div class="card-body">
                        <ol class="text-gray-700 mb-6 ps-5">
                            <li>Transfer sesuai paket ke salah satu rekening di bawah.</li>
                            <li>Tekan tombol <strong>Konfirmasi via WhatsApp</strong> dan lampirkan bukti transfer.</li>
                            <li>Admin mencatat pembayaran — masa aktif bertambah otomatis tanpa kehilangan sisa hari.</li>
                        </ol>

                        <div class="row g-4 mb-6">
                            @foreach ($plans as $plan)
                                <div class="col-md-6">
                                    <div class="border rounded p-5 h-100 d-flex flex-column {{ $plan->isLifetime() ? 'border-primary' : '' }}">
                                        <div class="fw-bold text-uppercase text-muted fs-7">{{ $plan->name }}</div>
                                        <div class="fs-2x fw-bolder">{{ $plan->priceLabel() }}</div>
                                        <div class="text-muted mb-4">{{ $plan->periodLabel() }}</div>
                                        @if ($renewLinks[$plan->id])
                                            <a href="{{ $renewLinks[$plan->id] }}" target="_blank" rel="noopener noreferrer" class="btn btn-success mt-auto">
                                                <i class="ki-outline ki-whatsapp fs-3"></i> Konfirmasi via WhatsApp
                                            </a>
                                        @else
                                            <span class="text-muted fs-7 mt-auto">Kontak admin belum diatur.</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <h4 class="fs-6 fw-bold">Rekening tujuan</h4>
                        @forelse ($banks as $bank)
                            <div class="d-flex justify-content-between align-items-center border rounded p-4 mb-3">
                                <div><div class="fw-bold text-muted fs-7">{{ $bank->bank_name }}</div>
                                    <div class="font-monospace fs-4 fw-bold">{{ $bank->account_number }}</div>
                                    <div class="text-muted fs-7">a.n. {{ $bank->account_holder }}</div></div>
                                <button type="button" class="btn btn-sm btn-light" onclick="navigator.clipboard.writeText('{{ preg_replace('/[^0-9]/', '', $bank->account_number) }}'); this.textContent='Tersalin';">Salin</button>
                            </div>
                        @empty
                            <p class="text-muted">Informasi rekening belum tersedia — hubungi admin.</p>
                        @endforelse
                    </div>
                </div>
            @endunless
        </div>
    </div>
@endsection
