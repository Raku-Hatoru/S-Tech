@php
  use App\Support\IndoFormat;
@endphp
@extends('layouts.app')

@push('styles')
{{-- Leaflet CSS --}}
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
<style>
  :root {
    --status-defisit: #ef4444;
    --status-seimbang: #16a34a;
    --status-waspada: #f59e0b;
    --status-penuh: #8b5cf6;
    --status-tanpa-data: #64748b;
  }

  /* ── Map Canvas Styling ───────────────────────────── */
  #peta-map {
    width: 100%;
    height: 100%;
    min-height: 520px;
    background: #080f1d;
    border-radius: 14px;
    overflow: hidden;
    outline: none;
    flex: 1 1 auto;
  }
  @media (max-width: 1024px) {
    #peta-map {
      min-height: 480px;
    }
  }
  @media (max-width: 640px) {
    #peta-map {
      min-height: 380px;
    }
  }

  .leaflet-dark-tooltip {
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
    padding: 0 !important;
  }
  .leaflet-dark-tooltip::before { display: none !important; }

  /* Custom Scrollbar */
  ::-webkit-scrollbar { width: 5px; height: 5px; }
  ::-webkit-scrollbar-track { background: transparent; }
  ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
</style>
@endpush

@section('content')
<div class="min-h-screen bg-[#f8fafc] py-3 sm:py-4">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-3 sm:space-y-3.5">

    <!-- Header Ringkas (Title + Subtitle 1 Baris + Tombol Aksi dalam 1 Baris di Desktop) -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
      <div class="min-w-0">
        <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
          Peta Heatmap &amp; Early-Warning Surplus Pangan Nasional
        </h1>
        <p class="text-xs text-slate-600 font-medium mt-0.5 truncate">
          Diagnostik rasio pasokan vs konsumsi wilayah untuk mitigasi pembusukan gabah dan pemborosan pangan.
        </p>
      </div>

      <div class="flex items-center gap-3 shrink-0 flex-wrap">
        <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 select-none py-1">
          <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
          Data Contoh: BAPANAS &amp; BPS
        </span>
        <button type="button" onclick="bukaModalTentangData()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold transition shadow-2xs border border-slate-200" title="Informasi metodologi, sumber data acuan, dan pemodelan">
          <svg class="w-3.5 h-3.5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16v-4m0-4h.01"/></svg>
          Tentang Data
        </button>
        <button type="button" onclick="window.print()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition shadow-xs">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
          Unduh Buletin (.PDF)
        </button>
      </div>
    </div>

    <!-- Filter Control Bar (4 Kolom Sesuai Mockup) -->
    <div class="bg-white rounded-2xl p-3 sm:p-3.5 border border-slate-200/90 shadow-2xs grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-center">
      <!-- 1. Komoditas Pangan -->
      <div>
        <label for="select-komoditas" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1 flex items-center gap-1.5">
          <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
          Komoditas Pangan
        </label>
        <div class="relative">
          <select id="select-komoditas" aria-label="Pilih Komoditas Pangan" class="w-full bg-slate-50 hover:bg-slate-100/80 border border-slate-200 rounded-xl px-3.5 py-2 text-xs font-bold text-slate-800 appearance-none outline-none cursor-pointer pr-9 transition">
            @foreach($komoditasList as $k)
              <option value="{{ $k->kode }}" {{ $loop->first ? 'selected' : '' }}>
                {{ $k->kode === 'beras' ? 'Beras Premium & Medium' : ucfirst($k->nama) }}
              </option>
            @endforeach
          </select>
          <svg class="w-4 h-4 text-slate-400 absolute right-3 top-2.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </div>
      </div>

      <!-- 2. Periode Proyeksi (12 Bulan ke Depan) -->
      <div>
        <div class="flex items-center justify-between mb-1">
          <label for="select-periode" class="block text-xs font-bold text-slate-600 uppercase tracking-wider">
            Periode Waktu
          </label>
          <span class="text-xs text-slate-500 font-medium inline-flex items-center gap-1 cursor-help select-none" title="Horizon proyeksi model hingga 12 bulan ke depan (Januari – Desember 2026)">
            Proyeksi 12 bulan
            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </span>
        </div>
        <div class="flex items-center gap-1.5">
          <select id="select-periode" aria-label="Pilih Periode Waktu" class="flex-1 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 outline-none cursor-pointer">
            @foreach($periodesList->reverse() as $p)
              <option value="{{ $p['kode'] }}" {{ $p['kode'] === $periodeDefault ? 'selected' : '' }}>
                {{ $p['label'] }} ({{ $p['tipe'] }})
              </option>
            @endforeach
          </select>
          <button type="button"
                  id="btn-bulan-ini"
                  onclick="pilihBulanBerjalan()"
                  class="px-2.5 py-2 rounded-xl bg-emerald-100 text-emerald-800 text-xs font-bold hover:bg-emerald-200 transition shrink-0 disabled:opacity-40 disabled:cursor-not-allowed select-none"
                  title="Pindah ke bulan kalender saat ini ({{ \Carbon\Carbon::parse($bulanBerjalan.'-01')->isoFormat('MMMM YYYY') }})">
            Ke bulan berjalan
          </button>
        </div>
      </div>

      <!-- 3. Level Agregasi Geografis -->
      <div>
        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1">
          Level Agregasi Geografis
        </label>
        <div class="flex bg-slate-100 p-1 rounded-xl border border-slate-200">
          <button type="button" id="btn-toggle-prov" onclick="switchLayerLevel('provinsi')" class="flex-1 py-1 rounded-lg text-xs font-bold transition text-slate-600 hover:text-slate-900">
            Level Provinsi
          </button>
          <button type="button" id="btn-toggle-kab" onclick="switchLayerLevel('kabupaten')" class="flex-1 py-1 rounded-lg text-xs font-bold transition bg-white text-slate-900 shadow-2xs">
            Level Kabupaten/Kota
          </button>
        </div>
      </div>

      <!-- 4. Pencarian Teritorial Cepat -->
      <div class="relative">
        <label for="input-search-wilayah" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1 flex items-center gap-1.5">
          <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          Pencarian Teritorial Cepat
        </label>
        <div class="relative">
          <input type="text" id="input-search-wilayah" placeholder="Ketik Banyumas, Brebes, dll..." autocomplete="off"
                 class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-3.5 py-2 text-xs font-medium text-slate-800 placeholder-slate-500 outline-none focus:border-emerald-600 focus:bg-white transition">
          <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          <div id="search-dropdown" class="absolute top-full left-0 right-0 mt-1.5 bg-white border border-slate-200 rounded-xl shadow-lg z-50 max-h-60 overflow-y-auto hidden"></div>
        </div>
      </div>
    </div>

    <!-- Banner Peringatan Dini Nasional (Ringkas 1 Baris dengan Tombol Tutup) -->
    <div id="banner-nasional" class="px-3.5 py-2 sm:py-2.5 rounded-xl bg-rose-50/90 border border-rose-200/90 flex items-center justify-between gap-3 text-xs shadow-2xs">
      <div class="flex items-center gap-2.5 min-w-0">
        <div class="w-6 h-6 rounded-lg bg-rose-100 text-rose-700 flex items-center justify-center shrink-0">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        </div>
        <div class="min-w-0 flex items-center gap-1.5 flex-wrap">
          <span class="font-bold text-rose-950">
            <span class="font-black text-rose-800 uppercase tracking-wide text-xs mr-1 px-1.5 py-0.5 rounded bg-rose-200">Peringatan</span>
            <span id="banner-nasional-text">{{ $ringkasan['banner_text'] ?? 'Status Pangan Nasional Terpantau: 23 Kabupaten Surplus/Penuh, 10 Waspada, 0 Defisit. Selisih Bersih Pasokan: +611.523,5 ton (+123,7% di atas kebutuhan). Pantauan mencakup 34 Kabupaten Percontohan.' }}</span>
          </span>
          <span class="hidden md:inline text-rose-900 text-xs truncate">
            &mdash; Distribusi pasokan darurat direkomendasikan segera dialihkan ke lumbung wilayah defisit pangan.
          </span>
        </div>
      </div>
      <div class="flex items-center gap-2 shrink-0">
        @if(auth()->check() && (auth()->user()->isDistributor() || auth()->user()->isAdmin()))
          <a href="{{ route('pengiriman.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-[#831843] hover:bg-[#701a36] text-white text-xs font-bold shadow-xs transition">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
            Disposisi Logistik
          </a>
        @endif
        <button type="button" onclick="tutupBannerNasional()" aria-label="Tutup" class="p-1 rounded-lg text-rose-600 hover:text-rose-900 hover:bg-rose-100 transition" title="Tutup peringatan">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>
    </div>

    <!-- Main Content: Peta Heatmap (Kiri 65%) + Panel Wilayah Terpilih (Kanan 35%) -->
    <div id="peta-content-grid" class="grid grid-cols-1 lg:grid-cols-12 gap-5 lg:items-stretch">

      <!-- Kolom Kiri: Peta Leaflet (lg:col-span-7 atau 8) -->
      <div id="map-card" class="lg:col-span-7 xl:col-span-8 bg-[#0b1329] rounded-2xl p-4 sm:p-5 border border-slate-800 shadow-md flex flex-col h-full">
        <div class="relative rounded-xl overflow-hidden flex-1 flex flex-col min-h-[520px]">
          <!-- Floating Header Map Controls -->
          <div class="absolute top-4 left-4 right-4 z-20 flex items-center justify-between pointer-events-none gap-2 flex-wrap">
            <div class="pointer-events-auto inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-900/90 backdrop-blur-md border border-slate-700 text-xs font-bold text-white shadow-xs">
              <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
              <span><span class="font-extrabold text-white">Peta:</span> <span class="font-medium text-slate-200">rasio pasokan terhadap kebutuhan</span></span>
              <button type="button" onclick="bukaModalTentangData()" class="ml-1 text-slate-300 hover:text-white transition" title="Informasi tentang data dan metodologi" aria-label="Informasi tentang data dan metodologi">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16v-4m0-4h.01"/></svg>
              </button>
            </div>
            <div class="pointer-events-auto flex items-center gap-2">
              <button type="button" onclick="resetViewNasional()" class="px-3 py-1.5 rounded-lg bg-slate-900/90 backdrop-blur-md border border-slate-700 text-xs font-bold text-white hover:bg-slate-800 transition flex items-center gap-1.5 shadow-xs" title="Kembalikan tampilan peta ke seluruh wilayah Indonesia">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                Reset Nasional
              </button>
            </div>
          </div>

          <!-- Canvas Peta -->
          <div id="peta-map" class="w-full flex-1 min-h-[520px]"></div>

          <!-- Floating Footer Map Stats -->
          <div class="absolute bottom-4 left-4 right-4 z-20 flex items-center justify-between pointer-events-none gap-2 flex-wrap">
            <div class="pointer-events-auto inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-900/90 backdrop-blur-md border border-slate-700 text-xs font-medium text-white shadow-xs">
              <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
              <span><span class="font-bold text-white">Proyeksi:</span> <span class="text-slate-200">model prakiraan (selisih rata-rata ±3,8%)</span></span>
              <button type="button" onclick="bukaModalTentangData()" class="ml-1 text-emerald-300 hover:text-emerald-200 transition underline font-bold" title="Detail model & sumber data">
                Tentang data
              </button>
            </div>
            <div class="pointer-events-auto flex items-center gap-1.5">
              <button type="button" onclick="map.zoomIn()" aria-label="Perbesar peta" class="w-8 h-8 rounded-lg bg-slate-900/90 text-white font-bold border border-slate-700 hover:bg-slate-800 flex items-center justify-center text-base shadow-xs">+</button>
              <button type="button" onclick="map.zoomOut()" aria-label="Perkecil peta" class="w-8 h-8 rounded-lg bg-slate-900/90 text-white font-bold border border-slate-700 hover:bg-slate-800 flex items-center justify-center text-base shadow-xs">-</button>
            </div>
          </div>

          <!-- Loading Overlay -->
          <div id="peta-loading" class="absolute inset-0 bg-[#0b1329]/90 backdrop-blur-xs flex flex-col items-center justify-center z-30 transition-opacity duration-300">
            <div class="w-10 h-10 border-4 border-emerald-500/20 border-t-emerald-500 rounded-full animate-spin"></div>
            <div class="text-xs font-bold text-white mt-3 tracking-wide">Memuat Data Spasial & Status...</div>
          </div>
        </div>

        <!-- Docked Compact Legend Strip -->
        <div id="map-legend" class="mt-3 pt-3 border-t border-slate-800/80 flex flex-wrap items-center justify-between gap-2 text-xs">
          <div class="flex items-center gap-1.5 text-xs font-bold text-slate-300 uppercase tracking-wider">
            <svg class="w-3.5 h-3.5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
            <span>Legenda Status:</span>
          </div>
          <div class="flex items-center gap-3 sm:gap-4 flex-wrap text-xs">
            <div class="flex items-center gap-1.5" title="Rasio pasokan &lt; {{ round(($ambangDefisit ?? 0.70) * 100) }}%">
              <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: var(--status-defisit);"></span>
              <span class="font-bold text-rose-300">▼ Defisit</span>
              <span class="text-xs text-slate-300">(&lt;{{ round(($ambangDefisit ?? 0.70) * 100) }}%)</span>
            </div>
            <div class="flex items-center gap-1.5" title="Rasio pasokan {{ round(($ambangDefisit ?? 0.70) * 100) }}% - &lt; {{ round(($ambangWaspada ?? 0.90) * 100) }}%">
              <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: var(--status-seimbang);"></span>
              <span class="font-bold text-emerald-300">✔ Seimbang</span>
              <span class="text-xs text-slate-300">({{ round(($ambangDefisit ?? 0.70) * 100) }}%–&lt;{{ round(($ambangWaspada ?? 0.90) * 100) }}%)</span>
            </div>
            <div class="flex items-center gap-1.5" title="Rasio pasokan {{ round(($ambangWaspada ?? 0.90) * 100) }}% - &lt; {{ round(($ambangPenuh ?? 1.00) * 100) }}%">
              <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: var(--status-waspada);"></span>
              <span class="font-bold text-amber-300">▲ Waspada</span>
              <span class="text-xs text-slate-300">({{ round(($ambangWaspada ?? 0.90) * 100) }}%–&lt;{{ round(($ambangPenuh ?? 1.00) * 100) }}%)</span>
            </div>
            <div class="flex items-center gap-1.5" title="Rasio pasokan &ge; {{ round(($ambangPenuh ?? 1.00) * 100) }}%">
              <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: var(--status-penuh);"></span>
              <span class="font-bold text-purple-200">◆ Penuh / Surplus</span>
              <span class="text-xs text-slate-300">(&ge;{{ round(($ambangPenuh ?? 1.00) * 100) }}%)</span>
            </div>
            <div class="flex items-center gap-1.5" title="Wilayah di luar 34 kabupaten/kota percontohan">
              <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: var(--status-tanpa-data);"></span>
              <span class="font-medium text-slate-300">— Tanpa Data</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Kolom Kanan: Panel Wilayah Terpilih (lg:col-span-5 atau 4) -->
      <div id="side-panel-card" class="lg:col-span-5 xl:col-span-4 bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/90 shadow-sm flex flex-col justify-between h-full space-y-4 overflow-y-auto">
        <!-- Header Wilayah Terpilih -->
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div class="flex items-center gap-2">
            <span class="text-xs font-black uppercase tracking-wider text-slate-700 bg-slate-200/80 px-2 py-0.5 rounded">
              WILAYAH TERPILIH
            </span>
            <span id="panel-stok-badge" class="inline-flex items-center gap-1 text-xs font-semibold text-slate-600 select-none">
              <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              <span>Stok Terdata (Simulasi)</span>
            </span>
          </div>
          <span id="panel-kode" class="tabular-nums font-bold text-xs text-slate-600">33.02</span>
        </div>

        <div>
          <h2 id="panel-nama" class="text-2xl font-black text-slate-900 tracking-tight">
            Kabupaten Banyumas
          </h2>
          <p id="panel-provinsi" class="text-xs text-slate-600 font-medium mt-0.5">
            Provinsi Jawa Tengah • Kode Wilayah: 33.02
          </p>
        </div>

        <!-- Status Peringatan Pangan Box -->
        <div id="panel-status-box" class="p-4 rounded-xl bg-purple-50 border border-purple-200 flex items-center justify-between">
          <div>
            <div class="text-xs font-extrabold uppercase tracking-wider text-purple-950">STATUS PERINGATAN PANGAN</div>
            <div id="panel-status-label" class="text-xl font-black text-purple-950 mt-0.5">
              ◆ PENUH / SURPLUS
            </div>
            <div id="panel-rasio-label" class="text-xs font-bold text-purple-800 mt-0.5">
              Rasio Pasokan: 105,1% (Batas Aman: 100%)
            </div>
          </div>
          <div id="panel-status-icon-box" class="w-10 h-10 rounded-xl bg-purple-200/80 text-purple-800 flex items-center justify-center shrink-0 text-lg font-black">
            ◆
          </div>
        </div>

        <!-- Neraca Komoditas (Periode Terpilih) -->
        <div class="space-y-2.5">
          <div class="flex items-center justify-between text-xs">
            <span class="font-bold uppercase tracking-wider text-slate-600" id="panel-neraca-title">NERACA KOMODITAS BERAS</span>
            <span class="font-bold text-slate-700" id="panel-neraca-periode">Februari 2026</span>
          </div>

          <!-- Total Supply Card -->
          <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between">
            <div>
              <div class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                Total Supply (Pasokan)
              </div>
              <div class="text-xs font-medium text-slate-600 mt-0.5" id="panel-supply-raw">18.500.000 kg terdata</div>
            </div>
            <div class="text-lg font-black text-slate-900" id="panel-supply-formatted">18.500,0 ton</div>
          </div>

          <!-- Total Demand Card -->
          <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between">
            <div>
              <div class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                Total Demand (Kebutuhan)
              </div>
              <div class="text-xs font-medium text-slate-600 mt-0.5" id="panel-demand-raw">17.600.000 kg konsumsi</div>
            </div>
            <div class="text-lg font-black text-slate-900" id="panel-demand-formatted">17.600,0 ton</div>
          </div>

          <!-- Selisih Surplus Bersih Highlight -->
          <div class="flex items-center justify-between text-xs px-2 pt-1 font-bold">
            <span class="text-slate-700">Selisih Surplus Bersih</span>
            <span id="panel-selisih" class="text-rose-700 tabular-nums font-bold">+900,0 ton</span>
          </div>

          <!-- 3 Rincian Mini Card -->
          <div class="grid grid-cols-3 gap-2 pt-1">
            <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200 text-center">
              <div class="text-xs font-bold text-slate-700">Stok Awal</div>
              <div id="panel-stok-awal" class="text-xs font-black text-slate-900 mt-0.5">3.100,0 t</div>
              <div class="text-xs font-medium text-slate-600">Laporan Jan</div>
            </div>
            <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200 text-center">
              <div class="text-xs font-bold text-slate-700">Panen Aktual</div>
              <div id="panel-panen" class="text-xs font-black text-slate-900 mt-0.5">15.200,0 t</div>
              <div class="text-xs font-medium text-slate-600">Hasil Sawah</div>
            </div>
            <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200 text-center">
              <div class="text-xs font-bold text-slate-700">Pasokan Masuk</div>
              <div id="panel-pasokan-masuk" class="text-xs font-black text-slate-900 mt-0.5">200,0 t</div>
              <div class="text-xs font-medium text-slate-600">Distribusi Luar</div>
            </div>
          </div>
        </div>

        <!-- Peringatan Dampak Wilayah -->
        <div class="p-3.5 rounded-xl bg-amber-50/80 border border-amber-200 space-y-1.5 text-xs">
          <div class="font-extrabold uppercase text-amber-950 flex items-center gap-1.5 text-xs">
            <svg class="w-3.5 h-3.5 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            Peringatan Dampak Wilayah
          </div>
          <p id="panel-pesan-dampak" class="text-amber-950 leading-relaxed text-xs">
            Pangan berisiko basi atau terbuang dan menghasilkan emisi gas rumah kaca bila pasokan melebihi kapasitas serap wilayah.
          </p>
        </div>

        <!-- Tombol Aksi -->
        <div class="space-y-2 pt-1">
          <a id="btn-lihat-analisis" href="{{ route('wilayah.detail', ['kode' => '3302']) }}"
             class="w-full flex items-center justify-center gap-2 py-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition shadow-xs">
            <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            Lihat Analisis Detail 12 Bulan
          </a>
          @if(auth()->check() && (auth()->user()->isDistributor() || auth()->user()->isAdmin()))
            <a id="btn-buat-pengiriman" href="{{ route('pengiriman.index') }}"
               class="w-full flex items-center justify-center gap-2 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition shadow-xs">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
              Buat Rute Pengiriman Logistik
            </a>
          @endif
        </div>

        <div class="text-xs font-medium text-center text-slate-600 pt-1">
          Diperbarui otomatis tiap 15 menit
        </div>
      </div>
    </div>

    <!-- Indikator Konsolidasi Pangan (4 KPI Cards Riil Basis Data) -->
    <div class="space-y-3 pt-2">
      <div class="flex items-center gap-2 text-sm font-black text-slate-900 uppercase tracking-tight">
        <svg class="w-5 h-5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
        Indikator Konsolidasi Pangan (34 Wilayah Pantau)
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- KPI 1: Total Pasokan Terpantau -->
        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/90 shadow-2xs space-y-3">
          <div class="flex items-center justify-between">
            <div class="text-xs font-bold text-slate-600">Total Pasokan Terpantau (34 Wilayah)</div>
            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </div>
          </div>
          <div>
            <div class="text-2xl font-black text-slate-900 tracking-tight">
              <span id="kpi-total-supply" class="tabular-nums">{{ $ringkasan['total_supply_formatted'] ?? '1.105.796,7 ton' }}</span>
            </div>
            <div id="kpi-total-demand" class="text-xs font-bold text-emerald-700 mt-0.5 flex items-center gap-1">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
              <span>Kebutuhan: <span id="kpi-demand-val" class="tabular-nums">{{ $ringkasan['total_demand_formatted'] ?? '494.273,2 ton' }}</span></span>
              <span id="kpi-selisih-persen" class="tabular-nums">({{ $ringkasan['selisih_persen_formatted'] ?? '+123,7%' }})</span>
            </div>
          </div>
          <div>
            <div class="flex justify-between text-xs font-semibold text-slate-600 mb-1">
              <span>Rasio Pasokan vs Kebutuhan</span>
              <span id="kpi-rasio-total" class="text-slate-900 font-bold tabular-nums">
                {{ IndoFormat::percent(($ringkasan['total_supply_kg'] ?? 0) / max(1, ($ringkasan['total_demand_kg'] ?? 1)), 1, true) }}
              </span>
            </div>
            <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
              <div id="kpi-rasio-bar" class="bg-emerald-600 h-full rounded-full" style="width: {{ min(100, (($ringkasan['total_supply_kg'] ?? 0) / max(1, ($ringkasan['total_demand_kg'] ?? 1))) * 50) }}%"></div>
            </div>
          </div>
        </div>

        <!-- KPI 2: Wilayah Surplus / Penuh -->
        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/90 shadow-2xs space-y-3">
          <div class="flex items-center justify-between">
            <div class="text-xs font-bold text-slate-600">Wilayah Surplus / Penuh</div>
            <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-700 flex items-center justify-center">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
          </div>
          <div>
            <div class="text-2xl font-black text-slate-900 tracking-tight">
              <span id="kpi-jumlah-penuh" class="tabular-nums">{{ $ringkasan['jumlah_penuh'] ?? 23 }}</span>
              <span class="text-base font-bold text-slate-600">Kabupaten (<span id="kpi-persen-penuh" class="tabular-nums">{{ $ringkasan['persen_penuh_formatted'] ?? '67,6%' }}</span>)</span>
            </div>
            <div class="text-xs font-bold text-purple-700 mt-0.5">
              Akumulasi Surplus: <span id="kpi-total-surplus" class="tabular-nums">{{ IndoFormat::ton($ringkasan['total_surplus_kg'] ?? 0, 1, true) }}</span>
            </div>
          </div>
          <div>
            <div class="flex justify-between text-xs font-semibold text-slate-600 mb-1">
              <span>Rerata Rasio Wilayah Surplus</span>
              <span id="kpi-rerata-surplus" class="text-purple-900 font-bold tabular-nums">{{ $ringkasan['rerata_rasio_surplus_formatted'] ?? '274,4%' }}</span>
            </div>
            <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
              <div id="kpi-rerata-surplus-bar" class="bg-purple-600 h-full rounded-full" style="width: 100%"></div>
            </div>
          </div>
        </div>

        <!-- KPI 3: Wilayah Defisit & Waspada -->
        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/90 shadow-2xs space-y-3">
          <div class="flex items-center justify-between">
            <div class="text-xs font-bold text-slate-600">Wilayah Defisit &amp; Waspada</div>
            <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-700 flex items-center justify-center">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
          </div>
          <div>
            <div class="text-2xl font-black text-slate-900 tracking-tight">
              <span id="kpi-jumlah-defisit-waspada" class="tabular-nums">{{ $ringkasan['jumlah_defisit_waspada'] ?? 10 }}</span>
              <span class="text-base font-bold text-slate-600">Kabupaten (<span id="kpi-persen-defisit-waspada" class="tabular-nums">{{ $ringkasan['persen_defisit_waspada_formatted'] ?? '29,4%' }}</span>)</span>
            </div>
            <div class="text-xs font-bold text-amber-800 mt-0.5">
              Rincian: <span id="kpi-rincian-defisit">{{ $ringkasan['jumlah_defisit'] ?? 0 }} Defisit</span> &bull; <span id="kpi-rincian-waspada">{{ $ringkasan['jumlah_waspada'] ?? 10 }} Waspada</span>
            </div>
          </div>
          <div>
            <div class="flex justify-between text-xs font-semibold text-slate-600 mb-1">
              <span>Kekurangan Pasokan Riil</span>
              <span id="kpi-total-defisit" class="text-amber-900 font-bold tabular-nums">{{ $ringkasan['total_defisit_formatted'] ?? '0,0 ton' }}</span>
            </div>
            <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
              <div id="kpi-defisit-bar" class="bg-amber-500 h-full rounded-full" style="width: {{ ($ringkasan['jumlah_defisit'] ?? 0) > 0 ? '75%' : '0%' }}"></div>
            </div>
          </div>
        </div>

        <!-- KPI 4: Neraca Bersih Konsolidasi -->
        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/90 shadow-2xs space-y-3">
          <div class="flex items-center justify-between">
            <div class="text-xs font-bold text-slate-600">Neraca Bersih Konsolidasi</div>
            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-700 flex items-center justify-center">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
          </div>
          <div>
            <div class="text-2xl font-black text-slate-900 tracking-tight">
              <span id="kpi-selisih-bersih" class="tabular-nums">{{ $ringkasan['selisih_formatted'] ?? '+611.523,5 ton' }}</span>
            </div>
            <div class="text-xs font-bold text-slate-700 mt-0.5 flex items-center gap-1">
              <span id="kpi-nama-komoditas">Komoditas {{ $komoditasDefault->nama ?? 'Beras' }}</span> &bull; <span id="kpi-jumlah-seimbang">{{ $ringkasan['jumlah_seimbang'] ?? 1 }} Wilayah Seimbang</span>
            </div>
          </div>
          <div>
            <div class="flex justify-between text-xs font-semibold text-slate-600 mb-1">
              <span>Cakupan Wilayah Pantau</span>
              <span id="kpi-cakupan-wilayah" class="text-slate-900 font-bold tabular-nums">{{ $ringkasan['total_wilayah'] ?? 34 }} / 34 Kabupaten</span>
            </div>
            <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
              <div id="kpi-cakupan-bar" class="bg-emerald-600 h-full rounded-full" style="width: 100%"></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tombol Toggle Fallback Tabel Wilayah -->
    <div class="pt-2">
      <button type="button" onclick="toggleTabel()" id="btn-toggle-tabel"
              class="w-full py-3 rounded-2xl bg-white hover:bg-slate-50 border border-slate-200 text-xs font-bold text-slate-700 transition shadow-2xs flex items-center justify-center gap-2">
        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        <span>Tampilkan Tabel Rekapitulasi Status Seluruh Wilayah</span>
      </button>
    </div>

    <!-- Fallback Tabel Wilayah -->
    <div id="tabel-fallback" class="bg-white rounded-2xl p-5 border border-slate-200/90 shadow-sm hidden space-y-4">
      <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
          <h3 class="text-base font-bold text-slate-900">Tabel Rekapitulasi Status Wilayah</h3>
          <p class="text-xs text-slate-600 font-medium">Daftar tabular data neraca pangan per kabupaten/kota</p>
        </div>
        <input type="text" id="tabel-filter-input" placeholder="Cari kabupaten atau provinsi..."
               class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 placeholder-slate-500 w-64 outline-none focus:border-emerald-600 focus:bg-white transition">
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead class="bg-slate-50 text-slate-600 uppercase font-semibold border-b border-slate-200">
            <tr>
              <th class="px-4 py-3">Wilayah</th>
              <th class="px-4 py-3">Status Wilayah</th>
              <th class="px-4 py-3 text-right">Rasio</th>
              <th class="px-4 py-3 text-right">Pasokan (Supply)</th>
              <th class="px-4 py-3 text-right">Kebutuhan (Demand)</th>
              <th class="px-4 py-3 text-center">Aksi</th>
            </tr>
          </thead>
          <tbody id="tabel-body" class="divide-y divide-slate-100 font-medium text-slate-700">
            <tr><td colspan="6" class="px-4 py-6 text-center text-slate-600 font-medium">Memuat data tabel...</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Tentang Data -->
    <div id="modal-tentang-data" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-tentang-title" role="dialog" aria-modal="true">
      <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" onclick="tutupModalTentangData()"></div>
      <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
        <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl border border-slate-200">
          <div class="bg-slate-900 px-6 py-4 flex items-center justify-between text-white">
            <div class="flex items-center gap-2.5">
              <div class="w-8 h-8 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16v-4m0-4h.01"/></svg>
              </div>
              <div>
                <h3 class="text-base font-bold text-white" id="modal-tentang-title">Tentang Data &amp; Metodologi Sistem</h3>
                <p class="text-xs text-slate-300">Transparansi sumber data, model prakiraan, dan acuan neraca pangan</p>
              </div>
            </div>
            <button type="button" onclick="tutupModalTentangData()" class="rounded-lg p-1.5 text-slate-300 hover:text-white hover:bg-slate-800 transition" aria-label="Tutup modal">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>

          <div class="px-6 py-5 space-y-4 text-xs text-slate-700">
            <!-- Spesifikasi Model & Titik Pantau (Dipindahkan dari Header Halaman) -->
            <div class="p-3.5 rounded-xl bg-slate-900 text-white flex items-center justify-between text-xs flex-wrap gap-2">
              <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span class="font-extrabold uppercase tracking-wider text-emerald-400">SIPANGAN-XGBOOST v1</span>
                <span class="text-slate-300">• 34 Wilayah Percontohan (Data Contoh)</span>
              </div>
              <span class="text-xs text-slate-200 font-semibold bg-slate-800 px-2.5 py-1 rounded-md">Data Contoh: Estimasi BPS &amp; BAPANAS</span>
            </div>
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-1.5">
              <div class="font-bold text-slate-900 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                Sumber Data Acuan
              </div>
              <p class="leading-relaxed">
                Basis data historis mengacu pada statistik <strong>Badan Pangan Nasional (BAPANAS)</strong> dan <strong>Badan Pusat Statistik (BPS)</strong> untuk pola konsumsi per kapita, neraca regional, dan iklim dasar. Data realisasi panen dikonsolidasikan dari simulasi pelaporan mitra BUMDes dan estimasi pertanian percontohan (PRD 2.3 &amp; 14).
              </p>
            </div>

            <!-- Cakupan Wilayah -->
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-1.5">
              <div class="font-bold text-slate-900 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Cakupan Wilayah Percontohan (Pilot Project)
              </div>
              <p class="leading-relaxed">
                Sistem saat ini memantau <strong>34 kabupaten/kota percontohan</strong> di 10 provinsi sentra pangan nasional. Peta georeferensi WGS84 menampilkan batas seluruh wilayah Indonesia, di mana wilayah yang belum masuk percontohan ditandai dengan status <em>Tanpa Data</em> berwarna abu-abu.
              </p>
            </div>

            <!-- Metodologi Rasio & Status -->
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-1.5">
              <div class="font-bold text-slate-900 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                Metodologi Indeks Rasio Pasokan
              </div>
              <p class="leading-relaxed">
                Rasio pasokan dihitung dari perbandingan antara total pasokan tersedia (stok awal + panen riil/proyeksi + pasokan masuk) terhadap estimasi total kebutuhan konsumsi penduduk per bulan:
              </p>
              <ul class="list-disc list-inside space-y-1 pl-1 text-slate-700">
                <li><strong>Defisit Kritis:</strong> Rasio &lt; 95% (kebutuhan melebihi pasokan).</li>
                <li><strong>Seimbang / Aman:</strong> Rasio 95% &ndash; 105% (cadangan pangan terjaga ideal).</li>
                <li><strong>Waspada Surplus:</strong> Rasio 105% &ndash; 120% (pasokan mulai melebihi daya serap).</li>
                <li><strong>Penuh / Surplus Berlebih:</strong> Rasio &gt; 120% (risiko kelebihan stok dan pemborosan pangan).</li>
              </ul>
            </div>

            <!-- Model Prakiraan -->
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-1.5">
              <div class="font-bold text-slate-900 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                Model Prakiraan (Forecast Horizon 12 Bulan)
              </div>
              <p class="leading-relaxed">
                Proyeksi menggunakan model <strong>Autoregressive XGBoost v1</strong> yang dilatih dengan deret waktu historis panen, anomali iklim BMKG, dan tren konsumsi. Model menghasilkan proyeksi horizon 12 bulan ke depan dengan deviasi rata-rata (MAE: ±3,8%, WAPE: ±14,2%).
              </p>
            </div>
          </div>

          <div class="bg-slate-50 px-6 py-3.5 border-t border-slate-200 flex justify-end">
            <button type="button" onclick="tutupModalTentangData()" class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition shadow-xs">
              Tutup
            </button>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>
