<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Masuk — {{ config('app.name', 'SIPANGAN WASPADA') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-50 font-sans antialiased">

    <div class="min-h-screen flex flex-col">
        {{-- Header --}}
        <header class="bg-white border-b border-slate-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <a href="{{ url('/') }}" class="flex items-center gap-3 group">
                    <div class="w-9 h-9 rounded-xl bg-emerald-700 flex items-center justify-center text-white shadow-sm">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2a10 10 0 0 1 10 10c0 5.523-4.477 10-10 10S2 17.523 2 12a10 10 0 0 1 10-10Z"/>
                            <path d="M12 6v6l4 2"/>
                            <path d="M7 12a5 5 0 0 0 5 5"/>
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-base tracking-tight text-slate-900">SIPANGAN WASPADA</span>
                            <span class="px-1.5 py-0.5 text-[10px] font-semibold tracking-wider uppercase bg-emerald-100 text-emerald-800 rounded">RESMI</span>
                        </div>
                        <p class="text-[11px] text-slate-500 font-medium leading-none">Sistem Pemantauan & Early-Warning Surplus Pangan Nasional</p>
                    </div>
                </a>
                <div class="flex items-center gap-4 text-sm text-slate-500">
                    <a href="{{ url('/') }}" class="hidden sm:flex items-center gap-1.5 hover:text-emerald-700 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Peta Heatmap Publik
                    </a>
                    <span class="hidden sm:flex items-center gap-1.5 text-xs text-slate-400">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2" stroke-width="2"/><path stroke-linecap="round" stroke-width="2" d="M7 11V7a5 5 0 0110 0v4"/></svg>
                        Sesi Enkripsi TLS 1.3
                    </span>
                </div>
            </div>
        </header>

        {{-- Main --}}
        <main class="flex-1 flex">
            {{-- Left Panel --}}
            <div class="hidden lg:flex flex-col justify-center px-12 xl:px-20 max-w-xl bg-white border-r border-slate-100">
                <div class="mb-8">
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-slate-100 border border-slate-200 mb-6">
                        <svg class="w-3.5 h-3.5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-600">Gerbang Akses Terpadu Multistakeholder</span>
                    </div>
                    <h1 class="text-3xl xl:text-4xl font-bold text-slate-900 leading-tight mb-4">
                        Konsol Otorisasi Rantai Pasok Ketahanan Pangan
                    </h1>
                    <p class="text-slate-500 leading-relaxed">
                        Akses resmi khusus bagi operator Badan Pangan Nasional, pengurus BUMDes kabupaten terdaftar, dan mitra distributor logistik pangan nasional.
                    </p>
                </div>

                {{-- Role Cards --}}
                <div class="grid grid-cols-3 gap-3 mb-6">
                    <div class="p-4 rounded-xl border border-slate-200 bg-slate-50 hover:border-emerald-200 hover:bg-emerald-50/30 transition group">
                        <div class="w-8 h-8 rounded-lg bg-slate-200 group-hover:bg-emerald-100 flex items-center justify-center mb-2.5 transition">
                            <svg class="w-4 h-4 text-slate-600 group-hover:text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        </div>
                        <p class="text-xs font-semibold text-slate-700 mb-1">BUMDes Desa</p>
                        <p class="text-[11px] text-slate-500 leading-snug">Input panen aktual & sisa stok survei bulanan lumbung pangan.</p>
                    </div>
                    <div class="p-4 rounded-xl border border-slate-200 bg-slate-50 hover:border-blue-200 hover:bg-blue-50/30 transition group">
                        <div class="w-8 h-8 rounded-lg bg-slate-200 group-hover:bg-blue-100 flex items-center justify-center mb-2.5 transition">
                            <svg class="w-4 h-4 text-slate-600 group-hover:text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                        </div>
                        <p class="text-xs font-semibold text-slate-700 mb-1">Distributor</p>
                        <p class="text-[11px] text-slate-500 leading-snug">Penerbitan surat jalan resi & mitigasi saturasi wilayah surplus.</p>
                    </div>
                    <div class="p-4 rounded-xl border border-slate-200 bg-slate-50 hover:border-purple-200 hover:bg-purple-50/30 transition group">
                        <div class="w-8 h-8 rounded-lg bg-slate-200 group-hover:bg-purple-100 flex items-center justify-center mb-2.5 transition">
                            <svg class="w-4 h-4 text-slate-600 group-hover:text-purple-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/></svg>
                        </div>
                        <p class="text-xs font-semibold text-slate-700 mb-1">Admin Pusat</p>
                        <p class="text-[11px] text-slate-500 leading-snug">Kalibrasi model XGBoost & ambang toleransi neraca wilayah.</p>
                    </div>
                </div>

                {{-- Info Box --}}
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 flex gap-3">
                    <svg class="w-4 h-4 text-slate-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    <div class="text-xs text-slate-600 leading-relaxed">
                        <span class="font-semibold text-slate-700">Ketentuan Akun Terdaftar: </span>
                        Akun pengguna diterbitkan langsung oleh Administrator Nasional (PRD F-11). Setiap BUMDes terikat tepat ke 1 yurisdiksi kabupaten guna menjaga validitas data stok survei.
                    </div>
                </div>
            </div>

            {{-- Right Panel — Form --}}
            <div class="flex-1 flex items-center justify-center px-6 py-10">
                <div class="w-full max-w-md">
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8">
                        <div class="mb-7">
                            <h2 class="text-2xl font-bold text-slate-900 mb-1">Masuk ke Akun Anda</h2>
                            <p class="text-sm text-slate-500">Silakan masukkan kredensial resmi instansi Anda</p>
                        </div>

                        {{-- Error Session --}}
                        @if (session('galat'))
                            <div class="mb-5 p-3 rounded-lg bg-rose-50 border border-rose-200 flex gap-2 items-start text-sm text-rose-700">
                                <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="2"/><line x1="12" y1="8" x2="12" y2="12" stroke-width="2"/><line x1="12" y1="16" x2="12.01" y2="16" stroke-width="2"/></svg>
                                {{ session('galat') }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('login.store') }}" id="form-login">
                            @csrf

                            {{-- Email --}}
                            <div class="mb-5">
                                <label for="email" class="block text-sm font-semibold text-slate-700 mb-1.5">
                                    Alamat Email Terdaftar
                                    <span class="text-xs font-normal text-slate-400 ml-1">Format: @badanpangan.go.id / bumdes</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    </span>
                                    <input
                                        id="email"
                                        name="email"
                                        type="email"
                                        autocomplete="email"
                                        value="{{ old('email') }}"
                                        required
                                        placeholder="contoh@badanpangan.go.id"
                                        class="w-full pl-10 pr-4 py-2.5 text-sm border rounded-lg bg-slate-50 focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition outline-none {{ $errors->has('email') ? 'border-rose-400 bg-rose-50' : 'border-slate-300' }}"
                                    >
                                </div>
                                @error('email')
                                    <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Password --}}
                            <div class="mb-5">
                                <div class="flex items-center justify-between mb-1.5">
                                    <label for="password" class="block text-sm font-semibold text-slate-700">Kata Sandi</label>
                                </div>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2" stroke-width="2"/><path stroke-linecap="round" stroke-width="2" d="M7 11V7a5 5 0 0110 0v4"/></svg>
                                    </span>
                                    <input
                                        id="password"
                                        name="password"
                                        type="password"
                                        autocomplete="current-password"
                                        required
                                        placeholder="••••••••••"
                                        class="w-full pl-10 pr-10 py-2.5 text-sm border rounded-lg bg-slate-50 focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition outline-none {{ $errors->has('password') ? 'border-rose-400 bg-rose-50' : 'border-slate-300' }}"
                                    >
                                    <button type="button" id="toggle-password" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 transition">
                                        <svg class="w-4 h-4" id="icon-show" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        <svg class="w-4 h-4 hidden" id="icon-hide" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                    </button>
                                </div>
                                @error('password')
                                    <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Demo Quick Login --}}
                            @if (config('app.env') !== 'production')
                                <div class="mb-5 p-3 rounded-lg bg-amber-50 border border-amber-200">
                                    <p class="text-xs font-semibold text-amber-800 mb-2 uppercase tracking-wider">Pilih Akun Cepat (Mode Demo / Evaluasi):</p>
                                    <div class="flex flex-wrap gap-2">
                                        <button type="button" data-email="admin@sipangan.test" data-pass="Admin1234" class="btn-demo px-3 py-1.5 text-xs font-medium bg-white border border-slate-300 rounded-md hover:bg-slate-50 text-slate-700 transition">Admin Pusat</button>
                                        <button type="button" data-email="bumdes.banyumas@sipangan.test" data-pass="Bumdes1234" class="btn-demo px-3 py-1.5 text-xs font-medium bg-white border border-slate-300 rounded-md hover:bg-slate-50 text-slate-700 transition">BUMDes Banyumas</button>
                                        <button type="button" data-email="distributor1@sipangan.test" data-pass="Distrib1234" class="btn-demo px-3 py-1.5 text-xs font-medium bg-white border border-slate-300 rounded-md hover:bg-slate-50 text-slate-700 transition">Distributor Pangan</button>
                                    </div>
                                </div>
                            @endif

                            {{-- Remember Me --}}
                            <div class="flex items-center justify-between mb-6">
                                <label class="flex items-center gap-2 cursor-pointer select-none">
                                    <input type="checkbox" name="remember" id="remember" class="w-4 h-4 rounded text-emerald-600 border-slate-300 focus:ring-emerald-500 transition">
                                    <span class="text-xs text-slate-600">Ingat sesi perangkat ini</span>
                                </label>
                                <span class="flex items-center gap-1 text-xs text-slate-400">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2" stroke-width="2"/><path stroke-linecap="round" stroke-width="2" d="M7 11V7a5 5 0 0110 0v4"/></svg>
                                    256-bit AES
                                </span>
                            </div>

                            {{-- Submit --}}
                            <button
                                type="submit"
                                id="btn-submit"
                                class="w-full flex items-center justify-center gap-2 bg-slate-900 hover:bg-slate-700 text-white font-semibold py-3 px-6 rounded-xl transition text-sm shadow-sm"
                            >
                                <span id="btn-text">Masuk ke Konsol</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                <svg class="w-4 h-4 animate-spin hidden" id="btn-spinner" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            </button>
                        </form>

                        {{-- Disclaimer --}}
                        <p class="mt-5 text-center text-xs text-slate-500 leading-relaxed">
                            Sistem ini bukan platform transaksi jual-beli komersial, melainkan sistem pemantauan dan mitigasi saturasi rantai pasok ketahanan pangan Republik Indonesia.
                        </p>
                    </div>
                </div>
            </div>
        </main>

        {{-- Footer --}}
        <footer class="bg-white border-t border-slate-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500">
                <span>&copy; {{ date('Y') }} SIPANGAN WASPADA. Tata Kelola Terpadu Ketahanan Pangan RI.</span>
                <div class="flex items-center gap-4">
                    <span class="hover:text-slate-700 cursor-pointer transition">Protokol Keamanan Data</span>
                    <span class="hover:text-slate-700 cursor-pointer transition">SOP Verifikasi BUMDes</span>
                    <span class="hover:text-slate-700 cursor-pointer transition">Bantuan Layanan TI</span>
                </div>
            </div>
        </footer>
    </div>

    <script>
        // Toggle visibilitas password
        document.getElementById('toggle-password').addEventListener('click', function () {
            const input = document.getElementById('password');
            const iconShow = document.getElementById('icon-show');
            const iconHide = document.getElementById('icon-hide');
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            iconShow.classList.toggle('hidden', isHidden);
            iconHide.classList.toggle('hidden', !isHidden);
        });

        // Tombol demo: isi email & password otomatis
        document.querySelectorAll('.btn-demo').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.getElementById('email').value = btn.dataset.email;
                document.getElementById('password').value = btn.dataset.pass;
            });
        });

        // Disable tombol saat submit
        document.getElementById('form-login').addEventListener('submit', function () {
            document.getElementById('btn-text').textContent = 'Memproses...';
            document.getElementById('btn-spinner').classList.remove('hidden');
            document.getElementById('btn-submit').disabled = true;
        });
    </script>
</body>
</html>
