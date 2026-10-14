@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-[#f8fafc] py-6 sm:py-8 text-slate-800">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

    <!-- Subheader: Breadcrumb & Title & Action Tabs -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 border-b border-slate-200/90 pb-5">
      <div>
        <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs text-slate-500 font-medium mb-1">
          <a href="{{ route('home') }}" class="min-h-[44px] inline-flex items-center hover:text-emerald-700 transition">
            Beranda
          </a>
          <span>/</span>
          <span class="min-h-[44px] inline-flex items-center text-slate-900 font-bold">
            Portal BUMDes
          </span>
        </nav>
        <h1 class="text-xl sm:text-2xl lg:text-3xl font-extrabold text-slate-900 tracking-tight">
          Portal Manajemen Pangan BUMDes
        </h1>
        <div class="flex items-center gap-2 text-xs text-slate-600 mt-1 flex-wrap font-medium">
          <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
            ID: BUMDES-{{ $kabupaten->kode }}-AKTIF
          </span>
          <span class="text-slate-300">·</span>
          <span>Wilayah Terdaftar: <b class="text-slate-900 font-bold">{{ $kabupaten->nama }}, {{ $kabupaten->provinsi->nama ?? 'Jawa Tengah' }}</b></span>
          <span class="text-slate-300">·</span>
          <span class="text-slate-500">Terkunci ke 1 Kabupaten sesuai regulasi ketahanan pangan</span>
        </div>
      </div>

      <!-- Action Tabs -->
      <div class="flex items-center gap-2.5 self-start lg:self-center flex-wrap">
        <span class="min-h-[44px] inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-emerald-800 shadow-2xs">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          <span>Input Panen & Stok</span>
        </span>
        <a href="{{ route('jadwal-tanam.index', ['kabupaten_id' => $kabupaten->id]) }}"
           class="min-h-[44px] inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 shadow-2xs transition">
          <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
          <span>Jadwal Tanam</span>
        </a>
      </div>
    </div>

    {{-- Flash Message Sukses --}}
    @if (session('sukses'))
      <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-xs font-bold text-emerald-900 flex items-center gap-2 shadow-2xs">
        <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <div>
          <span class="font-bold">Laporan Berhasil Disimpan:</span>
          <span class="font-normal ml-1">{{ session('sukses') }}</span>
        </div>
      </div>
    @endif

    {{-- Banner Status Wilayah (Ringkas 1-2 Baris Sesuai Mockup) --}}
    @if ($statusLevel === 'penuh')
      <div class="rounded-2xl bg-purple-50/90 border border-purple-200 p-4 sm:p-5 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-start sm:items-center gap-3.5">
          <div class="w-10 h-10 rounded-xl bg-purple-600 text-white flex items-center justify-center font-bold text-base shrink-0">
            ◆
          </div>
          <div>
            <div class="flex items-center gap-2 flex-wrap">
              <h2 class="text-xs sm:text-sm font-extrabold text-purple-950 uppercase tracking-wide">
                Peringatan Kapasitas Lumbung: Status PENUH / SURPLUS
              </h2>
              <span class="px-2 py-0.5 rounded text-xs font-bold bg-purple-200 text-purple-900">
                Kapasitas: {{ $rasioKapasitas }}%
              </span>
            </div>
            <p class="text-xs text-purple-900 font-medium mt-0.5 max-w-3xl leading-relaxed">
              Hasil agregasi survei lumbung per {{ $periodeDisplay }} menembus {{ number_format($stokAkhirRiilTon, 1, ',', '.') }} ton. Ambang batas aman terlampaui sehingga tindakan redistribusi disiagakan.
            </p>
          </div>
        </div>
        <div class="shrink-0 self-start md:self-center">
          @can('create', App\Models\Shipment::class)
            <a href="{{ route('pengiriman.create', ['asal_kabupaten_id' => $kabupaten->id, 'komoditas_id' => $komoditas->id]) }}"
               class="min-h-[44px] inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white hover:bg-purple-100 text-purple-900 font-bold text-xs border border-purple-300 shadow-2xs transition">
              <svg class="w-4 h-4 text-purple-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
              <span>Minta Disposisi Logistik</span>
            </a>
          @else
            <div class="min-h-[44px] inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-purple-100 text-purple-900 text-xs font-bold border border-purple-200">
              <svg class="w-4 h-4 text-purple-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              <span>Disposisi via Operator Terdaftar</span>
            </div>
          @endcan
        </div>
      </div>
    @elseif ($statusLevel === 'waspada')
      <div class="rounded-2xl bg-amber-50/90 border border-amber-200 p-4 sm:p-5 shadow-2xs flex items-center gap-3.5">
        <div class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center font-bold text-base shrink-0">
          ▲
        </div>
        <div>
          <div class="flex items-center gap-2 flex-wrap">
            <h2 class="text-xs sm:text-sm font-extrabold text-amber-950 uppercase tracking-wide">
              Peringatan Dini: Status Wilayah Waspada Pasokan
            </h2>
            <span class="px-2 py-0.5 rounded text-xs font-bold bg-amber-200 text-amber-900">
              Kapasitas: {{ $rasioKapasitas }}%
            </span>
          </div>
          <p class="text-xs text-amber-900 font-medium mt-0.5 leading-relaxed">
            Kapasitas penyangga mendekati ambang batas. Pantau pengisian lumbung secara berkala bersama Gapoktan.
          </p>
        </div>
      </div>
    @endif

    {{-- 4 Kartu KPI Summary Strip Horizontal --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
      <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-2xs border border-slate-200/90 flex flex-col justify-between">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-600">Stok Akhir Riil (Survei)</span>
        <div class="mt-2 text-2xl font-black text-slate-900 tracking-tight tabular-nums">
          {{ number_format($stokAkhirRiilTon, 1, ',', '.') }} <span class="text-sm font-bold text-slate-500">ton</span>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-2xs border border-slate-200/90 flex flex-col justify-between">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-600">Hasil Panen Riil Bulan Ini</span>
        <div class="mt-2 text-2xl font-black text-slate-900 tracking-tight tabular-nums">
          {{ number_format($hasilPanenRiilTon, 1, ',', '.') }} <span class="text-sm font-bold text-slate-500">ton</span>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-2xs border border-slate-200/90 flex flex-col justify-between">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-600">Status Wilayah Aktual</span>
        <div class="mt-2">
          @if ($statusLevel === 'penuh')
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-bold uppercase border bg-purple-100 text-purple-900 border-purple-200">
              <span>◆</span>
              <span>Penuh / Surplus</span>
            </span>
          @elseif ($statusLevel === 'waspada')
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-bold uppercase border bg-amber-100 text-amber-900 border-amber-200">
              <span>▲</span>
              <span>Waspada</span>
            </span>
          @elseif ($statusLevel === 'seimbang')
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-bold uppercase border bg-emerald-100 text-emerald-900 border-emerald-200">
              <span>✔</span>
              <span>Seimbang</span>
            </span>
          @else
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-bold uppercase border bg-rose-100 text-rose-900 border-rose-200">
              <span>▼</span>
              <span>Defisit</span>
            </span>
          @endif
        </div>
      </div>

      <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-2xs border border-slate-200/90 flex flex-col justify-between">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-600">Komoditas Terpilih</span>
        <div class="mt-2 text-lg sm:text-xl font-black text-slate-900 tracking-tight">
          {{ $komoditas->nama }}
        </div>
      </div>
    </div>

    {{-- Filter Bar Khusus Administrator --}}
    @if (auth()->user()->role?->kode === 'admin')
      <div class="p-4 rounded-2xl bg-amber-50/90 border border-amber-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
        <div class="flex items-center gap-2 text-amber-950 font-medium">
          <svg class="w-4 h-4 text-amber-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <span><b>Mode Administrator:</b> Pilih wilayah untuk meninjau atau mencatat laporan atas nama BUMDes.</span>
        </div>
        <form method="GET" action="{{ route('panen.index') }}" class="flex items-center gap-2 w-full sm:w-auto">
          <input type="hidden" name="komoditas_id" value="{{ $komoditas->id }}">
          <input type="hidden" name="periode" value="{{ $periodeParam }}">
          <select name="kabupaten_id"
                  aria-label="Pilih wilayah kabupaten"
                  onchange="this.form.submit()"
                  class="w-full sm:w-auto min-h-[44px] px-3.5 py-2 rounded-xl border border-amber-300 bg-white text-xs font-bold text-slate-900 focus:outline-none focus:ring-1 focus:ring-amber-500">
            @foreach ($semuaKabupaten as $kab)
              <option value="{{ $kab->id }}" {{ $kab->id == $kabupaten->id ? 'selected' : '' }}>
                {{ $kab->nama }} ({{ $kab->kode }})
              </option>
            @endforeach
          </select>
        </form>
      </div>
    @endif

    {{-- Main Grid: Form Input Survei (Kiri) vs Info Lumbung (Kanan) --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

      {{-- Kolom Kiri: Formulir Input Hasil Panen & Sisa Stok (7/12) --}}
      <div class="lg:col-span-7 bg-white rounded-2xl shadow-2xs border border-slate-200/90 overflow-hidden">
        <div class="p-5 sm:p-6 border-b border-slate-100 flex items-center justify-between">
          <div class="flex items-center gap-2.5">
            <svg class="w-5 h-5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <h2 class="text-base sm:text-lg font-extrabold text-slate-900 tracking-tight">
              Formulir Input Hasil Panen & Sisa Stok
            </h2>
          </div>
          <span class="px-2.5 py-1 rounded-md text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
            Survei Bulanan
          </span>
        </div>

        {{-- Filter Row: Periode & Komoditas --}}
        <form method="GET" action="{{ route('panen.index') }}" id="filterForm" class="p-5 sm:p-6 pb-0 grid grid-cols-1 sm:grid-cols-2 gap-4">
          @if (auth()->user()->role?->kode === 'admin')
            <input type="hidden" name="kabupaten_id" value="{{ $kabupaten->id }}">
          @endif
          <div>
            <label for="filter_periode" class="block text-xs font-bold text-slate-800 uppercase tracking-wide mb-1.5">
              Periode Laporan Bulanan
            </label>
            <select name="periode"
                    id="filter_periode"
                    aria-label="Pilih periode laporan bulanan"
                    onchange="document.getElementById('filterForm').submit()"
                    class="block w-full min-h-[44px] px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 text-xs font-semibold text-slate-900 focus:bg-white focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 transition">
              @foreach ($periodeOptions as $val => $label)
                <option value="{{ $val }}" {{ $val === $periodeParam ? 'selected' : '' }}>
                  {{ $label }}
                </option>
              @endforeach
            </select>
          </div>

          <div>
            <label for="filter_komoditas" class="block text-xs font-bold text-slate-800 uppercase tracking-wide mb-1.5">
              Komoditas Pangan
            </label>
            <select name="komoditas_id"
                    id="filter_komoditas"
                    aria-label="Pilih jenis komoditas pangan"
                    onchange="document.getElementById('filterForm').submit()"
                    class="block w-full min-h-[44px] px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 text-xs font-semibold text-slate-900 focus:bg-white focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 transition">
              @foreach ($komoditasList as $kom)
                <option value="{{ $kom->id }}" {{ $kom->id == $komoditas->id ? 'selected' : '' }}>
                  {{ $kom->nama }}
                </option>
              @endforeach
            </select>
          </div>
        </form>

        {{-- Form Input Utama --}}
        <form method="POST" action="{{ route('panen.store') }}" id="formLaporanPanen" class="p-5 sm:p-6 space-y-5">
          @csrf
          @if (auth()->user()->role?->kode === 'admin')
            <input type="hidden" name="kabupaten_id" value="{{ $kabupaten->id }}">
          @endif
          <input type="hidden" name="komoditas_id" value="{{ $komoditas->id }}">
          <input type="hidden" name="periode" value="{{ $periodeDate }}">

          {{-- Field Panen (kg) & Sisa Stok (kg) --}}
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            {{-- Input Hasil Panen Riil --}}
            <div>
              <div class="flex items-center justify-between mb-1.5">
                <label for="panen_kg" class="block text-xs font-bold text-slate-800 uppercase tracking-wide">
                  Hasil Panen Riil (kg)
                </label>
                <span id="badge_panen_ton" class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-bold bg-emerald-100 text-emerald-900 border border-emerald-200 tabular-nums">
                  = {{ number_format(($currentReport?->panen_kg ?? $hasilPanenRiilKg) / 1000, 1, ',', '.') }} ton
                </span>
              </div>
              <div class="relative rounded-xl shadow-2xs">
                <input type="number"
                       step="any"
                       min="0"
                       name="panen_kg"
                       id="panen_kg"
                       value="{{ old('panen_kg', $currentReport?->panen_kg ?? $hasilPanenRiilKg) }}"
                       required
                       placeholder="15200000"
                       class="block w-full min-h-[44px] px-3.5 pr-12 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 text-xs font-bold text-slate-900 focus:bg-white focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 transition tabular-nums" />
                <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-xs font-bold text-slate-600">
                  kg
                </div>
              </div>
            </div>

            {{-- Input Sisa Stok Akhir Bulan --}}
            <div>
              <div class="flex items-center justify-between mb-1.5">
                <label for="sisa_stok_kg" class="block text-xs font-bold text-slate-800 uppercase tracking-wide">
                  Sisa Stok Akhir (kg)
                </label>
                <span id="badge_stok_ton" class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-bold bg-emerald-100 text-emerald-900 border border-emerald-200 tabular-nums">
                  = {{ number_format(($currentReport?->sisa_stok_kg ?? $stokAkhirRiilKg) / 1000, 1, ',', '.') }} ton
                </span>
              </div>
              <div class="relative rounded-xl shadow-2xs">
                <input type="number"
                       step="any"
                       min="0"
                       name="sisa_stok_kg"
                       id="sisa_stok_kg"
                       value="{{ old('sisa_stok_kg', $currentReport?->sisa_stok_kg ?? $stokAkhirRiilKg) }}"
                       required
                       placeholder="3100000"
                       class="block w-full min-h-[44px] px-3.5 pr-12 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 text-xs font-bold text-slate-900 focus:bg-white focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 transition tabular-nums" />
                <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-xs font-bold text-slate-600">
                  kg
                </div>
              </div>
            </div>
          </div>

          {{-- Field Tanggal Survei & Catatan --}}
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label for="tanggal_survei" class="block text-xs font-bold text-slate-800 uppercase tracking-wide mb-1.5">
                Tanggal Pelaksanaan Survei
              </label>
              <input type="date"
                     name="tanggal_survei"
                     id="tanggal_survei"
                     value="{{ old('tanggal_survei', $currentReport?->tanggal_survei?->format('Y-m-d') ?? '2026-01-31') }}"
                     class="block w-full min-h-[44px] px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 text-xs font-semibold text-slate-900 focus:bg-white focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 transition" />
            </div>

            <div>
              <label for="catatan" class="block text-xs font-bold text-slate-800 uppercase tracking-wide mb-1.5">
                Catatan Lapangan & Lumbung
              </label>
              <input type="text"
                     name="catatan"
                     id="catatan"
                     value="{{ old('catatan', $currentReport?->catatan ?? 'Survei lumbung desa BUMDes ' . $kabupaten->nama) }}"
                     placeholder="Survei lumbung desa BUMDes {{ $kabupaten->nama }}"
                     class="block w-full min-h-[44px] px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 text-xs font-medium text-slate-900 focus:bg-white focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 transition" />
            </div>
          </div>

          {{-- Action Buttons --}}
          <div class="pt-2 flex flex-col sm:flex-row sm:items-center sm:justify-end gap-3 border-t border-slate-100">
            <button type="reset"
                    class="min-h-[44px] px-4 py-2.5 rounded-xl text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 transition">
              Reset Nilai
            </button>
            <button type="submit"
                    class="min-h-[44px] inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 shadow-2xs transition">
              <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
              <span>Kirim & Hitung Ulang Status Wilayah</span>
            </button>
          </div>
        </form>
      </div>

      {{-- Kolom Kanan: Info Lumbung & Neraca Pangan (5/12) --}}
      <div class="lg:col-span-5 space-y-6">

        {{-- Card Status Lumbung & Saldo Pangan --}}
        <div class="bg-white rounded-2xl p-5 sm:p-6 shadow-2xs border border-slate-200/90 space-y-4">
          <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
            <svg class="w-5 h-5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            <h3 class="text-base font-extrabold text-slate-900 tracking-tight">
              Lumbung Pangan {{ $kabupaten->nama }}
            </h3>
          </div>

          <dl class="space-y-3 text-xs">
            <div class="flex justify-between items-center py-1 border-b border-slate-100">
              <dt class="text-slate-500">Saldo Stok Awal Bulan Depan:</dt>
              <dd class="font-bold text-slate-900 tabular-nums">
                {{ number_format($stokAkhirRiilTon, 1, ',', '.') }} ton
              </dd>
            </div>
            <div class="flex justify-between items-center py-1 border-b border-slate-100">
              <dt class="text-slate-500">Rasio Keterisian Penyangga:</dt>
              <dd class="font-bold text-slate-900 tabular-nums">
                {{ $rasioKapasitas }}%
              </dd>
            </div>
            <div class="flex justify-between items-center py-1 border-b border-slate-100">
              <dt class="text-slate-500">Hasil Panen Riil Tercatat:</dt>
              <dd class="font-bold text-emerald-800 tabular-nums">
                {{ number_format($hasilPanenRiilTon, 1, ',', '.') }} ton
              </dd>
            </div>
            <div class="flex justify-between items-center py-1">
              <dt class="text-slate-500">Status Saldo Logistik:</dt>
              <dd class="font-bold text-slate-800">
                Data Survei Terverifikasi
              </dd>
            </div>
          </dl>
        </div>

        {{-- Card Pintasan ke Jadwal Tanam --}}
        <div class="bg-gradient-to-br from-slate-900 to-emerald-950 text-white rounded-2xl p-5 sm:p-6 shadow-2xs space-y-3">
          <div class="flex items-center gap-2 flex-wrap">
            <span class="px-2 py-0.5 rounded text-xs font-extrabold bg-emerald-500 text-slate-950 uppercase tracking-wide">
              SIMULASI / DATA CONTOH
            </span>
            <span class="text-xs text-slate-300 font-medium">Mitra Gapoktan</span>
          </div>
          <h3 class="text-sm sm:text-base font-extrabold text-white tracking-tight">
            Panduan Rekomendasi Jadwal Tanam untuk Petani
          </h3>
          <p class="text-xs text-slate-300 leading-relaxed font-medium">
            [SIMULASI / DATA CONTOH] Sampaikan panduan kalender tanam per komoditas kepada Gapoktan guna mencegah lonjakan panen berlebih.
          </p>
          <div class="pt-1">
            <a href="{{ route('jadwal-tanam.index', ['kabupaten_id' => $kabupaten->id]) }}"
               class="min-h-[44px] w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-800 hover:bg-emerald-900 text-white font-bold text-xs transition shadow-2xs">
              <span>Buka Modul Jadwal Tanam</span>
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </a>
          </div>
        </div>

      </div>

    </div>

    {{-- Tabel Rekapitulasi Laporan Panen & Evaluasi Prediksi --}}
    <div class="bg-white rounded-2xl shadow-2xs border border-slate-200/90 overflow-hidden space-y-4">
      <div class="p-5 sm:p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h3 class="text-base sm:text-lg font-extrabold text-slate-900 tracking-tight">
            Rekapitulasi Laporan Panen Bulanan & Evaluasi Prediksi
          </h3>
          <p class="text-xs text-slate-500 mt-0.5 font-medium">
            Evaluasi perbandingan akurasi model prediktif pasokan dengan pelaporan survei lapangan bulanan BUMDes {{ $kabupaten->nama }}.
          </p>
        </div>

        <div class="flex items-center gap-2.5 self-start sm:self-center">
          <a href="{{ route('panen.export-csv', ['kabupaten_id' => $kabupaten->id, 'komoditas_id' => $komoditas->id]) }}"
             class="min-h-[44px] inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold transition">
            <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            <span>Unduh CSV Rekap</span>
          </a>
          <button type="button"
                  onclick="window.print()"
                  class="min-h-[44px] inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold transition">
            <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            <span>Cetak Laporan</span>
          </button>
        </div>
      </div>

      {{-- Table --}}
      <div class="overflow-x-auto" tabindex="0" role="region" aria-label="Tabel Rekapitulasi Laporan Panen">
        <table class="w-full text-left text-xs text-slate-600">
          <thead class="bg-slate-50/90 text-slate-700 uppercase font-bold text-xs tracking-wider border-b border-slate-200">
            <tr>
              <th class="px-5 py-3.5">Bulan</th>
              <th class="px-4 py-3.5">Komoditas</th>
              <th class="px-4 py-3.5 text-right">Panen Aktual</th>
              <th class="px-4 py-3.5 text-right">Prediksi Model</th>
              <th class="px-4 py-3.5 text-center">Selisih Deviasi</th>
              <th class="px-4 py-3.5 text-right">Sisa Stok</th>
              <th class="px-5 py-3.5 text-center">Status Wilayah</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 font-medium">
            @forelse ($evaluasiList as $item)
              <tr class="hover:bg-slate-50/80 transition">
                <td class="px-5 py-3.5 font-bold text-slate-900 whitespace-nowrap tabular-nums">
                  {{ $item->periode_label }}
                </td>
                <td class="px-4 py-3.5 text-slate-800 whitespace-nowrap">
                  {{ $item->komoditas_nama }}
                </td>
                <td class="px-4 py-3.5 text-right font-bold text-slate-900 whitespace-nowrap tabular-nums">
                  {{ number_format($item->panen_aktual_ton, 1, ',', '.') }} ton
                </td>
                <td class="px-4 py-3.5 text-right text-slate-600 whitespace-nowrap tabular-nums">
                  {{ number_format($item->prediksi_model_ton, 1, ',', '.') }} ton
                </td>
                <td class="px-4 py-3.5 text-center whitespace-nowrap">
                  <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-bold tabular-nums {{ $item->deviasi_persen >= 0 ? 'bg-emerald-50 text-emerald-800' : 'bg-rose-50 text-rose-800' }}">
                    {{ $item->deviasi_persen >= 0 ? '+' : '' }}{{ number_format($item->deviasi_ton, 1, ',', '.') }} ton ({{ $item->deviasi_persen >= 0 ? '+' : '' }}{{ number_format($item->deviasi_persen, 1, ',', '.') }}%)
                  </span>
                </td>
                <td class="px-4 py-3.5 text-right font-bold text-slate-900 whitespace-nowrap tabular-nums">
                  {{ number_format($item->sisa_stok_ton, 1, ',', '.') }} ton
                </td>
                <td class="px-5 py-3.5 text-center whitespace-nowrap">
                  @if ($item->status_wilayah === 'penuh')
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded text-xs font-bold uppercase border bg-purple-100 text-purple-900 border-purple-200">
                      <span>◆</span><span>Penuh</span>
                    </span>
                  @elseif ($item->status_wilayah === 'waspada')
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded text-xs font-bold uppercase border bg-amber-100 text-amber-900 border-amber-200">
                      <span>▲</span><span>Waspada</span>
                    </span>
                  @elseif ($item->status_wilayah === 'seimbang')
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded text-xs font-bold uppercase border bg-emerald-100 text-emerald-900 border-emerald-200">
                      <span>✔</span><span>Seimbang</span>
                    </span>
                  @else
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded text-xs font-bold uppercase border bg-rose-100 text-rose-900 border-rose-200">
                      <span>▼</span><span>Defisit</span>
                    </span>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="px-5 py-8 text-center text-slate-500 italic">
                  Belum ada laporan panen tersimpan untuk kombinasi wilayah dan komoditas ini.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      {{-- Footer Presisi Model --}}
      <div class="p-4 sm:p-5 bg-slate-50/80 border-t border-slate-200/90 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs">
        <div class="flex items-center gap-2 text-slate-700">
          <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <span>Skor Presisi Model Pasokan di Wilayah {{ $kabupaten->nama }}: <b class="text-slate-900 font-bold tabular-nums">{{ $skorPresisiAi }}%</b></span>
        </div>
        <div class="text-slate-500 font-medium">
          Toleransi deviasi bulanan dalam batas normal
        </div>
      </div>
    </div>

  </div>
</div>

{{-- Script Live Konversi Metrik Ton --}}
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const panenInput = document.getElementById('panen_kg');
  const stokInput = document.getElementById('sisa_stok_kg');
  const badgePanenTon = document.getElementById('badge_panen_ton');
  const badgeStokTon = document.getElementById('badge_stok_ton');

  function formatTon(kg) {
    if (!kg || isNaN(kg)) return '= 0,0 ton';
    const ton = parseFloat(kg) / 1000;
    return '= ' + ton.toLocaleString('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + ' ton';
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
@endpush
@endsection