@endsection

@push('scripts')
{{-- Leaflet JS --}}
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

<script>
const API_BASE = '/api';
const GEO_BASE = '/geojson';

const IndoFormat = {
  number(num, decimals = 0) {
    if (num === null || num === undefined || isNaN(num) || num === '') return '—';
    return Number(num).toLocaleString('id-ID', {
      minimumFractionDigits: decimals,
      maximumFractionDigits: decimals,
    });
  },
  percent(val, decimals = 1, isRatio = false, showSign = false) {
    if (val === null || val === undefined || val === '' || val === '—') return '—';
    if (typeof val === 'string' && val.includes('%')) return val.replace('.', ',');
    let n = Number(val);
    if (isNaN(n)) return '—';
    if (isRatio) n *= 100;
    const formatted = Math.abs(n).toLocaleString('id-ID', {
      minimumFractionDigits: decimals,
      maximumFractionDigits: decimals,
    }) + '%';
    if (showSign) return (n > 0 ? '+' : (n < 0 ? '-' : '')) + formatted;
    return (n < 0 ? '-' : '') + formatted;
  },
  ton(kg, decimals = 1, showSign = false) {
    if (kg === null || kg === undefined || isNaN(kg) || kg === '') return '—';
    const t = Number(kg) / 1000.0;
    const str = Math.abs(t).toLocaleString('id-ID', {
      minimumFractionDigits: decimals,
      maximumFractionDigits: decimals,
    }) + ' ton';
    if (showSign) return (t > 0 ? '+' : (t < 0 ? '-' : '')) + str;
    return (t < 0 ? '-' : '') + str;
  },
  kg(kg) {
    if (kg === null || kg === undefined || isNaN(kg) || kg === '') return '—';
    return Math.round(Number(kg)).toLocaleString('id-ID') + ' kg';
  }
};

