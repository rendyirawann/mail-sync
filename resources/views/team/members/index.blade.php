@extends('team.layout.app')

@section('title', 'Anggota Tim')

@section('content')
    @php $used = $members->count(); $full = $used >= $max; @endphp

    <div class="mb-6">
        <h1 class="fs-2 fw-bold text-gray-900 mb-1">Anggota Tim</h1>
        <p class="text-muted fs-7 mb-0">Akun yang bisa login ke <span class="font-monospace">{{ route('team.login') }}</span></p>
    </div>

    @if (session('error'))<div class="alert alert-danger mb-6">{{ session('error') }}</div>@endif
    @if ($errors->any())
        <div class="alert alert-danger mb-6"><ul class="mb-0 ps-4">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <div class="row g-6">
        <div class="col-lg-8">
            <div class="card mb-6">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="fw-bold">Kuota akun paket {{ $team->plan->name }}</span>
                        <span class="fw-bold {{ $full ? 'text-danger' : 'text-gray-800' }}">{{ $used }} / {{ $max }}</span>
                    </div>
                    <div class="progress h-8px" role="progressbar" aria-label="Kuota akun" aria-valuenow="{{ $used }}" aria-valuemin="0" aria-valuemax="{{ $max }}">
                        <div class="progress-bar {{ $full ? 'bg-danger' : 'bg-primary' }}" style="width: {{ min(100, round($used / max(1, $max) * 100)) }}%"></div>
                    </div>
                    <div class="text-muted fs-7 mt-2">Termasuk owner. Butuh tujuan disposisi lebih banyak? Tambahkan sebagai <a href="{{ route('team.employees.index') }}">pegawai</a> — tidak memakai kuota.</div>
                </div>
            </div>

            <div class="card">
                <div class="card-body pt-4">
                    @foreach ($members as $m)
                        <div class="d-flex flex-column flex-md-row gap-4 py-5 {{ $loop->last ? '' : 'border-bottom border-gray-200' }}">
                            <div class="d-flex align-items-center gap-3 flex-grow-1 min-w-0">
                                <x-avatar :user="$m" :size="40" />
                                <div class="min-w-0">
                                    <div class="fw-bold">{{ $m->name }} @if ($m->is(auth()->user()))<span class="text-muted fw-normal">(Anda)</span>@endif</div>
                                    <div class="text-muted fs-7 text-truncate">{{ $m->email }}{{ $m->last_login ? ' · login '.$m->last_login->locale('id')->diffForHumans() : ' · belum pernah login' }}</div>
                                </div>
                            </div>
                            @if ($m->isOwner())
                                <span class="badge badge-light-primary align-self-md-center">Owner</span>
                            @else
                                <form method="POST" action="{{ route('team.members.update', $m) }}" class="d-flex align-items-center gap-2">
                                    @csrf @method('PUT')
                                    <select name="team_role" class="form-select form-select-sm form-select-solid w-125px" aria-label="Peran {{ $m->name }}">
                                        @foreach ($roles as $r)<option value="{{ $r->value }}" @selected($m->team_role === $r)>{{ $r->label() }}</option>@endforeach
                                    </select>
                                    <label class="form-check form-switch form-check-sm form-check-custom form-check-solid" title="Aktif">
                                        <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked($m->is_active) aria-label="Aktif" />
                                    </label>
                                    <button class="btn btn-sm btn-light-primary">Simpan</button>
                                </form>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#password-modal" data-url="{{ route('team.members.password', $m) }}" data-name="{{ $m->name }}">Reset password</button>
                                    <form method="POST" action="{{ route('team.members.destroy', $m) }}">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-icon btn-light-danger" aria-label="Hapus {{ $m->name }}" data-confirm="Akun {{ $m->name }} akan dihapus dan tidak bisa login lagi." data-confirm-title="Hapus anggota?"><i class="ki-outline ki-trash fs-5"></i></button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-6">
                <div class="card-header"><h3 class="card-title fs-5">Tambah anggota</h3></div>
                <div class="card-body">
                    @if ($full)
                        <p class="text-muted mb-0">Kuota akun penuh. Hapus anggota yang tidak aktif untuk menambah yang baru.</p>
                    @else
                        <form method="POST" action="{{ route('team.members.store') }}">
                            @csrf
                            <label class="form-label required" for="m-name">Nama</label>
                            <input id="m-name" name="name" class="form-control form-control-solid mb-4" required value="{{ old('name') }}" />
                            <label class="form-label required" for="m-email">Email (untuk login)</label>
                            <input id="m-email" name="email" type="email" class="form-control form-control-solid mb-4" required value="{{ old('email') }}" />
                            <label class="form-label" for="m-wa">WhatsApp</label>
                            <input id="m-wa" name="no_wa" class="form-control form-control-solid mb-4" value="{{ old('no_wa') }}" placeholder="0812..." />
                            <label class="form-label required">Peran</label>
                            @foreach ($roles as $r)
                                <label class="d-flex align-items-start gap-3 border rounded p-3 mb-2 cursor-pointer">
                                    <input class="form-check-input mt-1" type="radio" name="team_role" value="{{ $r->value }}" @checked(old('team_role', 'staf') === $r->value) required />
                                    <span><span class="fw-bold">{{ $r->label() }}</span><br><span class="text-muted fs-7">{{ $r->description() }}</span></span>
                                </label>
                            @endforeach
                            <label class="form-label required mt-3" for="m-password">Password awal</label>
                            <input id="m-password" name="password" type="text" class="form-control form-control-solid mb-1" required autocomplete="new-password" />
                            <div class="form-text mb-4">Min. 8 karakter, huruf besar, huruf kecil, angka.</div>
                            <button class="btn btn-primary w-100">Buat akun</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="password-modal" tabindex="-1" aria-labelledby="password-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content" id="password-form">
                @csrf @method('PUT')
                <div class="modal-header"><h2 class="modal-title fs-4" id="password-modal-title">Reset password</h2></div>
                <div class="modal-body">
                    <label class="form-label required" for="new-password">Password baru</label>
                    <input id="new-password" name="password" type="text" class="form-control form-control-solid" required autocomplete="new-password" />
                    <div class="form-text">Min. 8 karakter, huruf besar, huruf kecil, angka.</div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan</button></div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.getElementById('password-modal').addEventListener('show.bs.modal', function (event) {
            document.getElementById('password-form').action = event.relatedTarget.dataset.url;
            document.getElementById('password-modal-title').textContent = 'Reset password ' + event.relatedTarget.dataset.name;
        });
    </script>
@endpush
