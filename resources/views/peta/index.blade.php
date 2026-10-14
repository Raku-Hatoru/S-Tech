@php
  use App\Support\IndoFormat;
@endphp
@extends('layouts.app')

@push('styles')
{{-- Leaflet CSS --}}
<link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}" />
<style>
  :root {
    --status-defisit: #ef4444;
    --status-seimbang: #16a34a;
    --status-waspada: #f59e0b;
    --status-penuh: #8b5cf6;
    --status-tanpa-data: #64748b;
  }

  /* ── Map Container & Canvas Stacking Context ────── */
  #map-card {
    position: relative;
    overflow: hidden;
    isolation: isolate;
    z-index: 0;
  }

  #peta-map {
    position: relative;
    overflow: hidden;
    isolation: isolate;
    z-index: 0;
    width: 100%;
    height: 100%;
    min-height: 520px;
    background: #080f1d;
    border-radius: 14px;
    outline: none;
    flex: 1 1 auto;
  }
  @media (max-width: 1024px) {
    #peta-map {
      min-height: 480px;
    }
  }
  @media (max-width: 768px) {
    #peta-map {
      height: 60vh;
      min-height: 450px;
      max-height: 600px;
    }
  }

  /* ── Leaflet Container & Preflight Resets ────────── */
  .leaflet-container {
    overflow: hidden !important;
    position: relative !important;
    font-family: inherit !important;
    background-color: #080f1d !important;
  }
  .leaflet-container img {
    max-width: none !important;
  }
  .leaflet-pane svg {
    max-width: none !important;
  }

  /* ── Modal Top-Layer Stacking ─────────────────────── */
  #modal-tentang-data {
    z-index: 1000 !important;
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

    <!-- Header Ringkas (Title + Subtitle 1 Baris + Tombol Aksi) -->
    <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-3">
      <div class="min-w-0">
        <div class="flex items-center gap-2 flex-wrap">
          <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
            Peta Pemantauan Surplus &amp; Defisit Pangan
          </h1>
          <span class="px-2 py-0.5 text-xs font-bold uppercase tracking-wider bg-slate-200 text-slate-700 rounded border border-slate-300 shrink-0">
            Data contoh
          </span>
        </div>
        <p class="text-xs text-slate-600 font-medium mt-1 leading-relaxed">
          Diagnostik rasio pasokan vs kebutuhan wilayah untuk mitigasi pemborosan pangan.
        </p>
      </div>

      <div class="flex items-center gap-2.5 shrink-0 flex-wrap">
        <button type="button" id="btn-tentang-data" onclick="bukaModalTentangData()" class="min-h-[44px] inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold transition shadow-2xs border border-slate-200" title="Informasi metodologi, sumber data acuan, dan pemodelan">
          <svg class="w-3.5 h-3.5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16v-4m0-4h.01"/></svg>
          Tentang Data
        </button>
        <button type="button" onclick="window.print()" class="min-h-[44px] inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition shadow-xs">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
          Unduh Buletin (.PDF)
        </button>
      </div>
    </div>

    <!-- Mobile Filter Accordion Toggle (< md) -->
    <div class="md:hidden">
      <button type="button" id="btn-toggle-filter-mobile" onclick="toggleMobileFilter()"
              class="w-full min-h-[44px] px-4 py-2.5 rounded-2xl bg-white border border-slate-200/90 shadow-2xs flex items-center justify-between text-xs font-bold text-slate-800 transition">
        <div class="flex items-center gap-2">
          <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
          <span>Panel Filter &amp; Pencarian Peta</span>
        </div>
        <div class="flex items-center gap-2">
          <span id="mobile-filter-summary" class="text-xs text-slate-500 font-semibold whitespace-nowrap">Beras &bull; {{ $periodeDefault }}</span>
          <svg id="icon-filter-chevron" class="w-4 h-4 text-slate-500 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </div>
      </button>
    </div>

    <!-- Filter Control Bar (4 Kolom Ringkas) -->
    <div id="filter-container" class="hidden md:grid bg-white rounded-2xl p-3 sm:p-4 border border-slate-200/90 shadow-2xs grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3.5 sm:gap-4 items-start">
      <!-- 1. Komoditas Pangan -->
      <div class="min-w-0">
        <label for="select-komoditas" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1 flex items-center gap-1.5">
          <svg class="w-3.5 h-3.5 text-slate-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
          <span class="truncate">Komoditas Pangan</span>
        </label>
        <div class="relative">
          <select id="select-komoditas" aria-label="Pilih Komoditas Pangan" class="w-full min-h-[44px] bg-slate-50 hover:bg-slate-100/80 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs font-bold text-slate-800 appearance-none outline-none cursor-pointer pr-9 transition">
            @foreach($komoditasList as $k)
              <option value="{{ $k->kode }}" {{ $loop->first ? 'selected' : '' }}>
                {{ $k->kode === 'beras' ? 'Beras Premium & Medium' : ucfirst($k->nama) }}
              </option>
            @endforeach
          </select>
          <svg class="w-4 h-4 text-slate-400 absolute right-3 top-3.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </div>
      </div>

      <!-- 2. Periode Waktu -->
      <div class="min-w-0">
        <label for="select-periode" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1">
          Periode Waktu
        </label>
        <div>
          <select id="select-periode" aria-label="Pilih Periode Waktu" class="w-full min-h-[44px] bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-xs font-bold text-slate-800 outline-none cursor-pointer">
            @foreach($periodesList->reverse() as $p)
              <option value="{{ $p['kode'] }}" {{ $p['kode'] === $periodeDefault ? 'selected' : '' }}>
                {{ $p['label'] }} ({{ $p['tipe'] }})
              </option>
            @endforeach
          </select>
          <div class="mt-1.5 flex items-center">
            <button type="button"
                    id="btn-bulan-ini"
                    onclick="pilihBulanBerjalan()"
                    class="hidden min-h-[44px] w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold text-emerald-700 hover:text-emerald-800 bg-emerald-50 hover:bg-emerald-100/80 border border-emerald-200 transition select-none shadow-2xs"
                    title="Pindah ke bulan kalender saat ini ({{ \Carbon\Carbon::parse($bulanBerjalan.'-01')->isoFormat('MMMM YYYY') }})">
              <span>&larr;</span>
              <span>Ke bulan berjalan</span>
            </button>
          </div>
        </div>
      </div>

      <!-- 3. Tingkat -->
      <div class="min-w-0">
        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1">
          Tingkat
        </label>
        <div class="flex bg-slate-100 p-1 rounded-xl border border-slate-200">
          <button type="button" id="btn-toggle-prov" onclick="switchLayerLevel('provinsi')" class="flex-1 min-h-[44px] py-2.5 rounded-lg text-xs font-bold transition text-slate-600 hover:text-slate-900 flex items-center justify-center">
            Provinsi
          </button>
          <button type="button" id="btn-toggle-kab" onclick="switchLayerLevel('kabupaten')" class="flex-1 min-h-[44px] py-2.5 rounded-lg text-xs font-bold transition bg-white text-slate-900 shadow-2xs flex items-center justify-center">
            Kabupaten
          </button>
        </div>
      </div>

      <!-- 4. Pencarian Wilayah -->
      <div class="relative min-w-0">
        <label for="input-search-wilayah" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1 flex items-center gap-1.5">
          <svg class="w-3.5 h-3.5 text-slate-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          <span class="truncate">Pencarian Wilayah</span>
        </label>
        <div class="relative">
          <input type="text" id="input-search-wilayah" placeholder="Ketik Banyumas, Brebes, dll..." autocomplete="off"
                 class="w-full min-h-[44px] bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-3.5 py-2.5 text-xs font-medium text-slate-800 placeholder-slate-500 outline-none focus:border-emerald-600 focus:bg-white transition">
          <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          <div id="search-dropdown" class="absolute top-full left-0 right-0 mt-1.5 bg-white border border-slate-200 rounded-xl shadow-lg z-50 max-h-60 overflow-y-auto hidden"></div>
        </div>
      </div>
    </div>

    <!-- Banner Status Wilayah Nasional (Ringkas 1 Baris Netral) -->
    <div id="banner-nasional" class="px-3.5 py-2 sm:py-2.5 rounded-xl bg-slate-100 border border-slate-200 flex items-center justify-between gap-3 text-xs text-slate-800 shadow-2xs">
      <div class="flex items-center gap-2 min-w-0">
        <svg class="w-4 h-4 text-slate-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span class="font-semibold text-slate-800 leading-normal text-xs">
          @php
            $totalWil = $ringkasan['total_wilayah'] ?? 34;
            $penuhWil = $ringkasan['jumlah_penuh'] ?? 27;
            $defisitWil = $ringkasan['jumlah_defisit'] ?? 0;
            $waspadaWil = $ringkasan['jumlah_waspada'] ?? 3;
            if ($defisitWil > 0) {
                $bannerKalimat = "{$penuhWil} dari {$totalWil} wilayah surplus · {$defisitWil} wilayah defisit memerlukan relokasi pasokan";
            } elseif ($penuhWil > 0) {
                $bannerKalimat = "{$penuhWil} dari {$totalWil} wilayah surplus; pertimbangkan penyimpanan atau pengolahan";
            } else {
                $bannerKalimat = "{$totalWil} wilayah pantau dalam kondisi seimbang dan aman";
            }
          @endphp
          <span id="banner-nasional-text">{{ $bannerKalimat }}</span>
        </span>
      </div>
      <div class="flex items-center gap-2 shrink-0">
        @if(auth()->check() && (auth()->user()->isDistributor() || auth()->user()->isAdmin()))
          <a href="{{ route('pengiriman.index') }}" class="min-h-[44px] inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold shadow-xs transition">
            Disposisi Logistik
          </a>
        @endif
        <button type="button" onclick="tutupBannerNasional()" aria-label="Tutup ringkasan nasional" class="w-11 h-11 min-w-[44px] min-h-[44px] flex items-center justify-center rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-200 transition" title="Tutup ringkasan">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>
    </div>

    <!-- Main Content: Peta Heatmap (Kiri 65%) + Panel Wilayah Terpilih (Kanan 35%) -->
    <div id="peta-content-grid" class="grid grid-cols-1 lg:grid-cols-12 gap-5 lg:items-stretch">

      <!-- Kolom Kiri: Peta Leaflet (lg:col-span-7 atau 8) -->
      <div id="map-card" class="relative isolate z-0 overflow-hidden lg:col-span-7 xl:col-span-8 bg-[#0b1329] rounded-2xl p-4 sm:p-5 border border-slate-800 shadow-md flex flex-col h-full">
        <div class="relative rounded-xl overflow-hidden flex-1 flex flex-col min-h-[520px]">
          <!-- Floating Map Controls (Top-Right: Reset View) -->
          <div class="absolute top-4 right-4 z-20 pointer-events-none">
            <div class="pointer-events-auto">
              <button type="button" onclick="resetViewNasional()" aria-label="Kembalikan tampilan peta ke seluruh wilayah Indonesia" class="min-h-[44px] px-3.5 py-2 rounded-xl bg-slate-900/90 backdrop-blur-md border border-slate-700 text-xs font-bold text-white hover:bg-slate-800 transition flex items-center gap-1.5 shadow-xs" title="Kembalikan tampilan peta ke seluruh wilayah Indonesia">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                Reset Nasional
              </button>
            </div>
          </div>

          <!-- Canvas Peta -->
          <div id="peta-map" class="w-full flex-1 min-h-[520px]"></div>

          <!-- Floating Map Controls (Bottom-Right: Zoom Buttons) -->
          <div class="absolute bottom-4 right-4 z-20 pointer-events-none">
            <div class="pointer-events-auto flex items-center gap-1.5">
              <button type="button" onclick="if (map) map.zoomIn()" aria-label="Perbesar peta" class="w-11 h-11 min-w-[44px] min-h-[44px] rounded-xl bg-slate-900/90 text-white font-bold border border-slate-700 hover:bg-slate-800 flex items-center justify-center text-lg shadow-xs">+</button>
              <button type="button" onclick="if (map) map.zoomOut()" aria-label="Perkecil peta" class="w-11 h-11 min-w-[44px] min-h-[44px] rounded-xl bg-slate-900/90 text-white font-bold border border-slate-700 hover:bg-slate-800 flex items-center justify-center text-lg shadow-xs">-</button>
            </div>
          </div>

          <!-- Loading Overlay -->
          <div id="peta-loading" class="absolute inset-0 bg-[#0b1329]/90 backdrop-blur-xs flex flex-col items-center justify-center z-30 transition-opacity duration-300">
            <div class="w-10 h-10 border-4 border-emerald-500/20 border-t-emerald-500 rounded-full animate-spin"></div>
            <div class="text-xs font-bold text-white mt-3 tracking-wide">Memuat Data Spasial &amp; Status...</div>
          </div>

          <!-- Empty State Overlay (Jika API mengembalikan 0 data) -->
          <div id="peta-empty-state" class="hidden absolute inset-0 bg-[#0b1329]/95 backdrop-blur-sm z-30 flex flex-col items-center justify-center p-6 text-center">
            <div class="w-12 h-12 rounded-2xl bg-amber-500/20 text-amber-400 flex items-center justify-center mb-3">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h4 class="text-white font-bold text-base mb-1">Data Belum Tersedia</h4>
            <p class="text-xs text-slate-300 max-w-sm">Tidak ada data untuk periode ini.</p>
          </div>

          <!-- Error State Overlay (Jika API gagal / offline) -->
          <div id="peta-error-state" class="hidden absolute inset-0 bg-[#0b1329]/95 backdrop-blur-sm z-30 flex flex-col items-center justify-center p-6 text-center">
            <div class="w-12 h-12 rounded-2xl bg-rose-500/20 text-rose-400 flex items-center justify-center mb-3">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <h4 class="text-white font-bold text-base mb-1">Gagal Memuat Data Spasial</h4>
            <p id="peta-error-msg" class="text-xs text-slate-300 max-w-sm mb-4">Terjadi gangguan saat mengambil data status wilayah. Silakan periksa koneksi atau coba muat ulang.</p>
            <button type="button" onclick="muatStatusData()" class="min-h-[44px] px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center gap-2 shadow-sm transition">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
              <span>Coba Lagi</span>
            </button>
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
      <div id="side-panel-card" class="lg:col-span-5 xl:col-span-4 bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/90 shadow-sm flex flex-col justify-between h-full space-y-3.5 overflow-y-auto">
        <!-- Header: Nama Wilayah, Provinsi, Kode (1 Baris) -->
        <div class="border-b border-slate-100 pb-2.5">
          <h2 class="text-lg sm:text-xl font-black text-slate-900 tracking-tight flex items-baseline gap-1.5 flex-wrap">
            <span id="panel-nama">Kabupaten Banyumas</span><span class="text-slate-400 font-normal">,</span>
            <span id="panel-provinsi" class="text-xs font-semibold text-slate-600">Jawa Tengah</span>
            <span id="panel-kode" class="text-xs font-mono font-medium text-slate-500">3302</span>
          </h2>
        </div>

        <!-- Status Wilayah Box -->
        <div id="panel-status-box" class="p-3.5 rounded-xl bg-purple-50 border border-purple-200 flex items-center justify-between">
          <div>
            <div class="text-[11px] font-extrabold uppercase tracking-wider text-purple-950">STATUS WILAYAH</div>
            <div id="panel-status-label" class="text-lg font-black text-purple-950 mt-0.5">
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

        <!-- Pesan Dampak Ringkas (1 Kalimat untuk status Penuh atau Defisit) -->
        <div id="panel-dampak-container" class="text-xs text-slate-600 leading-relaxed px-1">
          <p id="panel-pesan-dampak">Pangan berisiko basi atau terbuang bila pasokan melebihi kapasitas serap wilayah.</p>
        </div>

        <!-- Tiga Angka Neraca Pangan: Pasokan, Kebutuhan, Selisih -->
        <div class="space-y-2 pt-1">
          <div class="flex items-center justify-between text-xs">
            <span class="font-bold uppercase tracking-wider text-slate-600" id="panel-neraca-title">NERACA KOMODITAS BERAS</span>
            @php
              $activePeriodeMeta = collect($periodesList)->firstWhere('kode', $periodeDefault);
              $activePeriodeLabel = $activePeriodeMeta ? "{$activePeriodeMeta['label']} ({$activePeriodeMeta['tipe']})" : 'Desember 2025 (Aktual)';
            @endphp
            <span class="font-bold text-slate-700" id="panel-neraca-periode">{{ $activePeriodeLabel }}</span>
          </div>

          <div class="grid grid-cols-3 gap-2">
            <!-- Pasokan -->
            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-center">
              <div class="text-[11px] font-bold text-slate-600">Pasokan</div>
              <div class="text-sm sm:text-base font-black text-slate-900 mt-0.5" id="panel-supply-formatted">18.500,0 t</div>
            </div>

            <!-- Kebutuhan -->
            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-center">
              <div class="text-[11px] font-bold text-slate-600">Kebutuhan</div>
              <div class="text-sm sm:text-base font-black text-slate-900 mt-0.5" id="panel-demand-formatted">17.600,0 t</div>
            </div>

            <!-- Selisih -->
            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-center">
              <div class="text-[11px] font-bold text-slate-600">Selisih</div>
              <div class="text-sm sm:text-base font-black text-rose-700 mt-0.5" id="panel-selisih">+900,0 t</div>
            </div>
          </div>
        </div>

        <!-- Tombol Aksi -->
        <div class="space-y-2 pt-2 mt-auto">
          <a id="btn-lihat-analisis" href="{{ route('wilayah.detail', ['kode' => '3302']) }}"
             class="w-full min-h-[44px] flex items-center justify-center gap-2 py-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition shadow-xs">
            <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            Lihat Detail 12 Bulan
          </a>
          @if(auth()->check() && (auth()->user()->isDistributor() || auth()->user()->isAdmin()))
            <a id="btn-buat-pengiriman" href="{{ route('pengiriman.index') }}"
               class="w-full min-h-[44px] flex items-center justify-center gap-2 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition shadow-xs">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
              Buat Rute Pengiriman Logistik
            </a>
          @endif
        </div>
      </div>
    </div>

    <!-- Indikator Konsolidasi Pangan (Ringkasan Horizontal 4 Angka Kunci) -->
    <div class="bg-white rounded-2xl p-3 sm:p-4 border border-slate-200/90 shadow-2xs">
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-3.5">
        <!-- 1. Total Pasokan -->
        <div class="p-2.5 sm:p-3 bg-slate-50/70 rounded-xl border border-slate-100 flex flex-col justify-between">
          <div class="text-[10px] sm:text-xs font-bold text-slate-500 uppercase tracking-wider">Total Pasokan</div>
          <div class="text-base sm:text-lg lg:text-2xl font-black text-slate-900 tracking-tight mt-1">
            <span id="kpi-total-supply" class="tabular-nums">{{ $ringkasan['total_supply_formatted'] ?? '1.105.796,7 ton' }}</span>
          </div>
        </div>

        <!-- 2. Total Kebutuhan -->
        <div class="p-2.5 sm:p-3 bg-slate-50/70 rounded-xl border border-slate-100 flex flex-col justify-between">
          <div class="text-[10px] sm:text-xs font-bold text-slate-500 uppercase tracking-wider">Total Kebutuhan</div>
          <div class="text-base sm:text-lg lg:text-2xl font-black text-slate-900 tracking-tight mt-1">
            <span id="kpi-demand-val" class="tabular-nums">{{ $ringkasan['total_demand_formatted'] ?? '494.273,2 ton' }}</span>
          </div>
        </div>

        <!-- 3. Wilayah Surplus/Penuh -->
        <div class="p-2.5 sm:p-3 bg-purple-50/60 rounded-xl border border-purple-100/80 flex flex-col justify-between">
          <div class="text-[10px] sm:text-xs font-bold text-purple-700 uppercase tracking-wider">Wilayah Surplus/Penuh</div>
          <div class="text-base sm:text-lg lg:text-2xl font-black text-purple-900 tracking-tight mt-1 flex items-baseline gap-1">
            <span id="kpi-jumlah-penuh" class="tabular-nums">{{ $ringkasan['jumlah_penuh'] ?? 23 }}</span>
            <span class="text-[10px] sm:text-xs font-bold text-purple-600">wilayah</span>
          </div>
        </div>

        <!-- 4. Wilayah Defisit/Waspada -->
        <div class="p-2.5 sm:p-3 bg-amber-50/60 rounded-xl border border-amber-100/80 flex flex-col justify-between">
          <div class="text-[10px] sm:text-xs font-bold text-amber-700 uppercase tracking-wider">Wilayah Defisit/Waspada</div>
          <div class="text-base sm:text-lg lg:text-2xl font-black text-amber-900 tracking-tight mt-1 flex items-baseline gap-1">
            <span id="kpi-jumlah-defisit-waspada" class="tabular-nums">{{ $ringkasan['jumlah_defisit_waspada'] ?? 10 }}</span>
            <span class="text-[10px] sm:text-xs font-bold text-amber-600">wilayah</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Tombol Toggle Fallback Tabel Wilayah -->
    <div class="pt-2">
      <button type="button" onclick="toggleTabel()" id="btn-toggle-tabel"
              class="w-full min-h-[44px] py-3 rounded-2xl bg-white hover:bg-slate-50 border border-slate-200 text-xs font-bold text-slate-700 transition shadow-2xs flex items-center justify-center gap-2">
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
               class="bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 min-h-[44px] text-xs font-medium text-slate-800 placeholder-slate-500 w-64 outline-none focus:border-emerald-600 focus:bg-white transition">
      </div>
      <div class="overflow-x-auto focus-visible:ring-2 focus-visible:ring-emerald-500 rounded-xl" tabindex="0" role="region" aria-label="Tabel Rekapitulasi Status Seluruh Wilayah">
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
    <div id="modal-tentang-data" class="fixed inset-0 z-[1000] hidden overflow-y-auto" aria-labelledby="modal-tentang-title" role="dialog" aria-modal="true">
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
            <button type="button" onclick="tutupModalTentangData()" class="w-11 h-11 min-w-[44px] min-h-[44px] flex items-center justify-center rounded-xl text-slate-300 hover:text-white hover:bg-slate-800 transition" aria-label="Tutup modal informasi">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>

          <div class="px-6 py-5 space-y-4 text-xs text-slate-700">
            <!-- 1. Sumber Data Acuan -->
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-1.5">
              <div class="font-bold text-slate-900 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                <span>Sumber Data Acuan</span>
              </div>
              <p class="leading-relaxed">
                Basis data historis mengacu pada statistik resmi Badan Pangan Nasional (BAPANAS) dan Badan Pusat Statistik (BPS) untuk pola konsumsi per kapita dan neraca wilayah. Angka panen dan stok dikonsolidasikan dari pelaporan mitra BUMDes serta data contoh simulasi pertanian percontohan.
              </p>
            </div>

            <!-- 2. Cakupan Wilayah Percontohan -->
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-1.5">
              <div class="font-bold text-slate-900 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>Cakupan Wilayah Percontohan</span>
              </div>
              <p class="leading-relaxed">
                Sistem saat ini memantau <strong>34 kabupaten/kota percontohan</strong> di 10 provinsi sentra pangan nasional. Wilayah di luar percontohan ditampilkan netral berwarna abu-abu dengan label <em>Tanpa Data</em>.
              </p>
            </div>

            <!-- 3. Cara Membaca Status & Rasio Pasokan -->
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-2">
              <div class="font-bold text-slate-900 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                <span>Cara Membaca Status &amp; Rasio Pasokan</span>
              </div>
              <p class="leading-relaxed text-slate-600">
                Status dihitung dari perbandingan total pasokan terhadap kebutuhan konsumsi wilayah per bulan acuan:
              </p>
              <div class="overflow-x-auto rounded-lg border border-slate-200">
                <table class="w-full text-left border-collapse text-xs">
                  <thead>
                    <tr class="bg-slate-100 text-slate-700 border-b border-slate-200 font-bold">
                      <th class="py-1.5 px-2.5">Status</th>
                      <th class="py-1.5 px-2.5">Rentang Rasio</th>
                      <th class="py-1.5 px-2.5">Makna Indikator</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-200 bg-white">
                    <tr>
                      <td class="py-1.5 px-2.5 font-bold text-rose-700 flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: var(--status-defisit);"></span>
                        ▼ Defisit
                      </td>
                      <td class="py-1.5 px-2.5 font-semibold text-slate-800">&lt;{{ round(($ambangDefisit ?? 0.70) * 100) }}%</td>
                      <td class="py-1.5 px-2.5 text-slate-600">Pasokan kritis di bawah kebutuhan konsumsi; perlu intervensi logistik.</td>
                    </tr>
                    <tr>
                      <td class="py-1.5 px-2.5 font-bold text-emerald-700 flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: var(--status-seimbang);"></span>
                        ✔ Seimbang
                      </td>
                      <td class="py-1.5 px-2.5 font-semibold text-slate-800">{{ round(($ambangDefisit ?? 0.70) * 100) }}% &ndash; &lt;{{ round(($ambangWaspada ?? 0.90) * 100) }}%</td>
                      <td class="py-1.5 px-2.5 text-slate-600">Pasokan mencukupi kebutuhan konsumsi wilayah secara proporsional.</td>
                    </tr>
                    <tr>
                      <td class="py-1.5 px-2.5 font-bold text-amber-700 flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: var(--status-waspada);"></span>
                        ▲ Waspada
                      </td>
                      <td class="py-1.5 px-2.5 font-semibold text-slate-800">{{ round(($ambangWaspada ?? 0.90) * 100) }}% &ndash; &lt;{{ round(($ambangPenuh ?? 1.00) * 100) }}%</td>
                      <td class="py-1.5 px-2.5 text-slate-600">Pasokan mendekati kapasitas batas aman; mitigasi penumpukan stok.</td>
                    </tr>
                    <tr>
                      <td class="py-1.5 px-2.5 font-bold text-purple-700 flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: var(--status-penuh);"></span>
                        ◆ Penuh / Surplus
                      </td>
                      <td class="py-1.5 px-2.5 font-semibold text-slate-800">&ge;{{ round(($ambangPenuh ?? 1.00) * 100) }}%</td>
                      <td class="py-1.5 px-2.5 text-slate-600">Pasokan melimpah; waspada risiko pembusukan dan pemborosan pangan.</td>
                    </tr>
                    <tr>
                      <td class="py-1.5 px-2.5 font-medium text-slate-600 flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: var(--status-tanpa-data);"></span>
                        — Tanpa Data
                      </td>
                      <td class="py-1.5 px-2.5 font-semibold text-slate-500">—</td>
                      <td class="py-1.5 px-2.5 text-slate-500">Wilayah di luar 34 kabupaten/kota percontohan nasional.</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- 4. Model Prakiraan Pasokan -->
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-1.5">
              <div class="font-bold text-slate-900 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                <span>Model Prakiraan Pasokan</span>
              </div>
              <p class="leading-relaxed">
                Proyeksi menggunakan model <strong>Autoregressive XGBoost v1</strong> dengan horizon prakiraan 12 bulan ke depan. Model dilatih dari deret waktu historis panen dan tren konsumsi dengan tingkat deviasi rata-rata MAE &plusmn;3,8% (WAPE &plusmn;14,2%).
              </p>
            </div>
          </div>

          <div class="bg-slate-50 px-6 py-3.5 border-t border-slate-200 flex justify-end">
            <button type="button" onclick="tutupModalTentangData()" class="min-h-[44px] px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition shadow-xs">
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
<script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>

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
let map = null;
if (typeof L !== 'undefined') {
  map = L.map('peta-map', {
    center: [-2.5, 118.0], // Full Indonesia center default
    zoom: 4.5,
    minZoom: 2.5,
    maxZoom: 11,
    zoomSnap: 0.25,
    zoomDelta: 0.5,
    zoomControl: false,
    attributionControl: false,
  });
  window._leaflet_map = map;
}

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
  if (!map || typeof L === 'undefined') return;
  const activeLayer = state.level === 'provinsi' ? geoLayerProv : geoLayerKab;
  if (!activeLayer) return;

  const allBounds = L.latLngBounds();
  let count = 0;
  activeLayer.eachLayer(layer => {
    if (layer.feature && layer.feature.properties) {
      allBounds.extend(layer.getBounds());
      count++;
    }
  });

  if (count > 0 && allBounds.isValid()) {
    map.fitBounds(allBounds, { padding: [12, 12], animate: false });
  }
}