const rootStyles = getComputedStyle(document.documentElement);
const getCssProp = (name, fallback) => rootStyles.getPropertyValue(name).trim() || fallback;

const STATUS_CONFIG = {
  defisit: {
    color: getCssProp('--status-defisit', '#ef4444'),
    border: '#b91c1c',
    symbol: '▼',
    label: 'Defisit',
    badgeClass: 'bg-rose-100 text-rose-900 border border-rose-300',
    panelBoxClass: 'p-4 rounded-xl bg-rose-50 border border-rose-200 flex items-center justify-between',
    textClass: 'text-rose-950',
    accentClass: 'text-rose-700',
    iconBgClass: 'bg-rose-200/80 text-rose-800',
  },
  seimbang: {
    color: getCssProp('--status-seimbang', '#16a34a'),
    border: '#15803d',
    symbol: '✔',
    label: 'Seimbang',
    badgeClass: 'bg-emerald-100 text-emerald-900 border border-emerald-300',
    panelBoxClass: 'p-4 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center justify-between',
    textClass: 'text-emerald-950',
    accentClass: 'text-emerald-700',
    iconBgClass: 'bg-emerald-200/80 text-emerald-800',
  },
  waspada: {
    color: getCssProp('--status-waspada', '#f59e0b'),
    border: '#b45309',
    symbol: '▲',
    label: 'Waspada',
    badgeClass: 'bg-amber-100 text-amber-950 border border-amber-300',
    panelBoxClass: 'p-4 rounded-xl bg-amber-50 border border-amber-200 flex items-center justify-between',
    textClass: 'text-amber-950',
    accentClass: 'text-amber-800',
    iconBgClass: 'bg-amber-200/80 text-amber-900',
  },
  penuh: {
    color: getCssProp('--status-penuh', '#8b5cf6'),
    border: '#7c3aed',
    symbol: '◆',
    label: 'Penuh / Surplus',
    badgeClass: 'bg-purple-100 text-purple-900 border border-purple-300',
    panelBoxClass: 'p-4 rounded-xl bg-purple-50 border border-purple-200 flex items-center justify-between',
    textClass: 'text-purple-950',
    accentClass: 'text-purple-800',
    iconBgClass: 'bg-purple-200/80 text-purple-800',
  },
  tanpa_data: {
    color: getCssProp('--status-tanpa-data', '#64748b'),
    border: '#475569',
    symbol: '—',
    label: 'Tanpa Data',
    badgeClass: 'bg-slate-100 text-slate-700 border border-slate-300',
    panelBoxClass: 'p-4 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between',
    textClass: 'text-slate-900',
    accentClass: 'text-slate-600',
    iconBgClass: 'bg-slate-200/80 text-slate-800',
  },
};

