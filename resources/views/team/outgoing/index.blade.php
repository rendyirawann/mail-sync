@extends('team.layout.app')

@section('title', 'Surat Keluar')

@section('content')
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-6">
        <div>
            <h1 class="fs-2 fw-bold text-gray-900 mb-1">Surat Keluar</h1>
            <p class="text-muted fs-7 mb-0">Buat dari template, ajukan, setujui, lalu unduh PDF ber-QR.</p>
        </div>
        @can('team.letters.manage')
            <a href="{{ route('team.outgoing.create') }}" class="btn btn-primary"><i class="ki-outline ki-plus fs-3"></i> Buat Surat Keluar</a>
        @endcan
    </div>

    <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-6 fw-semibold mb-5 flex-nowrap overflow-auto">
        <li class="nav-item"><a class="nav-link text-nowrap {{ $status ? '' : 'active' }}" href="{{ route('team.outgoing.index', array_filter(['q' => $search])) }}">Semua</a></li>
        @foreach (\App\Enums\OutgoingStatus::cases() as $case)
            <li class="nav-item">
                <a class="nav-link text-nowrap {{ $status === $case->value ? 'active' : '' }}" href="{{ route('team.outgoing.index', array_filter(['status' => $case->value, 'q' => $search])) }}">
                    {{ $case->label() }} <span class="badge badge-light-{{ $case->badge() }} ms-1">{{ $counts[$case->value] ?? 0 }}</span>
                </a>
            </li>
        @endforeach
    </ul>

    <div class="card">
        <div class="card-header border-0 pt-6">
            <form method="GET" class="d-flex gap-2" role="search">
                @if ($status)<input type="hidden" name="status" value="{{ $status }}" />@endif
                <input type="search" name="q" value="{{ $search }}" class="form-control form-control-solid w-300px" placeholder="Cari perihal, tujuan, nomor..." aria-label="Cari surat keluar" />
                <button class="btn btn-light-primary">Cari</button>
            </form>
        </div>
        <div class="card-body pt-2">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-4">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase">
                            <th>Nomor</th>
                            <th>Perihal / Tujuan</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th class="text-end"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($letters as $letter)
                            <tr>
                                <td class="font-monospace fs-7">{{ $letter->letter_number ?? '—' }}</td>
                                <td>
                                    <a href="{{ route('team.outgoing.show', $letter) }}" class="fw-bold text-gray-900 text-hover-primary">{{ $letter->subject }}</a>
                                    <div class="text-muted fs-7">Kepada {{ $letter->recipient }} · {{ $letter->template?->name ?? 'Surat bebas' }}</div>
                                </td>
                                <td class="text-nowrap">{{ $letter->letter_date->locale('id')->translatedFormat('j M Y') }}</td>
                                <td><span class="badge badge-light-{{ $letter->status->badge() }}">{{ $letter->status->label() }}</span></td>
                                <td class="text-end"><a href="{{ route('team.outgoing.show', $letter) }}" class="btn btn-sm btn-light">Buka</a></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-15">
                                    <i class="ki-outline ki-directbox-default fs-3x text-gray-400 d-block mb-3"></i>
                                    <div class="fw-semibold text-gray-700">Belum ada surat keluar.</div>
                                    @can('team.letters.manage')
                                        <a href="{{ route('team.outgoing.create') }}" class="btn btn-sm btn-primary mt-4">Buat surat pertama</a>
                                    @endcan
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $letters->links() }}
        </div>
    </div>
@endsection
