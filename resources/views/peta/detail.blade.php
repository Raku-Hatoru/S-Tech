@extends('layouts.app')

@push('styles')
{{-- Chart.js CDN --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<style>
  .badge-defisit    { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
  .badge-seimbang   { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
  .badge-waspada    { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
  .badge-penuh      { background: #fee2e2; color: #991b1b; border: 1px solid #f87171; }
  .badge-tanpa_data { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
</style>
@endpush

@section('content')
<div class="min-h-screen bg-[#f8fafc] py-6 sm:py-8 text-slate-800">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

    <!-- Subheader: Breadcrumb & Sync Info & Download CSV -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 text-xs text-slate-500 border-b border-slate-200/80 pb-4">
      <nav class="flex items-center gap-2 flex-wrap font-medium">
        <a href="{{ route('home') }}" class="flex items-center gap-1 hover:text-emerald-700 transition">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m3 12 2-2m0 0 7-7 7 7M5 10v10a1 1 0 0 0 1 1h3m10-11 2 2m-2-2v10a1 1 0 0 1-1 1h-3m-6 0a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1m-6 0h6"/></svg>
          Beranda
        </a>
        <span class="text-slate-400">/</span>
        @if ($isProvinsi)
          <span class="text-slate-800 font-semibold">{{ $namaWilayah }}</span>
        @else
          <a href="{{ route('wilayah.detail', ['kode' => $kodeProvinsi, 'komoditas' => $komoditas->kode]) }}" class="hover:text-emerald-700 transition">
            {{ $namaProvinsiInduk }}
          </a>
          <span class="text-slate-400">/</span>
          <span class="text-slate-800 font-semibold">{{ $tipeWilayah }} {{ $namaWilayah }} (Kode Wilayah: {{ $kode }})</span>
        @endif
        <span class="text-slate-400">/</span>
        <div class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-emerald-800 text-white font-semibold">
          <svg class="w-3 h-3 text-emerald-300" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1.323l3.954 1.582 1.599-.8a1 1 0 01.894 1.79l-1.233.616 1.738 5.42a1 1 0 01-.285 1.05A3.989 3.989 0 0115 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.715-5.349L11 6.477V16h2a1 1 0 110 2H7a1 1 0 110-2h2V6.477L6.237 7.582l1.715 5.349a1 1 0 01-.285 1.05A3.989 3.989 0 015 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.738-5.42-1.233-.617a1 1 0 01.894-1.788l1.599.799L9 4.323V3a1 1 0 011-1z" clip-rule="evenodd"/></svg>
          <span>Komoditas: {{ $komoditas->nama }}</span>
        </div>
      </nav>

      <div class="flex items-center gap-4 flex-wrap">
        <div class="flex items-center gap-1.5 text-slate-500">
          <svg class="w-3.5 h-3.5 text-emerald-600 animate-spin-slow" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
          <span>Sinkronisasi Terakhir: <b class="text-slate-700">19 Feb 2026, 08:30 WIB</b></span>
        </div>
        <button onclick="unduhRekapCSV()"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 shadow-xs transition">
          <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
          <span>Unduh Rekap CSV Data</span>
        </button>
      </div>
    </div>

    <!-- Alert Banner (Coral / Red Early Warning Banner) -->
    @php
      $isPenuh = ($statusTerbaru['status'] ?? '') === 'penuh';
      $isWaspada = ($statusTerbaru['status'] ?? '') === 'waspada';
      $isDefisit = ($statusTerbaru['status'] ?? '') === 'defisit';
      $rasioDisplay = $statusTerbaru['rasio'] !== null ? number_format($statusTerbaru['rasio'], 4, '.', '') . ' (' . ($statusTerbaru['rasio_persen'] ?? '—') . ')' : '—';
    @endphp

    <div class="rounded-2xl p-4 sm:p-5 border flex flex-col md:flex-row md:items-center md:justify-between gap-4 shadow-xs {{ $isPenuh ? 'bg-red-50/90 border-red-200' : ($isDefisit ? 'bg-rose-50 border-rose-200' : ($isWaspada ? 'bg-amber-50 border-amber-200' : 'bg-emerald-50 border-emerald-200')) }}">
      <div class="flex items-start gap-3.5">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 {{ $isPenuh ? 'bg-red-600 text-white' : ($isDefisit ? 'bg-rose-600 text-white' : ($isWaspada ? 'bg-amber-500 text-white' : 'bg-emerald-600 text-white')) }}">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        </div>
        <div>
          <div class="flex items-center gap-2 flex-wrap">
            <span class="px-2.5 py-0.5 rounded text-[11px] font-extrabold uppercase tracking-wide {{ $isPenuh ? 'bg-red-600 text-white' : ($isDefisit ? 'bg-rose-600 text-white' : ($isWaspada ? 'bg-amber-600 text-white' : 'bg-emerald-600 text-white')) }}">
              STATUS: {{ strtoupper($statusTerbaru['label'] ?? 'Penuh / Surplus') }}
            </span>
            <span class="text-xs font-bold text-slate-800">
              Rasio: {{ $rasioDisplay }}
            </span>
            <span class="text-xs font-semibold text-sky-700">
              Ambang Bahaya &gt; 100%
            </span>
          </div>
          <p class="text-xs sm:text-sm text-slate-700 font-medium mt-1 leading-relaxed">
            @if ($isPenuh)
              <b>Peringatan Sistem Ketahanan Pangan Nasional:</b> Tekanan kelebihan kapasitas gudang aktif terdeteksi di {{ $tipeWilayah }} {{ $namaWilayah }}.
            @elseif ($isDefisit)
              <b>Peringatan Defisit Pasokan:</b> Pasokan pangan di {{ $tipeWilayah }} {{ $namaWilayah }} di bawah batas aman (&lt;70%). Dibutuhkan pasokan segera.
            @elseif ($isWaspada)
              <b>Peringatan Waspada:</b> Kapasitas pasokan di {{ $tipeWilayah }} {{ $namaWilayah }} mendekati titik jenuh (90%–100%).
            @else
              <b>Status Terpantau Aman:</b> Neraca pasokan dan kebutuhan konsumsi di {{ $tipeWilayah }} {{ $namaWilayah }} dalam kondisi seimbang.
            @endif
          </p>
        </div>
      </div>

      <div class="shrink-0 flex items-center gap-2">
        <a href="#koridor-redistribusi"
           class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-[#800000] hover:bg-[#660000] shadow-sm transition">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
          <span>Tinjau Rute Redistribusi</span>
        </a>
      </div>
    </div>

    <!-- 4 Metric KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <!-- Card 1: Total Supply Aktif -->
      <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-200 relative overflow-hidden flex flex-col justify-between">
        <div>
          <div class="flex items-center justify-between text-xs font-bold uppercase tracking-wider text-slate-500">
            <span>Total Supply Aktif</span>
            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
          </div>
          <div class="mt-2 text-2xl font-black text-slate-900 tracking-tight">
            {{ $cardSupply['total_formatted'] }}
          </div>
          <div class="mt-4 pt-3 border-t border-slate-100 space-y-1.5 text-xs text-slate-600">
            <div class="flex justify-between">
              <span class="text-slate-500">Panen Aktual:</span>
              <span class="font-bold text-slate-800">{{ $cardSupply['panen_formatted'] }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500">Stok Awal Gudang:</span>
              <span class="font-bold text-slate-800">{{ $cardSupply['stok_awal_formatted'] }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500">Pasokan Masuk:</span>
              <span class="font-bold text-slate-800">{{ $cardSupply['pasokan_masuk_formatted'] }}</span>
            </div>
          </div>
        </div>
        <div class="mt-4 -mx-5 -mb-5 h-1.5 bg-red-600"></div>
      </div>

      <!-- Card 2: Demand Terproyeksi -->
      <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-200 relative overflow-hidden flex flex-col justify-between">
        <div>
          <div class="flex items-center justify-between text-xs font-bold uppercase tracking-wider text-slate-500">
            <span>Demand Terproyeksi</span>
            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
          </div>
          <div class="mt-2 text-2xl font-black text-slate-900 tracking-tight">
            {{ $cardDemand['total_formatted'] }}
          </div>
          <div class="mt-4 pt-3 border-t border-slate-100 space-y-1.5 text-xs text-slate-600">
            <div class="flex justify-between">
              <span class="text-slate-500">Konsumsi Domestik:</span>
              <span class="font-bold text-slate-800">{{ $cardDemand['domestik_formatted'] }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500">Kebutuhan Industri:</span>
              <span class="font-bold text-slate-800">{{ $cardDemand['industri_formatted'] }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500">Cadangan Wajib:</span>
              <span class="font-bold text-slate-800">{{ $cardDemand['cadangan_formatted'] }}</span>
            </div>
          </div>
        </div>
        <div class="mt-4 -mx-5 -mb-5 h-1.5 bg-emerald-600"></div>
      </div>

      <!-- Card 3: Surplus Bersih Kritis -->
      <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-200 relative overflow-hidden flex flex-col justify-between">
        <div>
          <div class="flex items-center justify-between text-xs font-bold uppercase tracking-wider text-slate-500">
            <span>{{ $cardSurplus['is_surplus'] ? 'Surplus Bersih Kritis' : 'Defisit Bersih Kritis' }}</span>
            <div class="w-5 h-5 rounded-full {{ $cardSurplus['is_surplus'] ? 'bg-red-100 text-red-600' : 'bg-rose-100 text-rose-600' }} flex items-center justify-center font-bold text-xs">
              {{ $cardSurplus['is_surplus'] ? '↑' : '↓' }}
            </div>
          </div>
          <div class="mt-2 text-2xl font-black {{ $cardSurplus['is_surplus'] ? 'text-red-600' : 'text-rose-600' }} tracking-tight">
            {{ $cardSurplus['selisih_formatted'] }}
          </div>
          <div class="mt-4 pt-3 border-t border-slate-100 space-y-1.5 text-xs text-slate-600">
            <div class="flex justify-between">
              <span class="text-slate-500">Status Risiko:</span>
              <span class="font-bold {{ $cardSurplus['is_surplus'] ? 'text-red-600' : 'text-rose-600' }}">{{ $cardSurplus['status_risiko'] }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500">Kapasitas Tersedia:</span>
              <span class="font-bold text-slate-800">{{ $cardSurplus['kapasitas_tersedia'] }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500">Tingkat Kepenuhan:</span>
              <span class="font-bold text-slate-800">{{ $cardSurplus['tingkat_kepenuhan'] }}</span>
            </div>
          </div>
        </div>
        <div class="mt-4 -mx-5 -mb-5 h-1.5 {{ $cardSurplus['is_surplus'] ? 'bg-red-600' : 'bg-rose-600' }}"></div>
      </div>

      <!-- Card 4: Pengiriman Aktif Masuk -->
      <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-200 relative overflow-hidden flex flex-col justify-between">
        <div>
          <div class="flex items-center justify-between text-xs font-bold uppercase tracking-wider text-slate-500">
            <span>Pengiriman Aktif Masuk</span>
            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
          </div>
          <div class="mt-2 text-2xl font-black text-slate-900 tracking-tight">
            {{ $cardPengiriman['total_formatted'] }}
          </div>
          <div class="mt-4 pt-3 border-t border-slate-100 space-y-1.5 text-xs text-slate-600">
            <div class="flex justify-between">
              <span class="text-slate-500">Kanal Distribusi:</span>
              <span class="font-medium text-slate-800">Mitra Logistik Terdaftar</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500">Kurir / Armada:</span>
              <span class="font-bold text-slate-800">{{ $cardPengiriman['kurir'] }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500">Status Transit:</span>
              <span class="font-bold text-slate-800">{{ $cardPengiriman['status_transit'] }}</span>
            </div>
          </div>
        </div>
        <div class="mt-4 -mx-5 -mb-5 h-1.5 bg-amber-500"></div>
      </div>
    </div>

    <!-- Chart Deret Waktu 12 Bulan (Aktual vs Prediksi XGBoost Multi-Horizon) -->
    <div class="bg-white rounded-2xl p-6 shadow-xs border border-slate-200/90 space-y-4">
      <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 border-b border-slate-100 pb-4">
        <div>
          <h2 class="text-base sm:text-lg font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
            <svg class="w-5 h-5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
            <span>Grafik Deret Waktu 12 Bulan (Aktual vs Prediksi XGBoost Multi-Horizon)</span>
          </h2>
          <p class="text-xs text-slate-500 mt-0.5">
            Perbandingan Kurva Supply Bergulir vs Proyeksi Demand (Feb 2026 – Jan 2027) dengan interval kepercayaan 90% (CI).
          </p>
        </div>

        <div class="flex items-center gap-3 flex-wrap text-xs">
          <div class="flex items-center gap-1.5 font-medium text-slate-700">
            <span class="w-3 h-3 rounded-full bg-red-600 inline-block"></span>
            <span>Supply Bergulir</span>
          </div>
          <div class="flex items-center gap-1.5 font-medium text-slate-700">
            <span class="w-4 h-0.5 bg-emerald-700 inline-block"></span>
            <span>Garis Kebutuhan (Demand)</span>
          </div>
          <div class="flex items-center gap-1.5 font-medium text-slate-700">
            <span class="w-3 h-3 rounded-xs bg-red-100 border border-red-300 inline-block"></span>
            <span>Zona Surplus Bahaya (&gt;100%)</span>
          </div>
          <span class="px-2.5 py-1 rounded-md text-[11px] font-bold bg-sky-50 text-sky-800 border border-sky-200">
            Model: {{ $modelVersion['nama'] }} (WAPE: {{ $modelVersion['wape'] }})
          </span>
        </div>
      </div>

      <!-- Canvas Chart Container -->
      <div class="relative w-full h-[380px] sm:h-[440px]">
        <div id="chart-loading" class="absolute inset-0 bg-white/80 backdrop-blur-xs flex items-center justify-center z-10">
          <div class="flex items-center gap-2 text-xs font-bold text-slate-600">
            <svg class="w-4 h-4 animate-spin text-emerald-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
            Memuat kurva deret waktu 12 bulan...
          </div>
        </div>
        <canvas id="chart12Bulan"></canvas>
      </div>

      <!-- Catatan Footnote Chart -->
      <div class="pt-2 text-xs text-slate-500 space-y-1">
        <div class="flex items-center gap-1.5">
          <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <span><b>Catatan:</b> *Bulan Februari 2026 merupakan data real-time campuran panen faktual + stok fisik BUMDes. Maret 2026 - Januari 2027 diproyeksikan oleh XGBoost Multi-Horizon.</span>
        </div>
        <div class="flex items-center gap-1.5 text-emerald-800 font-medium">
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
          <span>Jadwal Retraining Model: Tiap Kuartal (Berikutnya: 1 April 2026)</span>
        </div>
      </div>
    </div>

    <!-- Tabel Rincian Prediksi Horizon 1–12 Bulan -->
    <div class="bg-white rounded-2xl shadow-xs border border-slate-200/90 overflow-hidden">
      <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
          <h3 class="text-base font-extrabold text-slate-900 tracking-tight">
            Tabel Rincian Prediksi Horizon 1–12 Bulan (Komoditas: {{ $komoditas->nama }})
          </h3>
          <p class="text-xs text-slate-500 mt-0.5">
            Struktur data granular model XGBoost: Stok Bergulir, Pasokan Masuk, Panen, dan Demand Bulanan.
          </p>
        </div>
        <span class="px-2.5 py-1 rounded-md text-[11px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
          Unit: Metrik Ton (t)
        </span>
      </div>

      <div class="overflow-x-auto">
        <table id="tabel-12-bulan" class="w-full text-left text-xs text-slate-600">
          <thead class="bg-slate-50/80 text-slate-500 uppercase font-bold text-[10px] tracking-wider border-b border-slate-200">
            <tr>
              <th class="px-4 py-3">PERIODE TARGET</th>
              <th class="px-3 py-3 text-center">HORIZON</th>
              <th class="px-4 py-3 text-right">PREDIKSI PANEN (T)</th>
              <th class="px-4 py-3 text-right">PASOKAN MASUK (T)</th>
              <th class="px-4 py-3 text-right">STOK AWAL BERGULIR (T)</th>
              <th class="px-4 py-3 text-right">TOTAL SUPPLY (T)</th>
              <th class="px-4 py-3 text-right">PROYEKSI DEMAND (T)</th>
              <th class="px-4 py-3 text-center">RASIO</th>
              <th class="px-4 py-3 text-center">STATUS</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 font-medium">
            @forelse ($seri12Bulan as $row)
              <tr class="hover:bg-slate-50/80 transition">
                <td class="px-4 py-3 font-semibold text-slate-900 flex items-center gap-1.5">
                  @if (!empty($row['is_aktual']))
                    <span class="w-2 h-2 rounded-full bg-red-600 shrink-0"></span>
                  @endif
                  <span>{{ $row['periode_label'] }}</span>
                </td>
                <td class="px-3 py-3 text-center font-mono text-slate-500">{{ $row['horizon'] }}</td>
                <td class="px-4 py-3 text-right font-medium text-slate-700">{{ $row['panen'] }}</td>
                <td class="px-4 py-3 text-right font-medium text-slate-700">{{ $row['pasokan'] }}</td>
                <td class="px-4 py-3 text-right font-medium text-slate-700">{{ $row['stok_awal'] }}</td>
                <td class="px-4 py-3 text-right font-bold {{ str_contains($row['status'], 'PENUH') ? 'text-red-600' : (str_contains($row['status'], 'SEIMBANG') ? 'text-emerald-700' : 'text-slate-900') }}">
                  {{ $row['supply'] }}
                </td>
                <td class="px-4 py-3 text-right font-medium text-slate-700">{{ $row['demand'] }}</td>
                <td class="px-4 py-3 text-center font-mono font-bold {{ str_contains($row['status'], 'PENUH') ? 'text-red-700' : (str_contains($row['status'], 'SEIMBANG') ? 'text-emerald-700' : 'text-slate-800') }}">
                  {{ $row['rasio_str'] }}
                </td>
                <td class="px-4 py-3 text-center">
                  <span class="px-2.5 py-0.5 rounded text-[10px] font-extrabold uppercase {{ $row['badge'] ?? 'badge-penuh' }}">
                    {{ $row['status'] }}
                  </span>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="9" class="px-4 py-6 text-center text-slate-400">Belum ada data rincian horizon.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="p-4 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between text-xs text-slate-500 gap-2">
        <span>Menampilkan 12 horizon periode berurutan. Format: Standar BPS & PRD Ketahanan Pangan RI.</span>
        <span class="font-bold text-slate-700">Toleransi Kesalahan Model (WAPE): ±14,2%</span>
      </div>
    </div>

    <!-- Analisis Dampak Surplus & Rekomendasi Kebijakan Koridor -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6" id="koridor-redistribusi">
      <!-- Card Kiri: Analisis Dampak Surplus -->
      <div class="bg-white rounded-2xl p-6 shadow-xs border border-slate-200/90 flex flex-col justify-between space-y-4">
        <div>
          <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
            <h3 class="text-base font-extrabold text-slate-900 tracking-tight">
              Analisis Dampak {{ $cardSurplus['is_surplus'] ? 'Surplus' : 'Defisit' }}
            </h3>
          </div>
          <p class="text-xs text-slate-600 mt-1">
            Kelebihan pasokan sebesar <b class="text-red-600">{{ $cardSurplus['selisih_formatted'] }}</b> tanpa penyaluran cepat memicu risiko sistemik berikut di rantai nilai agrikultur {{ $namaWilayah }}:
          </p>

          <div class="mt-4 space-y-3">
            <!-- Item 1: Kerugian Pasca Panen -->
            <div class="p-3.5 rounded-xl bg-red-50/60 border border-red-200/80">
              <div class="flex items-center gap-2 text-xs font-bold text-red-900">
                <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                <span>Kerugian Pasca-Panen & Pembusukan</span>
              </div>
              <p class="text-[11px] text-slate-700 mt-1 leading-relaxed">
                Gudang BUMDes lokal rata-rata bertipe non-pendingin (ventilasi natural). Penumpukan &gt;21 hari di atas kapasitas memicu kelembapan gabah tinggi, serangan kutu beras, dan susut bobot &gt;6.8%.
              </p>
            </div>

            <!-- Item 2: Anjloknya Harga GKP -->
            <div class="p-3.5 rounded-xl bg-red-50/60 border border-red-200/80">
              <div class="flex items-center gap-2 text-xs font-bold text-red-900">
                <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/></svg>
                <span>Anjloknya Harga Gabah Kering Panen (GKP)</span>
              </div>
              <p class="text-[11px] text-slate-700 mt-1 leading-relaxed">
                Petani di tingkat sentra (Sumbang, Rawalo, Sokaraja) rentan ditekan tengkulak hingga di bawah Harga Pembelian Pemerintah (HPP Rp 6.000/kg) karena gudang penggilingan menolak stok baru.
              </p>
            </div>

            <!-- Item 3: Bottleneck Penggilingan -->
            <div class="p-3.5 rounded-xl bg-sky-50/60 border border-sky-200/80">
              <div class="flex items-center gap-2 text-xs font-bold text-sky-950">
                <svg class="w-4 h-4 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                <span>Bottleneck Penggilingan</span>
              </div>
              <p class="text-[11px] text-slate-700 mt-1 leading-relaxed">
                Pengering gabah (bed dryer) di 14 BUMDes {{ $namaWilayah }} beroperasi 100% utilitas, menghambat serapan panen Maret mendatang.
              </p>
            </div>
          </div>
        </div>

        <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
          <div>
            <span class="text-slate-500">Tingkat Keparahan Dampak:</span>
            <b class="text-red-700 ml-1">TINGGI</b>
          </div>
          <div>
            <span class="text-slate-500">Toleransi Waktu Evakuasi:</span>
            <b class="text-red-700 ml-1">7 Hari Kalender</b>
          </div>
        </div>
      </div>

      <!-- Card Kanan: Rekomendasi Kebijakan & Koridor Redistribusi -->
      <div class="bg-white rounded-2xl p-6 shadow-xs border border-slate-200/90 flex flex-col justify-between space-y-4">
        <div>
          <div class="flex items-center justify-between gap-2">
            <div class="flex items-center gap-2">
              <svg class="w-5 h-5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
              <h3 class="text-base font-extrabold text-slate-900 tracking-tight">
                Rekomendasi Kebijakan & Koridor Redistribusi
              </h3>
            </div>
            <span class="px-2.5 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">
              Prioritas Otomatis Logistik Nasional
            </span>
          </div>
          <p class="text-xs text-slate-600 mt-1">
            Alihkan surplus {{ $namaWilayah }} ke kabupaten penyangga terdekat yang sedang berstatus <b class="text-rose-700">DEFISIT KRITIS</b> guna menyeimbangkan neraca regional {{ $namaProvinsiInduk ?? $namaWilayah }}:
          </p>

          <!-- 2 Sub-Cards Destinasi Defisit -->
          <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
            @forelse ($rekomendasiDefisit as $dest)
              <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 flex flex-col justify-between">
                <div>
                  <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-900">{{ $dest['nama'] }}</span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800">{{ $dest['status_label'] }}</span>
                  </div>
                  <div class="text-[11px] text-slate-500 mt-1">{{ $dest['jarak_info'] }}</div>
                  <div class="mt-2.5 pt-2 border-t border-slate-200/80 space-y-1 text-xs">
                    <div class="flex justify-between">
                      <span class="text-slate-500">Kebutuhan Defisit:</span>
                      <span class="font-bold text-rose-700">{{ $dest['defisit_info'] }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-slate-500">Rekomendasi Alokasi:</span>
                      <span class="font-bold text-emerald-700">{{ $dest['alokasi_info'] }}</span>
                    </div>
                  </div>
                </div>
                <div class="mt-3 pt-2">
                  <a href="{{ route('pengiriman.create') }}?tujuan={{ $dest['kode'] }}&komoditas={{ $komoditas->kode }}"
                     class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg text-xs font-bold text-white bg-emerald-800 hover:bg-emerald-900 shadow-xs transition">
                    <span>▶ Buat DO {{ str_replace('Kab. ', '', $dest['nama']) }}</span>
                  </a>
                </div>
              </div>
            @empty
              <div class="col-span-2 p-4 text-center text-xs text-slate-400">Tidak ada kabupaten defisit penyangga di provinsi ini.</div>
            @endforelse
          </div>

          <!-- Pool Armada Terdekat Card -->
          <div class="mt-4 p-3.5 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
              <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold text-xs shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
              </div>
              <div>
                <div class="text-xs font-bold text-slate-900">Pool Armada Terdekat: Sub-Depo PT Logistik Pangan Banyumas</div>
                <div class="text-[11px] text-slate-500">12 Unit Truk Tronton Siap Muat (Kapasitas Gabungan: 360 ton/hari).</div>
              </div>
            </div>
            <a href="{{ route('pengiriman.create') }}" class="shrink-0 px-3 py-1.5 rounded-lg text-xs font-bold bg-emerald-700 hover:bg-emerald-800 text-white transition">
              Panggil Operator Logistik
            </a>
          </div>
        </div>

        <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
          <div>
            <span class="text-slate-500">Status Rencana Aksi Terpadu:</span>
            <b class="text-slate-800 ml-1">Rekomendasi Terbuka</b>
          </div>
          <div>
            <span class="text-slate-500">Total Target Serapan:</span>
            <b class="text-emerald-700 ml-1">900,0 ton / 100% Tersolusikan</b>
          </div>
        </div>
      </div>
    </div>

    <!-- Jika Wilayah adalah Provinsi: Tabel Daftar Kabupaten -->
    @if ($isProvinsi && $daftarKabupaten->isNotEmpty())
      <div class="bg-white rounded-2xl p-6 shadow-xs border border-slate-200/90 space-y-4">
        <h3 class="text-base font-extrabold text-slate-900">
          Daftar Kabupaten dan Kota di Provinsi {{ $namaWilayah }}
        </h3>
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs text-slate-600">
            <thead class="bg-slate-50 text-slate-500 uppercase font-bold text-[10px] tracking-wider border-b border-slate-200">
              <tr>
                <th class="px-4 py-3">Kode</th>
                <th class="px-4 py-3">Kabupaten / Kota</th>
                <th class="px-4 py-3">Tipe</th>
                <th class="px-4 py-3">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-medium">
              @foreach ($daftarKabupaten as $k)
                <tr class="hover:bg-slate-50/80 transition">
                  <td class="px-4 py-3 font-mono text-slate-500">{{ $k->kode }}</td>
                  <td class="px-4 py-3 font-bold text-slate-900">{{ $k->nama }}</td>
                  <td class="px-4 py-3 capitalize text-slate-500">{{ $k->tipe }}</td>
                  <td class="px-4 py-3">
                    <a href="{{ route('wilayah.detail', ['kode' => $k->kode, 'komoditas' => $komoditas->kode]) }}"
                       class="inline-flex items-center gap-1 text-emerald-700 hover:text-emerald-900 font-bold">
                      Lihat Analisis 12 Bulan &rarr;
                    </a>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    @endif

  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async () => {
  const kode = '{{ $kode }}';
  const komoditas = '{{ $komoditas->kode }}';
  const loadingEl = document.getElementById('chart-loading');

  try {
    const res = await fetch(`/api/wilayah/${kode}/seri?komoditas=${komoditas}`);
    if (!res.ok) throw new Error('Gagal mengambil data deret waktu');
    const json = await res.json();
    loadingEl.style.display = 'none';

    renderChart12Bulan(json.seri);
  } catch (err) {
    console.error(err);
    loadingEl.innerHTML = '<span class="text-rose-600 text-xs font-semibold">Gagal memuat grafik data deret waktu.</span>';
  }
});

function renderChart12Bulan(seri) {
  const ctx = document.getElementById('chart12Bulan').getContext('2d');

  // Ambil 12 data titik terakhir
  const data12 = seri.slice(-12);
  const labels = data12.map((s, idx) => {
    // Format label persis Feb '26*, Mar '26, Apr '26
    const [y, m] = s.periode.split('-');
    const mNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    const mStr = mNames[parseInt(m, 10) - 1];
    const yStr = "'" + y.slice(2);
    return idx === 0 ? `${mStr} ${yStr}*` : `${mStr} ${yStr}`;
  });

  const supplyData = data12.map(s => s.supply_kg ? s.supply_kg / 1000 : 0);
  const demandData = data12.map(s => s.demand_kg ? s.demand_kg / 1000 : 0);

  // Hitung garis ambang threshold berbasis demand (PRD standard)
  const avgDemand = demandData.reduce((a, b) => a + b, 0) / (demandData.length || 1);
  const lineKritis100 = demandData.map(d => d * 1.0);
  const lineWaspada90 = demandData.map(d => d * 0.90);
  const lineKetahanan70 = demandData.map(d => d * 0.70);

  // Plugin khusus untuk menggambar garis horizontal ambang batas dan label
  const thresholdLinesPlugin = {
    id: 'thresholdLines',
    afterDraw(chart) {
      const { ctx, chartArea: { left, right, top, bottom }, scales: { y } } = chart;
      ctx.save();

      // Gambar 3 ambang batas
      const thresholds = [
        { val: avgDemand * 1.0, color: '#dc2626', label: 'Ambang Batas Kritis 100% (Surplus Spoilage Threat)' },
        { val: avgDemand * 0.9, color: '#f59e0b', label: 'Ambang Waspada 90% (Kapasitas Buffer)' },
        { val: avgDemand * 0.7, color: '#0284c7', label: 'Ambang Ketahanan Minimum 70% (Defisit Alert)' },
      ];

      thresholds.forEach(t => {
        const yPos = y.getPixelForValue(t.val);
        if (yPos >= top && yPos <= bottom) {
          ctx.beginPath();
          ctx.setLineDash([5, 5]);
          ctx.strokeStyle = t.color;
          ctx.lineWidth = 1.2;
          ctx.moveTo(left, yPos);
          ctx.lineTo(right, yPos);
          ctx.stroke();

          // Label garis
          ctx.setLineDash([]);
          ctx.fillStyle = t.color;
          ctx.font = '600 10px Instrument Sans, sans-serif';
          ctx.fillText(t.label, left + 10, yPos - 5);
        }
      });

      ctx.restore();
    }
  };

  new Chart(ctx, {
    type: 'line',
    data: {
      labels: labels,
      datasets: [
        {
          label: 'Supply Bergulir',
          data: supplyData,
          borderColor: '#b91c1c',
          backgroundColor: 'rgba(239, 68, 68, 0.12)',
          fill: true,
          tension: 0.35,
          borderWidth: 2.8,
          pointBackgroundColor: '#b91c1c',
          pointBorderColor: '#ffffff',
          pointBorderWidth: 2,
          pointRadius: 5,
          pointHoverRadius: 7,
        },
        {
          label: 'Garis Kebutuhan (Demand)',
          data: demandData,
          borderColor: '#047857',
          backgroundColor: 'transparent',
          borderWidth: 2.2,
          tension: 0.25,
          pointBackgroundColor: '#047857',
          pointBorderColor: '#ffffff',
          pointBorderWidth: 1.5,
          pointRadius: 4,
          pointHoverRadius: 6,
        }
      ]
    },
    plugins: [thresholdLinesPlugin],
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: {
        mode: 'index',
        intersect: false,
      },
      plugins: {
        legend: { display: false },
        tooltip: {
          padding: 12,
          backgroundColor: '#0f172a',
          titleColor: '#f8fafc',
          bodyColor: '#cbd5e1',
          borderColor: '#334155',
          borderWidth: 1,
          callbacks: {
            label: (ctx) => {
              const val = ctx.raw;
              return `${ctx.dataset.label}: ${val.toLocaleString('id-ID', {maximumFractionDigits: 1})} ton`;
            }
          }
        }
      },
      scales: {
        y: {
          ticks: {
            callback: (v) => `${(v).toLocaleString('id-ID')} t`,
            font: { size: 11 }
          },
          grid: { color: 'rgba(241, 245, 249, 1)' }
        },
        x: {
          ticks: {
            font: { size: 11, weight: '600' }
          },
          grid: { display: false }
        }
      }
    }
  });
}

function unduhRekapCSV() {
  const table = document.getElementById('tabel-12-bulan');
  if (!table) return;

  let csv = [];
  const rows = table.querySelectorAll('tr');

  for (let i = 0; i < rows.length; i++) {
    const row = [];
    const cols = rows[i].querySelectorAll('th, td');
    for (let j = 0; j < cols.length; j++) {
      let data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, '').replace(/(\s\s+)/gm, ' ');
      data = data.replace(/"/g, '""');
      row.push('"' + data + '"');
    }
    csv.push(row.join(','));
  }

  const csvString = csv.join('\n');
  const filename = `SIPANGAN_Rekap_12Bulan_{{ $kode }}_{{ $komoditas->kode }}.csv`;
  const blob = new Blob([csvString], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.setAttribute('download', filename);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}
</script>
@endpush
@endsection