function getStatusConfig(status) {
  return STATUS_CONFIG[status] ?? STATUS_CONFIG.tanpa_data;
}

let state = {
  komoditas: document.getElementById('select-komoditas').value,
  periode: document.getElementById('select-periode').value,
  level: 'kabupaten', // Default kabupaten matching mockup
  statusData: {},
  selectedKode: '3302', // Default Banyumas
  geoCache: {},
};

// ── Inisialisasi Peta Leaflet (Tanpa Tile Eksternal) ──────────────────────
const map = L.map('peta-map', {
  center: [-2.5, 118.0], // Full Indonesia center default
  zoom: 5,
  minZoom: 4,
  maxZoom: 11,
  zoomControl: false,
  attributionControl: false,
});

let geoLayerProv = null;
let geoLayerKab  = null;
let selectedLayer = null;
let isInitialMapFramed = false;

function getFeatureStyle(kode, isHover = false) {
  const status = state.statusData[kode]?.status ?? 'tanpa_data';
  const conf = getStatusConfig(status);
  const isSelected = String(kode) === String(state.selectedKode);

  if (isSelected) {
    return {
      fillColor:   conf.color,
      fillOpacity: 0.95,
      color:       '#fbbf24', // Garis batas kontras tinggi (amber emas >= 3px)
      weight:      3.5,
      opacity:     1,
    };
  }

  return {
    fillColor:   conf.color,
    fillOpacity: isHover ? 0.95 : 0.78,
    color:       conf.border,
    weight:      isHover ? 2.5 : 1,
    opacity:     1,
  };
}

