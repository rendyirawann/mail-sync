@extends('team.layout.app')

@section('title', 'Template Surat')

@section('content')
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-6">
        <div>
            <h1 class="fs-2 fw-bold text-gray-900 mb-1">Template Surat</h1>
            <p class="text-muted fs-7 mb-0">Template bawaan siap pakai. Salin untuk menyesuaikan, atau buat template sendiri.</p>
        </div>
        @can('team.templates.manage')
            <a href="{{ route('team.templates.create') }}" class="btn btn-primary"><i class="ki-outline ki-plus fs-3"></i> Template Baru</a>
        @endcan
    </div>

    <div class="row g-5">
        @foreach ($templates as $template)
            <div class="col-md-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="fs-5 fw-bold text-gray-900">{{ $template->name }}</div>
                            <span class="badge badge-light-{{ $template->isBuiltIn() ? 'info' : 'success' }}">{{ $template->isBuiltIn() ? 'Bawaan' : 'Tim' }}</span>
                        </div>
                        <p class="text-muted flex-grow-1">{{ $template->description ?: count($template->fields).' isian' }}</p>
                        @unless ($template->is_active)<span class="badge badge-light-warning mb-3 align-self-start">Nonaktif</span>@endunless
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('team.templates.preview', $template) }}" class="btn btn-sm btn-light">Pratinjau</a>
                            <a href="{{ route('team.outgoing.create', ['template' => $template->id]) }}" class="btn btn-sm btn-light-primary">Pakai</a>
                            @can('team.templates.manage')
                                @if ($template->isBuiltIn())
                                    <form method="POST" action="{{ route('team.templates.duplicate', $template) }}">@csrf<button class="btn btn-sm btn-light-success">Salin & ubah</button></form>
                                @else
                                    <a href="{{ route('team.templates.edit', $template) }}" class="btn btn-sm btn-light-success">Ubah</a>
                                    <form method="POST" action="{{ route('team.templates.destroy', $template) }}">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-light-danger" data-confirm="Template akan dihapus. Surat yang sudah dibuat tetap aman." data-confirm-title="Hapus template?">Hapus</button>
                                    </form>
                                @endif
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
