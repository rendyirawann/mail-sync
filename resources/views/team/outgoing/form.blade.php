@extends('team.layout.app')

@php
    $editing = $letter->exists;
    $content = old('content', $letter->content ?? []);
@endphp

@section('title', $editing ? 'Ubah Surat Keluar' : 'Isi Surat Keluar')

@push('stylesheets')
    @include('letters._paper-style')
    <style>
        .lp-wrap { position: sticky; top: 90px; }
        .lp-viewport { background: #e5e7eb; border-radius: .75rem; padding: 16px; max-height: 80vh; overflow: auto; }
        .lp-scale { transform-origin: top left; }
        .lp-sheet { width: 210mm; min-height: 297mm; padding: 20mm 22mm 20mm 25mm; box-sizing: border-box; background: #fff; box-shadow: 0 4px 18px rgba(0, 0, 0, .12); }
        .lp-sheet--f4 { width: 215mm; min-height: 330mm; }
        .lp-sheet [data-pv] { background: rgba(29, 78, 216, .08); border-radius: 2px; transition: background .3s; }
        .lp-sheet [data-pv].lp-flash { background: rgba(250, 204, 21, .45); }
        .lp-sheet [data-pv]:empty::before { content: '..........'; }
        .lp-sheet .lp-multiline { white-space: pre-line; }
    </style>
@endpush

@section('content')
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-6">
        <div>
            <a href="{{ $editing ? route('team.outgoing.show', $letter) : route('team.outgoing.create') }}" class="text-muted fs-7"><i class="ki-outline ki-arrow-left"></i> {{ $editing ? 'Detail surat' : 'Pilih template lain' }}</a>
            <h1 class="fs-2 fw-bold text-gray-900 mb-1 mt-2">{{ $template?->name ?? 'Surat bebas' }}</h1>
            <p class="text-muted fs-7 mb-0">{{ $editing ? 'Ubah draf' : 'Langkah 2 dari 2' }} — isi di kiri, lihat hasilnya langsung di kanan.</p>
        </div>
    </div>

    @if ($letter->exists && $letter->status === \App\Enums\OutgoingStatus::Ditolak && $letter->approval_note)
        <div class="alert alert-warning mb-6"><strong>Catatan revisi dari pimpinan:</strong> {{ $letter->approval_note }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger mb-6"><strong>Periksa kembali isian:</strong>
            <ul class="mb-0 ps-4">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="row g-6">
        {{-- ================= FORM ================= --}}
        <div class="col-xl-5">
            <form method="POST" action="{{ $editing ? route('team.outgoing.update', $letter) : route('team.outgoing.store') }}" id="letter-form" novalidate>
                @csrf
                @if ($editing) @method('PUT') @endif
                <input type="hidden" name="template_id" value="{{ $template?->id }}" />

                <div class="card mb-6">
                    <div class="card-header"><h3 class="card-title fs-5">Data surat</h3></div>
                    <div class="card-body row g-4">
                        <div class="col-12">
                            <label class="form-label required" for="recipient">Kepada / tujuan</label>
                            <input id="recipient" name="recipient" data-pk="tujuan" class="form-control form-control-solid @error('recipient') is-invalid @enderror" value="{{ old('recipient', $letter->recipient) }}" required maxlength="150" />
                            <x-field-error name="recipient" />
                        </div>
                        <div class="col-12">
                            <label class="form-label required" for="subject">Perihal</label>
                            <input id="subject" name="subject" data-pk="perihal" class="form-control form-control-solid @error('subject') is-invalid @enderror" value="{{ old('subject', $letter->subject) }}" required maxlength="255" />
                            <x-field-error name="subject" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required" for="letter_date">Tanggal surat</label>
                            <input type="date" id="letter_date" name="letter_date" data-pk="tanggal_surat" data-format="date" class="form-control form-control-solid @error('letter_date') is-invalid @enderror" value="{{ old('letter_date', $letter->letter_date?->format('Y-m-d')) }}" required />
                            <x-field-error name="letter_date" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required" for="nature">Sifat</label>
                            <select id="nature" name="nature" data-pk="sifat" data-format="option-text" class="form-select form-select-solid">
                                @foreach (\App\Enums\LetterNature::cases() as $n)
                                    <option value="{{ $n->value }}" @selected(old('nature', $letter->nature?->value) === $n->value)>{{ $n->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="signer_employee_id">Penanda tangan</label>
                            <select id="signer_employee_id" name="signer_employee_id" data-signer class="form-select form-select-solid">
                                <option value="" data-name="" data-position="" data-nip="">— Pilih pegawai —</option>
                                @foreach ($employees as $e)
                                    <option value="{{ $e->id }}" data-name="{{ $e->name }}" data-position="{{ $e->position }}" data-nip="{{ $e->nip }}"
                                        @selected(old('signer_employee_id', $letter->signer_employee_id) === $e->id)>{{ $e->displayName() }}</option>
                                @endforeach
                            </select>
                            @if ($employees->isEmpty())
                                <div class="form-text">Tambahkan pegawai di <a href="{{ route('team.employees.index') }}">Pegawai & Divisi</a> agar bisa dipilih.</div>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="classification_id">Klasifikasi (kode nomor)</label>
                            <select id="classification_id" name="classification_id" class="form-select form-select-solid">
                                <option value="">—</option>
                                @foreach ($classifications as $c)
                                    <option value="{{ $c->id }}" @selected((string) old('classification_id', $letter->classification_id) === (string) $c->id)>{{ $c->code }} — {{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="category_id">Jenis surat</label>
                            <select id="category_id" name="category_id" class="form-select form-select-solid">
                                <option value="">—</option>
                                @foreach ($categories as $c)
                                    <option value="{{ $c->id }}" @selected((string) old('category_id', $letter->category_id) === (string) $c->id)>{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <div class="bg-light-info rounded p-3 fs-7">
                                <i class="ki-outline ki-information-5 text-info"></i>
                                Nomor surat terbit otomatis saat disetujui, perkiraan: <span class="font-monospace fw-bold">{{ $numberPreview }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-6">
                    <div class="card-header"><h3 class="card-title fs-5">Isi surat</h3></div>
                    <div class="card-body row g-4">
                        @if ($template)
                            @foreach ($template->fields as $field)
                                <div class="col-12">
                                    <label class="form-label {{ ($field['required'] ?? false) ? 'required' : '' }}" for="f-{{ $field['key'] }}">{{ $field['label'] }}</label>
                                    @if (($field['type'] ?? 'text') === 'textarea')
                                        <textarea id="f-{{ $field['key'] }}" name="content[{{ $field['key'] }}]" data-pk="{{ $field['key'] }}" rows="4" class="form-control form-control-solid @error('content.'.$field['key']) is-invalid @enderror">{{ $content[$field['key']] ?? '' }}</textarea>
                                    @else
                                        <input id="f-{{ $field['key'] }}" name="content[{{ $field['key'] }}]" data-pk="{{ $field['key'] }}" type="{{ ($field['type'] ?? 'text') === 'date' ? 'date' : 'text' }}" @if (($field['type'] ?? '') === 'date') data-format="date" @endif
                                            class="form-control form-control-solid @error('content.'.$field['key']) is-invalid @enderror" value="{{ $content[$field['key']] ?? '' }}" />
                                    @endif
                                    <x-field-error :name="'content.'.$field['key']" />
                                </div>
                            @endforeach
                        @else
                            <div class="col-12">
                                <label class="form-label required" for="f-isi">Isi surat</label>
                                <textarea id="f-isi" name="content[isi]" data-pk="isi" rows="12" class="form-control form-control-solid @error('content.isi') is-invalid @enderror">{{ $content['isi'] ?? '' }}</textarea>
                                <x-field-error name="content.isi" />
                            </div>
                        @endif
                        <div class="col-12">
                            <label class="form-label" for="attachment_url">Tautan lampiran (Google Drive, opsional)</label>
                            <input type="url" id="attachment_url" name="attachment_url" class="form-control form-control-solid @error('attachment_url') is-invalid @enderror" value="{{ old('attachment_url', $letter->attachment_url) }}" placeholder="https://drive.google.com/..." />
                            <x-field-error name="attachment_url" />
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-column gap-3">
                    @can('team.outgoing.approve')
                        <button type="submit" name="next" value="approve" class="btn btn-primary" data-confirm="Surat akan disetujui dan nomor surat terbit. Lanjutkan?" data-confirm-title="Simpan & setujui?">
                            <i class="ki-outline ki-check-circle fs-3"></i> Simpan & Setujui
                        </button>
                    @else
                        <button type="submit" name="next" value="submit" class="btn btn-primary"><i class="ki-outline ki-send fs-3"></i> Simpan & Ajukan Persetujuan</button>
                    @endcan
                    <button type="submit" name="next" value="draft" class="btn btn-light">Simpan sebagai draf</button>
                </div>
            </form>
        </div>

        {{-- ================= PRATINJAU ================= --}}
        <div class="col-xl-7">
            <div class="lp-wrap">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="fw-bold text-gray-800"><i class="ki-outline ki-eye fs-3"></i> Pratinjau langsung</span>
                    <span class="text-muted fs-8">Kertas {{ $paper['paper'] }} · bagian yang Anda isi disorot</span>
                </div>
                <div class="lp-viewport" id="lp-viewport">
                    <div class="lp-scale" id="lp-scale">
                        <div class="lp-sheet {{ $paper['paper'] === 'F4' ? 'lp-sheet--f4' : '' }}">
                            @include('letters._paper', $paper)
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var MONTHS = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            var EMPTY = '..........';
            var form = document.getElementById('letter-form');

            function dateId(value) {
                var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value || '');
                return m ? (parseInt(m[3], 10) + ' ' + MONTHS[parseInt(m[2], 10) - 1] + ' ' + m[1]) : '';
            }

            function valueOf(input) {
                if (input.dataset.format === 'date') { return dateId(input.value); }
                if (input.dataset.format === 'option-text') { return input.options[input.selectedIndex].text; }
                return input.value;
            }

            // Isi semua penanda {{kunci}} di pratinjau. textContent → aman dari XSS.
            function paint(key, value, flash) {
                document.querySelectorAll('.lp-sheet [data-pv="' + key + '"]').forEach(function (el) {
                    var text = (value || '').trim();
                    el.textContent = text === '' ? EMPTY : text;
                    el.classList.toggle('lp-multiline', text.indexOf('\n') !== -1);
                    if (flash) {
                        el.classList.add('lp-flash');
                        setTimeout(function () { el.classList.remove('lp-flash'); }, 400);
                    }
                });
            }

            form.querySelectorAll('[data-pk]').forEach(function (input) {
                ['input', 'change'].forEach(function (ev) {
                    input.addEventListener(ev, function () { paint(input.dataset.pk, valueOf(input), true); });
                });
            });

            var signer = form.querySelector('[data-signer]');
            if (signer) {
                signer.addEventListener('change', function () {
                    var opt = signer.options[signer.selectedIndex];
                    paint('penandatangan_nama', opt.dataset.name, true);
                    paint('penandatangan_jabatan', opt.dataset.position, true);
                    paint('penandatangan_nip', opt.dataset.nip, true);
                });
            }

            // Kertas digambar pada ukuran asli lalu diskalakan agar pas lebar panel.
            var viewport = document.getElementById('lp-viewport');
            var scale = document.getElementById('lp-scale');
            var sheet = scale.firstElementChild;
            function fit() {
                var available = viewport.clientWidth - 32;
                var ratio = Math.min(1, available / sheet.offsetWidth);
                scale.style.transform = 'scale(' + ratio + ')';
                scale.style.height = (sheet.offsetHeight * ratio) + 'px';
            }
            window.addEventListener('resize', fit);
            fit();

            // Peringatan bila meninggalkan halaman dengan perubahan belum disimpan.
            var dirty = false;
            form.addEventListener('input', function () { dirty = true; });
            form.addEventListener('submit', function () { dirty = false; });
            window.addEventListener('beforeunload', function (e) { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
        })();
    </script>
@endpush