function bringSelectedToFront() {
  if (selectedLayer && typeof selectedLayer.bringToFront === 'function') {
    selectedLayer.bringToFront();
  }
}

function updateAllLayerStyles() {
  const activeLayer = state.level === 'provinsi' ? geoLayerProv : geoLayerKab;
  if (!activeLayer) return;

  selectedLayer = null;
  activeLayer.eachLayer(layer => {
    const kode = String(layer.feature?.properties?.kode ?? '');
    const isSelected = kode === String(state.selectedKode);
    layer.setStyle(getFeatureStyle(kode, false));
    if (isSelected) {
      selectedLayer = layer;
    }
  });

  bringSelectedToFront();
}

function fitToPilotBounds() {
  const activeLayer = state.level === 'provinsi' ? geoLayerProv : geoLayerKab;
  if (!activeLayer) return;

  const pilotBounds = L.latLngBounds();
  let count = 0;
  activeLayer.eachLayer(layer => {
    const kode = String(layer.feature?.properties?.kode ?? '');
    if (state.statusData[kode]) {
      pilotBounds.extend(layer.getBounds());
      count++;
    }
  });

  if (count > 0 && pilotBounds.isValid()) {
    map.fitBounds(pilotBounds, { padding: [24, 24] });
  }
}

// ── Muat Data Status dari API ─────────────────────────────────────────────
async function muatStatusData() {
  document.getElementById('peta-loading').classList.remove('opacity-0', 'pointer-events-none');
  try {
    const res = await fetch(`${API_BASE}/status?komoditas=${state.komoditas}&periode=${state.periode}&level=${state.level}`);
    if (!res.ok) throw new Error('API status gagal');
    const json = await res.json();

    state.statusData = {};
    json.data.forEach(d => { state.statusData[d.kode] = d; });

    // Update Banner dan KPI cards dari data riil ringkasan
    if (json.ringkasan) {
      updateKpiCards(json.ringkasan, json.komoditas);
    }

    await renderMapLayer();
    renderTabelData(json.data);
    buildSearchAutocomplete(json.data);

    // Refresh panel wilayah terpilih
    if (state.selectedKode) {
      updatePanelWilayah(state.selectedKode);
    }
  } catch (err) {
    console.error(err);
  } finally {
    document.getElementById('peta-loading').classList.add('opacity-0', 'pointer-events-none');
  }
}