// ── Muat Data Status dari API ─────────────────────────────────────────────
async function muatStatusData() {
  const loadingEl = document.getElementById('peta-loading');
  const errorEl = document.getElementById('peta-error-state');
  const emptyEl = document.getElementById('peta-empty-state');

  if (loadingEl) loadingEl.classList.remove('opacity-0', 'pointer-events-none');
  if (errorEl) errorEl.classList.add('hidden');
  if (emptyEl) emptyEl.classList.add('hidden');

  // Pastikan frame peta spasial selalu ter-render
  try {
    await renderMapLayer();
  } catch (geoErr) {
    console.warn('[SIPANGAN] Base map frame loading:', geoErr);
  }

  try {
    const res = await fetch(`${API_BASE}/status?komoditas=${state.komoditas}&periode=${state.periode}&level=${state.level}`);
    if (!res.ok) throw new Error(`HTTP ${res.status}: Gagal memuat data status`);
    const json = await res.json();

    if (!json.data || json.data.length === 0) {
      if (emptyEl) emptyEl.classList.remove('hidden');
    }

    state.statusData = {};
    (json.data || []).forEach(d => { state.statusData[d.kode] = d; });

    // Update Banner dan KPI cards dari data riil ringkasan
    if (json.ringkasan) {
      updateKpiCards(json.ringkasan, json.komoditas);
    }

    updateAllLayerStyles();
    renderTabelData(json.data || []);
    buildSearchAutocomplete(json.data || []);

    // Refresh panel wilayah terpilih
    if (state.selectedKode) {
      updatePanelWilayah(state.selectedKode);
    }
  } catch (err) {
    console.error('[SIPANGAN] Gagal memuat status data:', err);
    if (errorEl) {
      errorEl.classList.remove('hidden');
      const msgEl = document.getElementById('peta-error-msg');
      if (msgEl) msgEl.textContent = err.message || 'Terjadi gangguan saat mengambil data status wilayah. Silakan periksa koneksi atau coba muat ulang.';
    }
    clearPanelWilayah();
  } finally {
    if (loadingEl) loadingEl.classList.add('opacity-0', 'pointer-events-none');
  }
}

