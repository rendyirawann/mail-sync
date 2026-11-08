@extends('team.layout.app')

@php $editing = $letter->exists; @endphp

@section('title', $editing ? 'Ubah Surat Masuk' : 'Catat Surat Masuk')

@section('content')
    <div class="mb-6">
        <a href="{{ $editing ? route('team.incoming.show', $letter) : route('team.incoming.index') }}" class="text-muted fs-7"><i class="ki-outline ki-arrow-left"></i> Kembali</a>
        <h1 class="fs-2 fw-bold text-gray-900 mb-1 mt-2">{{ $editing ? 'Ubah Surat Masuk '.$letter->agendaLabel() : 'Catat Surat Masuk' }}</h1>
        <p class="text-muted fs-7 mb-0">Kolom bertanda <span class="text-danger">*</span> wajib diisi. Nomor agenda dibuat otomatis.</p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger mb-6"><strong>Periksa kembali isian:</strong>
            <ul class="mb-0 ps-4">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ $editing ? route('team.incoming.update', $letter) : route('team.incoming.store') }}" novalidate>
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="row g-6">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body row g-5">
                        <div class="col-md-6">
                            <label class="form-label required" for="sender">Pengirim / instansi</label>
                            <input id="sender" name="sender" list="sender-suggestions" autocomplete="off"
                                class="form-control form-control-solid @error('sender') is-invalid @enderror" value="{{ old('sender', $letter->sender) }}" required maxlength="150" autofocus />
                            <datalist id="sender-suggestions"></datalist>
                            <x-field-error name="sender" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required" for="letter_number">Nomor surat</label>
                            <input id="letter_number" name="letter_number" class="form-control form-control-solid @error('letter_number') is-invalid @enderror" value="{{ old('letter_number', $letter->letter_number) }}" required maxlength="100" placeholder="Sesuai yang tertulis di surat" />
                            <x-field-error name="letter_number" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required" for="letter_date">Tanggal surat</label>
                            <input type="date" id="letter_date" name="letter_date" class="form-control form-control-solid @error('letter_date') is-invalid @enderror" value="{{ old('letter_date', $letter->letter_date?->format('Y-m-d')) }}" max="{{ now()->format('Y-m-d') }}" required />
                            <x-field-error name="letter_date" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required" for="received_date">Tanggal diterima</label>
                            <input type="date" id="received_date" name="received_date" class="form-control form-control-solid @error('received_date') is-invalid @enderror" value="{{ old('received_date', $letter->received_date?->format('Y-m-d')) }}" max="{{ now()->format('Y-m-d') }}" required />
                            <x-field-error name="received_date" />
                        </div>
                        <div class="col-12">
                            <label class="form-label required" for="subject">Perihal</label>
                            <input id="subject" name="subject" class="form-control form-control-solid @error('subject') is-invalid @enderror" value="{{ old('subject', $letter->subject) }}" required maxlength="255" />
                            <x-field-error name="subject" />
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="summary">Ringkasan isi</label>
                            <textarea id="summary" name="summary" rows="4" class="form-control form-control-solid @error('summary') is-invalid @enderror" maxlength="5000" placeholder="Inti isi surat agar mudah dicari & didisposisikan">{{ old('summary', $letter->summary) }}</textarea>
                            <x-field-error name="summary" />
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="attachment_url">Tautan berkas surat (Google Drive)</label>
                            <input type="url" id="attachment_url" name="attachment_url" class="form-control form-control-solid @error('attachment_url') is-invalid @enderror" value="{{ old('attachment_url', $letter->attachment_url) }}" placeholder="https://drive.google.com/..." maxlength="500" />
                            <div class="form-text">Unggah scan surat ke Google Drive, atur akses "Siapa saja yang memiliki link", lalu tempel tautannya di sini.</div>
                            <x-field-error name="attachment_url" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card mb-6">
                    <div class="card-body">
                        <label class="form-label required">Sifat surat</label>
                        <div class="d-flex flex-wrap gap-2 mb-5">
                            @foreach (\App\Enums\LetterNature::cases() as $n)
                                <input type="radio" class="btn-check" name="nature" id="nature-{{ $n->value }}" value="{{ $n->value }}" @checked(old('nature', $letter->nature?->value) === $n->value) />
                                <label class="btn btn-sm btn-outline btn-outline-{{ $n->badge() }} btn-active-light-{{ $n->badge() }}" for="nature-{{ $n->value }}">{{ $n->label() }}</label>
                            @endforeach
                        </div>
                        <x-field-error name="nature" />

                        <label class="form-label" for="category_id">Jenis surat</label>
                        <select id="category_id" name="category_id" class="form-select form-select-solid mb-5">
                            <option value="">— Pilih —</option>
                            @foreach ($categories as $c)
                                <option value="{{ $c->id }}" @selected((string) old('category_id', $letter->category_id) === (string) $c->id)>{{ $c->name }}</option>
                            @endforeach
                        </select>

                        <label class="form-label" for="classification_id">Klasifikasi</label>
                        <select id="classification_id" name="classification_id" class="form-select form-select-solid">
                            <option value="">— Pilih —</option>
                            @foreach ($classifications as $c)
                                <option value="{{ $c->id }}" @selected((string) old('classification_id', $letter->classification_id) === (string) $c->id)>{{ $c->code }} — {{ $c->name }}</option>
                            @endforeach
                        </select>
                        @if ($categories->isEmpty() || $classifications->isEmpty())
                            <div class="form-text">Daftar masih kosong? Atur di <a href="{{ route('team.master.index') }}">Klasifikasi & Kategori</a>.</div>
                        @endif
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 mb-3">{{ $editing ? 'Simpan Perubahan' : 'Simpan & Lanjut Disposisi' }}</button>
                @unless ($editing)
                    <button type="submit" name="add_another" value="1" class="btn btn-light-primary w-100">Simpan & Catat Lagi</button>
                @endunless
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        (function () {
            var input = document.getElementById('sender');
            var list = document.getElementById('sender-suggestions');
            var timer;
            input.addEventListener('input', function () {
                clearTimeout(timer);
                timer = setTimeout(function () {
                    fetch(@json(route('team.incoming.data')) + '?q=' + encodeURIComponent(input.value), { headers: { Accept: 'application/json' } })
                        .then(function (r) { return r.ok ? r.json() : []; })
                        .then(function (names) {
                            list.innerHTML = '';
                            names.forEach(function (name) {
                                var opt = document.createElement('option');
                                opt.value = name;
                                list.appendChild(opt);
                            });
                        });
                }, 250);
            });
        })();
    </script>
@endpush
