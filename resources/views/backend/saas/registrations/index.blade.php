@extends('backend.layout.app')

@section('title', 'Pendaftaran Masuk')

@section('content')
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-6">
        <div>
            <h1 class="fs-2 fw-bold text-gray-900 mb-1">Pendaftaran Masuk</h1>
            <p class="text-muted fs-7 mb-0">Cocokkan bukti transfer dari WhatsApp, lalu setujui untuk mengaktifkan tim.</p>
        </div>
        <a href="{{ route('admin.saas.registrations.create') }}" class="btn btn-primary">
            <i class="ki-outline ki-plus fs-3"></i> Daftarkan Tim
        </a>
    </div>

    <div class="card">
        <div class="card-header border-0 pt-6 flex-wrap gap-3">
            <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-6 fw-semibold">
                @foreach (\App\Enums\RegistrationStatus::cases() as $case)
                    <li class="nav-item">
                        <a class="nav-link {{ $status === $case->value ? 'active' : '' }}"
                            href="{{ route('admin.saas.registrations.index', ['status' => $case->value]) }}">
                            {{ $case->label() }}
                            <span class="badge badge-light-{{ $case->badge() }} ms-2">{{ $counts[$case->value] ?? 0 }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
            <form method="GET" class="d-flex gap-2" role="search">
                <input type="hidden" name="status" value="{{ $status }}" />
                <input type="search" name="q" value="{{ $search }}" class="form-control form-control-solid w-250px"
                    placeholder="Cari kode, tim, owner, WA..." aria-label="Cari pendaftaran" />
                <button class="btn btn-light">Cari</button>
            </form>
        </div>

        <div class="card-body pt-2">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-4">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase">
                            <th>Kode</th>
                            <th>Tim / Owner</th>
                            <th>Paket</th>
                            <th class="text-end">Total</th>
                            <th>Masuk</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($registrations as $registration)
                            <tr>
                                <td><span class="fw-bold font-monospace">{{ $registration->code }}</span>
                                    @if ($registration->source === 'superadmin')
                                        <span class="badge badge-light-info ms-1">oleh admin</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-bold text-gray-900">{{ $registration->team_name }}</div>
                                    <div class="text-muted fs-7">{{ $registration->owner_name }} · {{ $registration->owner_whatsapp }}</div>
                                </td>
                                <td>{{ $registration->plan->name }}</td>
                                <td class="text-end fw-bold">{{ $registration->amountLabel() }}</td>
                                <td class="text-muted fs-7">{{ $registration->created_at->locale('id')->diffForHumans() }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.saas.registrations.show', $registration) }}" class="btn btn-sm btn-light-primary">
                                        {{ $registration->status === \App\Enums\RegistrationStatus::Pending ? 'Proses' : 'Lihat' }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-10">
                                    Belum ada pendaftaran dengan status ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $registrations->links() }}
        </div>
    </div>
@endsection