// ── Update Reaktif Banner & 4 Kartu KPI Basis Data ─────────────────────────
function updateKpiCards(r, komoditas) {
  if (!r) return;

  // Banner Ringkas 1 Baris Dinamis Sesuai Data
  const bannerText = document.getElementById('banner-nasional-text');
  if (bannerText) {
    const totalWil = r.total_wilayah ?? 34;
    const penuhWil = r.jumlah_penuh ?? 0;
    const defisitWil = r.jumlah_defisit ?? 0;
    const waspadaWil = r.jumlah_waspada ?? 0;
    if (defisitWil > 0) {
      bannerText.textContent = `${penuhWil} dari ${totalWil} wilayah surplus · ${defisitWil} wilayah defisit memerlukan relokasi pasokan`;
    } else if (penuhWil > 0) {
      bannerText.textContent = `${penuhWil} dari ${totalWil} wilayah surplus; pertimbangkan penyimpanan atau pengolahan`;
    } else {
      bannerText.textContent = `${totalWil} wilayah pantau dalam kondisi seimbang dan aman`;
    }
  }

  // 4 Angka Kunci Horizontal Summary Strip
  const supplyEl = document.getElementById('kpi-total-supply');
  if (supplyEl && r.total_supply_formatted) supplyEl.textContent = r.total_supply_formatted;

  const demandVal = document.getElementById('kpi-demand-val');
  if (demandVal && r.total_demand_formatted) demandVal.textContent = r.total_demand_formatted;

  const jmlPenuhEl = document.getElementById('kpi-jumlah-penuh');
  if (jmlPenuhEl && r.jumlah_penuh !== undefined) jmlPenuhEl.textContent = r.jumlah_penuh;

  const jmlDefEl = document.getElementById('kpi-jumlah-defisit-waspada');
  if (jmlDefEl && r.jumlah_defisit_waspada !== undefined) jmlDefEl.textContent = r.jumlah_defisit_waspada;
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
  if (!map || typeof L === 'undefined') return;
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
    ? 'flex-1 min-h-[44px] py-2.5 rounded-lg text-xs font-bold transition bg-white text-slate-900 shadow-2xs flex items-center justify-center'
    : 'flex-1 min-h-[44px] py-2.5 rounded-lg text-xs font-bold transition text-slate-600 hover:text-slate-900 flex items-center justify-center';
  document.getElementById('btn-toggle-kab').className = !isProv
    ? 'flex-1 min-h-[44px] py-2.5 rounded-lg text-xs font-bold transition bg-white text-slate-900 shadow-2xs flex items-center justify-center'
    : 'flex-1 min-h-[44px] py-2.5 rounded-lg text-xs font-bold transition text-slate-600 hover:text-slate-900 flex items-center justify-center';

  muatStatusData();
}

