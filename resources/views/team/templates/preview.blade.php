@extends('team.layout.app')

@section('title', 'Pratinjau '.$template->name)

@push('stylesheets')
    @include('letters._paper-style')
    <style>
        .lp-viewport { background: #e5e7eb; border-radius: .75rem; padding: 16px; overflow: auto; }
        .lp-scale { transform-origin: top left; }
        .lp-sheet { width: 210mm; min-height: 297mm; padding: 20mm 22mm 20mm 25mm; box-sizing: border-box; background: #fff; }
        .lp-sheet--f4 { width: 215mm; min-height: 330mm; }
    </style>
@endpush

@section('content')
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-6">
        <div>
            <a href="{{ route('team.templates.index') }}" class="text-muted fs-7"><i class="ki-outline ki-arrow-left"></i> Template Surat</a>
            <h1 class="fs-2 fw-bold text-gray-900 mb-1 mt-2">{{ $template->name }}</h1>
            <p class="text-muted fs-7 mb-0">Contoh tampilan dengan isian berupa label — kop memakai data tim Anda.</p>
        </div>
        <a href="{{ route('team.outgoing.create', ['template' => $template->id]) }}" class="btn btn-primary">Pakai template ini</a>
    </div>

    <div class="lp-viewport" id="lp-viewport">
        <div class="lp-scale" id="lp-scale">
            <div class="lp-sheet {{ $paper['paper'] === 'F4' ? 'lp-sheet--f4' : '' }}">
                @include('letters._paper', $paper)
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var viewport = document.getElementById('lp-viewport');
            var scale = document.getElementById('lp-scale');
            var sheet = scale.firstElementChild;
            function fit() {
                var ratio = Math.min(1, (viewport.clientWidth - 32) / sheet.offsetWidth);
                scale.style.transform = 'scale(' + ratio + ')';
                scale.style.height = (sheet.offsetHeight * ratio) + 'px';
            }
            window.addEventListener('resize', fit);
            fit();
        })();
    </script>
@endpush
