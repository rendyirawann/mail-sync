@extends('team.layout.app')

@section('title', 'Klasifikasi & Kategori')

@section('content')
    @php $canManage = auth()->user()->can('team.master.manage'); @endphp

    <div class="mb-6">
        <h1 class="fs-2 fw-bold text-gray-900 mb-1">Klasifikasi & Kategori</h1>
        <p class="text-muted fs-7 mb-0">Kode klasifikasi dipakai di nomor surat keluar; jenis surat dan kontak mempercepat pencatatan.</p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger mb-6">{{ $errors->first() }}</div>
    @endif

    <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-6 fw-semibold mb-6">
        @foreach ($types as $key => $type)
            <li class="nav-item"><a class="nav-link {{ $tab === $key ? 'active' : '' }}" href="{{ route('team.master.index', ['tab' => $key]) }}">{{ $type['label'] }}</a></li>
        @endforeach
    </ul>

    <div class="row g-6">
        @if ($canManage)
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header"><h3 class="card-title fs-5">Tambah {{ strtolower($types[$tab]['label']) }}</h3></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('team.master.store', $tab) }}">
                            @csrf
                            @if ($tab === 'klasifikasi')
                                <label class="form-label required" for="code">Kode</label>
                                <input id="code" name="code" class="form-control form-control-solid mb-4 font-monospace" placeholder="mis. 005" required maxlength="30" value="{{ old('code') }}" />
                            @endif
                            <label class="form-label required" for="name">Nama</label>
                            <input id="name" name="name" class="form-control form-control-solid mb-4" required maxlength="150" value="{{ old('name') }}" />
                            @if ($tab === 'kontak')
                                <label class="form-label" for="address">Alamat</label>
                                <input id="address" name="address" class="form-control form-control-solid mb-4" maxlength="255" />
                                <label class="form-label" for="phone">Telepon</label>
                                <input id="phone" name="phone" class="form-control form-control-solid mb-4" maxlength="20" />
                                <label class="form-label" for="email">Email</label>
                                <input id="email" name="email" type="email" class="form-control form-control-solid mb-4" />
                            @endif
                            <button class="btn btn-primary w-100">Tambah</button>
                        </form>
                        @if ($tab !== 'kontak')
                            <form method="POST" action="{{ route('team.master.store', $tab) }}" class="mt-4">
                                @csrf <input type="hidden" name="defaults" value="1" />
                                <button class="btn btn-light w-100">Isi dengan daftar bawaan</button>
                            </form>
                        @else
                            <p class="text-muted fs-7 mt-4 mb-0">Kontak juga tersimpan otomatis dari pengirim/tujuan surat.</p>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <div class="{{ $canManage ? 'col-lg-8' : 'col-12' }}">
            <div class="card">
                <div class="card-body">
                    @php
                        $rows = match ($tab) { 'klasifikasi' => $classifications, 'jenis' => $categories, default => $contacts };
                    @endphp
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed gy-3">
                            <thead><tr class="text-muted fw-bold fs-7 text-uppercase">
                                @if ($tab === 'klasifikasi')<th class="w-100px">Kode</th>@endif
                                <th>Nama</th>
                                @if ($tab === 'kontak')<th>Kontak</th>@endif
                                @if ($canManage)<th class="text-end w-150px"></th>@endif
                            </tr></thead>
                            <tbody>
                                @forelse ($rows as $row)
                                    <tr>
                                        @if ($canManage)
                                            <td colspan="{{ $tab === 'kontak' ? 2 : ($tab === 'klasifikasi' ? 2 : 1) }}">
                                                <form method="POST" action="{{ route('team.master.update', [$tab, $row->id]) }}" class="d-flex gap-2" id="row-{{ $row->id }}">
                                                    @csrf @method('PUT')
                                                    @if ($tab === 'klasifikasi')
                                                        <input name="code" value="{{ $row->code }}" class="form-control form-control-sm form-control-solid font-monospace w-100px" aria-label="Kode" required />
                                                    @endif
                                                    <input name="name" value="{{ $row->name }}" class="form-control form-control-sm form-control-solid" aria-label="Nama" required />
                                                    @if ($tab === 'kontak')
                                                        <input name="phone" value="{{ $row->phone }}" class="form-control form-control-sm form-control-solid w-150px" aria-label="Telepon" placeholder="Telepon" />
                                                    @endif
                                                </form>
                                            </td>
                                            <td class="text-end text-nowrap">
                                                <button class="btn btn-sm btn-light-primary" form="row-{{ $row->id }}">Simpan</button>
                                                <form method="POST" action="{{ route('team.master.destroy', [$tab, $row->id]) }}" class="d-inline">
                                                    @csrf @method('DELETE')
                                                    <button class="btn btn-sm btn-icon btn-light-danger" aria-label="Hapus" data-confirm="Hapus data ini?" data-confirm-title="Hapus?"><i class="ki-outline ki-trash fs-5"></i></button>
                                                </form>
                                            </td>
                                        @else
                                            @if ($tab === 'klasifikasi')<td class="font-monospace">{{ $row->code }}</td>@endif
                                            <td>{{ $row->name }}</td>
                                            @if ($tab === 'kontak')<td class="text-muted">{{ $row->phone }} {{ $row->email }}</td>@endif
                                        @endif
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-10">Belum ada data.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($tab === 'kontak') {{ $contacts->links() }} @endif
                </div>
            </div>
        </div>
    </div>
@endsection
