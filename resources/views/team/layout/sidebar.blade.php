{{-- Drawer navigasi area tim (mobile). Struktur dari App\Support\TeamNavigation. --}}
<aside id="kt_app_sidebar" class="d-flex flex-column" aria-label="Navigasi utama" aria-hidden="true">
    <div class="d-flex align-items-center justify-content-between gap-2 px-5 pt-5 pb-4">
        <a href="{{ route('team.dashboard') }}" class="app-brand">
            <img src="{{ $brand['logo_url'] }}" alt="" class="app-brand__mark" width="34" height="34" />
            <span class="min-w-0">
                <span class="app-brand__name d-block text-truncate">{{ $team->name }}</span>
                <span class="app-brand__tagline">{{ config('app.name') }}</span>
            </span>
        </a>
        <button type="button" class="btn btn-icon btn-sm btn-active-color-primary" data-app-sidebar-toggle aria-label="Tutup menu navigasi">
            <i class="ki-outline ki-cross fs-2"></i>
        </button>
    </div>

    <div class="separator mx-5 mb-3"></div>

    <nav class="px-4 flex-column-fluid">
        <div class="menu menu-column menu-rounded menu-sub-indention menu-active-bg fw-semibold fs-6" data-kt-menu="true">
            @foreach ($teamNav as $group)
                @if ($group['title'] !== '')
                    <div class="menu-item pt-5">
                        <div class="menu-content"><span class="menu-heading fw-bold text-uppercase fs-7">{{ $group['title'] }}</span></div>
                    </div>
                @endif
                @foreach ($group['items'] as $item)
                    <div class="menu-item">
                        <a class="menu-link {{ request()->routeIs($item['active']) ? 'active' : '' }}" href="{{ route($item['route']) }}">
                            <span class="menu-icon"><i class="ki-outline {{ $item['icon'] }} fs-3"></i></span>
                            <span class="menu-title">{{ $item['label'] }}</span>
                            @if (($item['badge'] ?? 0) > 0)
                                <span class="menu-badge"><span class="badge badge-danger">{{ $item['badge'] }}</span></span>
                            @endif
                        </a>
                    </div>
                @endforeach
            @endforeach
        </div>
    </nav>

    <div class="px-5 py-5 mt-auto">
        <div class="separator mb-4"></div>
        <div class="d-flex align-items-center gap-3 min-w-0">
            <x-avatar :size="36" />
            <div class="d-flex flex-column min-w-0">
                <span class="fw-bold fs-7 text-gray-800 text-truncate">{{ auth()->user()->name }}</span>
                <span class="text-muted fs-8">{{ auth()->user()->role_label }}</span>
            </div>
        </div>
    </div>
</aside>