// ── Update Reaktif Banner & 4 Kartu KPI Basis Data ─────────────────────────
function updateKpiCards(r, komoditas) {
  if (!r) return;

  // Banner
  const bannerText = document.getElementById('banner-nasional-text');
  if (bannerText && r.banner_text) {
    bannerText.textContent = r.banner_text;
  }

  // KPI 1: Total Pasokan Terpantau
  const supplyEl = document.getElementById('kpi-total-supply');
  if (supplyEl) supplyEl.textContent = r.total_supply_formatted;
  const demandVal = document.getElementById('kpi-demand-val');
  if (demandVal) demandVal.textContent = r.total_demand_formatted;
  const selisihPersenEl = document.getElementById('kpi-selisih-persen');
  if (selisihPersenEl) selisihPersenEl.textContent = `(${r.selisih_persen_formatted})`;
  const rasioTotalEl = document.getElementById('kpi-rasio-total');
  if (rasioTotalEl) {
    const rasioVal = r.total_demand_kg > 0 ? (r.total_supply_kg / r.total_demand_kg) * 100 : 0;
    rasioTotalEl.textContent = IndoFormat.percent(rasioVal, 1);
  }
  const rasioBar = document.getElementById('kpi-rasio-bar');
  if (rasioBar) {
    const pct = r.total_demand_kg > 0 ? Math.min(100, (r.total_supply_kg / r.total_demand_kg) * 50) : 0;
    rasioBar.style.width = `${pct}%`;
  }

  // KPI 2: Wilayah Surplus / Penuh
  const jmlPenuhEl = document.getElementById('kpi-jumlah-penuh');
  if (jmlPenuhEl) jmlPenuhEl.textContent = r.jumlah_penuh;
  const persenPenuhEl = document.getElementById('kpi-persen-penuh');
  if (persenPenuhEl) persenPenuhEl.textContent = r.persen_penuh_formatted;
  const totalSurplusEl = document.getElementById('kpi-total-surplus');
  if (totalSurplusEl) totalSurplusEl.textContent = IndoFormat.ton(r.total_surplus_kg, 1, true);
  const rerataSurplusEl = document.getElementById('kpi-rerata-surplus');
  if (rerataSurplusEl) rerataSurplusEl.textContent = r.rerata_rasio_surplus_formatted;

  // KPI 3: Wilayah Defisit & Waspada
  const jmlDefEl = document.getElementById('kpi-jumlah-defisit-waspada');
  if (jmlDefEl) jmlDefEl.textContent = r.jumlah_defisit_waspada;
  const persenDefEl = document.getElementById('kpi-persen-defisit-waspada');
  if (persenDefEl) persenDefEl.textContent = r.persen_defisit_waspada_formatted;
  const rincianDef = document.getElementById('kpi-rincian-defisit');
  if (rincianDef) rincianDef.textContent = `${r.jumlah_defisit} Defisit`;
  const rincianWaspada = document.getElementById('kpi-rincian-waspada');
  if (rincianWaspada) rincianWaspada.textContent = `${r.jumlah_waspada} Waspada`;
  const totalDefisitEl = document.getElementById('kpi-total-defisit');
  if (totalDefisitEl) totalDefisitEl.textContent = r.total_defisit_formatted;
  const defisitBar = document.getElementById('kpi-defisit-bar');
  if (defisitBar) defisitBar.style.width = r.jumlah_defisit > 0 ? '75%' : '0%';

  // KPI 4: Neraca Bersih Konsolidasi
  const selisihEl = document.getElementById('kpi-selisih-bersih');
  if (selisihEl) selisihEl.textContent = r.selisih_formatted;
  const namaKomEl = document.getElementById('kpi-nama-komoditas');
  if (namaKomEl && komoditas) namaKomEl.textContent = `Komoditas ${komoditas.nama}`;
  const seimbangEl = document.getElementById('kpi-jumlah-seimbang');
  if (seimbangEl) seimbangEl.textContent = `${r.jumlah_seimbang} Wilayah Seimbang`;
  const cakupanEl = document.getElementById('kpi-cakupan-wilayah');
  if (cakupanEl) cakupanEl.textContent = `${r.total_wilayah} / 34 Kabupaten`;
}

// ── Fetch & Cache GeoJSON ────────────────────────────────────────────────
async function fetchGeoJSON(url) {
  if (state.geoCache[url]) return state.geoCache[url];
  const res = await fetch(url);
  const data = await res.json();
  state.geoCache[url] = data;
  return data;
}

