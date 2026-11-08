@extends('team.layout.app')

@section('title', 'Dashboard')

@section('content')
    @php
        $user = auth()->user();
        $steps = collect($onboarding)->filter(fn ($s) => $user->can($s['ability']));
        $doneSteps = $steps->where('done', true)->count();
    @endphp

    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-6">
        <div>
            <h1 class="fs-2 fw-bold text-gray-900 mb-1">Halo, {{ \Illuminate\Support\Str::of($user->name)->before(' ') }} 👋</h1>
            <p class="text-muted fs-7 mb-0">{{ now()->locale('id')->translatedFormat('l, j F Y') }} · {{ $team->name }}</p>
        </div>
        @can('team.letters.manage')
            <div class="d-flex gap-2">
                <a href="{{ route('team.incoming.create') }}" class="btn btn-primary"><i class="ki-outline ki-plus fs-3"></i> Catat Surat Masuk</a>
                <a href="{{ route('team.outgoing.create') }}" class="btn btn-light-primary"><i class="ki-outline ki-pencil fs-3"></i> Buat Surat Keluar</a>
            </div>
        @endcan
    </div>

    {{-- Checklist awal: hilang otomatis setelah semua langkah selesai. --}}
    @if ($steps->isNotEmpty() && $doneSteps < $steps->count())
        <div class="card mb-6 border border-primary border-dashed">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="fs-4 fw-bold mb-0">Mulai pakai {{ config('app.name') }}</h2>
                    <span class="badge badge-light-primary">{{ $doneSteps }}/{{ $steps->count() }} selesai</span>
                </div>
                <div class="progress h-6px mb-5" role="progressbar" aria-label="Kemajuan persiapan" aria-valuenow="{{ $doneSteps }}" aria-valuemin="0" aria-valuemax="{{ $steps->count() }}">
                    <div class="progress-bar bg-primary" style="width: {{ round($doneSteps / max(1, $steps->count()) * 100) }}%"></div>
                </div>
                <div class="row g-3">
                    @foreach ($steps as $step)
                        <div class="col-md-6 col-xl-4">
                            <a href="{{ route($step['route']) }}" class="d-flex align-items-center gap-3 p-3 rounded {{ $step['done'] ? 'bg-light-success text-success' : 'bg-light text-gray-800' }}">
                                <i class="ki-outline {{ $step['done'] ? 'ki-check-circle text-success' : 'ki-arrow-right text-primary' }} fs-2"></i>
                                <span class="fw-semibold {{ $step['done'] ? 'text-decoration-line-through' : '' }}">{{ $step['label'] }}</span>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div class="row g-5 mb-6">
        @foreach ([
            ['Surat masuk bulan ini', $stats['incoming_month'], 'ki-sms', 'primary', route('team.incoming.index')],
            ['Surat keluar bulan ini', $stats['outgoing_month'], 'ki-directbox-default', 'info', route('team.outgoing.index')],
            ['Surat belum didisposisi', $stats['incoming_new'], 'ki-information-5', 'warning', route('team.incoming.index', ['status' => 'baru'])],
            ['Disposisi lewat tenggat', $stats['dispositions_overdue'], 'ki-time', 'danger', route('team.dispositions.index', ['lingkup' => 'semua', 'status' => 'terlambat'])],
        ] as [$label, $value, $icon, $color, $url])
            <div class="col-6 col-xl-3">
                <a href="{{ $url }}" class="card card-flush h-100 hover-elevate-up">
                    <div class="card-body d-flex align-items-center gap-4">
                        <span class="symbol symbol-50px"><span class="symbol-label bg-light-{{ $color }}"><i class="ki-outline {{ $icon }} fs-2x text-{{ $color }}"></i></span></span>
                        <div>
                            <div class="fs-2hx fw-bolder text-gray-900 lh-1">{{ $value }}</div>
                            <div class="text-gray-600 fw-semibold fs-7 mt-1">{{ $label }}</div>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    <div class="row g-6">
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header"><h3 class="card-title fw-bold">Perlu tindakan Anda</h3></div>
                <div class="card-body pt-3">
                    @forelse ($myDispositions as $d)
                        <a href="{{ route('team.incoming.show', $d->incoming_letter_id) }}" class="d-flex align-items-start gap-3 py-3 border-bottom border-gray-200 text-gray-800">
                            <i class="ki-outline ki-send fs-2 text-primary mt-1"></i>
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-bold text-truncate">{{ $d->letter?->subject }}</div>
                                <div class="text-muted fs-7 text-truncate">Disposisi dari {{ $d->sender?->name ?? '-' }}: {{ \Illuminate\Support\Str::limit($d->instruction, 70) }}</div>
                            </div>
                            @if ($d->due_date)
                                <span class="badge badge-light-{{ $d->isOverdue() ? 'danger' : 'warning' }} text-nowrap">{{ $d->due_date->locale('id')->translatedFormat('j M') }}</span>
                            @endif
                        </a>
                    @empty
                        @if ($awaitingApproval->isEmpty())
                            <div class="text-center text-muted py-10">
                                <i class="ki-outline ki-like fs-3x text-success d-block mb-3"></i>
                                Tidak ada tugas yang menunggu. Kerja bagus!
                            </div>
                        @endif
                    @endforelse

                    @foreach ($awaitingApproval as $o)
                        <a href="{{ route('team.outgoing.show', $o) }}" class="d-flex align-items-start gap-3 py-3 border-bottom border-gray-200 text-gray-800">
                            <i class="ki-outline ki-check-square fs-2 text-warning mt-1"></i>
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-bold text-truncate">{{ $o->subject }}</div>
                                <div class="text-muted fs-7">Surat keluar menunggu persetujuan Anda</div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card mb-6">
                <div class="card-header"><h3 class="card-title fw-bold">Tren 6 bulan</h3></div>
                <div class="card-body">
                    @php $max = max(1, collect($trend)->max(fn ($t) => max($t['in'], $t['out']))); @endphp
                    <div class="d-flex align-items-end gap-3" style="height: 150px;" role="img"
                        aria-label="Grafik surat masuk dan keluar per bulan: @foreach ($trend as $t){{ $t['label'] }} masuk {{ $t['in'] }} keluar {{ $t['out'] }}; @endforeach">
                        @foreach ($trend as $t)
                            <div class="flex-grow-1 d-flex flex-column align-items-center justify-content-end h-100">
                                <div class="d-flex align-items-end gap-1 w-100 justify-content-center" style="height: 120px;">
                                    <div class="bg-primary rounded-top" style="width: 40%; height: {{ max(2, round($t['in'] / $max * 115)) }}px;" title="Masuk: {{ $t['in'] }}"></div>
                                    <div class="bg-info rounded-top" style="width: 40%; height: {{ max(2, round($t['out'] / $max * 115)) }}px;" title="Keluar: {{ $t['out'] }}"></div>
                                </div>
                                <span class="fs-8 text-gray-600 mt-2">{{ $t['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="d-flex gap-5 mt-4 fs-7">
                        <span><span class="bullet bullet-dot bg-primary me-2"></span>Surat masuk</span>
                        <span><span class="bullet bullet-dot bg-info me-2"></span>Surat keluar</span>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title fw-bold">Surat masuk terbaru</h3>
                    <div class="card-toolbar"><a href="{{ route('team.incoming.index') }}" class="btn btn-sm btn-light">Semua</a></div>
                </div>
                <div class="card-body pt-2">
                    @forelse ($recentIncoming as $l)
                        <a href="{{ route('team.incoming.show', $l) }}" class="d-flex justify-content-between gap-3 py-3 border-bottom border-gray-200 text-gray-800">
                            <div class="min-w-0">
                                <div class="fw-bold text-truncate">{{ $l->subject }}</div>
                                <div class="text-muted fs-7 text-truncate">{{ $l->sender }} · {{ $l->received_date->locale('id')->translatedFormat('j M Y') }}</div>
                            </div>
                            <span class="badge badge-light-{{ $l->status->badge() }} align-self-center">{{ $l->status->label() }}</span>
                        </a>
                    @empty
                        <p class="text-muted text-center py-6 mb-0">Belum ada surat masuk.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
