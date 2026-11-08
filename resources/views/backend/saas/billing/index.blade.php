@extends('backend.layout.app')

@section('title', 'Pengaturan Pembayaran')

@section('content')
    <div class="mb-6">
        <h1 class="fs-2 fw-bold text-gray-900 mb-1">Pengaturan Pembayaran</h1>
        <p class="text-muted fs-7 mb-0">Semua yang diatur di sini tampil otomatis di halaman pendaftaran pelanggan.</p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger mb-6"><ul class="mb-0 ps-4">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <div class="row g-6">
        <div class="col-xl-6">
            {{-- Paket --}}
            <div class="card mb-6">
                <div class="card-header"><h3 class="card-title">Paket & harga</h3></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.saas.billing.plans') }}">
                        @csrf @method('PUT')
                        @foreach ($plans as $plan)
                            <div class="border rounded p-5 mb-5">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <span class="badge badge-light-primary text-uppercase">{{ $plan->isLifetime() ? 'Sekali bayar' : $plan->duration_months.' bulan' }}</span>
                                    <label class="form-check form-switch form-check-custom form-check-solid">
                                        <input type="hidden" name="plans[{{ $plan->id }}][is_active]" value="0" />
                                        <input class="form-check-input" type="checkbox" name="plans[{{ $plan->id }}][is_active]" value="1" @checked($plan->is_active) />
                                        <span class="form-check-label">Dijual</span>
                                    </label>
                                </div>
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="form-label" for="plan-name-{{ $plan->id }}">Nama paket</label>
                                        <input id="plan-name-{{ $plan->id }}" name="plans[{{ $plan->id }}][name]" class="form-control form-control-solid" value="{{ $plan->name }}" required />
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label" for="plan-price-{{ $plan->id }}">Harga (Rp)</label>
                                        <input id="plan-price-{{ $plan->id }}" type="number" min="0" name="plans[{{ $plan->id }}][price]" class="form-control form-control-solid" value="{{ $plan->price }}" required />
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label" for="plan-max-{{ $plan->id }}">Maks. akun</label>
                                        <input id="plan-max-{{ $plan->id }}" type="number" min="1" max="50" name="plans[{{ $plan->id }}][max_members]" class="form-control form-control-solid" value="{{ $plan->max_members }}" required />
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label" for="plan-desc-{{ $plan->id }}">Deskripsi singkat</label>
                                        <input id="plan-desc-{{ $plan->id }}" name="plans[{{ $plan->id }}][description]" class="form-control form-control-solid" value="{{ $plan->description }}" maxlength="255" />
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        <button class="btn btn-primary">Simpan paket</button>
                    </form>
                </div>
            </div>

            {{-- WhatsApp --}}
            <div class="card">
                <div class="card-header"><h3 class="card-title">WhatsApp konfirmasi</h3></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.saas.billing.whatsapp') }}">
                        @csrf @method('PUT')
                        <label class="form-label required" for="whatsapp">Nomor WhatsApp admin</label>
                        <input id="whatsapp" name="whatsapp" class="form-control form-control-solid mb-5" value="{{ old('whatsapp', $whatsapp) }}" placeholder="081234567890" required />
                        <label class="form-label" for="wa_registration">Template pesan pendaftaran</label>
                        <textarea id="wa_registration" name="wa_registration" rows="7" class="form-control form-control-solid mb-2">{{ old('wa_registration', $waRegistration) }}</textarea>
                        <div class="form-text mb-5">Penanda: {app} {kode} {tim} {owner} {paket} {total}</div>
                        <label class="form-label" for="wa_renewal">Template pesan perpanjangan</label>
                        <textarea id="wa_renewal" name="wa_renewal" rows="6" class="form-control form-control-solid mb-2">{{ old('wa_renewal', $waRenewal) }}</textarea>
                        <div class="form-text mb-5">Penanda: {app} {tim} {slug} {paket} {total}</div>
                        <button class="btn btn-primary">Simpan</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Rekening tujuan transfer</h3></div>
                <div class="card-body">
                    @forelse ($banks as $bank)
                        <form method="POST" action="{{ route('admin.saas.billing.banks.update', $bank) }}" class="border rounded p-5 mb-4">
                            @csrf @method('PUT')
                            <div class="row g-3">
                                <div class="col-md-4"><input name="bank_name" class="form-control form-control-solid" value="{{ $bank->bank_name }}" aria-label="Bank" required /></div>
                                <div class="col-md-8"><input name="account_number" class="form-control form-control-solid font-monospace" value="{{ $bank->account_number }}" aria-label="Nomor rekening" required /></div>
                                <div class="col-12"><input name="account_holder" class="form-control form-control-solid" value="{{ $bank->account_holder }}" aria-label="Atas nama" required /></div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-4">
                                <label class="form-check form-switch form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked($bank->is_active) />
                                    <span class="form-check-label">Tampilkan</span>
                                </label>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-light-primary">Simpan</button>
                                    <button class="btn btn-sm btn-light-danger" form="delete-bank-{{ $bank->id }}">Hapus</button>
                                </div>
                            </div>
                        </form>
                        <form id="delete-bank-{{ $bank->id }}" method="POST" action="{{ route('admin.saas.billing.banks.destroy', $bank) }}" onsubmit="return confirm('Hapus rekening ini?');">
                            @csrf @method('DELETE')
                        </form>
                    @empty
                        <p class="text-muted">Belum ada rekening. Tambahkan minimal satu agar pelanggan tahu tujuan transfer.</p>
                    @endforelse

                    <form method="POST" action="{{ route('admin.saas.billing.banks.store') }}" class="bg-light rounded p-5 mt-6">
                        @csrf
                        <h4 class="fs-6 fw-bold mb-4">Tambah rekening</h4>
                        <div class="row g-3">
                            <div class="col-md-4"><input name="bank_name" class="form-control" placeholder="BCA / Mandiri / DANA" aria-label="Bank" required /></div>
                            <div class="col-md-8"><input name="account_number" class="form-control font-monospace" placeholder="Nomor rekening" aria-label="Nomor rekening" required /></div>
                            <div class="col-12"><input name="account_holder" class="form-control" placeholder="Atas nama" aria-label="Atas nama" required /></div>
                        </div>
                        <input type="hidden" name="is_active" value="1" />
                        <button class="btn btn-primary mt-4">Tambah</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
