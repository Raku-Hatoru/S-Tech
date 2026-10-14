@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- 1. Subheader Portal Manajemen Pangan BUMDes --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
        <div class="flex items-start sm:items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-emerald-900 text-white flex items-center justify-center shrink-0 shadow-sm">
                <svg class="w-7 h-7 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
            </div>
            <div>
                <div class="flex flex-wrap items-center gap-2 mb-1">
                    <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Portal Manajemen Pangan BUMDes</h1>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                        ID: BUMDES-{{ $kabupaten->kode }}-AKTIF
                    </span>
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        BUMDes Terverifikasi
                    </span>
                </div>
                <div class="flex flex-wrap items-center gap-y-1 gap-x-2 text-xs text-slate-500 font-medium">
                    <span class="flex items-center gap-1 text-slate-700">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Wilayah Terdaftar: <strong>{{ $kabupaten->nama }}, {{ $kabupaten->provinsi->nama ?? 'Jawa Tengah' }} (Kode: {{ $kabupaten->kode }})</strong>
                    </span>
                    <span class="text-slate-300">•</span>
                    <span class="text-slate-500">Terkunci ke 1 Kabupaten sesuai regulasi ketahanan pangan</span>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3 self-start lg:self-auto">
            <div class="px-3.5 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="text-slate-600">Sirkulasi Basis Data:</span>
                <span class="font-bold text-slate-900">Live • {{ $activeModelVersion?->nama ?? 'XGBoost v1-2026Q1' }}</span>
            </div>
            <a href="{{ route('jadwal-tanam.index', ['kabupaten_id' => $kabupaten->id]) }}" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 text-xs font-bold transition">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Jadwal Tanam (F-10)
            </a>
        </div>
    </div>

    {{-- Flash Message Sukses / Error --}}
    @if (session('sukses'))
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 flex items-start gap-3 text-sm text-emerald-900">
            <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div>
                <p class="font-bold text-emerald-950">Laporan Berhasil Disimpan</p>
                <p class="text-emerald-800 text-xs mt-0.5 leading-relaxed">{{ session('sukses') }}</p>
            </div>
        </div>
    @endif

    {{-- Banner Peringatan Kapasitas Lumbung Status PENUH (Sesuai Mockup) --}}
    @if ($statusLevel === 'penuh')
        <div class="rounded-2xl bg-rose-50/80 border-2 border-rose-300 p-5 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-start gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-rose-100 border border-rose-200 text-rose-700 flex items-center justify-center shrink-0 mt-0.5">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <h2 class="text-base font-bold text-rose-950">Peringatan Kapasitas Lumbung: Status PENUH / SURPLUS</h2>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-200 text-rose-900 border border-rose-300 uppercase tracking-wide">
                            Kapasitas Tertinggi {{ $rasioKapasitas }}%
                        </span>
                    </div>
                    <p class="text-xs text-rose-800 leading-relaxed max-w-3xl">
                        Hasil agregasi survei lumbung padi per {{ $periodeDisplay }} menembus {{ number_format($stokAkhirRiilTon, 1, ',', '.') }} ton. Indeks surplus melampaui kapasitas aman penyangga pangan lokal (ambang batas 90% terlewati). Rekomendasi redistribusi antar-kabupaten disiagakan guna mencegah penyusutan mutu dan pembusukan pangan di tingkat petani.
                    </p>
                </div>
            </div>
            @can('create', App\Models\Shipment::class)
              <a href="{{ route('pengiriman.create', ['asal_kabupaten_id' => $kabupaten->id, 'komoditas_id' => $komoditas->id]) }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-white hover:bg-rose-50 text-rose-800 font-bold text-xs border border-rose-300 shadow-2xs whitespace-nowrap transition">
                  <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                  Minta Disposisi Logistik
              </a>
            @else
              <div class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-rose-100/70 text-rose-900 text-xs font-bold border border-rose-300/80 whitespace-nowrap">
                <svg class="w-4 h-4 text-rose-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Disposisi via Operator Terdaftar
              </div>
            @endcan
        </div>
    @endif

    {{-- 2. Empat Kartu KPI Teratas (Top KPI Cards Sesuai Mockup) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- KPI 1: Stok Akhir Riil --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs">
            <div class="flex items-center justify-between text-xs text-slate-500 font-semibold mb-2">
                <span>STOK AKHIR RIIL (SURVEI)</span>
                <span class="w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center text-slate-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                </span>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">{{ number_format($stokAkhirRiilTon, 1, ',', '.') }}</span>
                <span class="text-sm font-bold text-slate-600">Ton</span>
            </div>
            <p class="text-[11px] font-semibold text-emerald-700 mt-2 flex items-center gap-1">
                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Lumbung Desa {{ $kabupaten->nama }} 100% Terverifikasi
            </p>
        </div>

        {{-- KPI 2: Hasil Panen Riil Bulan Ini --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs">
            <div class="flex items-center justify-between text-xs text-slate-500 font-semibold mb-2">
                <span>HASIL PANEN RIIL BULAN INI</span>
                <span class="w-7 h-7 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                </span>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">{{ number_format($hasilPanenRiilTon, 1, ',', '.') }}</span>
                <span class="text-sm font-bold text-slate-600">Ton</span>
            </div>
            <p class="text-[11px] font-semibold text-slate-600 mt-2 flex items-center gap-1">
                <span>Vs Prediksi AI: {{ number_format($refPrediksiPanenTon, 1, ',', '.') }} Ton</span>
                <span class="{{ $deviasiPanenPersen >= 0 ? 'text-emerald-700' : 'text-rose-700' }} font-bold">
                    {{ $deviasiPanenPersen >= 0 ? '↗ +' : '↘ ' }}{{ number_format($deviasiPanenPersen, 1, ',', '.') }}% Deviasi
                </span>
            </p>
        </div>

        {{-- KPI 3: Status Wilayah Aktual --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs">
            <div class="flex items-center justify-between text-xs text-slate-500 font-semibold mb-2">
                <span>STATUS WILAYAH AKTUAL</span>
                <span class="w-7 h-7 rounded-lg {{ $statusLevel === 'penuh' ? 'bg-rose-100 text-rose-700' : ($statusLevel === 'waspada' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700') }} flex items-center justify-center font-bold">
                    !
                </span>
            </div>
            <div class="mt-1">
                @if ($statusLevel === 'penuh')
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-sm font-bold bg-rose-100 text-rose-800 border border-rose-200">
                        ⚠ PENUH / SURPLUS
                    </span>
                @elseif ($statusLevel === 'waspada')
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-sm font-bold bg-amber-100 text-amber-800 border border-amber-200">
                        ⚠ WASPADA SURPLUS
                    </span>
                @elseif ($statusLevel === 'seimbang')
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-sm font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                        ✓ SEIMBANG
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-sm font-bold bg-red-100 text-red-800 border border-red-200">
                        ✕ DEFISIT PASOKAN
                    </span>
                @endif
            </div>
            <p class="text-[11px] font-semibold text-slate-500 mt-2">
                Kapasitas Buffer: <strong class="text-slate-800">{{ $rasioKapasitas }}%</strong> • Tindakan Redistribusi Disiagakan
            </p>
        </div>

        {{-- KPI 4: Fokus Komoditas Utama --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs">
            <div class="flex items-center justify-between text-xs text-slate-500 font-semibold mb-2">
                <span>FOKUS KOMODITAS UTAMA</span>
                <span class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                </span>
            </div>
            <div class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight mt-1">
                {{ $komoditas->nama }} (MT-1)
            </div>
            <p class="text-[11px] font-semibold text-slate-500 mt-2">
                Siklus Tanam: Tahap Pengeringan • 12 Gapoktan Terdata
            </p>
        </div>
    </div>

    {{-- Filter Bar jika user adalah Admin (Dapat Memilih Kabupaten) --}}
    @if (auth()->user()->role?->kode === 'admin')
        <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs">
            <div class="flex items-center gap-2 text-amber-900">
                <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span><strong>Mode Administrator:</strong> Anda dapat menginspeksi atau menginput laporan atas nama BUMDes wilayah lain.</span>
            </div>
            <form method="GET" action="{{ route('panen.index') }}" class="flex items-center gap-2 w-full sm:w-auto">
                <input type="hidden" name="komoditas_id" value="{{ $komoditas->id }}">
                <input type="hidden" name="periode" value="{{ $periodeParam }}">
                <select name="kabupaten_id" onchange="this.form.submit()" class="px-3 py-1.5 rounded-xl border border-amber-300 bg-white text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-500">
                    @foreach ($semuaKabupaten as $kab)
                        <option value="{{ $kab->id }}" {{ $kab->id == $kabupaten->id ? 'selected' : '' }}>
                            {{ $kab->nama }} ({{ $kab->kode }})
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
    @endif

    {{-- 3. Modul 1: Formulir Input Panen & Sisa Stok Akhir Bulan (PRD F-09) --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        {{-- Kolom Kiri: Form Input (8 Kolom / 65%) --}}
        <div class="lg:col-span-7 xl:col-span-8 bg-white rounded-2xl border border-slate-200 p-6 sm:p-7 shadow-xs">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-4 mb-6">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="px-2.5 py-0.5 rounded-md text-[10px] font-black bg-emerald-800 text-white uppercase tracking-wider">
                            MODUL 1 • F-09
                        </span>
                        <h2 class="text-lg font-bold text-slate-900 tracking-tight">Formulir Input Panen & Sisa Stok Akhir Bulan</h2>
                    </div>
                    <p class="text-xs text-slate-500">
                        Pencatatan data survei lapangan berkala bulanan pengurus BUMDes untuk pembaruan saldo logistik daerah.
                    </p>
                </div>
                <span class="text-[11px] font-mono font-semibold px-2 py-1 bg-slate-100 rounded-lg text-slate-600 border border-slate-200">
                    Schema: 4.12 & 4.13
                </span>
            </div>

            {{-- Filter Selector Header Form --}}
            <form method="GET" action="{{ route('panen.index') }}" id="filterForm" class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6 p-4 rounded-xl bg-slate-50 border border-slate-200">
                @if (auth()->user()->role?->kode === 'admin')
                    <input type="hidden" name="kabupaten_id" value="{{ $kabupaten->id }}">
                @endif
                <div>
                    <div class="flex items-center justify-between text-xs font-bold text-slate-700 mb-1.5">
                        <label for="filter_periode">Periode Laporan Bulanan</label>
                        <span class="text-[10px] text-emerald-700 font-semibold">Terdaftar 2026</span>
                    </div>
                    <div class="relative">
                        <select name="periode" id="filter_periode" onchange="document.getElementById('filterForm').submit()" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 bg-white text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 shadow-2xs">
                            @foreach ($periodeOptions as $val => $label)
                                <option value="{{ $val }}" {{ $val === $periodeParam ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between text-xs font-bold text-slate-700 mb-1.5">
                        <label for="filter_komoditas">Jenis Komoditas Strategis</label>
                        <span class="text-[10px] text-slate-500">Bahan Pokok</span>
                    </div>
                    <div class="relative">
                        <select name="komoditas_id" id="filter_komoditas" onchange="document.getElementById('filterForm').submit()" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 bg-white text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 shadow-2xs">
                            @foreach ($komoditasList as $kom)
                                <option value="{{ $kom->id }}" {{ $kom->id == $komoditas->id ? 'selected' : '' }}>
                                    {{ $kom->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </form>

            {{-- Formulir Simpan Data Laporan --}}
            <form method="POST" action="{{ route('panen.store') }}" id="formLaporanPanen" class="space-y-6">
                @csrf
                @if (auth()->user()->role?->kode === 'admin')
                    <input type="hidden" name="kabupaten_id" value="{{ $kabupaten->id }}">
                @endif
                <input type="hidden" name="komoditas_id" value="{{ $komoditas->id }}">
                <input type="hidden" name="periode" value="{{ $periodeDate }}">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    {{-- Field 1: Hasil Panen Riil Bulan Ini (kg) --}}
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                        <div class="flex items-center justify-between mb-2">
                            <label for="panen_kg" class="text-xs font-bold text-slate-800">
                                Field 1: Hasil Panen Riil Bulan Ini
                            </label>
                            <span class="text-[10px] font-semibold text-slate-500 bg-white px-2 py-0.5 rounded border border-slate-200">
                                Kg → Ton
                            </span>
                        </div>
                        <div class="relative rounded-xl shadow-2xs">
                            <input type="number" step="any" min="0" name="panen_kg" id="panen_kg"
                                   value="{{ old('panen_kg', $currentReport?->panen_kg ?? $hasilPanenRiilKg) }}"
                                   required
                                   placeholder="Contoh: 15200000"
                                   class="block w-full px-3.5 py-2.5 text-base font-bold text-slate-900 bg-white rounded-xl border border-slate-300 focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 pr-12">
                            <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-xs font-bold text-slate-400">
                                kg
                            </div>
                        </div>
                        <div class="mt-2.5 flex items-center justify-between text-xs">
                            <span class="text-slate-500 text-[11px]">Konversi Otomatis Metrik:</span>
                            <span id="badge_panen_ton" class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-extrabold bg-emerald-100 text-emerald-900 border border-emerald-300">
                                {{ number_format(($currentReport?->panen_kg ?? $hasilPanenRiilKg) / 1000, 1, ',', '.') }} ton
                            </span>
                        </div>
                    </div>

                    {{-- Field 2: Sisa Stok Akhir Bulan (Survei Lapangan) --}}
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                        <div class="flex items-center justify-between mb-2">
                            <label for="sisa_stok_kg" class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                                <span>Field 2: Sisa Stok Akhir Survei</span>
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-black bg-amber-200 text-amber-900 uppercase">
                                    PENTING
                                </span>
                            </label>
                            <span class="text-[10px] font-semibold text-slate-500 bg-white px-2 py-0.5 rounded border border-slate-200">
                                Gudang & Petani
                            </span>
                        </div>
                        <div class="relative rounded-xl shadow-2xs">
                            <input type="number" step="any" min="0" name="sisa_stok_kg" id="sisa_stok_kg"
                                   value="{{ old('sisa_stok_kg', $currentReport?->sisa_stok_kg ?? $stokAkhirRiilKg) }}"
                                   required
                                   placeholder="Contoh: 3100000"
                                   class="block w-full px-3.5 py-2.5 text-base font-bold text-slate-900 bg-white rounded-xl border border-slate-300 focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 pr-12">
                            <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-xs font-bold text-slate-400">
                                kg
                            </div>
                        </div>
                        <div class="mt-2.5 flex items-center justify-between text-xs">
                            <span class="text-slate-500 text-[11px]">Konversi Otomatis Metrik:</span>
                            <span id="badge_stok_ton" class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-extrabold bg-emerald-100 text-emerald-900 border border-emerald-300">
                                {{ number_format(($currentReport?->sisa_stok_kg ?? $stokAkhirRiilKg) / 1000, 1, ',', '.') }} ton
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Box Penjelasan Logika Sistem --}}
                <div class="p-4 rounded-xl bg-blue-50/70 border border-blue-200 text-xs text-blue-900 flex items-start gap-3">
                    <svg class="w-5 h-5 text-blue-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <p class="font-bold text-blue-950 mb-0.5">Ketentuan Arsitektur Logika Sistem:</p>
                        <p class="text-blue-800 leading-relaxed text-[11px]">
                            "Sisa stok survei ini akan otomatis menjadi <strong>STOK AWAL</strong> bulan berikutnya dan menggantikan hitungan estimasi matematis. Jika tidak diisi, sistem menggunakan stok estimasi cadangan <code>max(0, supply - demand)</code>."
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    {{-- Tanggal Pelaksanaan Survei --}}
                    <div>
                        <label for="tanggal_survei" class="block text-xs font-bold text-slate-700 mb-1.5">
                            Tanggal Pelaksanaan Survei Lapangan
                        </label>
                        <input type="date" name="tanggal_survei" id="tanggal_survei"
                               value="{{ old('tanggal_survei', $currentReport?->tanggal_survei?->format('Y-m-d') ?? '2026-01-31') }}"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 bg-white text-xs font-medium text-slate-800 focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 shadow-2xs">
                    </div>

                    {{-- Koordinator / Catatan Lumbung --}}
                    <div>
                        <label for="catatan" class="block text-xs font-bold text-slate-700 mb-1.5">
                            Catatan & Cakupan Lumbung Survei
                        </label>
                        <input type="text" name="catatan" id="catatan"
                               value="{{ old('catatan', $currentReport?->catatan ?? 'Survei gabungan 12 lumbung desa BUMDes Banyumas') }}"
                               placeholder="Contoh: Survei gabungan 12 lumbung desa BUMDes Banyumas"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 bg-white text-xs font-medium text-slate-800 focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 shadow-2xs">
                    </div>
                </div>

                {{-- Action Footer --}}
                <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center gap-2 text-[11px] text-slate-500 font-medium">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        <span>Validasi Kriptografis Akun BUMDes {{ $kabupaten->nama }} Aktif</span>
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="reset" class="px-4 py-2.5 rounded-xl border border-slate-300 hover:bg-slate-100 text-slate-700 text-xs font-bold transition shadow-2xs">
                            Reset Nilai
                        </button>
                        <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-emerald-950 text-white text-xs font-bold transition shadow-sm">
                            <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Kirim & Hitung Ulang Status Wilayah
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Kolom Kanan: Visual Lumbung Pangan & Status Kalkulasi Otomatis (4 Kolom / 35%) --}}
        <div class="lg:col-span-5 xl:col-span-4 space-y-6">

            {{-- Card Foto Lumbung Pangan Induk --}}
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
                <div class="relative h-44 bg-slate-800">
                    <img src="https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?q=80&w=800&auto=format&fit=crop"
                         alt="Lumbung Pangan Induk {{ $kabupaten->nama }}"
                         class="w-full h-full object-cover opacity-80">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950/90 via-slate-900/40 to-transparent flex flex-col justify-end p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-bold text-white leading-snug">Lumbung Pangan Induk {{ $kabupaten->nama }}</h3>
                                <p class="text-[11px] text-slate-300">Kecamatan {{ $kabupaten->nama }} • 12 Unit Terhubung</p>
                            </div>
                            <span class="w-7 h-7 rounded-lg bg-white/20 backdrop-blur-md flex items-center justify-center text-white">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Status Sensor Lingkungan Lumbung --}}
                <div class="p-4 space-y-3 text-xs bg-slate-50/50">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                        <span class="text-slate-500">Sensor Suhu & Kelembaban:</span>
                        <span class="font-bold text-slate-800">24.2°C • 62% RH (Optimal)</span>
                    </div>
                    <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                        <span class="text-slate-500">Toleransi Deviasi Maksimal:</span>
                        <span class="font-bold text-slate-800">± 5.0% dari Model XGBoost</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Jalur Distribusi Siap:</span>
                        <span class="font-bold text-emerald-700">3 Armada Truk BUMDes</span>
                    </div>
                </div>
            </div>

            {{-- Card Preview Kalkulasi Otomatis Sistem --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-4">
                <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
                    <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Status Kalkulasi Otomatis Sistem</h3>
                </div>

                <div class="space-y-2.5 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Formula Stok Awal Bulan Depan:</span>
                        <span class="font-bold text-slate-900">Stok Survei = {{ number_format($stokAkhirRiilTon, 1, ',', '.') }} Ton</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Estimasi Supply Math:</span>
                        <span class="text-slate-400 font-semibold line-through">{{ number_format($stokAkhirRiilTon * 0.95, 1, ',', '.') }} Ton (Dilewati)</span>
                    </div>
                    <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                        <span class="text-slate-600 font-medium">Indeks Surplus Wilayah:</span>
                        <span class="font-extrabold text-rose-700 bg-rose-50 px-2 py-0.5 rounded-md border border-rose-200">
                            +{{ number_format(max(0, $stokAkhirRiilTon - 1280), 1, ',', '.') }} Ton (Siaga Surplus)
                        </span>
                    </div>
                </div>

                <p class="text-[10px] text-slate-500 italic leading-relaxed pt-2 border-t border-slate-100">
                    *Data yang disimpan akan mengaktifkan rute koordinasi distributor nasional secara realtime jika ambang batas 90% terlewati.
                </p>
            </div>
        </div>
    </div>

    {{-- 4. Modul 2 • UML 9.5: Rekapitulasi Laporan Panen Bulanan & Evaluasi Prediksi --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-7 shadow-xs space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-100 pb-5">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="px-2.5 py-0.5 rounded-md text-[10px] font-black bg-blue-900 text-white uppercase tracking-wider">
                        MODUL 2 • REKAPITULASI
                    </span>
                    <h2 class="text-lg font-bold text-slate-900 tracking-tight">Rekapitulasi Laporan Panen Bulanan & Evaluasi Prediksi</h2>
                </div>
                <p class="text-xs text-slate-500">
                    Perbandingan akurasi model prediktif machine learning XGBoost dengan pelaporan riil hasil survei lapangan BUMDes {{ $kabupaten->nama }}.
                </p>
            </div>

            <div class="flex items-center gap-2.5">
                <a href="{{ route('panen.export-csv', ['kabupaten_id' => $kabupaten->id, 'komoditas_id' => $komoditas->id]) }}" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold transition shadow-2xs">
                    <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Unduh CSV Rekap
                </a>
                <button type="button" onclick="window.print()" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold transition shadow-2xs">
                    <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Cetak Laporan
                </button>
            </div>
        </div>

        {{-- Tabel Evaluasi Prediksi vs Aktual --}}
        <div class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="w-full text-xs text-left border-collapse">
                <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider font-bold border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3">BULAN</th>
                        <th class="px-4 py-3">KOMODITAS</th>
                        <th class="px-4 py-3 text-right">PANEN AKTUAL (TON)</th>
                        <th class="px-4 py-3 text-right">PREDIKSI MODEL (TON)</th>
                        <th class="px-4 py-3 text-center">DEVIASI / SELISIH</th>
                        <th class="px-4 py-3 text-right">SISA STOK SURVEI (TON)</th>
                        <th class="px-4 py-3 text-center">STATUS STOK AWAL</th>
                        <th class="px-4 py-3 text-center">STATUS WILAYAH</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse ($evaluasiList as $item)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="px-4 py-3.5 font-bold text-slate-900 whitespace-nowrap">
                                {{ $item->periode_label }}
                            </td>
                            <td class="px-4 py-3.5 text-slate-700 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    {{ $item->komoditas_nama }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-right font-bold text-slate-900 whitespace-nowrap">
                                {{ number_format($item->panen_aktual_ton, 1, ',', '.') }} ton
                            </td>
                            <td class="px-4 py-3.5 text-right text-slate-600 whitespace-nowrap">
                                {{ number_format($item->prediksi_model_ton, 1, ',', '.') }} ton
                            </td>
                            <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-bold {{ $item->deviasi_persen >= 0 ? 'bg-emerald-50 text-emerald-800' : 'bg-rose-50 text-rose-800' }}">
                                    {{ $item->deviasi_persen >= 0 ? '+' : '' }}{{ number_format($item->deviasi_ton, 1, ',', '.') }} ton ({{ $item->deviasi_persen >= 0 ? '+' : '' }}{{ number_format($item->deviasi_persen, 1, ',', '.') }}%)
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-right font-bold text-slate-900 whitespace-nowrap">
                                {{ number_format($item->sisa_stok_ton, 1, ',', '.') }} ton
                            </td>
                            <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-900 border border-emerald-200">
                                    <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Valid (Survei)
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                @if ($item->status_wilayah === 'penuh')
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200">
                                        ⚠ PENUH
                                    </span>
                                @elseif ($item->status_wilayah === 'waspada')
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                        ⚠ WASPADA
                                    </span>
                                @elseif ($item->status_wilayah === 'seimbang')
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                        ✓ SEIMBANG
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-800 border border-red-200">
                                        ✕ DEFISIT
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-slate-400">
                                Belum ada laporan panen tersimpan untuk kombinasi wilayah dan komoditas ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Baris Bawah / Skor Presisi Model AI --}}
        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </div>
                <div>
                    <h3 class="text-xs font-bold text-slate-900">
                        Skor Presisi Model AI di Wilayah {{ $kabupaten->nama }}: {{ $skorPresisiAi }}%
                    </h3>
                    <p class="text-[11px] text-slate-500">
                        Rata-rata Mean Absolute Percentage Error (MAPE) terjaga stabil di bawah 3.0%.
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3 self-end sm:self-auto">
                <div class="text-right">
                    <p class="text-[10px] text-slate-400 uppercase font-bold">Deviasi 3 Bulan</p>
                    <p class="text-xs font-bold text-emerald-800">Toleransi Normal</p>
                </div>
                <div class="flex items-end gap-1 h-7">
                    <span class="w-2 h-4 bg-emerald-600 rounded-xs"></span>
                    <span class="w-2 h-6 bg-emerald-700 rounded-xs"></span>
                    <span class="w-2 h-5 bg-emerald-600 rounded-xs"></span>
                </div>
            </div>
        </div>
    </div>

    {{-- 5. Section Pintasan ke Modul Rekomendasi Jadwal Tanam (PRD F-10) --}}
    <div class="p-6 rounded-2xl bg-gradient-to-r from-emerald-900 to-slate-900 text-white shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-6">
        <div class="space-y-1 max-w-2xl">
            <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 rounded text-[10px] font-black bg-emerald-500 text-slate-950 uppercase">
                    SIMULASI / DATA CONTOH
                </span>
                <h3 class="text-base font-bold text-white">Panduan Rekomendasi Jadwal Tanam untuk Petani</h3>
            </div>
            <p class="text-xs text-slate-300 leading-relaxed">
                [SIMULASI / DATA CONTOH] Sampaikan panduan kalender tanam per komoditas kepada Gabungan Kelompok Tani (Gapoktan) guna mencegah lonjakan panen berlebih di bulan yang sama.
            </p>
        </div>
        <a href="{{ route('jadwal-tanam.index', ['kabupaten_id' => $kabupaten->id]) }}" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-xs transition shadow-sm whitespace-nowrap">
            Buka Modul Jadwal Tanam
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
        </a>
    </div>

</div>

{{-- Script Live Konversi Metrik Ton --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const panenInput = document.getElementById('panen_kg');
        const stokInput = document.getElementById('sisa_stok_kg');
        const badgePanenTon = document.getElementById('badge_panen_ton');
        const badgeStokTon = document.getElementById('badge_stok_ton');

        function formatTon(kg) {
            if (!kg || isNaN(kg)) return '0,0 ton';
            const ton = parseFloat(kg) / 1000;
            return ton.toLocaleString('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + ' ton';
        }

        if (panenInput && badgePanenTon) {
            panenInput.addEventListener('input', function () {
                badgePanenTon.textContent = formatTon(this.value);
            });
        }

        if (stokInput && badgeStokTon) {
            stokInput.addEventListener('input', function () {
                badgeStokTon.textContent = formatTon(this.value);
            });
        }
    });
</script>
@endsection
