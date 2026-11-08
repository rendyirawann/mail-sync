@extends('team.layout.app')

@section('title', $letter->subject)

@section('content')
    <div class="d-flex flex-column flex-md-row align-items-md-start justify-content-between gap-3 mb-6">
        <div class="min-w-0">
            <a href="{{ route('team.incoming.index') }}" class="text-muted fs-7"><i class="ki-outline ki-arrow-left"></i> Surat Masuk</a>
            <h1 class="fs-2 fw-bold text-gray-900 mb-2 mt-2">{{ $letter->subject }}</h1>
            <span class="badge badge-light-{{ $letter->status->badge() }} me-1">{{ $letter->status->label() }}</span>
            <span class="badge badge-light-{{ $letter->nature->badge() }} me-1">{{ $letter->nature->label() }}</span>
            <span class="text-muted fs-7">Agenda <span class="font-monospace">{{ $letter->agendaLabel() }}</span></span>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if ($letter->attachment_url)
                <a href="{{ $letter->attachment_url }}" target="_blank" rel="noopener noreferrer" class="btn btn-light-primary"><i class="ki-outline ki-document fs-3"></i> Buka berkas</a>
            @endif
            @can('team.letters.manage')
                <a href="{{ route('team.incoming.edit', $letter) }}" class="btn btn-light">Ubah</a>
            @endcan
            @can('team.letters.delete')
                <form method="POST" action="{{ route('team.incoming.destroy', $letter) }}">
                    @csrf @method('DELETE')
                    <button class="btn btn-light-danger" data-confirm="Surat dan seluruh disposisinya akan dihapus. Lanjutkan?" data-confirm-title="Hapus surat?">Hapus</button>
                </form>
            @endcan
        </div>
    </div>

    {{-- Alur status: langkah yang sudah dilewati diberi tanda. --}}
    @php
        $flow = \App\Enums\IncomingStatus::cases();
        $currentIndex = array_search($letter->status, $flow, true);
    @endphp
    <div class="card mb-6">
        <div class="card-body py-5">
            <div class="d-flex flex-wrap align-items-center gap-2">
                @foreach ($flow as $i => $step)
                    <span class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded {{ $i < $currentIndex ? 'bg-light-success text-success' : ($i === $currentIndex ? 'bg-primary text-white' : 'bg-light text-muted') }}">
                        <i class="ki-outline {{ $i < $currentIndex ? 'ki-check' : 'ki-arrow-right' }} fs-5 {{ $i === $currentIndex ? 'text-white' : '' }}"></i> {{ $step->label() }}
                    </span>
                    @if (! $loop->last)<span class="text-gray-400">—</span>@endif
                @endforeach

                @can('team.letters.manage')
                    <form method="POST" action="{{ route('team.incoming.status', $letter) }}" class="ms-md-auto d-flex gap-2 mt-3 mt-md-0">
                        @csrf @method('PATCH')
                        <select name="status" class="form-select form-select-sm form-select-solid w-175px" aria-label="Ubah status">
                            @foreach ($flow as $step)
                                <option value="{{ $step->value }}" @selected($letter->status === $step)>{{ $step->label() }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-sm btn-light-primary">Ubah status</button>
                    </form>
                @endcan
            </div>
        </div>
    </div>

    <div class="row g-6">
        <div class="col-lg-5">
            <div class="card mb-6">
                <div class="card-header"><h3 class="card-title">Detail surat</h3></div>
                <div class="card-body">
                    <table class="table table-borderless mb-0 fs-6">
                        <tr><td class="text-muted w-125px">Pengirim</td><td class="fw-bold">{{ $letter->sender }}</td></tr>
                        <tr><td class="text-muted">Nomor surat</td><td>{{ $letter->letter_number }}</td></tr>
                        <tr><td class="text-muted">Tanggal surat</td><td>{{ $letter->letter_date->locale('id')->translatedFormat('j F Y') }}</td></tr>
                        <tr><td class="text-muted">Diterima</td><td>{{ $letter->received_date->locale('id')->translatedFormat('j F Y') }}</td></tr>
                        <tr><td class="text-muted">Jenis</td><td>{{ $letter->category?->name ?? '-' }}</td></tr>
                        <tr><td class="text-muted">Klasifikasi</td><td>{{ $letter->classification ? $letter->classification->code.' — '.$letter->classification->name : '-' }}</td></tr>
                        <tr><td class="text-muted">Dicatat oleh</td><td>{{ $letter->creator?->name ?? '-' }}</td></tr>
                    </table>
                    @if ($letter->summary)
                        <div class="separator my-4"></div>
                        <div class="text-muted fs-7 mb-1">Ringkasan</div>
                        <div class="text-gray-800" style="white-space: pre-line;">{{ $letter->summary }}</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            @can('team.dispositions.create')
                <div class="card mb-6">
                    <div class="card-header"><h3 class="card-title">Buat disposisi</h3></div>
                    <div class="card-body">
                        @if ($employees->isEmpty())
                            <div class="text-center py-6">
                                <p class="text-muted">Belum ada data pegawai sebagai tujuan disposisi.</p>
                                <a href="{{ route('team.employees.index') }}" class="btn btn-sm btn-primary">Tambah pegawai</a>
                            </div>
                        @else
                            <form method="POST" action="{{ route('team.dispositions.store', $letter) }}">
                                @csrf
                                <label class="form-label required" for="to_employee_ids">Kepada (bisa lebih dari satu)</label>
                                <select id="to_employee_ids" name="to_employee_ids[]" multiple size="5" class="form-select form-select-solid mb-1 @error('to_employee_ids') is-invalid @enderror" required>
                                    @foreach ($employees->groupBy(fn ($e) => $e->division?->name ?? 'Tanpa divisi') as $division => $group)
                                        <optgroup label="{{ $division }}">
                                            @foreach ($group as $e)
                                                <option value="{{ $e->id }}" @selected(in_array($e->id, old('to_employee_ids', []), true))>{{ $e->name }}{{ $e->position ? ' — '.$e->position : '' }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                                <div class="form-text mb-4">Tahan Ctrl (Windows) / Cmd (Mac) untuk memilih beberapa pegawai.</div>
                                <x-field-error name="to_employee_ids" />

                                <label class="form-label required">Instruksi</label>
                                <div class="d-flex flex-wrap gap-2 mb-2">
                                    @foreach (['Untuk diketahui', 'Untuk ditindaklanjuti', 'Untuk dihadiri', 'Untuk dipelajari & dilaporkan', 'Untuk disiapkan bahan', 'Untuk diarsipkan'] as $preset)
                                        <button type="button" class="btn btn-sm btn-light" data-instruction="{{ $preset }}">{{ $preset }}</button>
                                    @endforeach
                                </div>
                                <textarea name="instruction" id="instruction" rows="3" class="form-control form-control-solid mb-1 @error('instruction') is-invalid @enderror" maxlength="2000" required placeholder="Tulis atau pilih instruksi di atas">{{ old('instruction') }}</textarea>
                                <x-field-error name="instruction" />

                                <label class="form-label mt-4" for="due_date">Tenggat (opsional)</label>
                                <input type="date" id="due_date" name="due_date" class="form-control form-control-solid w-200px" value="{{ old('due_date') }}" min="{{ now()->format('Y-m-d') }}" />
                                <x-field-error name="due_date" />

                                <button class="btn btn-primary mt-5"><i class="ki-outline ki-send fs-3"></i> Kirim Disposisi</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endcan

            <div class="card">
                <div class="card-header"><h3 class="card-title">Riwayat disposisi</h3></div>
                <div class="card-body">
                    @forelse ($letter->dispositions as $d)
                        <div class="d-flex gap-4 pb-5 {{ $loop->last ? '' : 'border-start border-2 border-gray-200 ms-3 ps-5 position-relative' }}" style="{{ $loop->last ? 'margin-left: .75rem; padding-left: 1.25rem;' : '' }}">
                            <div class="flex-grow-1">
                                <div class="d-flex flex-wrap justify-content-between gap-2">
                                    <div class="fw-bold">{{ $d->sender?->name ?? 'Sistem' }} → {{ $d->recipient?->name }}
                                        <span class="text-muted fw-normal fs-7">{{ $d->recipient?->division?->name }}</span>
                                    </div>
                                    <span class="badge badge-light-{{ $d->isOverdue() ? 'danger' : $d->status->badge() }}">{{ $d->isOverdue() ? 'Lewat tenggat' : $d->status->label() }}</span>
                                </div>
                                <div class="text-gray-800 mt-1">{{ $d->instruction }}</div>
                                <div class="text-muted fs-7 mt-1">
                                    {{ $d->created_at->locale('id')->translatedFormat('j M Y H:i') }}
                                    @if ($d->due_date) · tenggat {{ $d->due_date->locale('id')->translatedFormat('j M Y') }} @endif
                                    · <a href="{{ route('team.dispositions.print', $d) }}" target="_blank" rel="noopener">Cetak lembar disposisi</a>
                                </div>
                                @if ($d->response)
                                    <div class="bg-light-success rounded p-3 mt-2 fs-7"><strong>Tindak lanjut:</strong> {{ $d->response }}</div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-muted mb-0">Belum ada disposisi untuk surat ini.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-instruction]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var box = document.getElementById('instruction');
                box.value = box.value ? box.value + '\n' + btn.dataset.instruction : btn.dataset.instruction;
                box.focus();
            });
        });
    </script>
@endpush
