@extends('team.layout.app')

@section('title', 'Notifikasi')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-6">
        <h1 class="fs-2 fw-bold text-gray-900 mb-0">Notifikasi</h1>
        <form method="POST" action="{{ route('team.notifications.read-all') }}">@csrf<button class="btn btn-sm btn-light">Tandai semua dibaca</button></form>
    </div>

    <div class="card">
        <div class="card-body pt-3">
            @forelse ($notifications as $n)
                <a href="{{ route('team.notifications.open', $n->id) }}" class="d-flex align-items-center gap-4 py-4 border-bottom border-gray-200 text-gray-800 {{ $n->read_at ? '' : 'fw-bold' }}">
                    <span class="symbol symbol-40px"><span class="symbol-label bg-light-{{ $n->data['color'] ?? 'primary' }}"><i class="ki-outline {{ $n->data['icon'] ?? 'ki-notification-on' }} fs-2 text-{{ $n->data['color'] ?? 'primary' }}"></i></span></span>
                    <div class="flex-grow-1 min-w-0">
                        <div class="text-truncate">{{ $n->data['title'] ?? '' }}</div>
                        <div class="text-muted fs-7 fw-normal">{{ $n->data['actor'] ?? '' }} · {{ $n->created_at->locale('id')->diffForHumans() }}</div>
                    </div>
                    @unless ($n->read_at)<span class="bullet bullet-dot bg-primary h-8px w-8px" aria-label="Belum dibaca"></span>@endunless
                </a>
            @empty
                <p class="text-center text-muted py-10 mb-0">Belum ada notifikasi.</p>
            @endforelse
            <div class="mt-4">{{ $notifications->links() }}</div>
        </div>
    </div>
@endsection
