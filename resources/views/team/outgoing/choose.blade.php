@extends('team.layout.app')

@section('title', 'Buat Surat Keluar')

@section('content')
    <div class="mb-6">
        <a href="{{ route('team.outgoing.index') }}" class="text-muted fs-7"><i class="ki-outline ki-arrow-left"></i> Surat Keluar</a>
        <h1 class="fs-2 fw-bold text-gray-900 mb-1 mt-2">Buat Surat Keluar</h1>
        <p class="text-muted fs-7 mb-0">Langkah 1 dari 2 — pilih template. Kop surat, nomor, dan tanda tangan terisi otomatis.</p>
    </div>

    <div class="row g-5">
        @foreach ($templates as $template)
            <div class="col-md-6 col-xl-4">
                <a href="{{ route('team.outgoing.create', ['template' => $template->id]) }}" class="card h-100 hover-elevate-up border border-hover-primary">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <span class="symbol symbol-45px"><span class="symbol-label bg-light-primary"><i class="ki-outline ki-document fs-2x text-primary"></i></span></span>
                            <div>
                                <div class="fs-5 fw-bold text-gray-900">{{ $template->name }}</div>
                                <span class="badge badge-light-{{ $template->isBuiltIn() ? 'info' : 'success' }}">{{ $template->isBuiltIn() ? 'Bawaan' : 'Template tim' }}</span>
                                <span class="badge badge-light">{{ $template->paper }}</span>
                            </div>
                        </div>
                        <p class="text-muted mb-0">{{ $template->description }}</p>
                    </div>
                </a>
            </div>
        @endforeach

        <div class="col-md-6 col-xl-4">
            <a href="{{ route('team.outgoing.create', ['template' => 'kosong']) }}" class="card h-100 hover-elevate-up border border-dashed border-gray-300 border-hover-primary">
                <div class="card-body d-flex flex-column justify-content-center text-center">
                    <i class="ki-outline ki-pencil fs-2x text-gray-500 mb-3"></i>
                    <div class="fs-5 fw-bold text-gray-900">Tulis bebas</div>
                    <p class="text-muted mb-0">Tanpa template — cukup isi teks surat. Kop tetap otomatis.</p>
                </div>
            </a>
        </div>
    </div>

    @can('team.templates.manage')
        <p class="text-muted mt-6">Butuh format khusus instansi? <a href="{{ route('team.templates.create') }}">Buat template sendiri</a>.</p>
    @endcan
@endsection
