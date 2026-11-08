@extends('team.layout.app')

@section('title', 'Arsip & Pencarian')

@section('content')
    <div class="mb-6">
        <h1 class="fs-2 fw-bold text-gray-900 mb-1">Arsip & Pencarian</h1>
        <p class="text-muted fs-7 mb-0">Cari di seluruh surat masuk dan keluar — perihal, pengirim/tujuan, nomor, atau isi ringkasan.</p>
    </div>

    <div class="card mb-6">
        <div class="card-body">
            <form method="GET" class="d-flex flex-column flex-md-row gap-3" role="search">
                <input type="search" name="q" value="{{ $q }}" class="form-control form-control-lg form-control-solid" placeholder="Ketik kata kunci, mis. undangan rapat, 005/123..." aria-label="Kata kunci" autofocus />
                <select name="tahun" class="form-select form-select-lg form-select-solid w-md-150px" aria-label="Tahun">
                    <option value="0">Semua tahun</option>
                    @foreach ($years as $y)<option value="{{ $y }}" @selected($year === (int) $y)>{{ $y }}</option>@endforeach
                </select>
                <select name="jenis" class="form-select form-select-lg form-select-solid w-md-175px" aria-label="Jenis">
                    @foreach (['semua' => 'Masuk & keluar', 'masuk' => 'Surat masuk', 'keluar' => 'Surat keluar'] as $v => $l)
                        <option value="{{ $v }}" @selected($kind === $v)>{{ $l }}</option>
                    @endforeach
                </select>
                <button class="btn btn-lg btn-primary">Cari</button>
            </form>
        </div>
    </div>

    @if ($q === '' && $year === 0)
        <div class="text-center text-muted py-10"><i class="ki-outline ki-magnifier fs-3x d-block mb-3"></i>Masukkan kata kunci atau pilih tahun untuk mulai mencari.</div>
    @else
        <div class="row g-6">
            @if ($kind !== 'keluar')
                <div class="{{ $kind === 'semua' ? 'col-xl-6' : 'col-12' }}">
                    <div class="card"><div class="card-header"><h3 class="card-title fs-5">Surat masuk <span class="badge badge-light ms-2">{{ $incoming->count() }}</span></h3></div>
                        <div class="card-body pt-2">
                            @forelse ($incoming as $l)
                                <a href="{{ route('team.incoming.show', $l) }}" class="d-block py-3 border-bottom border-gray-200 text-gray-800">
                                    <div class="fw-bold">{{ $l->subject }}</div>
                                    <div class="text-muted fs-7">{{ $l->sender }} · {{ $l->letter_number }} · {{ $l->received_date->locale('id')->translatedFormat('j M Y') }}</div>
                                </a>
                            @empty
                                <p class="text-muted mb-0">Tidak ditemukan.</p>
                            @endforelse
                        </div></div>
                </div>
            @endif
            @if ($kind !== 'masuk')
                <div class="{{ $kind === 'semua' ? 'col-xl-6' : 'col-12' }}">
                    <div class="card"><div class="card-header"><h3 class="card-title fs-5">Surat keluar <span class="badge badge-light ms-2">{{ $outgoing->count() }}</span></h3></div>
                        <div class="card-body pt-2">
                            @forelse ($outgoing as $l)
                                <a href="{{ route('team.outgoing.show', $l) }}" class="d-block py-3 border-bottom border-gray-200 text-gray-800">
                                    <div class="fw-bold">{{ $l->subject }}</div>
                                    <div class="text-muted fs-7">Kepada {{ $l->recipient }} · {{ $l->letter_number ?? 'draf' }} · {{ $l->letter_date->locale('id')->translatedFormat('j M Y') }}</div>
                                </a>
                            @empty
                                <p class="text-muted mb-0">Tidak ditemukan.</p>
                            @endforelse
                        </div></div>
                </div>
            @endif
        </div>
        @if ($incoming->count() >= 50 || $outgoing->count() >= 50)
            <p class="text-muted fs-7 mt-4">Menampilkan 50 hasil teratas per jenis — persempit kata kunci atau pilih tahun.</p>
        @endif
    @endif
@endsection
