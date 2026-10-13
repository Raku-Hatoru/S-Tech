@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    {{-- Header & Subnav Admin --}}
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-5">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <span>Panel Administrator</span>
                <span>/</span>
                <span class="text-slate-800 font-medium">Pengguna</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Kelola Pengguna Sistem</h1>
            <p class="text-sm text-slate-500 mt-0.5">Buat, ubah, dan nonaktifkan akun pengguna sistem.</p>
        </div>

        <div class="flex items-center gap-3">
            <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-xl border border-slate-200 text-xs font-medium">
                <a href="{{ route('admin.pengguna.index') }}" class="px-3 py-1.5 rounded-lg bg-white shadow-xs text-slate-900 font-semibold transition">
                    Pengguna
                </a>
                <a href="{{ route('admin.dataset.index') }}" class="px-3 py-1.5 rounded-lg text-slate-600 hover:text-slate-900 transition">
                    Dataset Historis
                </a>
                <a href="{{ route('admin.forecast.index') }}" class="px-3 py-1.5 rounded-lg text-slate-600 hover:text-slate-900 transition">
                    Prediksi Forecast
                </a>
                <a href="{{ route('admin.pengaturan.index') }}" class="px-3 py-1.5 rounded-lg text-slate-600 hover:text-slate-900 transition">
                    Pengaturan & Ambang
                </a>
            </div>

            <a href="{{ route('admin.pengguna.create') }}"
               class="inline-flex items-center gap-2 px-3.5 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-semibold rounded-xl transition shadow-2xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Buat Akun Baru
            </a>
        </div>
    </div>

    {{-- Notifikasi --}}
    @if (session('sukses'))
        <div class="mb-5 p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center gap-3 text-sm text-emerald-800">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('sukses') }}
        </div>
    @endif
    @if (session('galat'))
        <div class="mb-5 p-3.5 rounded-xl bg-rose-50 border border-rose-200 flex items-center gap-3 text-sm text-rose-700">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="2"/><line x1="12" y1="8" x2="12" y2="12" stroke-width="2"/><line x1="12" y1="16" x2="12.01" y2="16" stroke-width="2"/></svg>
            {{ session('galat') }}
        </div>
    @endif

    {{-- Filter / Pencarian --}}
    <form method="GET" action="{{ route('admin.pengguna.index') }}" class="mb-5 flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-48">
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Cari nama, email, atau organisasi..."
                class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
            >
        </div>
        <div>
            <select name="role" class="px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition">
                <option value="">Semua Peran</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->kode }}" {{ request('role') === $role->kode ? 'selected' : '' }}>
                        {{ $role->nama }}
                    </option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-slate-800 text-white text-sm font-medium rounded-lg hover:bg-slate-700 transition">
            Cari
        </button>
        @if (request('search') || request('role'))
            <a href="{{ route('admin.pengguna.index') }}" class="px-4 py-2 border border-slate-300 text-slate-600 text-sm font-medium rounded-lg hover:bg-slate-50 transition">
                Reset
            </a>
        @endif
    </form>

    {{-- Tabel --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Nama / Email</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Peran</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Organisasi / Wilayah</th>
                        <th class="px-5 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                        <th class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($users as $user)
                        <tr class="hover:bg-slate-50/60 transition {{ ! $user->is_active ? 'opacity-60' : '' }}">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0
                                        {{ $user->isAdmin() ? 'bg-purple-600' : ($user->isBumdes() ? 'bg-emerald-600' : 'bg-blue-600') }}">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-slate-900">{{ $user->name }}</p>
                                        <p class="text-xs text-slate-500">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium
                                    {{ $user->isAdmin() ? 'bg-purple-100 text-purple-700' : ($user->isBumdes() ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700') }}">
                                    {{ $user->role?->nama ?? '—' }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-slate-600">
                                @if ($user->organisasi)
                                    <p>{{ $user->organisasi }}</p>
                                @endif
                                @if ($user->kabupaten)
                                    <p class="text-xs text-slate-400">{{ $user->kabupaten->nama }}, {{ $user->kabupaten->provinsi?->nama }}</p>
                                @endif
                                @if (! $user->organisasi && ! $user->kabupaten)
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                @if ($user->is_active)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-500">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Nonaktif
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.pengguna.edit', $user) }}"
                                       class="text-xs font-medium text-slate-600 hover:text-slate-900 px-2 py-1 rounded hover:bg-slate-100 transition">
                                        Edit
                                    </a>
                                    @if ($user->id !== auth()->id())
                                        <form method="POST" action="{{ route('admin.pengguna.toggle-aktif', $user) }}" class="inline">
                                            @csrf
                                            <button type="submit"
                                                    class="text-xs font-medium px-2 py-1 rounded transition
                                                        {{ $user->is_active
                                                            ? 'text-rose-600 hover:text-rose-800 hover:bg-rose-50'
                                                            : 'text-emerald-700 hover:text-emerald-900 hover:bg-emerald-50' }}"
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
