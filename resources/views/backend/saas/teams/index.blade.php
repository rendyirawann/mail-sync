@extends('backend.layout.app')

@section('title', 'Tim Pelanggan')

@section('content')
    @php $rp = fn (int $v) => 'Rp'.number_format($v, 0, ',', '.'); @endphp

    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-6">
        <div>
            <h1 class="fs-2 fw-bold text-gray-900 mb-1">Tim Pelanggan</h1>
            <p class="text-muted fs-7 mb-0">Status langganan, masa aktif, dan pendapatan.</p>
        </div>
    </div>

    <div class="row g-5 mb-6">
        @foreach ([
            ['Tim aktif', $stats['active'], 'success', route('admin.saas.teams.index', ['status' => 'active'])],
            ['Jatuh tempo ≤ 7 hari', $stats['expiring'], 'warning', route('admin.saas.teams.index', ['expiring' => 1])],
            ['Tim beku', $stats['frozen'], 'danger', route('admin.saas.teams.index', ['status' => 'frozen'])],
            ['Menunggu pembayaran', $stats['pending'], 'info', route('admin.saas.registrations.index')],
        ] as [$label, $value, $color, $url])
            <div class="col-6 col-xl-3">
                <a href="{{ $url }}" class="card card-flush h-100 hover-elevate-up">
                    <div class="card-body">
                        <div class="fs-2hx fw-bolder text-{{ $color }}">{{ $value }}</div>
                        <div class="text-gray-600 fw-semibold">{{ $label }}</div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    <div class="row g-5 mb-6">
        <div class="col-md-4">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-600 fw-semibold">Pendapatan bulan ini</div>
                    <div class="fs-2x fw-bolder text-gray-900 mt-1">{{ $rp($stats['revenue_month']) }}</div>
                    <div class="text-gray-600 fw-semibold mt-5">Total pendapatan</div>
                    <div class="fs-3 fw-bold text-gray-800">{{ $rp($stats['revenue_total']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card card-flush h-100">
                <div class="card-header pt-5"><h3 class="card-title fw-bold">Pendapatan 6 bulan terakhir</h3></div>
                <div class="card-body pt-0">
                    @php $max = max(1, max($revenueChart['values'])); @endphp
                    <div class="d-flex align-items-end gap-4" style="height: 160px;" role="img"
                        aria-label="Grafik pendapatan: @foreach ($revenueChart['labels'] as $i => $l){{ $l }} {{ $rp($revenueChart['values'][$i]) }}; @endforeach">
                        @foreach ($revenueChart['values'] as $i => $value)
                            <div class="flex-grow-1 d-flex flex-column align-items-center justify-content-end h-100">
                                <span class="fs-8 text-muted mb-1">{{ $value > 0 ? number_format($value / 1000, 0, ',', '.').'rb' : '' }}</span>
                                <div class="w-100 rounded-top bg-primary" style="height: {{ max(2, round($value / $max * 120)) }}px;"></div>
                                <span class="fs-8 text-gray-600 mt-2 text-nowrap">{{ $revenueChart['labels'][$i] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header border-0 pt-6 flex-wrap gap-3">
            <form method="GET" class="d-flex flex-wrap gap-2" role="search">
                <select name="status" class="form-select form-select-solid w-175px" aria-label="Filter status">
                    <option value="">Semua status</option>
                    @foreach (\App\Enums\TeamStatus::cases() as $case)
                        <option value="{{ $case->value }}" @selected($status === $case->value)>{{ $case->label() }}</option>
                    @endforeach
                </select>
                <input type="search" name="q" value="{{ $search }}" class="form-control form-control-solid w-250px" placeholder="Cari nama, kode, email, WA..." aria-label="Cari tim" />
                <button class="btn btn-light">Terapkan</button>
                @if ($status || $search || $expiring)
                    <a href="{{ route('admin.saas.teams.index') }}" class="btn btn-link">Reset</a>
                @endif
            </form>
        </div>
        <div class="card-body pt-2">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-4">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase">
                            <th>Tim</th>
                            <th>Paket</th>
                            <th>Status</th>
                            <th>Aktif sampai</th>
                            <th class="text-center">Akun</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($teams as $team)
                            @php $days = $team->daysLeft(); @endphp
                            <tr>
                                <td>
                                    <div class="fw-bold text-gray-900">{{ $team->name }}</div>
                                    <div class="text-muted fs-7"><span class="font-monospace">/{{ $team->slug }}</span> · {{ $team->owner?->name }}</div>
                                </td>
                                <td>{{ $team->plan->name }}</td>
                                <td><span class="badge badge-light-{{ $team->status->badge() }}">{{ $team->status->label() }}</span></td>
                                <td>
                                    @if ($team->isLifetime())
                                        <span class="text-success fw-semibold">Selamanya</span>
                                    @else
                                        {{ $team->expires_at->locale('id')->translatedFormat('j M Y') }}
                                        <div class="fs-7 {{ $days < 0 ? 'text-danger' : ($days <= 7 ? 'text-warning' : 'text-muted') }}">
                                            {{ $days < 0 ? 'lewat '.abs($days).' hari' : ($days === 0 ? 'hari ini' : $days.' hari lagi') }}
                                        </div>
                                    @endif
                                </td>
                                <td class="text-center">{{ $team->users_count }}/{{ $team->plan->max_members }}</td>
                                <td class="text-end"><a href="{{ route('admin.saas.teams.show', $team->id) }}" class="btn btn-sm btn-light-primary">Kelola</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-10">Belum ada tim.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $teams->links() }}
        </div>
    </div>
@endsection
