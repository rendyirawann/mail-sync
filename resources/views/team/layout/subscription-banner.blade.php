{{-- Pengingat langganan: H-7 & H-1 sebelum jatuh tempo, masa tenggang, dan status beku. --}}
@php
    $days = $team->daysLeft();
    $canBill = auth()->user()?->can('team.billing.manage');
@endphp

@if ($team->status === \App\Enums\TeamStatus::Frozen)
    <div class="alert alert-danger d-flex flex-column flex-md-row align-items-md-center gap-3 mb-6" role="alert">
        <i class="ki-duotone ki-lock-2 fs-2hx text-danger"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
        <div class="flex-grow-1">
            <div class="fw-bold">Langganan tim sudah berakhir — data hanya bisa dilihat.</div>
            <div class="fs-7">Semua data tetap aman. Perpanjang untuk kembali menambah dan mengubah surat.</div>
        </div>
        @if ($canBill)
            <a href="{{ route('team.billing.index') }}" class="btn btn-danger">Perpanjang sekarang</a>
        @else
            <span class="fs-7">Hubungi owner tim untuk memperpanjang.</span>
        @endif
    </div>
@elseif ($days !== null && $days <= 7)
    <div class="alert alert-{{ $days <= 1 ? 'danger' : 'warning' }} d-flex flex-column flex-md-row align-items-md-center gap-3 mb-6" role="status">
        <i class="ki-duotone ki-calendar-tick fs-2hx"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span><span class="path6"></span></i>
        <div class="flex-grow-1">
            @if ($days < 0)
                <div class="fw-bold">Langganan lewat jatuh tempo {{ abs($days) }} hari.</div>
                <div class="fs-7">Tim akan dibekukan setelah masa tenggang {{ \App\Services\Saas\SubscriptionService::GRACE_DAYS }} hari.</div>
            @else
                <div class="fw-bold">Langganan berakhir {{ $days === 0 ? 'hari ini' : ($days === 1 ? 'besok' : 'dalam '.$days.' hari') }}
                    ({{ $team->expires_at->locale('id')->translatedFormat('j F Y') }}).</div>
                <div class="fs-7">Perpanjang sekarang agar pekerjaan tim tidak terhenti.</div>
            @endif
        </div>
        @if ($canBill)
            <a href="{{ route('team.billing.index') }}" class="btn btn-{{ $days <= 1 ? 'danger' : 'warning' }}">Perpanjang</a>
        @endif
    </div>
@endif