// ── Render Layer Map Sesuai Level ─────────────────────────────────────────
async function renderMapLayer() {
  if (state.level === 'provinsi') {
    if (geoLayerKab) map.removeLayer(geoLayerKab);
    if (!geoLayerProv) {
      const geo = await fetchGeoJSON(`${GEO_BASE}/provinsi.json`);
      geoLayerProv = L.geoJSON(geo, {
        style: feat => getFeatureStyle(feat.properties.kode),
        onEachFeature: (feat, layer) => {
          const kode = feat.properties.kode;
          layer.bindTooltip(buildTooltip(feat.properties, state.statusData[kode]), { sticky: true, className: 'leaflet-dark-tooltip' });
          layer.on('mouseover', function() {
            if (String(kode) !== String(state.selectedKode)) {
              this.setStyle(getFeatureStyle(kode, true));
              this.bringToFront();
              bringSelectedToFront();
            }
          });
          layer.on('mouseout', function() {
            if (String(kode) !== String(state.selectedKode)) {
              this.setStyle(getFeatureStyle(kode, false));
              bringSelectedToFront();
            }
          });
          layer.on('click', function() {
            state.selectedKode = kode;
            updateAllLayerStyles();
            map.fitBounds(layer.getBounds(), { padding: [30, 30] });
            updatePanelWilayah(kode);
          });
        }
      });
    }
    if (!map.hasLayer(geoLayerProv)) geoLayerProv.addTo(map);
  } else {
    if (geoLayerProv) map.removeLayer(geoLayerProv);
    if (!geoLayerKab) {
      const geo = await fetchGeoJSON(`${GEO_BASE}/kabupaten.json`);
      geoLayerKab = L.geoJSON(geo, {
        style: feat => getFeatureStyle(feat.properties.kode),
        onEachFeature: (feat, layer) => {
          const kode = feat.properties.kode;
          layer.bindTooltip(buildTooltip(feat.properties, state.statusData[kode]), { sticky: true, className: 'leaflet-dark-tooltip' });
          layer.on('mouseover', function() {
            if (String(kode) !== String(state.selectedKode)) {
              this.setStyle(getFeatureStyle(kode, true));
              this.bringToFront();
              bringSelectedToFront();
            }
          });
          layer.on('mouseout', function() {
            if (String(kode) !== String(state.selectedKode)) {
              this.setStyle(getFeatureStyle(kode, false));
              bringSelectedToFront();
            }
          });
          layer.on('click', function() {
            state.selectedKode = kode;
            updateAllLayerStyles();
            updatePanelWilayah(kode);
          });
        }
      });
    }
    if (!map.hasLayer(geoLayerKab)) geoLayerKab.addTo(map);
  }

  // Update styles (high-contrast outline on selectedKode)
  updateAllLayerStyles();

  // On first load, fit map to 34 pilot regions
  if (!isInitialMapFramed) {
    fitToPilotBounds();
    isInitialMapFramed = true;
  }
}

function buildTooltip(props, d) {
  const nama = props.nama ?? '—';
  const status = d?.status ?? 'tanpa_data';
  const conf = getStatusConfig(status);
  const rasio = d?.rasio_persen ?? '—';
  const supply = d?.supply_formatted ?? d?.total_supply_formatted ?? '—';

  return `<div style="background:#0f172a;border:1px solid rgba(255,255,255,.2);border-radius:10px;padding:10px 14px;min-width:180px;color:#fff;font-family:inherit;box-shadow:0 10px 25px -5px rgba(0,0,0,.5)">
    <div style="font-weight:800;font-size:.875rem;color:#f8fafc;margin-bottom:4px">${nama}</div>
    <div style="display:flex;align-items:center;gap:6px">
      <div style="width:10px;height:10px;border-radius:2px;background:${conf.color}"></div>
      <span style="font-size:.75rem;font-weight:700;color:${conf.color}">${conf.symbol} ${conf.label} (${rasio})</span>
    </div>
    <div style="font-size:.75rem;color:#cbd5e1;margin-top:5px">Supply: <span style="color:#ffffff;font-weight:600">${supply}</span></div>
  </div>`;
}

// ── Switch Level Geografis ───────────────────────────────────────────────
function switchLayerLevel(lvl) {
  state.level = lvl;
  const isProv = lvl === 'provinsi';
  document.getElementById('btn-toggle-prov').className = isProv
    ? 'flex-1 py-1.5 rounded-lg text-xs font-bold transition bg-white text-slate-900 shadow-2xs'
    : 'flex-1 py-1.5 rounded-lg text-xs font-bold transition text-slate-600 hover:text-slate-900';
  document.getElementById('btn-toggle-kab').className = !isProv
    ? 'flex-1 py-1.5 rounded-lg text-xs font-bold transition bg-white text-slate-900 shadow-2xs'
    : 'flex-1 py-1.5 rounded-lg text-xs font-bold transition text-slate-600 hover:text-slate-900';

  muatStatusData();
}

function resetViewNasional() {
  map.setView([-2.5, 118.0], 5);
}

const BULAN_BERJALAN = '{{ $bulanBerjalan }}';

function updateTombolBulanBerjalan() {
  const btn = document.getElementById('btn-bulan-ini');
  const sel = document.getElementById('select-periode');
  if (!btn || !sel) return;
  if (sel.value === BULAN_BERJALAN) {
    btn.disabled = true;
    btn.classList.add('opacity-40', 'cursor-not-allowed');
    btn.classList.remove('hover:bg-emerald-200');
  } else {
    btn.disabled = false;
    btn.classList.remove('opacity-40', 'cursor-not-allowed');
    btn.classList.add('hover:bg-emerald-200');
  }
}

function pilihBulanBerjalan() {
  const sel = document.getElementById('select-periode');
  if (!sel) return;
  sel.value = BULAN_BERJALAN;
  state.periode = BULAN_BERJALAN;
  updateTombolBulanBerjalan();
  muatStatusData();
}

function pilihBulanIni() {
  pilihBulanBerjalan();
}

// ── Update Panel Wilayah Terpilih (Kanan) ────────────────────────────────
async function updatePanelWilayah(kode) {
  state.selectedKode = kode;
  updateAllLayerStyles();

  try {
    const res = await fetch(`${API_BASE}/wilayah/${kode}/seri?komoditas=${state.komoditas}`);
    if (!res.ok) throw new Error('Seri error');
    const json = await res.json();

    const w = json.wilayah;
    document.getElementById('panel-nama').textContent = w.nama;
    document.getElementById('panel-kode').textContent = kode;
    document.getElementById('panel-provinsi').textContent = w.nama_provinsi
      ? `Provinsi ${w.nama_provinsi} • Kode Wilayah: ${kode}`
      : `Provinsi ${w.nama} • Kode Wilayah: ${kode}`;

    // Cari titik sesuai periode yang aktif
    const point = json.seri.find(s => s.periode === state.periode) || json.status_terbaru;
    if (point) {
      const conf = getStatusConfig(point.status);

      // Status Box
      const box = document.getElementById('panel-status-box');
      if (box) box.className = conf.panelBoxClass;

      const statusLabel = document.getElementById('panel-status-label');
      if (statusLabel) {
        statusLabel.textContent = `${conf.symbol} ${point.label.toUpperCase()}`;
        statusLabel.className = `text-xl font-black mt-0.5 ${conf.textClass}`;
      }

      const rasioLabel = document.getElementById('panel-rasio-label');
      if (rasioLabel) {
        rasioLabel.textContent = `Rasio Pasokan: ${point.rasio_persen} (Batas Aman: 100%)`;
        rasioLabel.className = `text-xs font-bold mt-0.5 ${conf.accentClass}`;
      }

      const iconBox = document.getElementById('panel-status-icon-box');
      if (iconBox) {
        iconBox.className = `w-10 h-10 rounded-xl flex items-center justify-center shrink-0 text-lg font-black ${conf.iconBgClass}`;
        iconBox.textContent = conf.symbol;
      }

      // Neraca Supply & Demand
      document.getElementById('panel-neraca-periode').textContent = point.periode;
      document.getElementById('panel-supply-formatted').textContent = point.supply_formatted || '—';
      document.getElementById('panel-demand-formatted').textContent = point.demand_formatted || '—';
      document.getElementById('panel-supply-raw').textContent = `${IndoFormat.kg(point.supply_kg)} terdata`;
      document.getElementById('panel-demand-raw').textContent = `${IndoFormat.kg(point.demand_kg)} konsumsi`;

      const selisihKg = (point.supply_kg || 0) - (point.demand_kg || 0);
      document.getElementById('panel-selisih').textContent = IndoFormat.ton(selisihKg, 1, true);
      document.getElementById('panel-selisih').className = selisihKg >= 0 ? 'text-rose-700 tabular-nums font-bold' : 'text-blue-700 tabular-nums font-bold';

      // Breakdown mini boxes (estimasi proporsional representatif)
      const supplyKg = point.supply_kg || 0;
      document.getElementById('panel-stok-awal').textContent = IndoFormat.ton(supplyKg * 0.17, 1);
      document.getElementById('panel-panen').textContent = IndoFormat.ton(supplyKg * 0.81, 1);
      document.getElementById('panel-pasokan-masuk').textContent = IndoFormat.ton(supplyKg * 0.02, 1);

      if (point.stok_estimasi) {
        document.getElementById('panel-stok-badge').innerHTML = '<span class="text-amber-600 font-medium inline-flex items-center gap-1">⚠ Stok Estimasi</span>';
        document.getElementById('panel-stok-badge').className = 'inline-flex items-center gap-1 text-xs font-medium text-amber-700 select-none';
      } else {
        document.getElementById('panel-stok-badge').innerHTML = '<svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> <span>Stok Terdata (Simulasi)</span>';
        document.getElementById('panel-stok-badge').className = 'inline-flex items-center gap-1 text-xs font-medium text-slate-600 select-none';
      }
    }

    if (json.pesan_dampak) {
      document.getElementById('panel-pesan-dampak').textContent = json.pesan_dampak;
    }

    // Link tombol ke detail
    const btnAnalisis = document.getElementById('btn-lihat-analisis');
    if (btnAnalisis) {
      btnAnalisis.href = `/wilayah/${kode}?komoditas=${state.komoditas}`;
    }
    const btnKirim = document.getElementById('btn-buat-pengiriman');
    if (btnKirim) {
      btnKirim.href = `/pengiriman?tujuan=${kode}`;
    }

  } catch (err) {
    console.error(err);
  }
}

