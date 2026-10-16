<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Masuk — {{ config('app.name', 'PRANALA PANGAN') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-50 font-sans antialiased text-slate-800">

    <div class="min-h-screen flex flex-col justify-between">
        {{-- Header Bersih --}}
        <header class="bg-white border-b border-slate-200/90">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <a href="{{ url('/') }}" class="flex items-center gap-3 group min-h-[44px]">
                    <div class="w-9 h-9 rounded-xl bg-emerald-700 flex items-center justify-center text-white shadow-2xs">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2a10 10 0 0 1 10 10c0 5.523-4.477 10-10 10S2 17.523 2 12a10 10 0 0 1 10-10Z"/>
                            <path d="M12 6v6l4 2"/>
                            <path d="M7 12a5 5 0 0 0 5 5"/>
                        </svg>
                    </div>
                    <div>
                        <span class="font-bold text-base tracking-tight text-slate-900">PRANALA PANGAN</span>
                    </div>
                </a>
                <div class="flex items-center gap-4 text-xs font-medium text-slate-600">
                    <a href="{{ url('/peta') }}" class="min-h-[44px] inline-flex items-center gap-1.5 px-2 hover:text-emerald-700 transition">
                        <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span>Peta Heatmap Publik</span>
                    </a>
                </div>
            </div>
        </header>

        {{-- Main Form Container: Ramping, Fokus, Modern --}}
        <main class="flex-1 flex items-center justify-center px-4 sm:px-6 py-8 sm:py-12">
            <div class="w-full max-w-md">
                <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs p-6 sm:p-8 space-y-6">
                    <div class="space-y-1.5">
                        <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                            Masuk ke Akun Anda
                        </h1>
                    </div>

                    {{-- Error Alert --}}
                    @if (session('galat'))
                        <div class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 flex gap-2.5 items-start text-xs font-semibold text-rose-800">
                            <svg class="w-4 h-4 shrink-0 mt-0.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="2"/><line x1="12" y1="8" x2="12" y2="12" stroke-width="2"/><line x1="12" y1="16" x2="12.01" y2="16" stroke-width="2"/></svg>
                            <span>{{ session('galat') }}</span>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login.store') }}" id="form-login" class="space-y-4">
                        @csrf

                        {{-- Email Input --}}
                        <div>
                            <label for="email" class="block text-xs font-bold text-slate-800 uppercase tracking-wide mb-1.5">
                                Alamat Email Terdaftar
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                </span>
                                <input
                                    id="email"
                                    name="email"
                                    type="email"
                                    autocomplete="email"
                                    value="{{ old('email') }}"
                                    required
                                    placeholder="nama@instansi.go.id"
                                    class="w-full min-h-[44px] pl-10 pr-4 py-2.5 text-xs font-semibold border rounded-xl bg-slate-50 focus:bg-white focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 transition outline-hidden {{ $errors->has('email') ? 'border-rose-400 bg-rose-50 text-rose-900' : 'border-slate-300 text-slate-900' }}"
                                />
                            </div>
                            @error('email')
                                <p class="mt-1 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Password Input with 44px Eye Toggle --}}
                        <div>
                            <label for="password" class="block text-xs font-bold text-slate-800 uppercase tracking-wide mb-1.5">
                                Kata Sandi
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2" stroke-width="2"/><path stroke-linecap="round" stroke-width="2" d="M7 11V7a5 5 0 0110 0v4"/></svg>
                                </span>
                                <input
                                    id="password"
                                    name="password"
                                    type="password"
                                    autocomplete="current-password"
                                    required
                                    placeholder="••••••••••••"
                                    class="w-full min-h-[44px] pl-10 pr-12 py-2.5 text-xs font-semibold border rounded-xl bg-slate-50 focus:bg-white focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 transition outline-hidden {{ $errors->has('password') ? 'border-rose-400 bg-rose-50 text-rose-900' : 'border-slate-300 text-slate-900' }}"
                                />
                                <button type="button"
                                        id="toggle-password"
                                        aria-label="Tampilkan atau sembunyikan kata sandi"
                                        class="min-h-[44px] min-w-[44px] absolute inset-y-0 right-0 flex items-center justify-center text-slate-400 hover:text-slate-700 transition">
                                    <svg class="w-4 h-4" id="icon-show" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <svg class="w-4 h-4 hidden" id="icon-hide" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                </button>
                            </div>
                            @error('password')
                                <p class="mt-1 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Remember Me --}}
                        <div class="flex items-center justify-between pt-1">
                            <label class="inline-flex items-center gap-2 cursor-pointer select-none min-h-[44px]">
                                <input type="checkbox" name="remember" id="remember" class="w-4 h-4 rounded text-emerald-600 border-slate-300 focus:ring-emerald-500 transition">
                                <span class="text-xs text-slate-700 font-medium">Ingat sesi perangkat ini</span>
                            </label>
                            <span class="text-xs text-slate-500 font-medium">Sesi Terenkripsi</span>
                        </div>

                        {{-- Submit Button --}}
                        <button
                            type="submit"
                            id="btn-submit"
                            class="w-full min-h-[44px] flex items-center justify-center gap-2 bg-slate-900 hover:bg-slate-800 text-white font-bold py-3 px-6 rounded-xl transition text-xs shadow-xs"
                        >
                            <span id="btn-text">Masuk ke Konsol</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            <svg class="w-4 h-4 animate-spin hidden" id="btn-spinner" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        </button>
                    </form>

                    {{-- 1 Baris Catatan Ringkas Resmi --}}
                    <div class="pt-4 border-t border-slate-100 text-center text-xs text-slate-600 font-medium">
                        Akun pengguna diterbitkan langsung oleh Administrator Nasional.
                    </div>
                </div>
            </div>
        </main>

        {{-- Footer Resmi --}}
        <footer class="bg-white border-t border-slate-200/90">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-slate-600 font-medium">
                <span>&copy; {{ date('Y') }} PRANALA PANGAN &bull; Sistem pemantauan dini pasokan pangan; bukan platform jual-beli.</span>
                <div class="text-slate-500">
                    <span>Sesi Terenkripsi &bull; Akses Terbatas Operator Resmi</span>
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

        // Disable tombol saat submit
        document.getElementById('form-login').addEventListener('submit', function () {
            document.getElementById('btn-text').textContent = 'Memproses...';
            document.getElementById('btn-spinner').classList.remove('hidden');
            document.getElementById('btn-submit').disabled = true;
        });
    </script>
</body>
</html>
