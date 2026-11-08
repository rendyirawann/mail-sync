@extends('team.layout.app')

@section('title', 'Surat Masuk')

@section('content')
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-6">
        <div>
            <h1 class="fs-2 fw-bold text-gray-900 mb-1">Surat Masuk</h1>
            <p class="text-muted fs-7 mb-0">Catat, disposisikan, dan pantau tindak lanjut setiap surat.</p>
        </div>
        @can('team.letters.manage')
            <a href="{{ route('team.incoming.create') }}" class="btn btn-primary"><i class="ki-outline ki-plus fs-3"></i> Catat Surat Masuk</a>
        @endcan
    </div>

    {{-- Tab status — satu klik untuk melihat yang belum ditindaklanjuti. --}}
    <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-6 fw-semibold mb-5 flex-nowrap overflow-auto">
        <li class="nav-item"><a class="nav-link text-nowrap {{ empty($filters['status']) ? 'active' : '' }}" href="{{ route('team.incoming.index', \Illuminate\Support\Arr::except($filters, 'status')) }}">Semua</a></li>
        @foreach (\App\Enums\IncomingStatus::cases() as $case)
            <li class="nav-item">
                <a class="nav-link text-nowrap {{ ($filters['status'] ?? '') === $case->value ? 'active' : '' }}" href="{{ route('team.incoming.index', array_merge($filters, ['status' => $case->value])) }}">
                    {{ $case->label() }} <span class="badge badge-light-{{ $case->badge() }} ms-1">{{ $counts[$case->value] ?? 0 }}</span>
                </a>
            </li>
        @endforeach
    </ul>

    <div class="card">
        <div class="card-header border-0 pt-6">
            <form method="GET" class="d-flex flex-wrap gap-2 w-100" role="search">
                @if (! empty($filters['status']))<input type="hidden" name="status" value="{{ $filters['status'] }}" />@endif
                <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control form-control-solid flex-grow-1 mw-350px" placeholder="Cari perihal, pengirim, nomor surat..." aria-label="Cari surat" />
                <select name="nature" class="form-select form-select-solid w-150px" aria-label="Sifat">
                    <option value="">Semua sifat</option>
                    @foreach (\App\Enums\LetterNature::cases() as $n)
                        <option value="{{ $n->value }}" @selected(($filters['nature'] ?? '') === $n->value)>{{ $n->label() }}</option>
                    @endforeach
                </select>
                <select name="category_id" class="form-select form-select-solid w-175px" aria-label="Jenis surat">
                    <option value="">Semua jenis</option>
                    @foreach ($categories as $c)
                        <option value="{{ $c->id }}" @selected((string) ($filters['category_id'] ?? '') === (string) $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
                <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control form-control-solid w-150px" aria-label="Diterima dari" />
                <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control form-control-solid w-150px" aria-label="Diterima sampai" />
                <button class="btn btn-light-primary">Terapkan</button>
                @if (array_filter($filters))
                    <a href="{{ route('team.incoming.index') }}" class="btn btn-link">Reset</a>
                @endif
            </form>
        </div>
        <div class="card-body pt-2">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-4">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase">
                            <th>Agenda</th>
                            <th>Perihal / Pengirim</th>
                            <th>Diterima</th>
                            <th>Sifat</th>
                            <th>Status</th>
                            <th class="text-end"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($letters as $letter)
                            <tr>
                                <td class="font-monospace text-gray-700">{{ $letter->agendaLabel() }}</td>
                                <td>
                                    <a href="{{ route('team.incoming.show', $letter) }}" class="fw-bold text-gray-900 text-hover-primary">{{ $letter->subject }}</a>
                                    <div class="text-muted fs-7">{{ $letter->sender }} · No. {{ $letter->letter_number }}
                                        @if ($letter->category) · {{ $letter->category->name }} @endif
                                    </div>
                                </td>
                                <td class="text-nowrap">{{ $letter->received_date->locale('id')->translatedFormat('j M Y') }}</td>
                                <td><span class="badge badge-light-{{ $letter->nature->badge() }}">{{ $letter->nature->label() }}</span></td>
                                <td>
                                    <span class="badge badge-light-{{ $letter->status->badge() }}">{{ $letter->status->label() }}</span>
                                    @if ($letter->dispositions_count > 0)
                                        <div class="text-muted fs-8 mt-1">{{ $letter->dispositions_count }} disposisi</div>
                                    @endif
                                </td>
                                <td class="text-end"><a href="{{ route('team.incoming.show', $letter) }}" class="btn btn-sm btn-light">Buka</a></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-15">
                                    <i class="ki-outline ki-sms fs-3x text-gray-400 d-block mb-3"></i>
                                    <div class="fw-semibold text-gray-700">Belum ada surat masuk{{ array_filter($filters) ? ' yang cocok dengan filter' : '' }}.</div>
                                    @can('team.letters.manage')
                                        @unless (array_filter($filters))
                                            <a href="{{ route('team.incoming.create') }}" class="btn btn-sm btn-primary mt-4">Catat surat pertama</a>
                                        @endunless
                                    @endcan
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $letters->links() }}
        </div>
    </div>
@endsection
