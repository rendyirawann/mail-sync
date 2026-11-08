@extends('team.layout.app')

@section('title', 'Disposisi')

@section('content')
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-6">
        <div>
            <h1 class="fs-2 fw-bold text-gray-900 mb-1">{{ $scope === 'saya' ? 'Disposisi Saya' : 'Semua Disposisi' }}</h1>
            <p class="text-muted fs-7 mb-0">Tindak lanjuti disposisi, lalu tandai selesai dengan laporan singkat.</p>
        </div>
        @can('team.dispositions.create')
            <div class="btn-group">
                <a href="{{ route('team.dispositions.index', ['lingkup' => 'saya', 'status' => $status]) }}" class="btn btn-sm {{ $scope === 'saya' ? 'btn-primary' : 'btn-light' }}">Untuk saya</a>
                <a href="{{ route('team.dispositions.index', ['lingkup' => 'semua', 'status' => $status]) }}" class="btn btn-sm {{ $scope === 'semua' ? 'btn-primary' : 'btn-light' }}">Semua</a>
            </div>
        @endcan
    </div>

    @if ($scope === 'saya' && ! $linkedEmployee)
        <div class="alert alert-info mb-6">
            Akun Anda belum ditautkan ke data pegawai, jadi belum ada disposisi yang ditujukan ke Anda.
            @can('team.master.manage') Tautkan di <a href="{{ route('team.employees.index') }}">Pegawai & Divisi</a>. @else Minta owner/admin menautkannya. @endcan
        </div>
    @endif

    <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-6 fw-semibold mb-5">
        @foreach (['aktif' => 'Belum selesai', 'terlambat' => 'Lewat tenggat', 'selesai' => 'Selesai', 'semua' => 'Semua'] as $key => $label)
            <li class="nav-item"><a class="nav-link {{ $status === $key ? 'active' : '' }}" href="{{ route('team.dispositions.index', ['lingkup' => $scope, 'status' => $key]) }}">{{ $label }}</a></li>
        @endforeach
    </ul>

    <div class="row g-5">
        @forelse ($dispositions as $d)
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex flex-column flex-lg-row gap-5">
                            <div class="flex-grow-1 min-w-0">
                                <div class="d-flex flex-wrap gap-2 mb-2">
                                    <span class="badge badge-light-{{ $d->isOverdue() ? 'danger' : $d->status->badge() }}">{{ $d->isOverdue() ? 'Lewat tenggat' : $d->status->label() }}</span>
                                    <span class="badge badge-light-{{ $d->letter->nature->badge() }}">{{ $d->letter->nature->label() }}</span>
                                    @if ($d->due_date)<span class="badge badge-light">Tenggat {{ $d->due_date->locale('id')->translatedFormat('j M Y') }}</span>@endif
                                </div>
                                <a href="{{ route('team.incoming.show', $d->incoming_letter_id) }}" class="fs-5 fw-bold text-gray-900 text-hover-primary">{{ $d->letter->subject }}</a>
                                <div class="text-muted fs-7">Dari {{ $d->letter->sender }} · Agenda {{ $d->letter->agendaLabel() }}</div>
                                <div class="mt-3"><span class="text-muted fs-7">{{ $d->sender?->name ?? '-' }} → {{ $d->recipient?->name }}:</span>
                                    <div class="text-gray-800">{{ $d->instruction }}</div>
                                </div>
                                @if ($d->response)
                                    <div class="bg-light-success rounded p-3 mt-3 fs-7"><strong>Laporan:</strong> {{ $d->response }}</div>
                                @endif
                            </div>

                            @if ($d->status !== \App\Enums\DispositionStatus::Selesai && ($d->recipient?->user_id === auth()->id() || auth()->user()->can('team.dispositions.create')))
                                <form method="POST" action="{{ route('team.dispositions.update', $d) }}" class="w-lg-350px">
                                    @csrf @method('PATCH')
                                    <textarea name="response" rows="2" class="form-control form-control-solid mb-3" maxlength="2000" placeholder="Laporan tindak lanjut (wajib saat selesai)" aria-label="Laporan tindak lanjut">{{ $d->response }}</textarea>
                                    <div class="d-flex gap-2">
                                        @if ($d->status === \App\Enums\DispositionStatus::Baru)
                                            <button name="status" value="diproses" class="btn btn-sm btn-light-warning flex-grow-1">Mulai proses</button>
                                        @endif
                                        <button name="status" value="selesai" class="btn btn-sm btn-success flex-grow-1"><i class="ki-outline ki-check fs-4"></i> Tandai selesai</button>
                                    </div>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card"><div class="card-body text-center py-15">
                    <i class="ki-outline ki-like fs-3x text-success d-block mb-3"></i>
                    <div class="fw-semibold text-gray-700">Tidak ada disposisi di daftar ini.</div>
                </div></div>
            </div>
        @endforelse
    </div>

    <div class="mt-5">{{ $dispositions->links() }}</div>
@endsection
