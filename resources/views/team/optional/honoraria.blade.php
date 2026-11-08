@extends('team.layout.app')

@section('title', 'Monitoring Honorarium')

@section('content')
    @include('team.optional._header', [
        'title' => 'Monitoring Honorarium', 'subtitle' => 'Honor kegiatan per bulan — nilai bersih dihitung otomatis setelah pajak.',
        'count' => $items->count(), 'totalLabel' => 'Total bersih', 'total' => 'Rp'.number_format($totalNet, 0, ',', '.'),
    ])
    @php $canManage = auth()->user()->can('team.optional.manage'); @endphp

    <div class="row g-6">
        @if ($canManage)
            <div class="col-lg-4">
                <form method="POST" action="{{ route('team.honoraria.store') }}" class="card">
                    @csrf
                    <div class="card-header"><h3 class="card-title fs-5">Catat honorarium</h3></div>
                    <div class="card-body">
                        <label class="form-label required" for="h-emp">Pegawai</label>
                        <select id="h-emp" name="employee_id" class="form-select form-select-solid mb-4" required>
                            <option value="">— Pilih —</option>
                            @foreach ($employees as $e)<option value="{{ $e->id }}" @selected(old('employee_id') === $e->id)>{{ $e->name }}</option>@endforeach
                        </select>
                        <label class="form-label required" for="h-act">Kegiatan</label>
                        <input id="h-act" name="activity" class="form-control form-control-solid mb-4" value="{{ old('activity') }}" required maxlength="255" />
                        <label class="form-label required" for="h-period">Periode</label>
                        <input type="month" id="h-period" name="period" class="form-control form-control-solid mb-4" value="{{ old('period', $month) }}" required />
                        <div class="row g-3 mb-4">
                            <div class="col-7"><label class="form-label required" for="h-gross">Bruto (Rp)</label><input type="number" min="0" id="h-gross" name="gross_amount" class="form-control form-control-solid" value="{{ old('gross_amount') }}" required /></div>
                            <div class="col-5"><label class="form-label required" for="h-tax">Pajak (%)</label><input type="number" min="0" max="50" id="h-tax" name="tax_percent" class="form-control form-control-solid" value="{{ old('tax_percent', 5) }}" required /></div>
                        </div>
                        <div class="bg-light rounded p-3 mb-4 fs-7">Bersih: <strong id="h-net">Rp0</strong></div>
                        <label class="form-label" for="h-url">Tautan bukti (Google Drive)</label>
                        <input type="url" id="h-url" name="attachment_url" class="form-control form-control-solid mb-4" placeholder="https://..." />
                        <button class="btn btn-primary w-100">Simpan</button>
                    </div>
                </form>
            </div>
        @endif
        <div class="{{ $canManage ? 'col-lg-8' : 'col-12' }}">
            <div class="card"><div class="card-body pt-3"><div class="table-responsive">
                <table class="table align-middle table-row-dashed gy-3">
                    <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Pegawai / Kegiatan</th><th class="text-end">Bruto</th><th class="text-end">Pajak</th><th class="text-end">Bersih</th><th>Status</th>@if ($canManage)<th></th>@endif</tr></thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr>
                                <td><div class="fw-bold">{{ $item->employee?->name }}</div><div class="text-muted fs-7">{{ $item->activity }}
                                    @if ($item->attachment_url) · <a href="{{ $item->attachment_url }}" target="_blank" rel="noopener noreferrer">bukti</a>@endif</div></td>
                                <td class="text-end">Rp{{ number_format($item->gross_amount, 0, ',', '.') }}</td>
                                <td class="text-end">{{ $item->tax_percent }}%</td>
                                <td class="text-end fw-bold">Rp{{ number_format($item->net_amount, 0, ',', '.') }}</td>
                                <td><span class="badge badge-light-{{ $item->status === 'dibayar' ? 'success' : 'warning' }}">{{ $statuses[$item->status] ?? $item->status }}</span></td>
                                @if ($canManage)
                                    <td class="text-end text-nowrap">
                                        <form method="POST" action="{{ route('team.honoraria.update', $item) }}" class="d-inline-flex">@csrf @method('PATCH')
                                            <select name="status" class="form-select form-select-sm form-select-solid w-125px" onchange="this.form.submit()" aria-label="Status">
                                                @foreach ($statuses as $v => $l)<option value="{{ $v }}" @selected($item->status === $v)>{{ $l }}</option>@endforeach
                                            </select></form>
                                        <form method="POST" action="{{ route('team.honoraria.destroy', $item) }}" class="d-inline">@csrf @method('DELETE')
                                            <button class="btn btn-sm btn-icon btn-light-danger" aria-label="Hapus" data-confirm="Hapus data honorarium ini?" data-confirm-title="Hapus?"><i class="ki-outline ki-trash fs-5"></i></button></form>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-10">Belum ada honorarium di bulan ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div></div></div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var gross = document.getElementById('h-gross');
            var tax = document.getElementById('h-tax');
            var out = document.getElementById('h-net');
            if (!gross) { return; }
            function calc() {
                var net = Math.round((parseInt(gross.value, 10) || 0) * (100 - (parseInt(tax.value, 10) || 0)) / 100);
                out.textContent = 'Rp' + net.toLocaleString('id-ID');
            }
            gross.addEventListener('input', calc);
            tax.addEventListener('input', calc);
            calc();
        })();
    </script>
@endpush
