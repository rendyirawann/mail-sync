@extends('team.layout.app')

@section('title', 'Laporan')

@section('content')
    <div class="mb-6">
        <h1 class="fs-2 fw-bold text-gray-900 mb-1">Laporan</h1>
        <p class="text-muted fs-7 mb-0">Unduh buku agenda dan rekap disposisi per periode — PDF siap cetak atau Excel untuk diolah.</p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger mb-6"><ul class="mb-0 ps-4">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <form method="GET" action="{{ route('team.reports.download') }}" class="card mw-700px">
        <div class="card-body">
            <label class="form-label required">Jenis laporan</label>
            @foreach ($types as $key => $label)
                <label class="d-flex align-items-center gap-3 border rounded p-4 mb-3 cursor-pointer">
                    <input class="form-check-input" type="radio" name="type" value="{{ $key }}" @checked(old('type', 'masuk') === $key) required />
                    <span class="fw-semibold">{{ $label }}</span>
                </label>
            @endforeach

            <div class="row g-4 mt-1">
                <div class="col-md-6">
                    <label class="form-label required" for="from">Dari tanggal</label>
                    <input type="date" id="from" name="from" class="form-control form-control-solid" value="{{ old('from', now()->startOfMonth()->format('Y-m-d')) }}" required />
                </div>
                <div class="col-md-6">
                    <label class="form-label required" for="to">Sampai tanggal</label>
                    <input type="date" id="to" name="to" class="form-control form-control-solid" value="{{ old('to', now()->format('Y-m-d')) }}" required />
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2 mt-3">
                @foreach (['Bulan ini' => [now()->startOfMonth(), now()], 'Bulan lalu' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()], 'Tahun ini' => [now()->startOfYear(), now()]] as $label => [$a, $b])
                    <button type="button" class="btn btn-sm btn-light" data-range="{{ $a->format('Y-m-d') }}|{{ $b->format('Y-m-d') }}">{{ $label }}</button>
                @endforeach
            </div>

            <div class="d-flex gap-3 mt-6">
                <button name="format" value="pdf" class="btn btn-primary"><i class="ki-outline ki-file-down fs-3"></i> Unduh PDF</button>
                <button name="format" value="xlsx" class="btn btn-light-success"><i class="ki-outline ki-file-sheet fs-3"></i> Unduh Excel</button>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-range]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var parts = btn.dataset.range.split('|');
                document.getElementById('from').value = parts[0];
                document.getElementById('to').value = parts[1];
            });
        });
    </script>
@endpush
