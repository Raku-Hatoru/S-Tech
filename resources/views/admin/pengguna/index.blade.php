@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    {{-- Header & Subnav Admin --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 border-b border-slate-200 pb-5">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Kelola Pengguna Sistem</h1>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <nav class="flex flex-wrap items-center gap-1 bg-slate-100 p-1 rounded-xl border border-slate-200 text-xs font-medium" aria-label="Navigasi Panel Admin">
                <a href="{{ route('admin.pengguna.index') }}" class="min-h-[44px] px-3.5 py-2 rounded-lg bg-white shadow-xs text-slate-900 font-semibold inline-flex items-center transition">
                    Pengguna
                </a>
                <a href="{{ route('admin.dataset.index') }}" class="min-h-[44px] px-3.5 py-2 rounded-lg text-slate-600 hover:text-slate-900 inline-flex items-center transition">
                    Dataset Historis
                </a>
                <a href="{{ route('admin.forecast.index') }}" class="min-h-[44px] px-3.5 py-2 rounded-lg text-slate-600 hover:text-slate-900 inline-flex items-center transition">
                    Prediksi Forecast
                </a>
                <a href="{{ route('admin.pengaturan.index') }}" class="min-h-[44px] px-3.5 py-2 rounded-lg text-slate-600 hover:text-slate-900 inline-flex items-center transition">
                    Pengaturan & Ambang
                </a>
            </nav>

            <a href="{{ route('admin.pengguna.create') }}"
               class="min-h-[44px] inline-flex items-center gap-2 px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs sm:text-sm font-semibold rounded-xl transition shadow-2xs">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Buat Akun Baru</span>
            </a>
        </div>
    </div>

    {{-- Notifikasi --}}
    @if (session('sukses'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center gap-3 text-xs sm:text-sm text-emerald-900">
            <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>{{ session('sukses') }}</span>
        </div>
    @endif
    @if (session('galat'))
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 flex items-center gap-3 text-xs sm:text-sm text-rose-900">
            <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="2"/><line x1="12" y1="8" x2="12" y2="12" stroke-width="2"/><line x1="12" y1="16" x2="12.01" y2="16" stroke-width="2"/></svg>
            <span>{{ session('galat') }}</span>
        </div>
    @endif

    {{-- Filter / Pencarian --}}
    <form method="GET" action="{{ route('admin.pengguna.index') }}" class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center">
        <div class="flex-1">
            <input
                type="text"
                name="search"
                id="search-pengguna"
                aria-label="Cari nama, email, atau organisasi"
                value="{{ request('search') }}"
                placeholder="Cari nama, email, atau organisasi..."
                class="w-full min-h-[44px] px-3.5 py-2 text-xs sm:text-sm border border-slate-300 rounded-xl bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
            >
        </div>
        <div class="w-full sm:w-52">
            <select name="role" id="filter-role" aria-label="Filter berdasarkan peran pengguna" class="w-full min-h-[44px] px-3.5 py-2 text-xs sm:text-sm border border-slate-300 rounded-xl bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition">
                <option value="">Semua Peran</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->kode }}" {{ request('role') === $role->kode ? 'selected' : '' }}>
                        {{ $role->nama }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="flex items-center gap-2">
            <button type="submit" class="min-h-[44px] px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs sm:text-sm font-semibold rounded-xl transition inline-flex items-center justify-center">
                Cari
            </button>
            @if (request('search') || request('role'))
                <a href="{{ route('admin.pengguna.index') }}" class="min-h-[44px] px-4 py-2 border border-slate-300 text-slate-700 text-xs sm:text-sm font-medium rounded-xl hover:bg-slate-50 transition inline-flex items-center justify-center">
                    Reset
                </a>
            @endif
        </div>
    </form>

    {{-- Tabel Pengguna --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
        <div class="overflow-x-auto" tabindex="0" role="region" aria-label="Tabel Pengguna Sistem">
            <table class="w-full text-left text-xs sm:text-sm">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase tracking-wider font-semibold text-xs">
                    <tr>
                        <th class="px-5 py-3.5">Nama / Email</th>
                        <th class="px-5 py-3.5">Peran</th>
                        <th class="px-5 py-3.5">Organisasi / Wilayah</th>
                        <th class="px-5 py-3.5 text-center">Status</th>
                        <th class="px-5 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($users as $user)
                        <tr class="hover:bg-slate-50/70 transition {{ ! $user->is_active ? 'opacity-60' : '' }}">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0
                                        {{ $user->isAdmin() ? 'bg-purple-600' : ($user->isBumdes() ? 'bg-emerald-600' : 'bg-blue-600') }}">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="font-semibold text-slate-900">{{ $user->name }}</p>
                                        <p class="text-xs text-slate-500">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium
                                    {{ $user->isAdmin() ? 'bg-purple-100 text-purple-800' : ($user->isBumdes() ? 'bg-emerald-100 text-emerald-800' : 'bg-blue-100 text-blue-800') }}">
                                    {{ $user->role?->nama ?? '—' }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-slate-600 text-xs sm:text-sm">
                                @if ($user->organisasi)
                                    <p class="font-medium text-slate-800">{{ $user->organisasi }}</p>
                                @endif
                                @if ($user->kabupaten)
                                    <p class="text-xs text-slate-500">{{ $user->kabupaten->nama }}, {{ $user->kabupaten->provinsi?->nama }}</p>
                                @endif
                                @if (! $user->organisasi && ! $user->kabupaten)
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                @if ($user->is_active)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-600">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                        Nonaktif
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.pengguna.edit', $user) }}"
                                       class="min-h-[44px] min-w-[44px] inline-flex items-center justify-center text-xs font-semibold text-slate-700 hover:text-slate-900 px-3 py-2 rounded-xl border border-slate-200 hover:bg-slate-100 transition">
                                        Edit
                                    </a>
                                    @if ($user->id !== auth()->id())
                                        <form method="POST" action="{{ route('admin.pengguna.toggle-aktif', $user) }}" class="inline">
                                            @csrf
                                            <button type="submit"
                                                    class="min-h-[44px] inline-flex items-center justify-center text-xs font-semibold px-3 py-2 rounded-xl transition border
                                                        {{ $user->is_active
                                                            ? 'border-rose-200 text-rose-700 hover:text-rose-900 hover:bg-rose-50'
                                                            : 'border-emerald-200 text-emerald-800 hover:text-emerald-950 hover:bg-emerald-50' }}"
                                                    onclick="return confirm('{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }} akun {{ $user->name }}?')">
                                                {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-slate-400">
                                <svg class="w-10 h-10 mx-auto mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <p class="text-sm font-medium">Tidak ada pengguna ditemukan.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($users->hasPages())
            <div class="px-5 py-4 border-t border-slate-100 bg-slate-50">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