function resetViewNasional() {
  fitToPilotBounds();
}

const BULAN_BERJALAN = '{{ $bulanBerjalan }}';

function updateTombolBulanBerjalan() {
  const btn = document.getElementById('btn-bulan-ini');
  const sel = document.getElementById('select-periode');
  if (!btn || !sel) return;
  if (sel.value === BULAN_BERJALAN) {
    btn.classList.add('hidden');
  } else {
    btn.classList.remove('hidden');
  }
}

function pilihBulanBerjalan() {
  const sel = document.getElementById('select-periode');
  if (!sel) return;
  sel.value = BULAN_BERJALAN;
  state.periode = BULAN_BERJALAN;
  updateTombolBulanBerjalan();
  updateMobileFilterSummary();
  muatStatusData();
}

function pilihBulanIni() {
  pilihBulanBerjalan();
}

function clearPanelWilayah() {
  const ids = [
    'panel-supply-formatted',
    'panel-demand-formatted',
    'panel-selisih',
  ];
  ids.forEach(id => {
    const el = document.getElementById(id);
    if (el) el.textContent = '—';
  });
  const statusLabel = document.getElementById('panel-status-label');
  if (statusLabel) {
    statusLabel.textContent = 'Data wilayah tidak dapat dimuat';
    statusLabel.className = 'text-sm sm:text-base font-bold mt-0.5 text-slate-600';
  }
  const rasioLabel = document.getElementById('panel-rasio-label');
  if (rasioLabel) {
    rasioLabel.textContent = '—';
    rasioLabel.className = 'text-xs font-bold mt-0.5 text-slate-500';
  }
  const neracaPeriode = document.getElementById('panel-neraca-periode');
  if (neracaPeriode) {
    neracaPeriode.textContent = '—';
  }
  const iconBox = document.getElementById('panel-status-icon-box');
  if (iconBox) {
    iconBox.className = 'w-10 h-10 rounded-xl flex items-center justify-center shrink-0 text-lg font-black bg-slate-200 text-slate-600';
    iconBox.textContent = '—';
  }
  const statusBox = document.getElementById('panel-status-box');
  if (statusBox) {
    statusBox.className = 'p-3.5 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between';
  }
  const dampakContainer = document.getElementById('panel-dampak-container');
  if (dampakContainer) dampakContainer.classList.add('hidden');
}

