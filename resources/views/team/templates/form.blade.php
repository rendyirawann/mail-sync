@extends('team.layout.app')

@php
    $editing = $template->exists;
    $fields = old('fields', $template->fields ?? []);
@endphp

@section('title', $editing ? 'Ubah Template' : 'Template Baru')

@section('content')
    <div class="mb-6">
        <a href="{{ route('team.templates.index') }}" class="text-muted fs-7"><i class="ki-outline ki-arrow-left"></i> Template Surat</a>
        <h1 class="fs-2 fw-bold text-gray-900 mb-1 mt-2">{{ $editing ? 'Ubah: '.$template->name : 'Template Baru' }}</h1>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger mb-6"><ul class="mb-0 ps-4">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <form method="POST" action="{{ $editing ? route('team.templates.update', $template) : route('team.templates.store') }}">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="row g-6">
            <div class="col-xl-7">
                <div class="card mb-6">
                    <div class="card-body row g-4">
                        <div class="col-md-8">
                            <label class="form-label required" for="name">Nama template</label>
                            <input id="name" name="name" class="form-control form-control-solid" value="{{ old('name', $template->name) }}" required maxlength="100" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required" for="paper">Ukuran kertas</label>
                            <select id="paper" name="paper" class="form-select form-select-solid">
                                @foreach (['A4' => 'A4 (21 × 29,7 cm)', 'F4' => 'F4 / Folio (21,5 × 33 cm)'] as $v => $l)
                                    <option value="{{ $v }}" @selected(old('paper', $template->paper) === $v)>{{ $l }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="description">Deskripsi singkat</label>
                            <input id="description" name="description" class="form-control form-control-solid" value="{{ old('description', $template->description) }}" maxlength="255" />
                        </div>
                        <div class="col-12">
                            <label class="form-label required" for="body">Isi template (HTML sederhana)</label>
                            <textarea id="body" name="body" rows="18" class="form-control form-control-solid font-monospace fs-7" required>{{ old('body', $template->body) }}</textarea>
                            <div class="form-text">Tulis penanda seperti <code>&#123;&#123;tempat&#125;&#125;</code> di posisi yang akan diisi. Kop surat ditambahkan otomatis. Tag berbahaya (script, link, gambar luar) dibuang saat disimpan.</div>
                        </div>
                        @if ($editing)
                            <div class="col-12">
                                <label class="form-check form-switch form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked($template->is_active) />
                                    <span class="form-check-label">Aktif (muncul saat membuat surat)</span>
                                </label>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-xl-5">
                <div class="card mb-6">
                    <div class="card-header"><h3 class="card-title fs-5">Isian yang diminta</h3></div>
                    <div class="card-body">
                        <p class="text-muted fs-7">Setiap isian menjadi kolom di form surat. <strong>Kunci</strong> = nama penanda di template.</p>
                        <div id="fields">
                            @foreach ($fields as $i => $f)
                                @include('team.templates._field', ['i' => $i, 'f' => $f])
                            @endforeach
                        </div>
                        <button type="button" class="btn btn-sm btn-light-primary" id="add-field"><i class="ki-outline ki-plus fs-4"></i> Tambah isian</button>
                        <template id="field-template">@include('team.templates._field', ['i' => '__I__', 'f' => []])</template>
                    </div>
                </div>

                <div class="card mb-6">
                    <div class="card-header"><h3 class="card-title fs-5">Penanda sistem (otomatis)</h3></div>
                    <div class="card-body">
                        <div class="d-flex flex-wrap gap-2">
                            @foreach (\App\Services\Letters\LetterRenderer::SYSTEM_FIELDS + ['qr' => 'QR verifikasi'] as $key => $label)
                                <button type="button" class="btn btn-sm btn-light font-monospace" data-insert="{{ '{'.'{'.$key.'}'.'}' }}" title="{{ $label }}">&#123;&#123;{{ $key }}&#125;&#125;</button>
                            @endforeach
                        </div>
                        <div class="form-text">Klik untuk menyisipkan ke isi template.</div>
                    </div>
                </div>

                <button class="btn btn-primary w-100">Simpan Template</button>
                @if ($editing)
                    <a href="{{ route('team.templates.preview', $template) }}" class="btn btn-light w-100 mt-3">Lihat pratinjau</a>
                @endif
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        (function () {
            var list = document.getElementById('fields');
            var tpl = document.getElementById('field-template').innerHTML;
            var next = list.children.length + 1000;

            document.getElementById('add-field').addEventListener('click', function () {
                list.insertAdjacentHTML('beforeend', tpl.replace(/__I__/g, String(next++)));
            });
            list.addEventListener('click', function (e) {
                var btn = e.target.closest('[data-remove-field]');
                if (btn) { btn.closest('[data-field]').remove(); }
            });

            var body = document.getElementById('body');
            document.querySelectorAll('[data-insert]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var start = body.selectionStart || body.value.length;
                    body.value = body.value.slice(0, start) + btn.dataset.insert + body.value.slice(body.selectionEnd || start);
                    body.focus();
                });
            });
        })();
    </script>
@endpush