// ── Quick Search Autocomplete ─────────────────────────────────────────────
let searchDataset = [];

function buildSearchAutocomplete(data) {
  searchDataset = data;
}

const searchInput = document.getElementById('input-search-wilayah');
const searchDropdown = document.getElementById('search-dropdown');

searchInput.addEventListener('input', (e) => {
  const q = e.target.value.trim().toLowerCase();
  if (q.length < 2) {
    searchDropdown.classList.add('hidden');
    return;
  }

  const matches = searchDataset.filter(d =>
    d.nama.toLowerCase().includes(q) || (d.nama_provinsi ?? '').toLowerCase().includes(q)
  ).slice(0, 8);

  if (matches.length === 0) {
    searchDropdown.innerHTML = '<div class="p-3 text-xs text-slate-600 text-center font-medium">Wilayah tidak ditemukan</div>';
    searchDropdown.classList.remove('hidden');
    return;
  }

  searchDropdown.innerHTML = matches.map(m => {
    const conf = getStatusConfig(m.status);
    return `
    <div onclick="pilihWilayahHasil('${m.kode}', '${m.nama.replace("'", "\\'")}')"
         class="px-3.5 py-2.5 hover:bg-slate-50 cursor-pointer flex items-center justify-between border-b border-slate-100 text-xs">
      <div>
        <div class="font-bold text-slate-900">${m.nama}</div>
        <div class="text-xs text-slate-600 font-medium">${m.nama_provinsi ?? ''}</div>
      </div>
      <span class="inline-flex items-center gap-1 text-xs font-bold px-2 py-0.5 rounded ${conf.badgeClass}">
        <span>${conf.symbol}</span>
        <span>${conf.label}</span>
      </span>
    </div>`;
  }).join('');
  searchDropdown.classList.remove('hidden');
});

async function pilihWilayahHasil(kode, nama) {
  searchDropdown.classList.add('hidden');
  searchInput.value = nama;

  if (kode.length === 4 && state.level === 'provinsi') {
    switchLayerLevel('kabupaten');
  }

  const activeLayer = state.level === 'provinsi' ? geoLayerProv : geoLayerKab;
  if (activeLayer) {
    activeLayer.eachLayer(l => {
      if (l.feature.properties.kode === kode) {
        map.fitBounds(l.getBounds(), { padding: [30, 30], maxZoom: 9 });
      }
    });
  }

  updatePanelWilayah(kode);
}

document.addEventListener('click', (e) => {
  if (!searchInput.contains(e.target) && !searchDropdown.contains(e.target)) {
    searchDropdown.classList.add('hidden');
  }
});

// ── Fallback Tabel Wilayah ───────────────────────────────────────────────
let rawTabelData = [];

function renderTabelData(data) {
  rawTabelData = data;
  filterTabel(document.getElementById('tabel-filter-input').value || '');
}

function filterTabel(q) {
  const lower = q.toLowerCase();
  const filtered = rawTabelData.filter(d =>
    d.nama.toLowerCase().includes(lower) || (d.nama_provinsi ?? '').toLowerCase().includes(lower)
  );
  const tbody = document.getElementById('tabel-body');
  if (filtered.length === 0) {
    tbody.innerHTML = '<tr><td colspan="6" class="px-4 py-6 text-center text-slate-600 font-medium text-xs">Tidak ada data ditemukan</td></tr>';
    return;
  }

  tbody.innerHTML = filtered.map(d => {
    const conf = getStatusConfig(d.status);
    return `<tr class="hover:bg-slate-50 transition cursor-pointer" onclick="updatePanelWilayah('${d.kode}')">
      <td class="px-4 py-3">
        <div class="font-bold text-slate-900">${d.nama}</div>
        <div class="text-xs text-slate-600 font-medium">${d.nama_provinsi ?? ''}</div>
      </td>
      <td class="px-4 py-3">
        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-bold ${conf.badgeClass}">
          <span>${conf.symbol}</span>
          <span>${conf.label}</span>
        </span>
      </td>
      <td class="px-4 py-3 text-right font-semibold text-slate-900">${d.rasio_persen ?? '—'}</td>
      <td class="px-4 py-3 text-right text-slate-700">${d.supply_formatted ?? d.total_supply_formatted ?? '—'}</td>
      <td class="px-4 py-3 text-right text-slate-700">${d.demand_formatted ?? d.total_demand_formatted ?? '—'}</td>
      <td class="px-4 py-3 text-center">
        <a href="/wilayah/${d.kode}?komoditas=${state.komoditas}" class="text-emerald-700 hover:underline font-bold text-xs">Detail &rarr;</a>
      </td>
    </tr>`;
  }).join('');
}

function toggleTabel() {
  const el = document.getElementById('tabel-fallback');
  el.classList.toggle('hidden');
}

document.getElementById('tabel-filter-input').addEventListener('input', e => filterTabel(e.target.value));

// ── Modal Tentang Data ───────────────────────────────────────────────────
function bukaModalTentangData() {
  const modal = document.getElementById('modal-tentang-data');
  if (modal) {
    modal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
  }
}

function tutupModalTentangData() {
  const modal = document.getElementById('modal-tentang-data');
  if (modal) {
    modal.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
  }
}

// ── Banner Peringatan Nasional ───────────────────────────────────────────
function tutupBannerNasional() {
  const banner = document.getElementById('banner-nasional');
  if (banner) {
    banner.style.display = 'none';
    if (typeof map !== 'undefined' && map.invalidateSize) {
      setTimeout(() => map.invalidateSize(), 50);
    }
  }
}

// Invalidate size on resize and after initial layout
window.addEventListener('resize', () => {
  if (typeof map !== 'undefined' && map.invalidateSize) {
    map.invalidateSize();
  }
});
setTimeout(() => {
  if (typeof map !== 'undefined' && map.invalidateSize) {
    map.invalidateSize();
  }
}, 300);

document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    tutupModalTentangData();
  }
});

// ── Event Filters ────────────────────────────────────────────────────────
document.getElementById('select-komoditas').addEventListener('change', e => {
  state.komoditas = e.target.value;
  muatStatusData();
});

document.getElementById('select-periode').addEventListener('change', e => {
  state.periode = e.target.value;
  updateTombolBulanBerjalan();
  muatStatusData();
});

// ── Inisialisasi Awal ────────────────────────────────────────────────────
updateTombolBulanBerjalan();
muatStatusData();
</script>
@endpush
