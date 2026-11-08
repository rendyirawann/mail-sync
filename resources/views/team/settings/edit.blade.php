@extends('team.layout.app')

@section('title', 'Pengaturan Tim')

@section('content')
    <div class="mb-6">
        <h1 class="fs-2 fw-bold text-gray-900 mb-1">Pengaturan Tim</h1>
        <p class="text-muted fs-7 mb-0">Data instansi, kop surat, dan format penomoran surat keluar.</p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger mb-6"><ul class="mb-0 ps-4">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <div class="row g-6">
        <div class="col-xl-7">
            <form method="POST" action="{{ route('team.settings.update') }}">
                @csrf @method('PUT')
                <div class="card mb-6">
                    <div class="card-header"><h3 class="card-title fs-5">Profil tim</h3></div>
                    <div class="card-body row g-4">
                        <div class="col-12"><label class="form-label required" for="name">Nama tim / instansi</label>
                            <input id="name" name="name" class="form-control form-control-solid" value="{{ old('name', $team->name) }}" required maxlength="120" /></div>
                        <div class="col-md-6"><label class="form-label" for="email">Email</label>
                            <input id="email" name="email" type="email" class="form-control form-control-solid" value="{{ old('email', $team->email) }}" /></div>
                        <div class="col-md-6"><label class="form-label" for="phone">Telepon</label>
                            <input id="phone" name="phone" class="form-control form-control-solid" value="{{ old('phone', $team->phone) }}" maxlength="20" /></div>
                        <div class="col-12"><label class="form-label" for="address">Alamat</label>
                            <input id="address" name="address" class="form-control form-control-solid" value="{{ old('address', $team->address) }}" maxlength="255" /></div>
                    </div>
                </div>

                <div class="card mb-6">
                    <div class="card-header"><h3 class="card-title fs-5">Kop surat</h3></div>
                    <div class="card-body row g-4">
                        <div class="col-12"><label class="form-label required" for="letterhead_name">Nama instansi (baris utama kop)</label>
                            <input id="letterhead_name" name="letterhead_name" class="form-control form-control-solid" value="{{ old('letterhead_name', $team->profileValue('letterhead.name', $team->name)) }}" required maxlength="150" /></div>
                        <div class="col-12"><label class="form-label" for="letterhead_lines">Baris tambahan (satu baris per baris)</label>
                            <textarea id="letterhead_lines" name="letterhead_lines" rows="3" class="form-control form-control-solid" maxlength="500" placeholder="DINAS KOMUNIKASI DAN INFORMATIKA&#10;Jl. Merdeka No. 1, Telp. (061) 123456">{{ old('letterhead_lines', $team->profileValue('letterhead.lines')) }}</textarea></div>
                        <div class="col-12"><label class="form-label" for="letterhead_logo_url">Tautan logo (Google Drive / https)</label>
                            <input id="letterhead_logo_url" name="letterhead_logo_url" type="url" class="form-control form-control-solid" value="{{ old('letterhead_logo_url', $team->profileValue('letterhead.logo_url')) }}" placeholder="https://drive.google.com/file/d/..." />
                            <div class="form-text">Unggah logo PNG/JPG ke Google Drive, atur akses "Siapa saja yang memiliki link", lalu tempel tautannya.</div></div>
                    </div>
                </div>

                <div class="card mb-6">
                    <div class="card-header"><h3 class="card-title fs-5">Penomoran surat keluar</h3></div>
                    <div class="card-body row g-4">
                        <div class="col-md-8"><label class="form-label required" for="outgoing_format">Format nomor</label>
                            <input id="outgoing_format" name="outgoing_format" class="form-control form-control-solid font-monospace" value="{{ old('outgoing_format', $team->profileValue('numbering.outgoing_format', \App\Services\Letters\NumberingService::DEFAULT_FORMAT)) }}" required maxlength="100" /></div>
                        <div class="col-md-2"><label class="form-label required" for="digits">Digit</label>
                            <input id="digits" name="digits" type="number" min="1" max="6" class="form-control form-control-solid" value="{{ old('digits', $team->profileValue('numbering.digits', 3)) }}" required /></div>
                        <div class="col-md-2"><label class="form-label" for="abbreviation">Singkatan</label>
                            <input id="abbreviation" name="abbreviation" class="form-control form-control-solid" value="{{ old('abbreviation', $team->profileValue('numbering.abbreviation')) }}" maxlength="20" /></div>
                        <div class="col-12">
                            <div class="form-text">Penanda: <code>{nomor}</code> urut per tahun · <code>{kode}</code> kode klasifikasi · <code>{bulan}</code> 01–12 · <code>{bulan_romawi}</code> I–XII · <code>{tahun}</code> · <code>{singkatan}</code></div>
                            <div class="bg-light rounded p-3 mt-3">Contoh hasil: <span class="font-monospace fw-bold">{{ $numberExample }}</span></div>
                        </div>
                    </div>
                </div>

                <button class="btn btn-primary">Simpan Pengaturan</button>
            </form>
        </div>

        <div class="col-xl-5">
            <div class="card">
                <div class="card-header"><h3 class="card-title fs-5">Modul tambahan</h3></div>
                <div class="card-body">
                    <p class="text-muted fs-7">Buka hanya modul yang dipakai tim agar menu tetap ringkas. Data modul yang dikunci tetap tersimpan.</p>
                    @can('team.modules.manage')
                        <form method="POST" action="{{ route('team.settings.modules') }}">
                            @csrf @method('PUT')
                            @foreach ($modules as $key => $label)
                                <label class="d-flex justify-content-between align-items-center border rounded p-4 mb-3">
                                    <span class="fw-semibold">{{ $label }}</span>
                                    <span class="form-check form-switch form-check-custom form-check-solid">
                                        <input class="form-check-input" type="checkbox" name="modules[{{ $key }}]" value="1" @checked($team->moduleEnabled($key)) />
                                    </span>
                                </label>
                            @endforeach
                            <button class="btn btn-light-primary w-100">Simpan modul</button>
                        </form>
                    @else
                        @foreach ($modules as $key => $label)
                            <div class="d-flex justify-content-between border rounded p-4 mb-3"><span>{{ $label }}</span>
                                <span class="badge badge-light-{{ $team->moduleEnabled($key) ? 'success' : 'secondary' }}">{{ $team->moduleEnabled($key) ? 'Dibuka' : 'Dikunci' }}</span></div>
                        @endforeach
                        <div class="text-muted fs-7">Hanya owner yang dapat membuka/mengunci modul.</div>
                    @endcan
                </div>
            </div>
        </div>
    </div>
@endsection