function formatPeriodeWithTag(periodeKode) {
  const selectPeriode = document.getElementById('select-periode');
  if (selectPeriode) {
    for (let i = 0; i < selectPeriode.options.length; i++) {
      const opt = selectPeriode.options[i];
      if (opt.value === periodeKode) {
        return opt.text.trim();
      }
    }
  }
  const parts = (periodeKode || '').split('-');
  if (parts.length === 2) {
    const bulanNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    const m = parseInt(parts[1], 10);
    const bName = bulanNames[m] || parts[1];
    const tag = periodeKode <= '2025-12' ? '(Aktual)' : '(Proyeksi)';
    return `${bName} ${parts[0]} ${tag}`;
  }
  return periodeKode || '—';
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
    const elNama = document.getElementById('panel-nama');
    if (elNama) elNama.textContent = w.nama;
    const elKode = document.getElementById('panel-kode');
    if (elKode) elKode.textContent = kode;
    const elProv = document.getElementById('panel-provinsi');
    if (elProv) elProv.textContent = w.nama_provinsi ?? w.nama;

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
        statusLabel.className = `text-lg font-black mt-0.5 ${conf.textClass}`;
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
      const neracaPeriode = document.getElementById('panel-neraca-periode');
      if (neracaPeriode) neracaPeriode.textContent = formatPeriodeWithTag(point.periode || state.periode);
      const supplyEl = document.getElementById('panel-supply-formatted');
      if (supplyEl) supplyEl.textContent = point.supply_formatted || '—';
      const demandEl = document.getElementById('panel-demand-formatted');
      if (demandEl) demandEl.textContent = point.demand_formatted || '—';

      const selisihKg = (point.supply_kg || 0) - (point.demand_kg || 0);
      const selisihEl = document.getElementById('panel-selisih');
      if (selisihEl) {
        selisihEl.textContent = IndoFormat.ton(selisihKg, 1, true);
        selisihEl.className = selisihKg >= 0 ? 'text-sm sm:text-base font-black text-rose-700 mt-0.5' : 'text-sm sm:text-base font-black text-blue-700 mt-0.5';
      }

      // Dampak Wilayah: ONE sentence about impact, shown only for penuh or defisit
      const dampakContainer = document.getElementById('panel-dampak-container');
      const pesanDampakEl = document.getElementById('panel-pesan-dampak');
      if (point.status === 'penuh' || point.status === 'defisit') {
        if (dampakContainer) dampakContainer.classList.remove('hidden');
        if (pesanDampakEl) {
          pesanDampakEl.textContent = json.pesan_dampak || (point.status === 'penuh'
            ? 'Pangan berisiko basi atau terbuang bila pasokan melebihi kapasitas serap wilayah.'
            : 'Wilayah mengalami defisit pasokan kritis; intervensi logistik darurat diperlukan.');
        }
      } else {
        if (dampakContainer) dampakContainer.classList.add('hidden');
      }
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
    clearPanelWilayah();
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
    searchDropdown.innerHTML = '<div class="p-4 text-xs text-slate-600 text-center font-semibold flex items-center justify-center gap-1.5"><svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg><span>Wilayah tidak ditemukan</span></div>';
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
      if (l.feature?.properties?.kode === kode) {
        if (map) map.fitBounds(l.getBounds(), { padding: [30, 30], maxZoom: 9 });
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
    tbody.innerHTML = '<tr><td colspan="6" class="px-4 py-8 text-center text-slate-600 font-medium text-xs">Tidak ada data untuk periode ini</td></tr>';
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
        <a href="/wilayah/${d.kode}?komoditas=${state.komoditas}" aria-label="Lihat detail wilayah ${d.nama}" class="min-h-[44px] min-w-[44px] inline-flex items-center justify-center px-3 py-2 text-emerald-700 hover:underline font-bold text-xs">Detail &rarr;</a>
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
let lastFocusedElement = null;

function bukaModalTentangData() {
  const modal = document.getElementById('modal-tentang-data');
  if (modal) {
    lastFocusedElement = document.activeElement;
    modal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
    const closeBtn = modal.querySelector('button[aria-label="Tutup modal informasi"]');
    if (closeBtn) closeBtn.focus();
  }
}

function tutupModalTentangData() {
  const modal = document.getElementById('modal-tentang-data');
  if (modal) {
    modal.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
    if (lastFocusedElement && typeof lastFocusedElement.focus === 'function') {
      lastFocusedElement.focus();
    }
  }
}

// Focus trap & Escape listener for modal
document.addEventListener('keydown', e => {
  const modal = document.getElementById('modal-tentang-data');
  if (!modal || modal.classList.contains('hidden')) return;

  if (e.key === 'Escape') {
    e.preventDefault();
    tutupModalTentangData();
    return;
  }

  if (e.key === 'Tab') {
    const focusables = Array.from(modal.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'))
      .filter(el => !el.hasAttribute('disabled') && el.offsetParent !== null);
    if (focusables.length === 0) return;
    const first = focusables[0];
    const last = focusables[focusables.length - 1];

    if (e.shiftKey) {
      if (document.activeElement === first) {
        e.preventDefault();
        last.focus();
      }
    } else {
      if (document.activeElement === last) {
        e.preventDefault();
        first.focus();
      }
    }
  }
});

// ── Banner Peringatan Nasional ───────────────────────────────────────────
function tutupBannerNasional() {
  const banner = document.getElementById('banner-nasional');
  if (banner) {
    banner.style.display = 'none';
    if (map && map.invalidateSize) {
      setTimeout(() => map.invalidateSize(), 50);
    }
  }
}

// ── ResizeObserver & Map Sizing Lifecycle ────────────────────────────────
if (typeof ResizeObserver !== 'undefined') {
  const mapContainer = document.getElementById('peta-map');
  if (mapContainer) {
    const ro = new ResizeObserver(() => {
      if (map && map.invalidateSize) {
        map.invalidateSize();
      }
    });
    ro.observe(mapContainer);
  }
}

// Fallback resize listener and delayed invalidateSize
window.addEventListener('resize', () => {
  if (map && map.invalidateSize) {
    map.invalidateSize();
  }
});
setTimeout(() => {
  if (map && map.invalidateSize) {
    map.invalidateSize();
  }
}, 300);

document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    tutupModalTentangData();
  }
});

