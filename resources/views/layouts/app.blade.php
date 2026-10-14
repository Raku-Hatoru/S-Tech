<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'SIPANGAN WASPADA') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="h-full flex flex-col font-sans text-slate-900 antialiased bg-[#f8fafc]">
    <!-- Navbar / Header Resmi -->
    <header class="sticky top-0 z-50 bg-white border-b border-slate-200 shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <!-- Brand / Logo -->
                <div class="flex items-center gap-3 min-w-0">
                    <a href="{{ url('/') }}" class="min-h-[44px] flex items-center gap-2.5 sm:gap-3 group min-w-0">
                        <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-emerald-700 flex items-center justify-center text-white shadow-sm ring-1 ring-emerald-800/10 shrink-0 transition-transform group-hover:scale-105">
                            <!-- Emblem Logo Pangan -->
                            <svg class="w-5 h-5 sm:w-6 sm:h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 2a10 10 0 0 1 10 10c0 5.523-4.477 10-10 10S2 17.523 2 12a10 10 0 0 1 10-10Z"/>
                                <path d="M12 6v6l4 2"/>
                                <path d="M7 12a5 5 0 0 0 5 5"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5 sm:gap-2">
                                <span class="font-bold text-base sm:text-lg tracking-tight text-slate-900">SIPANGAN</span>
                                <span class="hidden sm:inline font-bold text-base sm:text-lg tracking-tight text-slate-900">WASPADA</span>
                                <span class="hidden md:inline-block px-1.5 py-0.5 text-[10px] sm:text-xs font-bold tracking-wider uppercase bg-slate-100 text-slate-700 rounded border border-slate-200 shrink-0">PROTOTIPE</span>
                            </div>
                            <p class="hidden xl:block text-xs text-slate-600 font-medium">Sistem Pemantauan &amp; Early-Warning Surplus Pangan Nasional</p>
                        </div>
                    </a>
                </div>

                {{-- Nav Menu Desktop --}}
                <nav aria-label="Navigasi Utama" class="hidden md:flex items-center space-x-1.5 bg-slate-100/80 p-1 rounded-xl border border-slate-200/80">
                    {{-- 1. Peta Heatmap Nasional (Semua Role & Guest) --}}
                    <a href="{{ route('peta.index') }}"
                       class="min-h-[44px] px-3.5 flex items-center justify-center rounded-lg text-xs font-bold transition {{ request()->is('peta*') || request()->is('/') ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-white/50' }}">
                        Peta Heatmap Nasional
                    </a>

                    {{-- 2. Detail Wilayah (Semua Role & Guest) --}}
                    <a href="{{ route('wilayah.detail', ['kode' => '3302']) }}"
                       class="min-h-[44px] px-3.5 flex items-center justify-center rounded-lg text-xs font-bold transition {{ request()->is('wilayah*') ? 'bg-emerald-800 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-white/50' }}">
                        Detail Wilayah
                    </a>

                    {{-- 3. Portal Pengiriman (Distributor & Admin) --}}
                    @if(auth()->check() && (auth()->user()->isDistributor() || auth()->user()->isAdmin()))
                        <a href="{{ route('pengiriman.index') }}"
                           class="min-h-[44px] px-3.5 flex items-center justify-center rounded-lg text-xs font-bold transition {{ request()->is('pengiriman*') ? 'bg-emerald-800 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-white/50' }}">
                            Portal Pengiriman
                        </a>
                    @endif

                    {{-- 4. Portal BUMDes (BUMDes & Admin) --}}
                    @if(auth()->check() && (auth()->user()->isBumdes() || auth()->user()->isAdmin()))
                        <a href="{{ route('panen.index') }}"
                           class="min-h-[44px] px-3.5 flex items-center justify-center rounded-lg text-xs font-bold transition {{ request()->is('panen*') || request()->is('jadwal-tanam*') ? 'bg-emerald-800 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-white/50' }}">
                            Portal BUMDes
                        </a>
                    @endif

                    {{-- 5. Panel Admin (Khusus Admin) --}}
                    @if(auth()->check() && auth()->user()->isAdmin())
                        <a href="{{ route('admin.pengguna.index') }}"
                           class="min-h-[44px] px-3.5 flex items-center justify-center rounded-lg text-xs font-bold transition {{ request()->is('admin*') ? 'bg-emerald-800 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-white/50' }}">
                            Panel Admin
                        </a>
                    @endif
                </nav>

                <!-- Status / Auth User (Sesuai Design Mockup) -->
                <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                    @auth
                        <div class="hidden xl:flex items-center gap-1.5 text-xs font-semibold text-slate-600 select-none py-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            <span>{{ auth()->user()->role->nama ?? 'Operator' }}</span>
                        </div>
                    @endauth

                    @auth
                        <div class="hidden md:flex items-center gap-2.5 px-3 py-1.5 rounded-xl bg-slate-50 border border-slate-200">
                            <div class="w-7 h-7 rounded-full bg-slate-800 text-white flex items-center justify-center font-bold text-xs">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                            <div class="text-left hidden sm:block">
                                <div class="text-xs font-bold text-slate-900 leading-tight">{{ auth()->user()->name }}</div>
                                <div class="text-xs text-slate-600 font-medium leading-none">{{ auth()->user()->role->nama ?? 'Operator' }}</div>
                            </div>
                            <form method="POST" action="{{ route('logout') }}" class="inline ml-1">
                                @csrf
                                <button type="submit" class="min-h-[44px] min-w-[44px] flex items-center justify-center text-xs text-rose-600 hover:text-rose-800 font-bold p-1 rounded-lg" title="Keluar" aria-label="Keluar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                </button>
                            </form>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="min-h-[44px] hidden md:inline-flex items-center gap-2 px-4 py-2.5 border border-slate-300 rounded-xl text-xs font-bold text-slate-800 bg-white hover:bg-slate-50 transition shadow-2xs">
                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                            Masuk
                        </a>
                    @endauth

                    <!-- Mobile Menu Button (md:hidden) -->
                    <button type="button"
                            onclick="document.getElementById('mobile-nav').classList.toggle('hidden')"
                            class="md:hidden min-h-[44px] min-w-[44px] flex items-center justify-center rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 p-2 transition"
                            aria-label="Buka menu navigasi mobile">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Navigation Menu Dropdown -->
        <nav id="mobile-nav" aria-label="Navigasi Mobile" class="hidden md:hidden border-t border-slate-200 py-3 px-4 space-y-2 bg-slate-50">
            <div class="flex items-center justify-between pb-2 mb-1 border-b border-slate-200">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Menu Sistem</span>
                <span class="px-2 py-0.5 text-[10px] font-bold tracking-wider uppercase bg-slate-200 text-slate-700 rounded border border-slate-300">PROTOTIPE</span>
            </div>
            <a href="{{ route('peta.index') }}"
               class="min-h-[44px] px-3.5 flex items-center rounded-xl text-xs font-bold transition {{ request()->is('peta*') || request()->is('/') ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-white/50' }}">
                Peta Heatmap Nasional
            </a>
            <a href="{{ route('wilayah.detail', ['kode' => '3302']) }}"
               class="min-h-[44px] px-3.5 flex items-center rounded-xl text-xs font-bold transition {{ request()->is('wilayah*') ? 'bg-emerald-800 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-white/50' }}">
                Detail Wilayah
            </a>
            @if(auth()->check() && (auth()->user()->isDistributor() || auth()->user()->isAdmin()))
                <a href="{{ route('pengiriman.index') }}"
                   class="min-h-[44px] px-3.5 flex items-center rounded-xl text-xs font-bold transition {{ request()->is('pengiriman*') ? 'bg-emerald-800 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-white/50' }}">
                    Portal Pengiriman
                </a>
            @endif
            @if(auth()->check() && (auth()->user()->isBumdes() || auth()->user()->isAdmin()))
                <a href="{{ route('panen.index') }}"
                   class="min-h-[44px] px-3.5 flex items-center rounded-xl text-xs font-bold transition {{ request()->is('panen*') || request()->is('jadwal-tanam*') ? 'bg-emerald-800 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-white/50' }}">
                    Portal BUMDes
                </a>
            @endif
            @if(auth()->check() && auth()->user()->isAdmin())
                <a href="{{ route('admin.pengguna.index') }}"
                   class="min-h-[44px] px-3.5 flex items-center rounded-xl text-xs font-bold transition {{ request()->is('admin*') ? 'bg-emerald-800 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-white/50' }}">
                    Panel Admin
                </a>
            @endif

            <div class="pt-2 border-t border-slate-200">
                @auth
                    <div class="flex items-center justify-between p-2 rounded-xl bg-white border border-slate-200">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-slate-800 text-white flex items-center justify-center font-bold text-xs">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                            <div>
                                <div class="text-xs font-bold text-slate-900">{{ auth()->user()->name }}</div>
                                <div class="text-[10px] text-slate-600 font-medium">{{ auth()->user()->role->nama ?? 'Operator' }}</div>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="min-h-[44px] min-w-[44px] flex items-center justify-center text-xs text-rose-600 hover:text-rose-800 font-bold p-1 rounded-lg" title="Keluar" aria-label="Keluar">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            </button>
                        </form>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="w-full min-h-[44px] flex items-center justify-center gap-2 px-4 py-2.5 border border-slate-300 rounded-xl text-xs font-bold text-slate-800 bg-white hover:bg-slate-50 transition shadow-2xs">
                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                        Masuk
                    </a>
                @endauth
            </div>
        </nav>
    </header>

    <!-- Main Content Area -->
    <main class="flex-1">
        @yield('content')
        {{ $slot ?? '' }}
    </main>

    <!-- Footer Resmi -->
    <footer class="mt-auto bg-white border-t border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-slate-600">
            <div>
                <span>&copy; {{ date('Y') }} SIPANGAN WASPADA &bull; Sistem pemantauan dini pasokan pangan; bukan platform jual-beli.</span>
            </div>
            <div class="text-xs text-slate-500 font-medium">
                <span>Diperbarui otomatis tiap 15 menit</span>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
