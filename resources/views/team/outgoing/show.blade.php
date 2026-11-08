@extends('team.layout.app')

@section('title', $letter->subject)

@push('stylesheets')
    @include('letters._paper-style')
    <style>
        .lp-viewport { background: #e5e7eb; border-radius: .75rem; padding: 16px; overflow: auto; }
        .lp-scale { transform-origin: top left; }
        .lp-sheet { width: 210mm; min-height: 297mm; padding: 20mm 22mm 20mm 25mm; box-sizing: border-box; background: #fff; box-shadow: 0 4px 18px rgba(0, 0, 0, .12); }
        .lp-sheet--f4 { width: 215mm; min-height: 330mm; }
    </style>
@endpush

@section('content')
    @php $S = \App\Enums\OutgoingStatus::class; @endphp

    <div class="d-flex flex-column flex-md-row align-items-md-start justify-content-between gap-3 mb-6">
        <div class="min-w-0">
            <a href="{{ route('team.outgoing.index') }}" class="text-muted fs-7"><i class="ki-outline ki-arrow-left"></i> Surat Keluar</a>
            <h1 class="fs-2 fw-bold text-gray-900 mb-2 mt-2">{{ $letter->subject }}</h1>
            <span class="badge badge-light-{{ $letter->status->badge() }} me-1">{{ $letter->status->label() }}</span>
            <span class="font-monospace text-muted">{{ $letter->letter_number ?? 'Nomor belum terbit' }}</span>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('team.outgoing.pdf', $letter) }}" target="_blank" rel="noopener" class="btn btn-light-primary"><i class="ki-outline ki-file-down fs-3"></i> {{ $letter->letter_number ? 'Unduh PDF' : 'Pratinjau PDF' }}</a>
            @if ($letter->isEditable())
                @can('team.letters.manage')
                    <a href="{{ route('team.outgoing.edit', $letter) }}" class="btn btn-light">Ubah</a>
                @endcan
            @endif
            @can('team.letters.delete')
                <form method="POST" action="{{ route('team.outgoing.destroy', $letter) }}">
                    @csrf @method('DELETE')
                    <button class="btn btn-light-danger" data-confirm="Surat keluar ini akan dihapus. Lanjutkan?" data-confirm-title="Hapus surat?">Hapus</button>
                </form>
            @endcan
        </div>
    </div>

    <div class="row g-6">
        <div class="col-xl-4">
            {{-- Langkah berikutnya, sesuai status & peran — supaya pengguna tidak bingung. --}}
            <div class="card mb-6 border border-primary">
                <div class="card-header"><h3 class="card-title fs-5">Langkah berikutnya</h3></div>
                <div class="card-body">
                    @if (in_array($letter->status, [$S::Draf, $S::Ditolak], true))
                        @if ($letter->approval_note)
                            <div class="alert alert-warning py-3 fs-7"><strong>Catatan revisi:</strong> {{ $letter->approval_note }}</div>
                        @endif
                        @can('team.outgoing.approve')
                            <form method="POST" action="{{ route('team.outgoing.approve', $letter) }}">
                                @csrf
                                <label class="form-label fs-7" for="letter_number">Nomor surat (kosongkan = otomatis)</label>
                                <input id="letter_number" name="letter_number" class="form-control form-control-solid mb-3 font-monospace" maxlength="100" />
                                <button class="btn btn-primary w-100">Setujui & Terbitkan Nomor</button>
                            </form>
                        @else
                            <p class="text-muted fs-7">Periksa isi surat, lalu ajukan ke pimpinan untuk disetujui.</p>
                            <form method="POST" action="{{ route('team.outgoing.submit', $letter) }}">@csrf
                                <button class="btn btn-primary w-100"><i class="ki-outline ki-send fs-3"></i> Ajukan Persetujuan</button>
                            </form>
                        @endcan
                    @elseif ($letter->status === $S::Menunggu)
                        @can('team.outgoing.approve')
                            <form method="POST" action="{{ route('team.outgoing.approve', $letter) }}" class="mb-4">
                                @csrf
                                <label class="form-label fs-7" for="letter_number">Nomor surat (kosongkan = otomatis)</label>
                                <input id="letter_number" name="letter_number" class="form-control form-control-solid mb-3 font-monospace" maxlength="100" />
                                <button class="btn btn-success w-100"><i class="ki-outline ki-check-circle fs-3"></i> Setujui</button>
                            </form>
                            <form method="POST" action="{{ route('team.outgoing.reject', $letter) }}">
                                @csrf
                                <textarea name="note" rows="2" class="form-control form-control-solid mb-3" required maxlength="255" placeholder="Catatan revisi untuk pembuat surat" aria-label="Catatan revisi"></textarea>
                                <button class="btn btn-light-danger w-100">Kembalikan untuk revisi</button>
                            </form>
                        @else
                            <div class="d-flex align-items-center gap-3"><i class="ki-outline ki-time fs-2x text-warning"></i><span>Menunggu persetujuan pimpinan. Anda akan diberi notifikasi.</span></div>
                        @endcan
                    @elseif ($letter->status === $S::Disetujui)
                        <ol class="ps-4 mb-4 text-gray-700 fs-7">
                            <li>Unduh PDF dan cetak / tandatangani bila perlu.</li>
                            <li>Kirim ke penerima.</li>
                            <li>Tandai sebagai terkirim.</li>
                        </ol>
                        @can('team.letters.manage')
                            <form method="POST" action="{{ route('team.outgoing.sent', $letter) }}">@csrf
                                <button class="btn btn-success w-100"><i class="ki-outline ki-check fs-3"></i> Tandai Terkirim</button>
                            </form>
                        @endcan
                    @else
                        <div class="d-flex align-items-center gap-3"><i class="ki-outline ki-check-circle fs-2x text-success"></i><span>Surat sudah terkirim {{ $letter->sent_at?->locale('id')->translatedFormat('j M Y H:i') }}.</span></div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <table class="table table-borderless mb-0 fs-7">
                        <tr><td class="text-muted w-100px">Kepada</td><td class="fw-bold">{{ $letter->recipient }}</td></tr>
                        <tr><td class="text-muted">Tanggal</td><td>{{ $letter->letter_date->locale('id')->translatedFormat('j F Y') }}</td></tr>
                        <tr><td class="text-muted">Sifat</td><td>{{ $letter->nature->label() }}</td></tr>
                        <tr><td class="text-muted">Template</td><td>{{ $letter->template?->name ?? 'Surat bebas' }}</td></tr>
                        <tr><td class="text-muted">Dibuat</td><td>{{ $letter->creator?->name }}</td></tr>
                        @if ($letter->approver && $letter->approved_at)
                            <tr><td class="text-muted">Disetujui</td><td>{{ $letter->approver->name }}, {{ $letter->approved_at->locale('id')->translatedFormat('j M Y') }}</td></tr>
                        @endif
                        @if ($letter->verification_token)
                            <tr><td class="text-muted">Verifikasi</td><td><a href="{{ route('letters.verify', $letter->verification_token) }}" target="_blank" rel="noopener">Halaman verifikasi QR</a></td></tr>
                        @endif
                        @if ($letter->attachment_url)
                            <tr><td class="text-muted">Lampiran</td><td><a href="{{ $letter->attachment_url }}" target="_blank" rel="noopener noreferrer">Buka tautan</a></td></tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="lp-viewport" id="lp-viewport">
                <div class="lp-scale" id="lp-scale">
                    <div class="lp-sheet {{ $paper['paper'] === 'F4' ? 'lp-sheet--f4' : '' }}">
                        @include('letters._paper', $paper)
                    </div>
                </div>
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
