<!DOCTYPE html>
@php
    $center = $currentCenter ?? null;
    $themeColors = [
        'pink' => '#db2777', 'emerald' => '#059669', 'amber' => '#d97706', 'orange' => '#ea580c',
        'blue' => '#2563eb', 'rose' => '#e11d48', 'violet' => '#7c3aed',
    ];
    $accent = $center->accent_color ?? 'pink';
    $isOwner = auth()->user()?->isOwner();

    // Mục chính — mọi thành viên đều thấy
    $workNav = [
        ['route' => 'today', 'active' => 'today', 'icon' => 'fa-house', 'label' => 'Hôm nay'],
        ['route' => 'attendance.index', 'active' => 'attendance.*', 'icon' => 'fa-clipboard-user', 'label' => 'Điểm danh', 'badge' => $unmarkedBadgeCount ?? 0, 'badge_color' => 'info'],
        ['route' => 'children.index', 'active' => 'children.*', 'icon' => 'fa-children', 'label' => 'Trẻ'],
        ['route' => 'diary.index', 'active' => 'diary.*', 'icon' => 'fa-book-open', 'label' => 'Nhật ký'],
        ['route' => 'tasks.index', 'active' => 'tasks.*', 'icon' => 'fa-list-check', 'label' => 'Công việc', 'badge' => $taskBadgeCount ?? 0, 'badge_color' => 'info'],
        ['route' => 'menus.index', 'active' => 'menus.*', 'icon' => 'fa-utensils', 'label' => 'Thực đơn'],
        ['route' => 'inventory.index', 'active' => 'inventory.*', 'icon' => 'fa-boxes-stacked', 'label' => 'Kho', 'badge' => $lowStockBadgeCount ?? 0, 'badge_color' => 'danger'],
    ];

    // Mục quản lý — chỉ chủ nhóm
    $ownerNav = [
        ['route' => 'invoices.index', 'active' => 'invoices.*', 'icon' => 'fa-file-invoice-dollar', 'label' => 'Học phí'],
        ['route' => 'expenses.index', 'active' => 'expenses.*', 'icon' => 'fa-receipt', 'label' => 'Chi phí'],
        ['route' => 'reports.index', 'active' => 'reports.*', 'icon' => 'fa-chart-line', 'label' => 'Báo cáo thu/chi'],
        ['route' => 'routines.index', 'active' => 'routines.*', 'icon' => 'fa-repeat', 'label' => 'Lịch định kỳ'],
        ['route' => 'classrooms.index', 'active' => 'classrooms.*', 'icon' => 'fa-chalkboard-user', 'label' => 'Lớp / nhóm tuổi'],
        ['route' => 'staff.index', 'active' => 'staff.*', 'icon' => 'fa-person-chalkboard', 'label' => 'Cô giáo'],
        ['route' => 'centers.index', 'active' => 'centers.*', 'icon' => 'fa-house-chimney', 'label' => 'Các cơ sở'],
    ];
