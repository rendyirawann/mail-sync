{{-- Kepala halaman modul opsional: judul, filter bulan, ringkasan total. --}}
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-6">
    <div>
        <h1 class="fs-2 fw-bold text-gray-900 mb-1">{{ $title }}</h1>
        <p class="text-muted fs-7 mb-0">{{ $subtitle }}</p>
    </div>
    <form method="GET" class="d-flex gap-2 align-items-center">
        <label for="bulan" class="text-muted fs-7 text-nowrap">Bulan</label>
        <input type="month" id="bulan" name="bulan" value="{{ $month }}" class="form-control form-control-solid w-175px" onchange="this.form.submit()" />
    </form>
</div>

<div class="card mb-6">
    <div class="card-body d-flex flex-wrap gap-10">
        <div><div class="text-muted fs-7">Jumlah data</div><div class="fs-2 fw-bolder">{{ $count }}</div></div>
        <div><div class="text-muted fs-7">{{ $totalLabel }}</div><div class="fs-2 fw-bolder text-primary">{{ $total }}</div></div>
    </div>
</div>

@if ($errors->any())
    <div class="alert alert-danger mb-6"><ul class="mb-0 ps-4">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif
