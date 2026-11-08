{{-- Menu atas area tim (desktop). Grup jadi dropdown; struktur sama dengan sidebar. --}}
<nav class="menu menu-rounded menu-column menu-lg-row menu-root-here-bg-desktop menu-active-bg menu-state-primary menu-title-gray-800 menu-arrow-gray-500 align-items-stretch fw-semibold fs-6 py-1"
    data-kt-menu="true" aria-label="Navigasi utama">
    @foreach ($teamNav as $group)
        @if ($group['title'] === '')
            @foreach ($group['items'] as $item)
                <div class="menu-item me-lg-2 {{ request()->routeIs($item['active']) ? 'here show menu-here-bg' : '' }}">
                    <a class="menu-link py-3" href="{{ route($item['route']) }}">
                        <span class="menu-icon"><i class="ki-outline {{ $item['icon'] }} fs-3"></i></span>
                        <span class="menu-title">{{ $item['label'] }}</span>
                    </a>
                </div>
            @endforeach
        @else
            @php
                $groupActive = collect($group['items'])->contains(fn ($i) => request()->routeIs($i['active']));
                $groupBadge = collect($group['items'])->sum(fn ($i) => $i['badge'] ?? 0);
            @endphp
            <div data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-placement="bottom-start"
                class="menu-item menu-lg-down-accordion me-lg-2 {{ $groupActive ? 'here show menu-here-bg' : '' }}">
                <span class="menu-link py-3">
                    <span class="menu-title">{{ $group['title'] }}</span>
                    @if ($groupBadge > 0)
                        <span class="badge badge-circle badge-danger ms-2 w-20px h-20px fs-9">{{ $groupBadge }}</span>
                    @endif
                    <span class="menu-arrow d-lg-none"></span>
                </span>
                <div class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown py-4 w-250px">
                    @foreach ($group['items'] as $item)
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs($item['active']) ? 'active' : '' }}" href="{{ route($item['route']) }}">
                                <span class="menu-icon"><i class="ki-outline {{ $item['icon'] }} fs-4"></i></span>
                                <span class="menu-title">{{ $item['label'] }}</span>
                                @if (($item['badge'] ?? 0) > 0)
                                    <span class="menu-badge"><span class="badge badge-danger">{{ $item['badge'] }}</span></span>
                                @endif
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endforeach
</nav>
