@extends('team.layout.app')

@section('title', 'Monitoring Lembur')

@section('content')
    @include('team.optional._header', [
        'title' => 'Monitoring Lembur', 'subtitle' => 'Catat lembur pegawai — jam dihitung otomatis.',
        'count' => $items->count(), 'totalLabel' => 'Total jam (tidak termasuk ditolak)', 'total' => number_format($totalHours, 2, ',', '.').' jam',
    ])
    @php $canManage = auth()->user()->can('team.optional.manage'); @endphp

    <div class="row g-6">
        @if ($canManage)
            <div class="col-lg-4">
                <form method="POST" action="{{ route('team.overtimes.store') }}" class="card">
                    @csrf
                    <div class="card-header"><h3 class="card-title fs-5">Catat lembur</h3></div>
                    <div class="card-body">
                        <label class="form-label required" for="o-emp">Pegawai</label>
                        <select id="o-emp" name="employee_id" class="form-select form-select-solid mb-4" required>
                            <option value="">— Pilih —</option>
                            @foreach ($employees as $e)<option value="{{ $e->id }}" @selected(old('employee_id') === $e->id)>{{ $e->name }}</option>@endforeach
                        </select>
                        <label class="form-label required" for="o-date">Tanggal</label>
                        <input type="date" id="o-date" name="date" class="form-control form-control-solid mb-4" value="{{ old('date', now()->format('Y-m-d')) }}" required />
                        <div class="row g-3 mb-4">
                            <div class="col-6"><label class="form-label required" for="o-start">Mulai</label><input type="time" id="o-start" name="start_time" class="form-control form-control-solid" value="{{ old('start_time', '16:00') }}" required /></div>
                            <div class="col-6"><label class="form-label required" for="o-end">Selesai</label><input type="time" id="o-end" name="end_time" class="form-control form-control-solid" value="{{ old('end_time', '19:00') }}" required /></div>
                        </div>
                        <label class="form-label required" for="o-act">Kegiatan</label>
                        <input id="o-act" name="activity" class="form-control form-control-solid mb-4" value="{{ old('activity') }}" required maxlength="255" />
                        <label class="form-label" for="o-url">Tautan bukti (Google Drive)</label>
                        <input type="url" id="o-url" name="attachment_url" class="form-control form-control-solid mb-4" value="{{ old('attachment_url') }}" placeholder="https://..." />
                        <button class="btn btn-primary w-100">Simpan</button>
                    </div>
                </form>
            </div>
        @endif
        <div class="{{ $canManage ? 'col-lg-8' : 'col-12' }}">
            <div class="card"><div class="card-body pt-3"><div class="table-responsive">
                <table class="table align-middle table-row-dashed gy-3">
                    <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Tanggal</th><th>Pegawai / Kegiatan</th><th>Jam</th><th>Status</th>@if ($canManage)<th></th>@endif</tr></thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr>
                                <td class="text-nowrap">{{ $item->date->locale('id')->translatedFormat('j M') }}<div class="text-muted fs-8">{{ substr($item->start_time, 0, 5) }}–{{ substr($item->end_time, 0, 5) }}</div></td>
                                <td><div class="fw-bold">{{ $item->employee?->name }}</div><div class="text-muted fs-7">{{ $item->activity }}
                                    @if ($item->attachment_url) · <a href="{{ $item->attachment_url }}" target="_blank" rel="noopener noreferrer">bukti</a>@endif</div></td>
                                <td>{{ number_format((float) $item->hours, 2, ',', '.') }}</td>
                                <td><span class="badge badge-light-{{ ['diajukan' => 'warning', 'disetujui' => 'success', 'ditolak' => 'danger'][$item->status] ?? 'secondary' }}">{{ $statuses[$item->status] ?? $item->status }}</span></td>
                                @if ($canManage)
                                    <td class="text-end text-nowrap">
                                        <form method="POST" action="{{ route('team.overtimes.update', $item) }}" class="d-inline-flex gap-1">
                                            @csrf @method('PATCH')
                                            <select name="status" class="form-select form-select-sm form-select-solid w-125px" onchange="this.form.submit()" aria-label="Status">
                                                @foreach ($statuses as $v => $l)<option value="{{ $v }}" @selected($item->status === $v)>{{ $l }}</option>@endforeach
                                            </select>
                                        </form>
                                        <form method="POST" action="{{ route('team.overtimes.destroy', $item) }}" class="d-inline">@csrf @method('DELETE')
                                            <button class="btn btn-sm btn-icon btn-light-danger" aria-label="Hapus" data-confirm="Hapus data lembur ini?" data-confirm-title="Hapus?"><i class="ki-outline ki-trash fs-5"></i></button></form>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-10">Belum ada lembur di bulan ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div></div></div>
        </div>
    </div>
@endsection
