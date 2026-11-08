@extends('team.layout.app')

@section('title', 'Monitoring Perjalanan Dinas')

@section('content')
    @include('team.optional._header', [
        'title' => 'Monitoring Perjalanan Dinas', 'subtitle' => 'Jadwal perjalanan dinas pegawai, terhubung dengan surat tugas.',
        'count' => $items->count(), 'totalLabel' => 'Total biaya (tidak termasuk batal)', 'total' => 'Rp'.number_format($totalCost, 0, ',', '.'),
    ])
    @php $canManage = auth()->user()->can('team.optional.manage'); @endphp

    <div class="row g-6">
        @if ($canManage)
            <div class="col-lg-4">
                <form method="POST" action="{{ route('team.trips.store') }}" class="card">
                    @csrf
                    <div class="card-header"><h3 class="card-title fs-5">Catat perjalanan</h3></div>
                    <div class="card-body">
                        <label class="form-label required" for="t-emp">Pegawai</label>
                        <select id="t-emp" name="employee_id" class="form-select form-select-solid mb-4" required>
                            <option value="">— Pilih —</option>
                            @foreach ($employees as $e)<option value="{{ $e->id }}" @selected(old('employee_id') === $e->id)>{{ $e->name }}</option>@endforeach
                        </select>
                        <label class="form-label required" for="t-dest">Tujuan</label>
                        <input id="t-dest" name="destination" class="form-control form-control-solid mb-4" value="{{ old('destination') }}" required maxlength="150" />
                        <label class="form-label required" for="t-purpose">Keperluan</label>
                        <input id="t-purpose" name="purpose" class="form-control form-control-solid mb-4" value="{{ old('purpose') }}" required maxlength="255" />
                        <div class="row g-3 mb-4">
                            <div class="col-6"><label class="form-label required" for="t-start">Berangkat</label><input type="date" id="t-start" name="start_date" class="form-control form-control-solid" value="{{ old('start_date') }}" required /></div>
                            <div class="col-6"><label class="form-label required" for="t-end">Kembali</label><input type="date" id="t-end" name="end_date" class="form-control form-control-solid" value="{{ old('end_date') }}" required /></div>
                        </div>
                        <label class="form-label" for="t-cost">Biaya (Rp)</label>
                        <input type="number" min="0" id="t-cost" name="cost" class="form-control form-control-solid mb-4" value="{{ old('cost') }}" />
                        <label class="form-label" for="t-letter">Surat tugas (opsional)</label>
                        <select id="t-letter" name="outgoing_letter_id" class="form-select form-select-solid mb-4">
                            <option value="">—</option>
                            @foreach ($letters as $l)<option value="{{ $l->id }}">{{ $l->letter_number }} — {{ \Illuminate\Support\Str::limit($l->subject, 40) }}</option>@endforeach
                        </select>
                        <label class="form-label" for="t-url">Tautan dokumen (Google Drive)</label>
                        <input type="url" id="t-url" name="attachment_url" class="form-control form-control-solid mb-4" placeholder="https://..." />
                        <button class="btn btn-primary w-100">Simpan</button>
                    </div>
                </form>
            </div>
        @endif
        <div class="{{ $canManage ? 'col-lg-8' : 'col-12' }}">
            <div class="card"><div class="card-body pt-3"><div class="table-responsive">
                <table class="table align-middle table-row-dashed gy-3">
                    <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Tanggal</th><th>Pegawai / Tujuan</th><th class="text-end">Biaya</th><th>Status</th>@if ($canManage)<th></th>@endif</tr></thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr>
                                <td class="text-nowrap">{{ $item->start_date->locale('id')->translatedFormat('j M') }} – {{ $item->end_date->locale('id')->translatedFormat('j M') }}</td>
                                <td><div class="fw-bold">{{ $item->employee?->name }} → {{ $item->destination }}</div>
                                    <div class="text-muted fs-7">{{ $item->purpose }}
                                        @if ($item->assignmentLetter) · <a href="{{ route('team.outgoing.show', $item->assignmentLetter) }}">{{ $item->assignmentLetter->letter_number }}</a>@endif
                                        @if ($item->attachment_url) · <a href="{{ $item->attachment_url }}" target="_blank" rel="noopener noreferrer">dokumen</a>@endif</div></td>
                                <td class="text-end">Rp{{ number_format($item->cost, 0, ',', '.') }}</td>
                                <td><span class="badge badge-light-{{ ['direncanakan' => 'info', 'berlangsung' => 'warning', 'selesai' => 'success', 'batal' => 'secondary'][$item->status] ?? 'secondary' }}">{{ $statuses[$item->status] ?? $item->status }}</span></td>
                                @if ($canManage)
                                    <td class="text-end text-nowrap">
                                        <form method="POST" action="{{ route('team.trips.update', $item) }}" class="d-inline-flex">@csrf @method('PATCH')
                                            <select name="status" class="form-select form-select-sm form-select-solid w-125px" onchange="this.form.submit()" aria-label="Status">
                                                @foreach ($statuses as $v => $l)<option value="{{ $v }}" @selected($item->status === $v)>{{ $l }}</option>@endforeach
                                            </select></form>
                                        <form method="POST" action="{{ route('team.trips.destroy', $item) }}" class="d-inline">@csrf @method('DELETE')
                                            <button class="btn btn-sm btn-icon btn-light-danger" aria-label="Hapus" data-confirm="Hapus data perjalanan ini?" data-confirm-title="Hapus?"><i class="ki-outline ki-trash fs-5"></i></button></form>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-10">Tidak ada perjalanan dinas di bulan ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div></div></div>
        </div>
    </div>
@endsection