@endphp
<html lang="vi" class="h-full"
      data-font-scale="{{ $center->font_scale ?? 'md' }}"
      data-accent="{{ $accent }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
    <title>@yield('title', 'Mầm')</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    {{-- PWA — "Cài đặt ứng dụng" lên màn hình chính để mở nhanh như app thật. --}}
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="{{ $themeColors[$accent] ?? '#db2777' }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Mầm">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-neutral-50 text-neutral-900 antialiased overscroll-none">
<div class="h-full md:flex" style="height: 100dvh;">

    {{-- Sidebar: từ tablet trở lên, kéo mép phải để đổi độ rộng --}}
    <aside x-data="resizablePanel('mam-sidebar-width', 256, 220, 380, 'right')" :style="`width: ${width}px`"
           class="hidden md:flex md:flex-col bg-white border-r border-neutral-200 shrink-0 relative">
        <div @mousedown="startResize($event)" @touchstart="startResize($event)"
             class="hidden md:block absolute top-0 right-0 w-1.5 h-full cursor-col-resize hover:bg-[var(--accent)] transition z-10"
             :class="resizing && 'bg-[var(--accent)]'"></div>
        <a href="{{ route('today') }}" class="h-16 px-5 flex items-center gap-2.5 border-b border-neutral-200 hover:bg-neutral-50 transition shrink-0">
            <div class="w-9 h-9 rounded-xl bg-[var(--accent)] flex items-center justify-center text-white shrink-0">
                <i class="fa-solid fa-seedling"></i>
            </div>
            <div class="min-w-0">
                <div class="text-base font-bold leading-tight text-neutral-900">Mầm</div>
                <div class="text-xs text-neutral-500 truncate">{{ $center?->name }}</div>
            </div>
        </a>

        <nav class="flex-1 min-h-0 overflow-y-auto scrollbar-hide px-3 py-4 space-y-1">
            <a href="{{ route('diary.create') }}"
               class="flex items-center justify-center gap-2 px-3 py-2.5 mb-3 rounded-xl text-sm font-bold bg-[var(--accent-light)] text-[var(--accent-text)] hover:brightness-95 transition">
                <i class="fa-solid fa-pen-to-square"></i>Ghi nhật ký nhanh
            </a>

            @foreach($workNav as $item)
                @php $active = request()->routeIs($item['active']); @endphp
                <a href="{{ route($item['route']) }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition {{ $active ? 'bg-[var(--accent)] text-white' : 'text-neutral-600 hover:bg-neutral-100' }}">
                    <i class="fa-solid {{ $item['icon'] }} w-5 text-center"></i>{{ $item['label'] }}
                    @isset($item['badge'])
                        <x-nav-badge :count="$item['badge']" :color="$item['badge_color']" class="ml-auto" :inverted="$active" />
                    @endisset
                </a>
            @endforeach

            @if($isOwner)
                <div class="pt-3 mt-3 border-t border-neutral-200 space-y-1">
                    <p class="px-3 text-xs font-bold text-neutral-400 uppercase tracking-wider mb-1">Quản lý</p>
                    @foreach($ownerNav as $item)
                        <a href="{{ route($item['route']) }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs($item['active']) ? 'bg-[var(--accent)] text-white' : 'text-neutral-600 hover:bg-neutral-100' }}">
                            <i class="fa-solid {{ $item['icon'] }} w-5 text-center"></i>{{ $item['label'] }}
                        </a>
                    @endforeach
                </div>
            @endif
        </nav>

        <div class="px-3 py-4 border-t border-neutral-200">
            <a href="{{ route('profile.edit') }}" class="flex items-center gap-2.5 px-3 py-2 mb-1 rounded-xl hover:bg-neutral-100 transition {{ request()->routeIs('profile.*') ? 'bg-neutral-100' : '' }}">
                @if(auth()->user()?->avatar_url)
                    <img src="{{ auth()->user()->avatar_url }}" class="w-6 h-6 rounded-full object-cover shrink-0" alt="">
                @else
                    <i class="fa-solid fa-circle-user text-lg text-neutral-400"></i>
                @endif
                <span class="text-sm text-neutral-600 truncate font-medium">{{ auth()->user()?->name }}</span>
            </a>
            @if(auth()->user()?->isPlatformAdmin())
                <a href="{{ route('admin.dashboard') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition mb-1 text-neutral-600 hover:bg-neutral-100">
                    <i class="fa-solid fa-user-shield w-5 text-center"></i>Super Admin
                </a>
            @endif
            @if($isOwner)
                <a href="{{ route('settings.index') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition mb-1 {{ request()->routeIs('settings.*') ? 'bg-neutral-100 text-neutral-900' : 'text-neutral-600 hover:bg-neutral-100' }}">
                    <i class="fa-solid fa-gear w-5 text-center"></i>Cài đặt
                </a>
            @endif
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-neutral-600 hover:bg-neutral-100 text-sm font-semibold transition">
                    <i class="fa-solid fa-right-from-bracket w-5 text-center"></i>Đăng xuất
                </button>
            </form>
        </div>
    </aside>

    <div class="flex-1 flex flex-col min-w-0 min-h-0">
        {{-- Header mobile --}}
        <header class="flex md:hidden items-center justify-between px-4 py-3 bg-white border-b border-neutral-200 shrink-0">
            <a href="{{ route('today') }}" class="flex items-center gap-2 min-w-0">
                <div class="w-7 h-7 rounded-lg bg-[var(--accent)] flex items-center justify-center text-white text-xs shrink-0">
                    <i class="fa-solid fa-seedling"></i>
                </div>
                <span class="text-sm text-neutral-700 font-semibold truncate">{{ $center?->name }}</span>
            </a>

            <div x-data="{ open: false }" class="relative shrink-0" @click.outside="open = false">
                <button @click="open = !open" class="block">
                    @if(auth()->user()?->avatar_url)
                        <img src="{{ auth()->user()->avatar_url }}" class="w-7 h-7 rounded-full object-cover border border-neutral-200" alt="">
                    @else
                        <i class="fa-solid fa-circle-user text-2xl text-neutral-400"></i>
                    @endif
                </button>

                <div x-show="open" x-cloak x-transition
                     class="absolute right-0 mt-2 w-64 max-h-[75vh] overflow-y-auto bg-white rounded-2xl border border-neutral-200 shadow-lg py-2 z-30">
                    <div class="px-4 py-2 border-b border-neutral-100 mb-1">
                        <p class="text-sm font-semibold text-neutral-900 truncate">{{ auth()->user()?->name }}</p>
                        <span class="text-xs px-2 py-0.5 rounded-full {{ $isOwner ? 'bg-[var(--accent-light)] text-[var(--accent-text)]' : 'bg-neutral-100 text-neutral-600' }} font-medium inline-block mt-1">
                            {{ auth()->user()?->isPlatformAdmin() ? 'Super Admin' : ($isOwner ? 'Chủ nhóm' : 'Cô giáo') }}
                        </span>
                    </div>

                    <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 px-4 py-2 text-sm text-neutral-600 hover:bg-neutral-50 transition">
                        <i class="fa-solid fa-user w-4 text-center"></i>Hồ sơ cá nhân
                    </a>
                    @if(auth()->user()?->isPlatformAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-4 py-2 text-sm text-neutral-600 hover:bg-neutral-50 transition">
                            <i class="fa-solid fa-user-shield w-4 text-center"></i>Super Admin
                        </a>
                    @endif
                    @foreach([$workNav[3], $workNav[5], $workNav[6]] as $item)
                        <a href="{{ route($item['route']) }}" class="flex items-center gap-3 px-4 py-2 text-sm text-neutral-600 hover:bg-neutral-50 transition">
                            <i class="fa-solid {{ $item['icon'] }} w-4 text-center"></i>{{ $item['label'] }}
                        </a>
                    @endforeach

                    @if($isOwner)
                        <p class="px-4 pt-3 pb-1 text-xs font-bold text-neutral-400 uppercase tracking-wider">Quản lý</p>
                        @foreach($ownerNav as $item)
                            <a href="{{ route($item['route']) }}" class="flex items-center gap-3 px-4 py-2 text-sm text-neutral-600 hover:bg-neutral-50 transition">
                                <i class="fa-solid {{ $item['icon'] }} w-4 text-center"></i>{{ $item['label'] }}
                            </a>
                        @endforeach
                        <a href="{{ route('settings.index') }}" class="flex items-center gap-3 px-4 py-2 text-sm text-neutral-600 hover:bg-neutral-50 transition">
                            <i class="fa-solid fa-gear w-4 text-center"></i>Cài đặt
                        </a>
                    @endif

                    <div class="border-t border-neutral-100 mt-1 pt-1">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="w-full flex items-center gap-3 px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition text-left">
                                <i class="fa-solid fa-right-from-bracket w-4 text-center"></i>Đăng xuất
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        {{-- Header desktop/tablet --}}
        <header class="hidden md:flex h-16 items-center justify-between px-8 bg-white border-b border-neutral-200 shrink-0">
            <div class="text-sm font-semibold text-neutral-700">@yield('page-title', 'Mầm')</div>
            <div class="flex items-center gap-2 text-sm text-neutral-500 font-medium">
                <i class="fa-solid fa-location-dot"></i>{{ $center?->name }}
            </div>
        </header>

        @if (session('status'))
            <div class="bg-emerald-50 text-emerald-700 text-sm font-medium text-center py-2.5 px-4 flex items-center justify-center gap-2 border-b border-emerald-100">
                <i class="fa-solid fa-circle-check"></i>{{ session('status') }}
            </div>
        @endif
        @if (session('warning'))
            <div class="bg-amber-50 text-amber-800 text-sm font-medium text-center py-2.5 px-4 flex items-center justify-center gap-2 border-b border-amber-100">
                <i class="fa-solid fa-triangle-exclamation"></i>{{ session('warning') }}
            </div>
        @endif

        {{-- relative: phần tử absolute bên trong (VD: input sr-only của các nút chọn màu/cỡ chữ) lấy <main> làm
             khối chứa nên bị cắt theo vùng cuộn của main — không "lọt" ra ngoài kéo dài cả trang (lỗi 2 thanh cuộn). --}}
        <main class="relative flex-1 min-h-0 overflow-y-auto">
            @yield('content')
        </main>
    </div>

    {{-- Bottom nav mobile: Hôm nay · Điểm danh · [Ghi] · Trẻ · Công việc --}}
    <nav class="md:hidden fixed bottom-0 inset-x-0 bg-white border-t border-neutral-200 grid grid-cols-5 shrink-0 z-20 shadow-[0_-2px_12px_rgba(0,0,0,0.06)]"
         style="padding-bottom: env(safe-area-inset-bottom);">
        @php
            $mobileNav = [
                $workNav[0], $workNav[1],
                'fab',
                $workNav[2], $workNav[4],
            ];
        @endphp
        @foreach($mobileNav as $item)
            @if($item === 'fab')
                <a href="{{ route('diary.create') }}" class="flex flex-col items-center justify-center -mt-5" aria-label="Ghi nhật ký">
                    <span class="w-14 h-14 rounded-full bg-[var(--accent)] text-white flex items-center justify-center text-xl shadow-lg shadow-[var(--accent)]/40 border-4 border-white">
                        <i class="fa-solid fa-plus"></i>
                    </span>
                </a>
            @else
                <a href="{{ route($item['route']) }}"
                   class="flex flex-col items-center justify-center py-2.5 gap-1 {{ request()->routeIs($item['active']) ? 'text-[var(--accent-text)]' : 'text-neutral-500' }}">
                    <span class="relative">
                        <i class="fa-solid {{ $item['icon'] }} text-lg"></i>
                        @isset($item['badge'])
                            <x-nav-badge :count="$item['badge']" :color="$item['badge_color']" class="absolute -top-1.5 -right-3.5" />
                        @endisset
                    </span>
                    <span class="text-xs font-semibold">{{ $item['label'] }}</span>
                </a>
            @endif
        @endforeach
    </nav>
</div>
<script>
    // Service worker CHỈ để trình duyệt cho phép "Cài đặt ứng dụng" lên màn hình chính, không cache dữ liệu.
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js'));
    }
</script>
</body>
</html>
