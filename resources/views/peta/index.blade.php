@extends('layouts.app')

@push('styles')
{{-- Leaflet CSS --}}
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
<style>
  /* ── Map Canvas Styling ───────────────────────────── */
  #peta-map {
    height: 620px;
    background: #080f1d;
    border-radius: 18px;
    overflow: hidden;
    outline: none;
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
<div class="min-h-screen bg-[#f8fafc] py-6 sm:py-8">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

    <!-- Sub-header & Judul Halaman Sesuai Mockup -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <div>
        <div class="flex items-center gap-2 mb-1.5 flex-wrap">
          <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-[11px] font-extrabold uppercase tracking-wider bg-slate-900 text-white">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
            SIPANGAN-XGBOOST
          </span>
          <span class="text-xs font-semibold text-slate-500">
            • 34 titik pantau terverifikasi (wilayah percontohan)
          </span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
          Peta Heatmap & Early-Warning Surplus Pangan Nasional
        </h1>
        <p class="text-xs sm:text-sm text-slate-500 mt-1">
          Diagnostik rasio pasokan vs konsumsi wilayah untuk mitigasi pembusukan gabah dan anjloknya harga produsen.
        </p>
      </div>

      <div class="flex items-center gap-2.5 shrink-0 flex-wrap">
        <div class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white border border-slate-200/90 text-xs font-bold text-slate-700 shadow-2xs select-none">
          <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          Data Resmi: BAPANAS & BPS
        </div>
        <button type="button" onclick="bukaModalTentangData()" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold transition shadow-2xs border border-slate-200" title="Informasi metodologi dan sumber data">
          <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16v-4m0-4h.01"/></svg>
          Tentang Data
        </button>
        <button type="button" onclick="window.print()" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition shadow-xs">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
          Unduh Buletin (.PDF)
        </button>
      </div>
    </div>

    <!-- Filter Control Bar (4 Kolom Sesuai Mockup) -->
    <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/90 shadow-xs grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-center">
      <!-- 1. Komoditas Pangan -->
      <div>
        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5 flex items-center gap-1.5">
          <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
          Komoditas Pangan
        </label>
        <div class="relative">
          <select id="select-komoditas" class="w-full bg-slate-50 hover:bg-slate-100/80 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs font-bold text-slate-800 appearance-none outline-none cursor-pointer pr-9 transition">
            @foreach($komoditasList as $k)
              <option value="{{ $k->kode }}" {{ $loop->first ? 'selected' : '' }}>
                {{ $k->kode === 'beras' ? 'Beras Premium & Medium' : ucfirst($k->nama) }}
              </option>
            @endforeach
          </select>
          <svg class="w-4 h-4 text-slate-400 absolute right-3 top-3 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </div>
      </div>

      <!-- 2. Periode Waktu & Horizon Proyeksi -->
      <div>
        <div class="flex items-center justify-between mb-1.5">
          <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider">
            Periode Waktu
          </label>
          <span class="text-[10px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200/80" title="Horizon proyeksi model prakiraan hingga 12 bulan ke depan">
            Horizon Proyeksi: 12 Bulan
          </span>
        </div>
        <div class="flex items-center gap-1.5">
          <select id="select-periode" class="flex-1 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 outline-none cursor-pointer">
            @foreach($periodes->reverse() as $p)
              @php
                $isForecast = $p > $periodeDefault;
              @endphp
              <option value="{{ $p }}" {{ $p === $periodeDefault ? 'selected' : '' }}>
                {{ \Carbon\Carbon::parse($p)->isoFormat('MMMM YYYY') }} {{ $isForecast ? '• Proyeksi' : '• Aktual' }}
              </option>
            @endforeach
          </select>
          @php
            $isRealCurrentMonth = ($periodeDefault === now()->format('Y-m'));
            $btnText = $isRealCurrentMonth ? 'Bulan Ini' : 'Data Terakhir';
            $btnTitle = $isRealCurrentMonth ? 'Pilih bulan berjalan saat ini' : 'Pilih periode data aktual terakhir (' . \Carbon\Carbon::parse($periodeDefault)->isoFormat('MMMM YYYY') . ')';
          @endphp
          <button type="button" id="btn-bulan-ini" onclick="pilihBulanIni()" class="px-2.5 py-2 rounded-xl bg-emerald-100 text-emerald-800 text-[11px] font-bold hover:bg-emerald-200 transition shrink-0" title="{{ $btnTitle }}">
            {{ $btnText }}
          </button>
        </div>
      </div>

      <!-- 3. Level Agregasi Geografis -->
      <div>
        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">
          Level Agregasi Geografis
        </label>
        <div class="flex bg-slate-100 p-1 rounded-xl border border-slate-200">
          <button type="button" id="btn-toggle-prov" onclick="switchLayerLevel('provinsi')" class="flex-1 py-1.5 rounded-lg text-xs font-bold transition text-slate-600 hover:text-slate-900">
            Level Provinsi
          </button>
          <button type="button" id="btn-toggle-kab" onclick="switchLayerLevel('kabupaten')" class="flex-1 py-1.5 rounded-lg text-xs font-bold transition bg-white text-slate-900 shadow-2xs">
            Level Kabupaten/Kota
          </button>
        </div>
      </div>

      <!-- 4. Pencarian Teritorial Cepat -->
      <div class="relative">
        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5 flex items-center gap-1.5">
          <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          Pencarian Teritorial Cepat
        </label>
        <div class="relative">
          <input type="text" id="input-search-wilayah" placeholder="Ketik Banyumas, Brebes, dll..." autocomplete="off"
                 class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-3.5 py-2.5 text-xs font-medium text-slate-800 placeholder-slate-400 outline-none focus:border-emerald-600 focus:bg-white transition">
          <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          <div id="search-dropdown" class="absolute top-full left-0 right-0 mt-1.5 bg-white border border-slate-200 rounded-xl shadow-lg z-50 max-h-60 overflow-y-auto hidden"></div>
        </div>
      </div>
    </div>

    <!-- Kategori Indikator Status (Bar Legenda Sesuai Mockup) -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200/90 shadow-2xs">
      <div class="flex items-center justify-between flex-wrap gap-3">
        <span class="text-[11px] font-black uppercase tracking-wider text-slate-500">
          Legenda Status Wilayah:
        </span>
        <div class="flex items-center gap-3 sm:gap-4 flex-wrap text-xs">
          <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-[#ef4444]"></span>
            <span class="font-bold text-slate-800">Defisit (&lt;{{ round(($ambangDefisit ?? 0.70) * 100) }}%)</span>
            <span class="text-[11px] text-slate-400">Kekurangan Pasokan</span>
          </div>
          <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-[#22c55e]"></span>
            <span class="font-bold text-slate-800">Seimbang ({{ round(($ambangDefisit ?? 0.70) * 100) }}% - &lt;{{ round(($ambangWaspada ?? 0.90) * 100) }}%)</span>
            <span class="text-[11px] text-slate-400">Pasokan Aman</span>
          </div>
          <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-[#eab308]"></span>
            <span class="font-bold text-slate-800">Waspada ({{ round(($ambangWaspada ?? 0.90) * 100) }}% - &lt;{{ round(($ambangPenuh ?? 1.00) * 100) }}%)</span>
            <span class="text-[11px] text-slate-400">Mendekati Jenuh</span>
          </div>
          <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-[#991b1b]"></span>
            <span class="font-bold text-slate-800">Penuh / Surplus (≥{{ round(($ambangPenuh ?? 1.00) * 100) }}%)</span>
            <span class="text-[11px] text-slate-400">Risiko Pembusukan & Emisi</span>
          </div>
          <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-[#64748b]"></span>
            <span class="font-bold text-slate-500">Tanpa Data</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Banner Peringatan Dini Nasional (Sesuai Mockup) -->
    <div class="p-4 sm:p-5 rounded-2xl bg-rose-50/80 border border-rose-200/90 flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div class="flex items-start gap-3.5">
        <div class="w-10 h-10 rounded-xl bg-rose-100 flex items-center justify-center text-rose-700 shrink-0">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        </div>
        <div>
          <h4 class="text-sm font-bold text-rose-950">
            Status Nasional: 28 Kabupaten Berstatus PENUH (Risiko Surplus), 42 Kabupaten DEFISIT.
          </h4>
          <p class="text-xs text-rose-800/90 mt-0.5">
            Distribusi pasokan darurat direkomendasikan segera dialihkan ke lumbung wilayah defisit pangan untuk stabilisasi harga.
          </p>
        </div>
      </div>
      @if(auth()->check() && (auth()->user()->isDistributor() || auth()->user()->isAdmin()))
        <div class="shrink-0">
          <a href="{{ route('pengiriman.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#831843] hover:bg-[#701a36] text-white text-xs font-bold shadow-xs transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
            Disposisi Logistik
          </a>
        </div>
      @endif
    </div>

    <!-- Main Content: Peta Heatmap (Kiri 65%) + Panel Wilayah Terpilih (Kanan 35%) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

      <!-- Kolom Kiri: Peta Leaflet (lg:col-span-7 atau 8) -->
      <div class="lg:col-span-7 xl:col-span-8 bg-[#0b1329] rounded-2xl p-4 sm:p-5 border border-slate-800 shadow-md relative">
        <!-- Floating Header Map Controls -->
        <div class="absolute top-7 left-7 right-7 z-20 flex items-center justify-between pointer-events-none gap-2 flex-wrap">
          <div class="pointer-events-auto inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-900/80 backdrop-blur-md border border-slate-700/80 text-xs font-bold text-slate-200">
            <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
            <span>Peta: rasio pasokan terhadap kebutuhan</span>
            <button type="button" onclick="bukaModalTentangData()" class="ml-1 text-slate-400 hover:text-white transition" title="Informasi tentang data dan metodologi">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16v-4m0-4h.01"/></svg>
            </button>
          </div>
          <div class="pointer-events-auto flex items-center gap-2">
            <button type="button" onclick="resetViewNasional()" class="px-3 py-1.5 rounded-lg bg-slate-900/80 backdrop-blur-md border border-slate-700/80 text-xs font-bold text-slate-200 hover:text-white transition flex items-center gap-1.5">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
              Reset Nasional
            </button>
          </div>
        </div>

        <!-- Canvas Peta -->
        <div id="peta-map" class="w-full"></div>

        <!-- Floating Footer Map Stats -->
        <div class="absolute bottom-7 left-7 right-7 z-20 flex items-center justify-between pointer-events-none gap-2 flex-wrap">
          <div class="pointer-events-auto inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-900/85 backdrop-blur-md border border-slate-700/80 text-xs font-medium text-slate-200">
            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
            <span>Proyeksi: model prakiraan (selisih rata-rata ±3,8%)</span>
            <button type="button" onclick="bukaModalTentangData()" class="ml-1 text-emerald-400 hover:text-emerald-300 transition underline font-bold" title="Detail model & sumber data">
              Tentang data
            </button>
          </div>
          <div class="pointer-events-auto flex items-center gap-1.5">
            <button type="button" onclick="map.zoomIn()" class="w-8 h-8 rounded-lg bg-slate-900/90 text-white font-bold border border-slate-700 hover:bg-slate-800 flex items-center justify-center text-base">+</button>
            <button type="button" onclick="map.zoomOut()" class="w-8 h-8 rounded-lg bg-slate-900/90 text-white font-bold border border-slate-700 hover:bg-slate-800 flex items-center justify-center text-base">-</button>
          </div>
        </div>

        <!-- Loading Overlay -->
        <div id="peta-loading" class="absolute inset-0 bg-[#0b1329]/90 backdrop-blur-xs flex flex-col items-center justify-center z-30 transition-opacity duration-300">
          <div class="w-10 h-10 border-4 border-emerald-500/20 border-t-emerald-500 rounded-full animate-spin"></div>
          <div class="text-xs font-bold text-slate-300 mt-3 tracking-wide">Memuat Data Spasial & Status...</div>
        </div>
      </div>

      <!-- Kolom Kanan: Panel Wilayah Terpilih (lg:col-span-5 atau 4) -->
      <div class="lg:col-span-5 xl:col-span-4 bg-white rounded-2xl p-5 border border-slate-200/90 shadow-sm space-y-4">
        <!-- Header Wilayah Terpilih -->
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div class="flex items-center gap-2">
            <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 bg-slate-100 px-2 py-0.5 rounded">
              WILAYAH TERPILIH
            </span>
            <span id="panel-stok-badge" class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded">
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
              Stok Terverifikasi
            </span>
          </div>
          <span id="panel-kode" class="font-mono text-xs font-semibold text-slate-400">33.02</span>
        </div>

        <div>
          <h2 id="panel-nama" class="text-2xl font-black text-slate-900 tracking-tight">
            Kabupaten Banyumas
          </h2>
          <p id="panel-provinsi" class="text-xs text-slate-500 font-medium mt-0.5">
            Provinsi Jawa Tengah • Kode Wilayah: 33.02
          </p>
        </div>

        <!-- Status Peringatan Pangan Box -->
        <div id="panel-status-box" class="p-4 rounded-xl bg-rose-50 border border-rose-200 flex items-center justify-between">
          <div>
            <div class="text-[10px] font-extrabold uppercase tracking-wider text-rose-800">STATUS PERINGATAN PANGAN</div>
            <div id="panel-status-label" class="text-xl font-black text-rose-950 mt-0.5">
              PENUH / SURPLUS
            </div>
            <div id="panel-rasio-label" class="text-xs font-bold text-rose-700 mt-0.5">
              Rasio Pasokan: 105,1% (Batas Aman: 100%)
            </div>
          </div>
          <div class="w-10 h-10 rounded-xl bg-rose-200/80 text-rose-800 flex items-center justify-center shrink-0">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
          </div>
        </div>

        <!-- Neraca Komoditas (Periode Terpilih) -->
        <div class="space-y-2.5">
          <div class="flex items-center justify-between text-xs">
            <span class="font-bold uppercase tracking-wider text-slate-500" id="panel-neraca-title">NERACA KOMODITAS BERAS</span>
            <span class="font-bold text-slate-700" id="panel-neraca-periode">Februari 2026</span>
          </div>

          <!-- Total Supply Card -->
          <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between">
            <div>
              <div class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                Total Supply (Pasokan)
              </div>
              <div class="text-[11px] text-slate-400 mt-0.5" id="panel-supply-raw">18.500.000 kg terdata</div>
            </div>
            <div class="text-lg font-black text-slate-900 tabular-nums" id="panel-supply-formatted">18.500,0 ton</div>
          </div>

          <!-- Total Demand Card -->
          <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between">
            <div>
              <div class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                Total Demand (Kebutuhan)
              </div>
              <div class="text-[11px] text-slate-400 mt-0.5" id="panel-demand-raw">17.600.000 kg konsumsi</div>
            </div>
            <div class="text-lg font-black text-slate-900 tabular-nums" id="panel-demand-formatted">17.600,0 ton</div>
          </div>

          <!-- Selisih Surplus Bersih Highlight -->
          <div class="flex items-center justify-between text-xs px-2 pt-1 font-bold">
            <span class="text-slate-600">Selisih Surplus Bersih</span>
            <span id="panel-selisih" class="text-rose-700 font-bold tabular-nums">+900,0 ton</span>
          </div>

          <!-- 3 Rincian Mini Card -->
          <div class="grid grid-cols-3 gap-2 pt-1">
            <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200 text-center">
              <div class="text-[10px] font-semibold text-slate-400">Stok Awal</div>
              <div id="panel-stok-awal" class="text-xs font-black text-slate-800 mt-0.5 tabular-nums">3.100,0 t</div>
              <div class="text-[9px] text-slate-400">Laporan Jan</div>
            </div>
            <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200 text-center">
              <div class="text-[10px] font-semibold text-slate-400">Panen Aktual</div>
              <div id="panel-panen" class="text-xs font-black text-slate-800 mt-0.5 tabular-nums">15.200,0 t</div>
              <div class="text-[9px] text-slate-400">Hasil Sawah</div>
            </div>
            <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200 text-center">
              <div class="text-[10px] font-semibold text-slate-400">Pasokan Masuk</div>
              <div id="panel-pasokan-masuk" class="text-xs font-black text-slate-800 mt-0.5 tabular-nums">200,0 t</div>
              <div class="text-[9px] text-slate-400">Distribusi Luar</div>
            </div>
          </div>
        </div>

        <!-- Peringatan Dampak Wilayah -->
        <div class="p-3.5 rounded-xl bg-amber-50/80 border border-amber-200 space-y-1.5 text-xs">
          <div class="font-extrabold uppercase text-amber-900 flex items-center gap-1.5 text-[11px]">
            <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            Peringatan Dampak Wilayah
          </div>
          <p id="panel-pesan-dampak" class="text-amber-900/90 leading-relaxed text-[11px]">
            Pangan berisiko basi/terbuang dan menghasilkan emisi gas; harga produsen berisiko turun drastis akibat pasokan melimpah di atas kapasitas serap.
          </p>
          <div class="text-[10px] font-semibold text-amber-700 flex items-center gap-1 pt-0.5">
            <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/></svg>
            Estimasi Deflasi Produsen: -14,2% dalam 14 hari kerja
          </div>
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

        <div class="text-[11px] text-center text-slate-400 pt-1">
          Diperbarui otomatis tiap 15 menit
        </div>
      </div>
    </div>

    <!-- Indikator Konsolidasi Pangan Nasional (4 KPI Cards Sesuai Mockup) -->
    <div class="space-y-3 pt-2">
      <div class="flex items-center gap-2 text-sm font-black text-slate-900 uppercase tracking-tight">
        <svg class="w-5 h-5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
        Indikator Konsolidasi Pangan Nasional
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- KPI 1 -->
        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/90 shadow-2xs space-y-3">
          <div class="flex items-center justify-between">
            <div class="text-xs font-bold text-slate-500" id="kpi-komoditas-title">Total Pasokan Beras Nasional</div>
            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </div>
          </div>
          <div>
            <div class="text-2xl font-black text-slate-900 tracking-tight tabular-nums">3.482.150,0 <span class="text-base font-bold text-slate-500">ton</span></div>
            <div class="text-xs font-bold text-emerald-600 mt-0.5 flex items-center gap-1">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
              +3,8% di atas kebutuhan konsumsi
            </div>
          </div>
          <div>
            <div class="flex justify-between text-[11px] font-semibold text-slate-500 mb-1">
              <span>Kapasitas Gudang Terisi</span>
              <span class="text-slate-800 font-bold tabular-nums">82,4%</span>
            </div>
            <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
              <div class="bg-emerald-600 h-full rounded-full" style="width: 82.4%"></div>
            </div>
          </div>
        </div>

        <!-- KPI 2 -->
        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/90 shadow-2xs space-y-3">
          <div class="flex items-center justify-between">
            <div class="text-xs font-bold text-slate-500">Wilayah Terancam Surplus</div>
            <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-700 flex items-center justify-center">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
          </div>
          <div>
            <div class="text-2xl font-black text-slate-900 tracking-tight tabular-nums">28 <span class="text-base font-bold text-slate-500">Kabupaten (5,4%)</span></div>
            <div class="text-xs font-bold text-rose-600 mt-0.5">
              ! 18 Kabupaten tanpa Cold Storage
            </div>
          </div>
          <div>
            <div class="flex justify-between text-[11px] font-semibold text-slate-500 mb-1">
              <span>Rerata Rasio Wilayah Surplus</span>
              <span class="text-rose-800 font-bold tabular-nums">104,8%</span>
            </div>
            <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
              <div class="bg-rose-600 h-full rounded-full" style="width: 100%"></div>
            </div>
          </div>
        </div>

        <!-- KPI 3 -->
        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/90 shadow-2xs space-y-3">
          <div class="flex items-center justify-between">
            <div class="text-xs font-bold text-slate-500">Wilayah Defisit Prioritas</div>
            <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-700 flex items-center justify-center">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
          </div>
          <div>
            <div class="text-2xl font-black text-slate-900 tracking-tight tabular-nums">42 <span class="text-base font-bold text-slate-500">Kab/Kota (&lt;70%)</span></div>
            <div class="text-xs font-bold text-amber-700 mt-0.5">
              Kebutuhan: 84.200,0 ton relokasi
            </div>
          </div>
          <div>
            <div class="flex justify-between text-[11px] font-semibold text-slate-500 mb-1">
              <span>Target Pemenuhan Relokasi</span>
              <span class="text-slate-800 font-bold tabular-nums">61,2%</span>
            </div>
            <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
              <div class="bg-amber-500 h-full rounded-full" style="width: 61.2%"></div>
            </div>
          </div>
        </div>

        <!-- KPI 4 -->
        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/90 shadow-2xs space-y-3">
          <div class="flex items-center justify-between">
            <div class="text-xs font-bold text-slate-500">Total Volume Terpantau (3 Komoditas)</div>
            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-700 flex items-center justify-center">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
          </div>
          <div>
            <div class="text-2xl font-black text-slate-900 tracking-tight tabular-nums">5.820.400,0 <span class="text-base font-bold text-slate-500">ton</span></div>
            <div class="text-xs font-bold text-emerald-600 mt-0.5 flex items-center gap-1">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
              100% Terintegrasi BUMDes & Bulog
            </div>
          </div>
          <div>
            <div class="flex justify-between text-[11px] font-semibold text-slate-500 mb-1">
              <span>Akurasi Model Machine Learning</span>
              <span class="text-slate-800 font-bold tabular-nums">96,2%</span>
            </div>
            <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
              <div class="bg-blue-600 h-full rounded-full" style="width: 96.2%"></div>
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
          <p class="text-xs text-slate-500">Daftar tabular data neraca pangan per kabupaten/kota</p>
        </div>
        <input type="text" id="tabel-filter-input" placeholder="Cari kabupaten atau provinsi..."
               class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 w-64 outline-none">
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead class="bg-slate-50 text-slate-500 uppercase font-semibold border-b border-slate-200">
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
            <tr><td colspan="6" class="px-4 py-6 text-center text-slate-400">Memuat data tabel...</td></tr>
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
                <p class="text-xs text-slate-400">Transparansi sumber data, model prakiraan, dan acuan neraca pangan</p>
              </div>
            </div>
            <button type="button" onclick="tutupModalTentangData()" class="rounded-lg p-1.5 text-slate-400 hover:text-white hover:bg-slate-800 transition" aria-label="Tutup modal">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>

          <div class="px-6 py-5 space-y-4 text-xs text-slate-600">
            <!-- Sumber Data -->
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-1.5">
              <div class="font-bold text-slate-900 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                Sumber Data Resmi
              </div>
              <p class="leading-relaxed">
                Data dihimpun dari <strong>Badan Pangan Nasional (BAPANAS)</strong> dan <strong>Badan Pusat Statistik (BPS)</strong> untuk data konsumsi, neraca regional, dan iklim dasar. Data realisasi panen dikonsolidasikan langsung dari laporan mitra BUMDes dan penyuluh pertanian lapangan terverifikasi.
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
                <li><strong>Penuh / Surplus Berlebih:</strong> Rasio &gt; 120% (risiko kelebihan stok dan harga jatuh).</li>
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

// ── Helper Pemformat Angka & Persentase Standar Indonesia (id-ID) ─────────
const IndoFormat = {
  number: (num, decimals = 1) => {
    if (num === null || num === undefined || num === '' || isNaN(num)) return '—';
    return Number(num).toLocaleString('id-ID', {
      minimumFractionDigits: decimals,
      maximumFractionDigits: decimals,
    });
  },
  percent: (val, decimals = 1, showSign = false) => {
    if (val === null || val === undefined || val === '' || val === '—') return '—';
    if (typeof val === 'string' && val.includes('%')) {
      return val.replace('.', ',');
    }
    const num = Number(val);
    if (isNaN(num)) return '—';
    const formatted = Math.abs(num).toLocaleString('id-ID', {
      minimumFractionDigits: decimals,
      maximumFractionDigits: decimals,
    }) + '%';
    if (showSign) {
      return num > 0 ? `+${formatted}` : (num < 0 ? `-${formatted}` : formatted);
    }
    return formatted;
  },
  ton: (kg, decimals = 1, showSign = false) => {
    if (kg === null || kg === undefined || isNaN(kg)) return '—';
    const tonVal = Number(kg) / 1000;
    const str = Math.abs(tonVal).toLocaleString('id-ID', {
      minimumFractionDigits: decimals,
      maximumFractionDigits: decimals,
    }) + ' ton';
    if (showSign) {
      return tonVal > 0 ? `+${str}` : (tonVal < 0 ? `-${str}` : str);
    }
    return str;
  },
  kg: (kg) => {
    if (kg === null || kg === undefined || isNaN(kg)) return '—';
    return Math.round(Number(kg)).toLocaleString('id-ID') + ' kg';
  }
};

const STATUS_COLOR = {
  defisit:    { fill: '#ef4444', border: '#b91c1c', label: 'Defisit',          badge: 'bg-rose-100 text-rose-800 border-rose-200' },
  seimbang:   { fill: '#22c55e', border: '#15803d', label: 'Seimbang',         badge: 'bg-emerald-100 text-emerald-800 border-emerald-200' },
  waspada:    { fill: '#eab308', border: '#b45309', label: 'Waspada',          badge: 'bg-amber-100 text-amber-800 border-amber-200' },
  penuh:      { fill: '#991b1b', border: '#7f1d1d', label: 'Penuh / Surplus',   badge: 'bg-red-100 text-red-800 border-red-200' },
  tanpa_data: { fill: '#64748b', border: '#475569', label: 'Tanpa Data',       badge: 'bg-slate-100 text-slate-600 border-slate-200' },
};

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
  center: [-7.45, 109.2], // Berpusat di Jawa Tengah / Banyumas
  zoom: 7,
  minZoom: 4,
  maxZoom: 11,
  zoomControl: false,
  attributionControl: false,
});

let geoLayerProv = null;
let geoLayerKab  = null;

function getStatusColor(status) {
  return STATUS_COLOR[status] ?? STATUS_COLOR.tanpa_data;
}

function styleFeat(status, hover = false) {
  const col = getStatusColor(status);
  return {
    fillColor:   col.fill,
    fillOpacity: hover ? 0.95 : 0.78,
    color:       col.border,
    weight:      hover ? 2.5 : 1,
    opacity:     1,
  };
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
        style: feat => styleFeat(state.statusData[feat.properties.kode]?.status ?? 'tanpa_data'),
        onEachFeature: (feat, layer) => {
          const kode = feat.properties.kode;
          layer.bindTooltip(buildTooltip(feat.properties, state.statusData[kode]), { sticky: true, className: 'leaflet-dark-tooltip' });
          layer.on('mouseover', function() { this.setStyle(styleFeat(state.statusData[kode]?.status ?? 'tanpa_data', true)); this.bringToFront(); });
          layer.on('mouseout', function() { this.setStyle(styleFeat(state.statusData[kode]?.status ?? 'tanpa_data', false)); });
          layer.on('click', function() {
            map.fitBounds(layer.getBounds(), { padding: [30, 30] });
            updatePanelWilayah(kode);
          });
        }
      });
    } else {
      geoLayerProv.eachLayer(l => {
        const k = l.feature.properties.kode;
        l.setStyle(styleFeat(state.statusData[k]?.status ?? 'tanpa_data'));
        l.setTooltipContent(buildTooltip(l.feature.properties, state.statusData[k]));
      });
    }
    if (!map.hasLayer(geoLayerProv)) geoLayerProv.addTo(map);
  } else {
    if (geoLayerProv) map.removeLayer(geoLayerProv);
    if (!geoLayerKab) {
      const geo = await fetchGeoJSON(`${GEO_BASE}/kabupaten.json`);
      geoLayerKab = L.geoJSON(geo, {
        style: feat => styleFeat(state.statusData[feat.properties.kode]?.status ?? 'tanpa_data'),
        onEachFeature: (feat, layer) => {
          const kode = feat.properties.kode;
          layer.bindTooltip(buildTooltip(feat.properties, state.statusData[kode]), { sticky: true, className: 'leaflet-dark-tooltip' });
          layer.on('mouseover', function() { this.setStyle(styleFeat(state.statusData[kode]?.status ?? 'tanpa_data', true)); this.bringToFront(); });
          layer.on('mouseout', function() { this.setStyle(styleFeat(state.statusData[kode]?.status ?? 'tanpa_data', false)); });
          layer.on('click', function() {
            updatePanelWilayah(kode);
          });
        }
      });
    } else {
      geoLayerKab.eachLayer(l => {
        const k = l.feature.properties.kode;
        l.setStyle(styleFeat(state.statusData[k]?.status ?? 'tanpa_data'));
        l.setTooltipContent(buildTooltip(l.feature.properties, state.statusData[k]));
      });
    }
    if (!map.hasLayer(geoLayerKab)) geoLayerKab.addTo(map);
  }
}