// ── Mobile Filter Accordion Toggle (< md) ────────────────────────────────
function toggleMobileFilter() {
  const filterContainer = document.getElementById('filter-container');
  const chevron = document.getElementById('icon-filter-chevron');
  if (!filterContainer) return;
  const isHidden = filterContainer.classList.contains('hidden');
  if (isHidden) {
    filterContainer.classList.remove('hidden');
    if (chevron) chevron.classList.add('rotate-180');
  } else {
    filterContainer.classList.add('hidden');
    if (chevron) chevron.classList.remove('rotate-180');
  }
}

function updateMobileFilterSummary() {
  const summaryEl = document.getElementById('mobile-filter-summary');
  const selectKom = document.getElementById('select-komoditas');
  const selectPer = document.getElementById('select-periode');
  if (!summaryEl) return;
  const komText = selectKom?.options[selectKom.selectedIndex]?.text?.split(' ')[0] || state.komoditas;
  const perText = selectPer?.value || state.periode;
  summaryEl.textContent = `${komText} • ${perText}`;
}

// ── Event Filters ────────────────────────────────────────────────────────
document.getElementById('select-komoditas').addEventListener('change', e => {
  state.komoditas = e.target.value;
  updateMobileFilterSummary();
  muatStatusData();
});

document.getElementById('select-periode').addEventListener('change', e => {
  state.periode = e.target.value;
  updateTombolBulanBerjalan();
  updateMobileFilterSummary();
  muatStatusData();
});

// ── Inisialisasi Awal ────────────────────────────────────────────────────
updateTombolBulanBerjalan();
updateMobileFilterSummary();
muatStatusData();
</script>
@endpush
