@extends('team.layout.app')

@section('title', 'Pegawai & Divisi')

@section('content')
    @php $canManage = auth()->user()->can('team.master.manage'); @endphp

    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-6">
        <div>
            <h1 class="fs-2 fw-bold text-gray-900 mb-1">Pegawai & Divisi</h1>
            <p class="text-muted fs-7 mb-0">Tujuan disposisi & penanda tangan surat. Data pegawai <strong>tidak</strong> memakai kuota akun.</p>
        </div>
        @if ($canManage)
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#employee-modal" data-employee="{}">
                <i class="ki-outline ki-plus fs-3"></i> Tambah Pegawai
            </button>
        @endif
    </div>

    @if ($errors->any())
        <div class="alert alert-danger mb-6"><ul class="mb-0 ps-4">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <div class="row g-6">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title fs-5">Divisi / Bidang</h3></div>
                <div class="card-body">
                    <a href="{{ route('team.employees.index') }}" class="d-flex justify-content-between py-2 px-3 rounded mb-1 {{ $divisionFilter === null ? 'bg-light-primary text-primary fw-bold' : 'text-gray-800' }}">
                        <span>Semua pegawai</span>
                    </a>
                    @foreach ($divisions as $division)
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <a href="{{ route('team.employees.index', ['divisi' => $division->id]) }}" class="flex-grow-1 d-flex justify-content-between py-2 px-3 rounded {{ (string) $divisionFilter === (string) $division->id ? 'bg-light-primary text-primary fw-bold' : 'text-gray-800' }}">
                                <span>{{ $division->name }}</span><span class="badge badge-light">{{ $division->employees_count }}</span>
                            </a>
                            @if ($canManage)
                                <form method="POST" action="{{ route('team.employees.divisions.destroy', $division) }}">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-icon btn-light-danger" aria-label="Hapus divisi {{ $division->name }}" data-confirm="Hapus divisi {{ $division->name }}? Pegawai di dalamnya menjadi tanpa divisi." data-confirm-title="Hapus divisi?"><i class="ki-outline ki-trash fs-6"></i></button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                    @if ($canManage)
                        <form method="POST" action="{{ route('team.employees.divisions.store') }}" class="d-flex gap-2 mt-4">
                            @csrf
                            <input name="name" class="form-control form-control-sm form-control-solid" placeholder="Nama divisi baru" aria-label="Nama divisi baru" required maxlength="100" />
                            <button class="btn btn-sm btn-light-primary">Tambah</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header border-0 pt-6">
                    <form method="GET" class="d-flex gap-2" role="search">
                        @if ($divisionFilter)<input type="hidden" name="divisi" value="{{ $divisionFilter }}" />@endif
                        <input type="search" name="q" value="{{ $search }}" class="form-control form-control-solid w-250px" placeholder="Cari nama, NIP, jabatan" aria-label="Cari pegawai" />
                        <button class="btn btn-light">Cari</button>
                    </form>
                </div>
                <div class="card-body pt-2">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed gy-3">
                            <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Pegawai</th><th>Divisi</th><th>Akun</th>@if ($canManage)<th class="text-end"></th>@endif</tr></thead>
                            <tbody>
                                @forelse ($employees as $e)
                                    <tr class="{{ $e->is_active ? '' : 'opacity-50' }}">
                                        <td>
                                            <div class="fw-bold">{{ $e->name }} @unless ($e->is_active)<span class="badge badge-light-secondary">nonaktif</span>@endunless</div>
                                            <div class="text-muted fs-7">{{ $e->position ?? '-' }}{{ $e->nip ? ' · NIP '.$e->nip : '' }}</div>
                                        </td>
                                        <td>{{ $e->division?->name ?? '-' }}</td>
                                        <td>@if ($e->user)<span class="badge badge-light-success">{{ $e->user->name }}</span>@else<span class="text-muted fs-7">—</span>@endif</td>
                                        @if ($canManage)
                                            <td class="text-end text-nowrap">
                                                <button type="button" class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#employee-modal"
                                                    data-employee="{{ json_encode(['id' => $e->id, 'name' => $e->name, 'nip' => $e->nip, 'position' => $e->position, 'division_id' => $e->division_id, 'phone' => $e->phone, 'email' => $e->email, 'user_id' => $e->user_id, 'is_active' => $e->is_active, 'url' => route('team.employees.update', $e)]) }}">Ubah</button>
                                                <form method="POST" action="{{ route('team.employees.destroy', $e) }}" class="d-inline">
                                                    @csrf @method('DELETE')
                                                    <button class="btn btn-sm btn-icon btn-light-danger" aria-label="Hapus {{ $e->name }}" data-confirm="Hapus {{ $e->name }}? Bila punya riwayat disposisi, pegawai hanya dinonaktifkan." data-confirm-title="Hapus pegawai?"><i class="ki-outline ki-trash fs-5"></i></button>
                                                </form>
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-10">Belum ada pegawai. Tambahkan pegawai agar bisa menerima disposisi.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $employees->links() }}
                </div>
            </div>
        </div>
    </div>

    @if ($canManage)
        <div class="modal fade" id="employee-modal" tabindex="-1" aria-labelledby="employee-modal-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" class="modal-content" id="employee-form" action="{{ route('team.employees.store') }}">
                    @csrf
                    <input type="hidden" name="_method" value="POST" id="employee-method" />
                    <div class="modal-header"><h2 class="modal-title fs-4" id="employee-modal-title">Pegawai</h2>
                        <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal" aria-label="Tutup"><i class="ki-outline ki-cross fs-2"></i></button>
                    </div>
                    <div class="modal-body row g-4">
                        <div class="col-12"><label class="form-label required" for="e-name">Nama</label><input id="e-name" name="name" class="form-control form-control-solid" required maxlength="100" /></div>
                        <div class="col-md-6"><label class="form-label" for="e-position">Jabatan</label><input id="e-position" name="position" class="form-control form-control-solid" maxlength="100" /></div>
                        <div class="col-md-6"><label class="form-label" for="e-nip">NIP</label><input id="e-nip" name="nip" class="form-control form-control-solid" maxlength="30" /></div>
                        <div class="col-md-6"><label class="form-label" for="e-division">Divisi</label>
                            <select id="e-division" name="division_id" class="form-select form-select-solid"><option value="">—</option>
                                @foreach ($divisions as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-md-6"><label class="form-label" for="e-phone">Telepon</label><input id="e-phone" name="phone" class="form-control form-control-solid" maxlength="20" /></div>
                        <div class="col-12"><label class="form-label" for="e-user">Tautkan ke akun anggota (opsional)</label>
                            <select id="e-user" name="user_id" class="form-select form-select-solid"><option value="">— Tidak punya akun —</option>
                                @foreach ($accounts as $a)<option value="{{ $a->id }}">{{ $a->name }} ({{ $a->email }})</option>@endforeach
                            </select>
                            <div class="form-text">Disposisi untuk pegawai ini akan muncul di "Disposisi Saya" akun tersebut + notifikasi.</div>
                        </div>
                        <div class="col-12">
                            <input type="hidden" name="is_active" value="0" />
                            <label class="form-check form-switch form-check-custom form-check-solid"><input id="e-active" class="form-check-input" type="checkbox" name="is_active" value="1" checked /><span class="form-check-label">Aktif</span></label>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan</button></div>
                </form>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        (function () {
            var modal = document.getElementById('employee-modal');
            if (!modal) { return; }
            modal.addEventListener('show.bs.modal', function (event) {
                var data = JSON.parse(event.relatedTarget.getAttribute('data-employee') || '{}');
                var form = document.getElementById('employee-form');
                form.action = data.url || @json(route('team.employees.store'));
                document.getElementById('employee-method').value = data.url ? 'PUT' : 'POST';
                document.getElementById('employee-modal-title').textContent = data.id ? 'Ubah pegawai' : 'Tambah pegawai';
                document.getElementById('e-name').value = data.name || '';
                document.getElementById('e-position').value = data.position || '';
                document.getElementById('e-nip').value = data.nip || '';
                document.getElementById('e-division').value = data.division_id || '';
                document.getElementById('e-phone').value = data.phone || '';
                document.getElementById('e-user').value = data.user_id || '';
                document.getElementById('e-active').checked = data.is_active !== false;
            });
        })();
    </script>
@endpush