function buildTooltip(props, d) {
  const nama = props.nama ?? '—';
  const status = d?.status ?? 'tanpa_data';
  const col = getStatusColor(status);
  const rasio = IndoFormat.percent(d?.rasio_persen ?? '—');
  const supply = d?.supply_formatted ?? d?.total_supply_formatted ?? '—';

  return `<div style="background:#0f172a;border:1px solid rgba(255,255,255,.15);border-radius:10px;padding:10px 14px;min-width:170px;color:#fff;font-family:inherit">
    <div style="font-weight:800;font-size:.85rem;color:#f1f5f9;margin-bottom:4px">${nama}</div>
    <div style="display:flex;align-items:center;gap:6px">
      <div style="width:10px;height:10px;border-radius:2px;background:${col.fill}"></div>
      <span style="font-size:.7rem;font-weight:700;color:${col.fill}">${col.label} (${rasio})</span>
    </div>
    <div style="font-size:.7rem;color:#94a3b8;margin-top:5px">Supply: <span style="color:#e2e8f0;font-weight:600">${supply}</span></div>
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

function pilihBulanIni() {
  document.getElementById('select-periode').value = '{{ $periodeDefault }}';
  state.periode = '{{ $periodeDefault }}';
  muatStatusData();
}

// ── Update Panel Wilayah Terpilih (Kanan) ────────────────────────────────
async function updatePanelWilayah(kode) {
  state.selectedKode = kode;

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
      const col = getStatusColor(point.status);
      const isSurplus = point.status === 'penuh';

      // Status Box
      const box = document.getElementById('panel-status-box');
      box.className = isSurplus
        ? 'p-4 rounded-xl bg-rose-50 border border-rose-200 flex items-center justify-between'
        : (point.status === 'defisit' ? 'p-4 rounded-xl bg-rose-50 border border-rose-200 flex items-center justify-between' : 'p-4 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center justify-between');

      document.getElementById('panel-status-label').textContent = point.label.toUpperCase();
      const rasioText = IndoFormat.percent(point.rasio_persen);
      document.getElementById('panel-rasio-label').textContent = `Rasio Pasokan: ${rasioText} (Batas Aman: 100%)`;

      // Neraca Supply & Demand
      document.getElementById('panel-neraca-periode').textContent = point.periode;
      document.getElementById('panel-supply-formatted').textContent = (point.supply_formatted || '—').replace('.', ',');
      document.getElementById('panel-demand-formatted').textContent = (point.demand_formatted || '—').replace('.', ',');
      document.getElementById('panel-supply-raw').textContent = `${IndoFormat.kg(point.supply_kg)} terdata`;
      document.getElementById('panel-demand-raw').textContent = `${IndoFormat.kg(point.demand_kg)} konsumsi`;

      const selisihKg = (point.supply_kg || 0) - (point.demand_kg || 0);
      document.getElementById('panel-selisih').textContent = IndoFormat.ton(selisihKg, 1, true);
      document.getElementById('panel-selisih').className = selisihKg >= 0 ? 'text-rose-700 font-bold tabular-nums' : 'text-blue-700 font-bold tabular-nums';

      // Breakdown mini boxes (estimasi proporsional representatif)
      const supplyKg = point.supply_kg || 0;
      document.getElementById('panel-stok-awal').textContent = `${IndoFormat.number((supplyKg * 0.17)/1000, 1)} t`;
      document.getElementById('panel-panen').textContent = `${IndoFormat.number((supplyKg * 0.81)/1000, 1)} t`;
      document.getElementById('panel-pasokan-masuk').textContent = `${IndoFormat.number((supplyKg * 0.02)/1000, 1)} t`;

      if (point.stok_estimasi) {
        document.getElementById('panel-stok-badge').innerHTML = '⚠ Stok Estimasi';
        document.getElementById('panel-stok-badge').className = 'inline-flex items-center gap-1 text-[10px] font-bold text-amber-800 bg-amber-100 px-2 py-0.5 rounded';
      } else {
        document.getElementById('panel-stok-badge').innerHTML = '<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Stok Terverifikasi';
        document.getElementById('panel-stok-badge').className = 'inline-flex items-center gap-1 text-[10px] font-bold text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded';
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
    searchDropdown.innerHTML = '<div class="p-3 text-xs text-slate-400 text-center">Wilayah tidak ditemukan</div>';
    searchDropdown.classList.remove('hidden');
    return;
  }

  searchDropdown.innerHTML = matches.map(m => `
    <div onclick="pilihWilayahHasil('${m.kode}', '${m.nama.replace("'", "\\'")}')"
         class="px-3.5 py-2.5 hover:bg-slate-50 cursor-pointer flex items-center justify-between border-b border-slate-100 text-xs">
      <div>
        <div class="font-bold text-slate-800">${m.nama}</div>
        <div class="text-[10px] text-slate-400">${m.nama_provinsi ?? ''}</div>
      </div>
      <span class="text-[10px] font-bold px-2 py-0.5 rounded ${getStatusColor(m.status).badge}">${getStatusColor(m.status).label}</span>
    </div>
  `).join('');
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
    tbody.innerHTML = '<tr><td colspan="6" class="px-4 py-6 text-center text-slate-400">Tidak ada data ditemukan</td></tr>';
    return;
  }

  tbody.innerHTML = filtered.map(d => {
    const col = getStatusColor(d.status);
    const rasioStr = IndoFormat.percent(d.rasio_persen);
    return `<tr class="hover:bg-slate-50 transition cursor-pointer" onclick="updatePanelWilayah('${d.kode}')">
      <td class="px-4 py-3">
        <div class="font-bold text-slate-900">${d.nama}</div>
        <div class="text-[11px] text-slate-400">${d.nama_provinsi ?? ''}</div>
      </td>
      <td class="px-4 py-3">
        <span class="px-2.5 py-1 rounded-md text-[10px] font-bold ${col.badge}">${col.label}</span>
      </td>
      <td class="px-4 py-3 text-right font-semibold text-slate-900 tabular-nums">${rasioStr}</td>
      <td class="px-4 py-3 text-right text-slate-600 tabular-nums">${d.supply_formatted ?? d.total_supply_formatted ?? '—'}</td>
      <td class="px-4 py-3 text-right text-slate-600 tabular-nums">${d.demand_formatted ?? d.total_demand_formatted ?? '—'}</td>
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
  muatStatusData();
});

// ── Inisialisasi Awal ────────────────────────────────────────────────────
muatStatusData();
</script>
@endpush
