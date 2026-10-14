@extends('layouts.app')

@push('styles')
{{-- Chart.js CDN --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<style>
  .badge-defisit    { background: #ffe4e6; color: #9f1239; border: 1px solid #fecdd3; }
  .badge-seimbang   { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
  .badge-waspada    { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
  .badge-penuh      { background: #f3e8ff; color: #581c87; border: 1px solid #e9d5ff; }
  .badge-tanpa_data { background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; }
</style>
@endpush

@section('content')
<div class="min-h-screen bg-[#f8fafc] py-6 sm:py-8 text-slate-800">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

    <!-- Subheader: Breadcrumb & Controls -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-slate-200/90 pb-4">
      <nav aria-label="Breadcrumb" class="flex items-center gap-1 flex-wrap text-xs text-slate-600 font-medium">
        <a href="{{ route('home') }}" class="min-h-[44px] min-w-[44px] inline-flex items-center justify-center gap-1 px-2.5 text-slate-700 hover:text-emerald-700 font-semibold transition rounded-lg">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m3 12 2-2m0 0 7-7 7 7M5 10v10a1 1 0 0 0 1 1h3m10-11 2 2m-2-2v10a1 1 0 0 1-1 1h-3m-6 0a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1m-6 0h6"/></svg>
          <span>Beranda</span>
        </a>
        <span class="text-slate-400">/</span>
        <a href="{{ route('peta.index') }}" class="min-h-[44px] min-w-[44px] inline-flex items-center justify-center px-2.5 text-slate-700 hover:text-emerald-700 font-semibold transition rounded-lg">
          Peta
        </a>
        <span class="text-slate-400">/</span>
        @if ($isProvinsi)
          <span class="min-h-[44px] inline-flex items-center px-2 text-slate-900 font-bold">Provinsi {{ $namaWilayah }}</span>
        @else
          @if ($kodeProvinsi)
            <a href="{{ route('wilayah.detail', ['kode' => $kodeProvinsi, 'komoditas' => $komoditas->kode]) }}" class="min-h-[44px] min-w-[44px] inline-flex items-center justify-center px-2.5 text-slate-700 hover:text-emerald-700 font-semibold transition rounded-lg">
              {{ $namaProvinsiInduk }}
            </a>
            <span class="text-slate-400">/</span>
          @endif
          <span class="min-h-[44px] inline-flex items-center px-2 text-slate-900 font-bold">{{ $tipeWilayah }} {{ $namaWilayah }} ({{ $kode }})</span>
        @endif
      </nav>

      <!-- Selector Komoditas & Unduh CSV -->
      <div class="flex items-center gap-3 flex-wrap">
        <div class="flex items-center gap-2">
          <label for="select-komoditas" class="text-xs font-bold text-slate-700">Komoditas:</label>
          <select id="select-komoditas"
                  onchange="location.href='{{ route('wilayah.detail', ['kode' => $kode]) }}?komoditas=' + this.value"
                  class="min-h-[44px] bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 shadow-2xs focus:ring-2 focus:ring-emerald-600 focus:outline-hidden">
            @foreach ($komoditasList as $k)
              <option value="{{ $k->kode }}" {{ $komoditas->kode === $k->kode ? 'selected' : '' }}>{{ $k->nama }}</option>
            @endforeach
          </select>
        </div>

        <button type="button"
                onclick="unduhRekapCSV()"
                class="min-h-[44px] inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 shadow-2xs transition">
          <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
          <span>Unduh Rekap CSV Data</span>
        </button>
      </div>
    </div>

    @if ($kode === '3302' && $komoditas->kode === 'beras')
      <!-- Banner Showcase Mockup Desain Banyumas (E-04) -->
      <div class="rounded-xl p-3 bg-amber-50/90 border border-amber-200 text-amber-900 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 text-xs shadow-2xs">
        <div class="flex items-center gap-2 flex-wrap">
          <span class="px-2 py-0.5 rounded-md text-xs font-black bg-amber-200 text-amber-950 uppercase tracking-wide border border-amber-300">
            [Data Contoh / Simulasi]
          </span>
          <span class="font-medium text-slate-700">
            Data showcase Kabupaten Banyumas disajikan sebagai simulasi pemantauan rantai pasok pangan.
          </span>
        </div>
        <span class="text-xs text-amber-800 shrink-0 font-bold">Banyumas (3302) • Beras</span>
      </div>
    @endif

    <!-- Alert Banner Status Wilayah (Warna Status Ungu untuk Penuh / Surplus) -->
    @php
      $isPenuh = ($statusTerbaru['status'] ?? '') === 'penuh';
      $isWaspada = ($statusTerbaru['status'] ?? '') === 'waspada';
      $isDefisit = ($statusTerbaru['status'] ?? '') === 'defisit';
      $rasioDisplay = $statusTerbaru['rasio'] !== null ? number_format($statusTerbaru['rasio'], 4, '.', '') . ' (' . ($statusTerbaru['rasio_persen'] ?? '—') . ')' : '—';
    @endphp

    <div class="rounded-2xl p-4 sm:p-5 border flex flex-col md:flex-row md:items-center md:justify-between gap-4 shadow-2xs {{ $isPenuh ? 'bg-purple-50/90 border-purple-200' : ($isDefisit ? 'bg-rose-50 border-rose-200' : ($isWaspada ? 'bg-amber-50 border-amber-200' : 'bg-emerald-50 border-emerald-200')) }}">
      <div class="flex items-start gap-3.5">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 font-black text-lg {{ $isPenuh ? 'bg-purple-600 text-white' : ($isDefisit ? 'bg-rose-600 text-white' : ($isWaspada ? 'bg-amber-500 text-white' : 'bg-emerald-600 text-white')) }}">
          {{ $isPenuh ? '◆' : ($isDefisit ? '▼' : ($isWaspada ? '▲' : '✔')) }}
        </div>
        <div>
          <div class="flex items-center gap-2 flex-wrap">
            <span class="px-2.5 py-0.5 rounded text-xs font-extrabold uppercase tracking-wide {{ $isPenuh ? 'bg-purple-600 text-white' : ($isDefisit ? 'bg-rose-600 text-white' : ($isWaspada ? 'bg-amber-600 text-white' : 'bg-emerald-600 text-white')) }}">
              STATUS: {{ strtoupper($statusTerbaru['label'] ?? 'Penuh / Surplus') }}
            </span>
            <span class="text-xs font-bold text-slate-800">
              Rasio Pasokan: {{ $rasioDisplay }}
            </span>
            <span class="text-xs font-semibold {{ $isPenuh ? 'text-purple-800' : ($isDefisit ? 'text-rose-800' : 'text-slate-600') }}">
              Batas Aman: 100%
            </span>
          </div>
          <p class="text-xs sm:text-sm text-slate-700 font-medium mt-1 leading-relaxed">
            @if ($isPenuh)
              <b>Peringatan Kelebihan Pasokan:</b> Akumulasi pasokan melebihi kapasitas serap di {{ $tipeWilayah }} {{ $namaWilayah }}.
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
           class="inline-flex items-center gap-2 px-4 py-2.5 min-h-[44px] rounded-xl text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 shadow-xs transition">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
          <span>Tinjau Rute Redistribusi</span>
        </a>
      </div>
    </div>

    <!-- 3 Kotak Metrik KPI Utama Bersih & Lega -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <!-- Card 1: Total Pasokan (Supply) -->
      <div class="bg-white rounded-2xl p-5 shadow-2xs border border-slate-200/90 relative overflow-hidden flex flex-col justify-between">
        <div>
          <div class="flex items-center justify-between text-xs font-bold uppercase tracking-wider text-slate-600">
            <span>Total Supply Aktif</span>
            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
          </div>
          <div class="mt-2 text-2xl font-black text-slate-900 tracking-tight tabular-nums">
            {{ $cardSupply['total_formatted'] }}
          </div>
          <div class="mt-3 pt-2.5 border-t border-slate-100 text-xs text-slate-600 flex items-center justify-between flex-wrap gap-1">
            <span>Panen: <b class="text-slate-800 font-bold tabular-nums">{{ $cardSupply['panen_formatted'] }}</b></span>
            <span class="text-slate-300">·</span>
            <span>Stok Awal: <b class="text-slate-800 font-bold tabular-nums">{{ $cardSupply['stok_awal_formatted'] }}</b></span>
            <span class="text-slate-300">·</span>
            <span>Pengiriman Aktif Masuk: <b class="text-slate-800 font-bold tabular-nums">{{ $cardSupply['pasokan_masuk_formatted'] }}</b></span>
          </div>
        </div>
        <div class="mt-3.5 -mx-5 -mb-5 h-1.5 bg-purple-600"></div>
      </div>

      <!-- Card 2: Total Kebutuhan (Demand) -->
      <div class="bg-white rounded-2xl p-5 shadow-2xs border border-slate-200/90 relative overflow-hidden flex flex-col justify-between">
        <div>
          <div class="flex items-center justify-between text-xs font-bold uppercase tracking-wider text-slate-600">
            <span>Demand Terproyeksi</span>
            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
          </div>
          <div class="mt-2 text-2xl font-black text-slate-900 tracking-tight tabular-nums">
            {{ $cardDemand['total_formatted'] }}
          </div>
          <div class="mt-3 pt-2.5 border-t border-slate-100 text-xs text-slate-600 flex items-center justify-between flex-wrap gap-1">
            <span>Konsumsi Domestik: <b class="text-slate-800 font-bold tabular-nums">{{ $cardDemand['domestik_formatted'] }}</b></span>
            <span class="text-slate-300">·</span>
            <span>Industri & Cadangan: <b class="text-slate-800 font-bold tabular-nums">{{ $cardDemand['industri_formatted'] }}</b></span>
          </div>
        </div>
        <div class="mt-3.5 -mx-5 -mb-5 h-1.5 bg-emerald-600"></div>
      </div>

      <!-- Card 3: Selisih Neraca (Surplus / Defisit) -->
      <div class="bg-white rounded-2xl p-5 shadow-2xs border border-slate-200/90 relative overflow-hidden flex flex-col justify-between">
        <div>
          <div class="flex items-center justify-between text-xs font-bold uppercase tracking-wider text-slate-600">
            <span>{{ $cardSurplus['is_surplus'] ? 'Surplus Bersih Kritis' : 'Defisit Bersih Kritis' }}</span>
            <div class="w-6 h-6 rounded-lg {{ $cardSurplus['is_surplus'] ? 'bg-purple-100 text-purple-800' : 'bg-rose-100 text-rose-800' }} flex items-center justify-center font-bold text-xs">
              {{ $cardSurplus['is_surplus'] ? '◆' : '▼' }}
            </div>
          </div>
          <div class="mt-2 text-2xl font-black {{ $cardSurplus['is_surplus'] ? 'text-purple-700' : 'text-rose-700' }} tracking-tight tabular-nums">
            {{ $cardSurplus['selisih_formatted'] }}
          </div>
          <div class="mt-3 pt-2.5 border-t border-slate-100 text-xs text-slate-600 flex items-center justify-between flex-wrap gap-1">
            <span>Rasio Pasokan: <b class="{{ $cardSurplus['is_surplus'] ? 'text-purple-800' : 'text-rose-800' }} font-bold tabular-nums">{{ $statusTerbaru['rasio_persen'] ?? '105,1%' }}</b></span>
            <span class="text-slate-300">·</span>
            <span>Batas Aman: <b class="text-slate-700 font-bold">100%</b></span>
          </div>
        </div>
        <div class="mt-3.5 -mx-5 -mb-5 h-1.5 {{ $cardSurplus['is_surplus'] ? 'bg-purple-600' : 'bg-rose-600' }}"></div>
      </div>
    </div>

    <!-- Chart Deret Waktu 12 Bulan (Bersih & Ramping) -->
    <div class="bg-white rounded-2xl p-5 sm:p-6 shadow-2xs border border-slate-200/90 space-y-4">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-100 pb-3.5">
        <div>
          <h2 class="text-base sm:text-lg font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
            <svg class="w-5 h-5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
            <span>Grafik Deret Waktu 12 Bulan (Model Prakiraan Multi-Horizon)</span>
          </h2>
        </div>

        <div class="flex items-center gap-3 flex-wrap text-xs">
          <div class="flex items-center gap-1.5 font-medium text-slate-700">
            <span class="w-3 h-3 rounded-full bg-purple-600 inline-block"></span>
            <span>Supply Bergulir</span>
          </div>
          <div class="flex items-center gap-1.5 font-medium text-slate-700">
            <span class="w-4 h-0.5 bg-emerald-700 inline-block"></span>
            <span>Garis Kebutuhan (Demand)</span>
          </div>
          <div class="flex items-center gap-1.5 font-medium text-slate-700">
            <span class="w-3 h-3 rounded-xs bg-purple-100 border border-purple-300 inline-block"></span>
            <span>Zona Surplus (≥100%)</span>
          </div>
          <span class="px-2.5 py-1 rounded-md text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
            Model Prakiraan v1 (Akurasi: ±14,2%)
          </span>
        </div>
      </div>

      <!-- Canvas Chart Container -->
      <div class="relative w-full h-[360px] sm:h-[400px]">
        <div id="chart-loading" class="absolute inset-0 bg-white/80 backdrop-blur-xs flex items-center justify-center z-10">
          <div class="flex items-center gap-2 text-xs font-bold text-slate-600">
            <svg class="w-4 h-4 animate-spin text-emerald-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
            <span>Memuat kurva deret waktu 12 bulan...</span>
          </div>
        </div>
        <canvas id="chart12Bulan"></canvas>
      </div>
    </div>

    <!-- Tombol Toggle Tabel Rincian 12 Bulan (Default Tersembunyi) -->
    <div class="pt-1">
      <button type="button"
              onclick="toggleTabel12Bulan()"
              id="btn-toggle-tabel-12"
              aria-expanded="false"
              aria-controls="container-tabel-12-bulan"
              class="w-full min-h-[44px] py-3 rounded-2xl bg-white hover:bg-slate-50 border border-slate-200 text-xs font-bold text-slate-700 transition shadow-2xs flex items-center justify-center gap-2">
        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        <span id="btn-toggle-tabel-text">Tampilkan Tabel Rincian Prediksi Horizon 1–12 Bulan</span>
      </button>
    </div>

    <!-- Kontainer Tabel Rincian 12 Bulan (Default Hidden) -->
    <div id="container-tabel-12-bulan" class="hidden bg-white rounded-2xl shadow-2xs border border-slate-200/90 overflow-hidden space-y-0">
      <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <h3 class="text-sm sm:text-base font-extrabold text-slate-900 tracking-tight">
          Tabel Rincian Prediksi Horizon 1–12 Bulan (Komoditas: {{ $komoditas->nama }})
        </h3>
        <span class="px-2.5 py-1 rounded-md text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200 self-start sm:self-auto">
          Satuan: Ton (t)
        </span>
      </div>

      <div class="overflow-x-auto">
        <table id="tabel-12-bulan" class="w-full text-left text-xs text-slate-600">
          <thead class="bg-slate-50/80 text-slate-700 uppercase font-bold text-xs tracking-wider border-b border-slate-200">
            <tr>
              <th class="px-4 py-3">PERIODE TARGET</th>
              <th class="px-3 py-3 text-center">HORIZON</th>
              <th class="px-4 py-3 text-right">PANEN (T)</th>
              <th class="px-4 py-3 text-right">PASOKAN MASUK (T)</th>
              <th class="px-4 py-3 text-right">STOK AWAL (T)</th>
              <th class="px-4 py-3 text-right">TOTAL SUPPLY (T)</th>
              <th class="px-4 py-3 text-right">DEMAND (T)</th>
              <th class="px-4 py-3 text-center">RASIO</th>
              <th class="px-4 py-3 text-center">STATUS</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 font-medium">
            @forelse ($seri12Bulan as $row)
              @php
                $st = strtoupper($row['status']);
                $isPenuhRow = str_contains($st, 'PENUH') || str_contains($st, 'SURPLUS');
                $isSeimbangRow = str_contains($st, 'SEIMBANG');
                $isWaspadaRow = str_contains($st, 'WASPADA');
                $isDefisitRow = str_contains($st, 'DEFISIT');
                
                $badgeClass = $isPenuhRow ? 'bg-purple-100 text-purple-900 border-purple-200' :
                  ($isSeimbangRow ? 'bg-emerald-100 text-emerald-900 border-emerald-200' :
                  ($isWaspadaRow ? 'bg-amber-100 text-amber-900 border-amber-200' :
                  ($isDefisitRow ? 'bg-rose-100 text-rose-900 border-rose-200' : 'bg-slate-100 text-slate-700 border-slate-200')));
                $badgeIcon = $isPenuhRow ? '◆' : ($isSeimbangRow ? '✔' : ($isWaspadaRow ? '▲' : ($isDefisitRow ? '▼' : '—')));
                
                // Format clean percentage only (e.g. "105,1%")
                $rasioClean = $row['rasio_str'];
                if (preg_match('/\(([^)]+)\)/', $row['rasio_str'], $m)) {
                  $rasioClean = $m[1];
                }
              @endphp
              <tr class="hover:bg-slate-50/80 transition">
                <td class="px-4 py-3 font-semibold text-slate-900 flex items-center gap-1.5">
                  @if (!empty($row['is_aktual']))
                    <span class="w-2 h-2 rounded-full bg-purple-600 shrink-0"></span>
                  @endif
                  <span>{{ $row['periode_label'] }}</span>
                </td>
                <td class="px-3 py-3 text-center text-slate-600 font-medium">{{ $row['horizon'] }}</td>
                <td class="px-4 py-3 text-right font-medium text-slate-700 tabular-nums">{{ $row['panen'] }}</td>
                <td class="px-4 py-3 text-right font-medium text-slate-700 tabular-nums">{{ $row['pasokan'] }}</td>
                <td class="px-4 py-3 text-right font-medium text-slate-700 tabular-nums">{{ $row['stok_awal'] }}</td>
                <td class="px-4 py-3 text-right font-bold tabular-nums {{ $isPenuhRow ? 'text-purple-800' : ($isSeimbangRow ? 'text-emerald-800' : 'text-slate-900') }}">
                  {{ $row['supply'] }}
                </td>
                <td class="px-4 py-3 text-right font-medium text-slate-700 tabular-nums">{{ $row['demand'] }}</td>
                <td class="px-4 py-3 text-center font-bold tabular-nums {{ $isPenuhRow ? 'text-purple-800' : ($isSeimbangRow ? 'text-emerald-800' : 'text-slate-800') }}">
                  {{ $rasioClean }}
                </td>
                <td class="px-4 py-3 text-center">
                  <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-bold uppercase border {{ $badgeClass }}">
                    <span>{{ $badgeIcon }}</span>
                    <span>{{ $st }}</span>
                  </span>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="9" class="px-4 py-6 text-center text-slate-500">Belum ada data rincian horizon.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <!-- Analisis Dampak & Rekomendasi Koridor Redistribusi (Pangkas Klaim Fiktif) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6" id="koridor-redistribusi">
      <!-- Card Kiri: Analisis Dampak Wilayah -->
      <div class="bg-white rounded-2xl p-5 sm:p-6 shadow-2xs border border-slate-200/90 flex flex-col justify-between space-y-4">
        <div class="space-y-3">
          <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
            <h3 class="text-base font-extrabold text-slate-900 tracking-tight">
              Analisis Dampak {{ $cardSurplus['is_surplus'] ? 'Surplus' : 'Defisit' }}
            </h3>
          </div>
          <p class="text-xs text-slate-600 leading-relaxed">
            Kelebihan pasokan sebesar <b class="text-purple-800 font-bold tabular-nums">{{ $cardSurplus['selisih_formatted'] }}</b> memerlukan penanganan logistik terencana di {{ $namaWilayah }}.
          </p>

          <!-- 1 Blok Dampak Resmi Ringkas & Jelas -->
          <div class="p-4 rounded-xl bg-purple-50 border border-purple-200">
            <div class="flex items-start gap-3">
              <div class="w-8 h-8 rounded-lg bg-purple-200 text-purple-900 flex items-center justify-center font-bold text-sm shrink-0">
                ◆
              </div>
              <div class="space-y-1">
                <div class="text-xs font-bold text-purple-950 uppercase tracking-wider">Dampak Wilayah</div>
                <p class="text-xs text-slate-700 leading-relaxed">
                  {{ $pesanDampak }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-600">
          <span>Status Wilayah: <b class="text-purple-800 font-bold ml-1">Penuh / Surplus</b></span>
          <span>Tindakan: <b class="text-slate-800 font-bold ml-1">Redistribusi Pangan</b></span>
        </div>
      </div>

      <!-- Card Kanan: Rekomendasi Kebijakan & Koridor Redistribusi -->
      <div class="bg-white rounded-2xl p-5 sm:p-6 shadow-2xs border border-slate-200/90 flex flex-col justify-between space-y-4">
        <div class="space-y-3">
          <div class="flex items-center justify-between gap-2 flex-wrap">
            <div class="flex items-center gap-2">
              <svg class="w-5 h-5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
              <h3 class="text-base font-extrabold text-slate-900 tracking-tight">
                Rekomendasi Kebijakan & Koridor Redistribusi
              </h3>
            </div>
            <span class="px-2.5 py-0.5 rounded text-xs font-bold bg-emerald-100 text-emerald-800">
              Prioritas Logistik
            </span>
          </div>
          <p class="text-xs text-slate-600 leading-relaxed">
            Alihkan surplus pasokan ke kabupaten penyangga terdekat yang berstatus defisit di provinsi {{ $namaProvinsiInduk ?? $namaWilayah }}:
          </p>

          <!-- Daftar Wilayah Defisit Terdekat -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
            @forelse ($rekomendasiDefisit as $dest)
              <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 flex flex-col justify-between space-y-2.5">
                <div>
                  <div class="flex items-center justify-between gap-1">
                    <span class="text-xs font-bold text-slate-900">{{ $dest['nama'] }}</span>
                    <span class="px-2 py-0.5 rounded text-xs font-bold bg-rose-100 text-rose-800">{{ $dest['status_label'] }}</span>
                  </div>
                  <div class="text-xs text-slate-500 mt-1">{{ $dest['jarak_info'] }}</div>
                  <div class="mt-2 pt-2 border-t border-slate-200/80 space-y-1 text-xs">
                    <div class="flex justify-between">
                      <span class="text-slate-500">Defisit:</span>
                      <span class="font-bold text-rose-700 tabular-nums">{{ $dest['defisit_info'] }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-slate-500">Rekomendasi:</span>
                      <span class="font-bold text-emerald-700 tabular-nums">{{ $dest['alokasi_info'] }}</span>
                    </div>
                  </div>
                </div>
                <div class="pt-1">
                  @can('create', App\Models\Shipment::class)
                    <a href="{{ route('pengiriman.create') }}?tujuan={{ $dest['kode'] }}&komoditas={{ $komoditas->kode }}"
                       class="w-full min-h-[44px] inline-flex items-center justify-center gap-1.5 px-3 py-2.5 rounded-xl text-xs font-bold text-white bg-emerald-800 hover:bg-emerald-900 shadow-xs transition">
                      <span>▶ Buat DO {{ str_replace('Kab. ', '', $dest['nama']) }}</span>
                    </a>
                  @else
                    <div class="w-full min-h-[44px] flex items-center justify-center py-2 px-3 rounded-xl bg-slate-100 text-xs font-semibold text-slate-600">
                      Disposisi Mandat Distributor
                    </div>
                  @endcan
                </div>
              </div>
            @empty
              <div class="col-span-2 p-4 text-center text-xs text-slate-500">Tidak ada kabupaten defisit penyangga di provinsi ini.</div>
            @endforelse
          </div>

          <!-- Pool Armada Terdekat Card -->
          <div class="mt-4 p-3.5 rounded-xl bg-slate-50 border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
              <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold text-xs shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
              </div>
              <div>
                <div class="text-xs font-bold text-slate-900">Pool Armada Terdekat: Sub-Depo PT Logistik Pangan Banyumas</div>
                <div class="text-xs text-slate-500">Kapasitas angkut terdistribusi untuk pengalihan surplus antar-wilayah.</div>
              </div>
            </div>
            @can('create', App\Models\Shipment::class)
              <a href="{{ route('pengiriman.create') }}" class="min-h-[44px] px-4 py-2 inline-flex items-center justify-center rounded-xl text-xs font-bold bg-emerald-700 hover:bg-emerald-800 text-white transition shrink-0">
                Panggil Operator Logistik
              </a>
            @else
              <span class="min-h-[44px] px-4 py-2 inline-flex items-center justify-center rounded-xl text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200 shrink-0">
                Khusus Operator Logistik
              </span>
            @endcan
          </div>
        </div>

        <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-600">
          <span>Rekomendasi Rute: <b class="text-slate-800 font-bold ml-1">Terbuka</b></span>
          <span>Target Serapan: <b class="text-emerald-700 font-bold ml-1 tabular-nums">900,0 ton</b></span>
        </div>
      </div>
    </div>

    <!-- Jika Wilayah adalah Provinsi: Tabel Daftar Kabupaten -->
    @if ($isProvinsi && $daftarKabupaten->isNotEmpty())
      <div class="bg-white rounded-2xl p-5 sm:p-6 shadow-2xs border border-slate-200/90 space-y-4">
        <h3 class="text-base font-extrabold text-slate-900 tracking-tight">
          Daftar Kabupaten dan Kota di Provinsi {{ $namaWilayah }}
        </h3>
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs text-slate-600">
            <thead class="bg-slate-50 text-slate-700 uppercase font-bold text-xs tracking-wider border-b border-slate-200">
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
                  <td class="px-4 py-3 text-slate-600 font-medium">{{ $k->kode }}</td>
                  <td class="px-4 py-3 font-bold text-slate-900">{{ $k->nama }}</td>
                  <td class="px-4 py-3 capitalize text-slate-600">{{ $k->tipe }}</td>
                  <td class="px-4 py-3">
                    <a href="{{ route('wilayah.detail', ['kode' => $k->kode, 'komoditas' => $komoditas->kode]) }}"
                       class="min-h-[44px] inline-flex items-center gap-1 text-emerald-700 hover:text-emerald-900 font-bold">
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
    if (loadingEl) loadingEl.style.display = 'none';

    renderChart12Bulan(json.seri);
  } catch (err) {
    console.error(err);
    if (loadingEl) loadingEl.innerHTML = '<span class="text-rose-600 text-xs font-semibold">Gagal memuat grafik data deret waktu.</span>';
  }
});

function renderChart12Bulan(seri) {
  const canvas = document.getElementById('chart12Bulan');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');

  // Ambil 12 data titik terakhir
  const data12 = (seri || []).slice(-12);
  const labels = data12.map((s, idx) => {
    const [y, m] = (s.periode || '').split('-');
    const mNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    const mStr = mNames[parseInt(m, 10) - 1] || m;
    const yStr = "'" + (y ? y.slice(2) : '');
    return idx === 0 ? `${mStr} ${yStr}*` : `${mStr} ${yStr}`;
  });

  const supplyData = data12.map(s => s.supply_kg ? s.supply_kg / 1000 : 0);
  const demandData = data12.map(s => s.demand_kg ? s.demand_kg / 1000 : 0);
  const avgDemand = demandData.reduce((a, b) => a + b, 0) / (demandData.length || 1);

  // Plugin khusus untuk garis horizontal ambang batas selaras palet status
  const thresholdLinesPlugin = {
    id: 'thresholdLines',
    afterDraw(chart) {
      const { ctx, chartArea: { left, right, top, bottom }, scales: { y } } = chart;
      if (!y) return;
      ctx.save();

      const thresholds = [
        { val: avgDemand * 1.0, color: '#8b5cf6', label: 'Batas Penuh / Surplus (≥100%)' },
        { val: avgDemand * 0.9, color: '#f59e0b', label: 'Ambang Waspada (90%)' },
        { val: avgDemand * 0.7, color: '#ef4444', label: 'Ambang Defisit (<70%)' },
      ];

      thresholds.forEach(t => {
        const yPos = y.getPixelForValue(t.val);
        if (yPos >= top && yPos <= bottom) {
          ctx.beginPath();
          ctx.setLineDash([5, 5]);
          ctx.strokeStyle = t.color;
          ctx.lineWidth = 1.4;
          ctx.moveTo(left, yPos);
          ctx.lineTo(right, yPos);
          ctx.stroke();

          // Label garis
          ctx.setLineDash([]);
          ctx.fillStyle = t.color;
          ctx.font = '700 11px Instrument Sans, sans-serif';
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
          borderColor: '#8b5cf6',
          backgroundColor: 'rgba(139, 92, 246, 0.12)',
          fill: true,
          tension: 0.35,
          borderWidth: 2.8,
          pointBackgroundColor: '#8b5cf6',
          pointBorderColor: '#ffffff',
          pointBorderWidth: 2,
          pointRadius: 5,
          pointHoverRadius: 7,
        },
        {
          label: 'Garis Kebutuhan (Demand)',
          data: demandData,
          borderColor: '#059669',
          backgroundColor: 'transparent',
          borderWidth: 2.2,
          tension: 0.25,
          pointBackgroundColor: '#059669',
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
            font: { size: 12 }
          },
          grid: { color: 'rgba(241, 245, 249, 1)' }
        },
        x: {
          ticks: {
            font: { size: 12, weight: '600' }
          },
          grid: { display: false }
        }
      }
    }
  });
}

function toggleTabel12Bulan() {
  const container = document.getElementById('container-tabel-12-bulan');
  const btn = document.getElementById('btn-toggle-tabel-12');
  const txt = document.getElementById('btn-toggle-tabel-text');
  if (!container || !btn || !txt) return;

  const isHidden = container.classList.contains('hidden');
  if (isHidden) {
    container.classList.remove('hidden');
    btn.setAttribute('aria-expanded', 'true');
    txt.textContent = 'Sembunyikan Tabel Rincian 12 Bulan';
  } else {
    container.classList.add('hidden');
    btn.setAttribute('aria-expanded', 'false');
    txt.textContent = 'Tampilkan Tabel Rincian Prediksi Horizon 1–12 Bulan';
  }
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
