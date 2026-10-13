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
                <div class="flex items-center gap-3">
                    <a href="{{ url('/') }}" class="flex items-center gap-3 group">
                        <div class="w-11 h-11 rounded-xl bg-emerald-700 flex items-center justify-center text-white shadow-sm ring-1 ring-emerald-800/10 transition-transform group-hover:scale-105">
                            <!-- Emblem Logo Pangan -->
                            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 2a10 10 0 0 1 10 10c0 5.523-4.477 10-10 10S2 17.523 2 12a10 10 0 0 1 10-10Z"/>
                                <path d="M12 6v6l4 2"/>
                                <path d="M7 12a5 5 0 0 0 5 5"/>
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-lg tracking-tight text-slate-900">SIPANGAN WASPADA</span>
                                <span class="px-2 py-0.5 text-[10px] font-semibold tracking-wider uppercase bg-emerald-100 text-emerald-800 rounded">RESMI</span>
                            </div>
                            <p class="text-xs text-slate-500 font-medium">Sistem Pemantauan & Early-Warning Surplus Pangan Nasional</p>
                        </div>
                    </a>
                </div>

                <!-- Nav Menu Desktop — Sesuai Design Mockup -->
                <nav class="hidden md:flex items-center space-x-1.5 bg-slate-100/80 p-1 rounded-xl border border-slate-200/80">
                    {{-- 1. Peta Heatmap Nasional --}}
                    <a href="{{ route('peta.index') }}"
                       class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition {{ request()->is('peta*') || request()->is('/') ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-white/50' }}">
                        Peta Heatmap Nasional
                    </a>

                    {{-- 2. Detail Wilayah --}}
                    <a href="{{ route('wilayah.detail', ['kode' => '3302']) }}"
                       class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition {{ request()->is('wilayah*') ? 'bg-emerald-800 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-white/50' }}">
                        Detail Wilayah
                    </a>

                    {{-- 3. Portal Pengiriman --}}
                    <a href="{{ route('pengiriman.index') }}"
                       class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition {{ request()->is('pengiriman*') ? 'bg-emerald-800 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-white/50' }}">
                        Portal Pengiriman
                    </a>

                    {{-- 4. Portal BUMDes --}}
                    <a href="{{ route('panen.index') }}"
                       class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition {{ request()->is('panen*') || request()->is('jadwal-tanam*') ? 'bg-emerald-800 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-white/50' }}">
                        Portal BUMDes
                    </a>

                    {{-- 5. Panel Admin --}}
                    <a href="{{ route('admin.pengguna.index') }}"
                       class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition {{ request()->is('admin*') ? 'bg-emerald-800 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-white/50' }}">
                        Panel Admin
                    </a>
                </nav>

                <!-- Status / Auth User (Sesuai Design Mockup) -->
                <div class="flex items-center gap-3">
                    <div class="hidden xl:flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-50/80 border border-blue-200/80 text-[11px] font-bold text-blue-900">
                        <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        <span>Mode: BUMDes / Distributor / Publik</span>
                    </div>

                    @auth
                        <div class="flex items-center gap-2.5 px-3 py-1.5 rounded-xl bg-slate-50 border border-slate-200">
                            <div class="w-7 h-7 rounded-full bg-slate-800 text-white flex items-center justify-center font-bold text-xs">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                            <div class="text-left hidden sm:block">
                                <div class="text-xs font-bold text-slate-900 leading-tight">{{ auth()->user()->name }}</div>
                                <div class="text-[10px] text-slate-500 font-medium leading-none">{{ auth()->user()->role->nama ?? 'Operator' }}</div>
                            </div>
                            <form method="POST" action="{{ route('logout') }}" class="inline ml-1">
                                @csrf
                                <button type="submit" class="text-xs text-rose-600 hover:text-rose-800 font-bold p-1" title="Keluar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                </button>
                            </form>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="inline-flex items-center gap-2 px-3.5 py-2 border border-slate-300 rounded-xl text-xs font-bold text-slate-800 bg-white hover:bg-slate-50 transition shadow-2xs">
                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                            Masuk ke Konsol
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-1">
        @yield('content')
        {{ $slot ?? '' }}
    </main>

    <!-- Footer Resmi -->
    <footer class="mt-auto bg-white border-t border-slate-200">
        <!-- Klausul & Regulasi PRD Banner -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 pb-4">
            <div class="p-4 rounded-xl bg-amber-50/70 border border-amber-200/80 flex items-start gap-3">
                <svg class="w-5 h-5 text-amber-700 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10" stroke-width="2"/>
                    <line x1="12" y1="8" x2="12" y2="12" stroke-width="2"/>
                    <line x1="12" y1="16" x2="12.01" y2="16" stroke-width="2"/>
                </svg>
                <div class="text-xs text-amber-900 leading-relaxed">
                    <span class="font-bold uppercase tracking-wider block mb-0.5 text-amber-950">Klausul Regulasi & Batasan Sistem</span>
                    Sistem ini merupakan platform pemantauan & tracking early-warning pasokan pangan. Bukan platform transaksi jual-beli komersial. Mengacu pada PRD & Dokumen Arsitektur Ketahanan Pangan Nasional Republik Indonesia.
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500">
            <div class="flex items-center gap-2">
                <span>&copy; {{ date('Y') }} SIPANGAN WASPADA. Tata Kelola Terpadu Ketahanan Pangan RI.</span>
            </div>
            <div class="flex items-center gap-6">
                <span class="hover:text-slate-800 transition cursor-pointer">Protokol BUMDes</span>
                <span class="hover:text-slate-800 transition cursor-pointer">SOP Distribusi</span>
                <span class="hover:text-slate-800 transition cursor-pointer">Status Integrasi API</span>
                <span class="hover:text-slate-800 transition cursor-pointer">Bantuan Layanan TI</span>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
