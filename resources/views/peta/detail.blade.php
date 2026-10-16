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
      <div class="rounded-xl p-3 bg-amber-50/90 border border-amber-200 text-amber-900 flex items-center gap-2 text-xs shadow-2xs">
        <span class="px-2 py-0.5 rounded-md text-xs font-black bg-amber-200 text-amber-950 uppercase tracking-wide border border-amber-300 shrink-0">
          [Data Contoh / Simulasi]
        </span>
        <span class="font-medium text-slate-700">
          Data showcase Kabupaten Banyumas disajikan sebagai simulasi pemantauan rantai pasok pangan.
        </span>
      </div>
    @endif

    <!-- Alert Banner Status Wilayah -->
    @php
      $isPenuh = ($statusTerbaru['status'] ?? '') === 'penuh';
      $isWaspada = ($statusTerbaru['status'] ?? '') === 'waspada';
      $isDefisit = ($statusTerbaru['status'] ?? '') === 'defisit';
    @endphp

    <div class="rounded-2xl p-4 sm:p-5 border flex flex-col md:flex-row md:items-center md:justify-between gap-4 shadow-2xs {{ $isPenuh ? 'bg-purple-50/90 border-purple-200' : ($isDefisit ? 'bg-rose-50 border-rose-200' : ($isWaspada ? 'bg-amber-50 border-amber-200' : 'bg-emerald-50 border-emerald-200')) }}">
      <div class="flex items-center gap-3.5">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 font-black text-lg {{ $isPenuh ? 'bg-purple-600 text-white' : ($isDefisit ? 'bg-rose-600 text-white' : ($isWaspada ? 'bg-amber-500 text-white' : 'bg-emerald-600 text-white')) }}">
          {{ $isPenuh ? '◆' : ($isDefisit ? '▼' : ($isWaspada ? '▲' : '✔')) }}
        </div>
        <div class="flex items-center gap-2.5 flex-wrap">
          <span class="px-2.5 py-0.5 rounded text-xs font-extrabold uppercase tracking-wide {{ $isPenuh ? 'bg-purple-600 text-white' : ($isDefisit ? 'bg-rose-600 text-white' : ($isWaspada ? 'bg-amber-600 text-white' : 'bg-emerald-600 text-white')) }}">
            STATUS: {{ strtoupper($statusTerbaru['label'] ?? 'Penuh / Surplus') }}
          </span>
          <span class="text-xs font-bold text-slate-800">
            Rasio: {{ $statusTerbaru['rasio_persen'] ?? '105,1%' }}
          </span>
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
      <div class="bg-white rounded-2xl p-5 shadow-2xs border border-slate-200/90 flex flex-col justify-between">
        <div>
          <div class="text-xs font-bold uppercase tracking-wider text-slate-500">
            Total Supply Aktif
          </div>
          <div class="mt-2 text-2xl font-black text-slate-900 tracking-tight tabular-nums">
            {{ $cardSupply['total_formatted'] }}
          </div>
          <div class="mt-1 text-xs text-slate-500">
            Akumulasi pasokan berjalan
          </div>
        </div>
      </div>

      <!-- Card 2: Total Kebutuhan (Demand) -->
      <div class="bg-white rounded-2xl p-5 shadow-2xs border border-slate-200/90 flex flex-col justify-between">
        <div>
          <div class="text-xs font-bold uppercase tracking-wider text-slate-500">
            Demand Terproyeksi
          </div>
          <div class="mt-2 text-2xl font-black text-slate-900 tracking-tight tabular-nums">
            {{ $cardDemand['total_formatted'] }}
          </div>
          <div class="mt-1 text-xs text-slate-500">
            Kebutuhan konsumsi &amp; industri
          </div>
        </div>
      </div>

      <!-- Card 3: Selisih Neraca (Surplus / Defisit) -->
      <div class="bg-white rounded-2xl p-5 shadow-2xs border border-slate-200/90 flex flex-col justify-between">
        <div>
          <div class="text-xs font-bold uppercase tracking-wider text-slate-500">
            {{ $cardSurplus['is_surplus'] ? 'Surplus Bersih Kritis' : 'Defisit Bersih' }}
          </div>
          <div class="mt-2 text-2xl font-black {{ $cardSurplus['is_surplus'] ? 'text-purple-700' : 'text-rose-700' }} tracking-tight tabular-nums">
            {{ $cardSurplus['selisih_formatted'] }}
          </div>
          <div class="mt-1 text-xs text-slate-500">
            {{ $cardSurplus['is_surplus'] ? 'Kelebihan pasokan terhitung' : 'Kekurangan pasokan' }}
          </div>
        </div>
      </div>
    </div>

    <!-- Accordion Rincian Komponen KPI -->
    <details class="group bg-white rounded-xl border border-slate-200 p-3.5 shadow-2xs">
      <summary class="cursor-pointer text-xs font-bold text-slate-700 hover:text-emerald-700 flex items-center justify-between select-none">
        <span>Rincian Komponen Pasokan &amp; Kebutuhan</span>
        <svg class="w-4 h-4 text-slate-400 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
      </summary>
      <div class="mt-3 pt-3 border-t border-slate-100 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 text-xs">
        <div>
          <div class="text-slate-500">Panen</div>
          <div class="font-bold text-slate-900 tabular-nums">{{ $cardSupply['panen_formatted'] }}</div>
        </div>
        <div>
          <div class="text-slate-500">Stok Awal</div>
          <div class="font-bold text-slate-900 tabular-nums">{{ $cardSupply['stok_awal_formatted'] }}</div>
        </div>
        <div>
          <div class="text-slate-500">Pengiriman Aktif Masuk</div>
          <div class="font-bold text-slate-900 tabular-nums">{{ $cardSupply['pasokan_masuk_formatted'] }}</div>
        </div>
        <div>
          <div class="text-slate-500">Konsumsi Domestik</div>
          <div class="font-bold text-slate-900 tabular-nums">{{ $cardDemand['domestik_formatted'] }}</div>
        </div>
        <div>
          <div class="text-slate-500">Industri &amp; Cadangan</div>
          <div class="font-bold text-slate-900 tabular-nums">{{ $cardDemand['industri_formatted'] }}</div>
        </div>
        <div>
          <div class="text-slate-500">Rasio Pasokan</div>
          <div class="font-bold text-slate-900 tabular-nums">{{ $statusTerbaru['rasio_persen'] ?? '105,1%' }}</div>
        </div>
      </div>
    </details>

    <!-- Chart Deret Waktu 12 Bulan -->
    <div class="bg-white rounded-2xl p-5 sm:p-6 shadow-2xs border border-slate-200/90 space-y-4">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-100 pb-3.5">
        <h2 class="text-base font-bold text-slate-900 tracking-tight">
          Grafik Deret Waktu 12 Bulan (Model Prakiraan)
        </h2>

        <div class="flex items-center gap-3.5 flex-wrap text-xs text-slate-600 font-medium">
          <div class="flex items-center gap-1.5">
            <span class="w-3 h-3 rounded-full bg-purple-600 inline-block"></span>
            <span>Supply Bergulir</span>
          </div>
          <div class="flex items-center gap-1.5">
            <span class="w-4 h-0.5 bg-emerald-700 inline-block"></span>
            <span>Kebutuhan (Demand)</span>
          </div>
          <div class="flex items-center gap-1.5">
            <span class="w-3 h-3 rounded-xs bg-purple-100 border border-purple-300 inline-block"></span>
            <span>Zona Surplus (≥100%)</span>
          </div>
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
        <h3 class="text-sm sm:text-base font-bold text-slate-900 tracking-tight">
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

    <!-- Analisis Dampak & Rekomendasi Koridor Redistribusi -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6" id="koridor-redistribusi">
      <!-- Card Kiri: Analisis Dampak Wilayah -->
      <div class="bg-white rounded-2xl p-5 sm:p-6 shadow-2xs border border-slate-200/90 flex flex-col justify-between">
        <div>
          <h3 class="text-base font-bold text-slate-900 tracking-tight mb-3">
            Analisis Dampak {{ $cardSurplus['is_surplus'] ? 'Surplus' : 'Defisit' }}
          </h3>
          <p class="text-xs sm:text-sm text-slate-700 leading-relaxed">
            {{ $pesanDampak }}
          </p>
        </div>
      </div>

      <!-- Card Kanan: Rekomendasi Kebijakan & Koridor Redistribusi -->
      <div class="bg-white rounded-2xl p-5 sm:p-6 shadow-2xs border border-slate-200/90 flex flex-col justify-between">
        <div>
          <h3 class="text-base font-bold text-slate-900 tracking-tight mb-3">
            Rekomendasi Kebijakan &amp; Koridor Redistribusi
          </h3>

          <!-- Daftar Wilayah Defisit Terdekat (Flattened Rows) -->
          <div class="divide-y divide-slate-100">
            @forelse ($rekomendasiDefisit as $dest)
              <div class="py-3 first:pt-0 last:pb-0 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="space-y-0.5">
                  <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-slate-900">{{ $dest['nama'] }}</span>
                    <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-rose-100 text-rose-800">{{ $dest['status_label'] }}</span>
                  </div>
                  <div class="text-xs text-slate-500">
                    Defisit: <b class="text-rose-700 font-semibold tabular-nums">{{ $dest['defisit_info'] }}</b> &bull; Rekomendasi Alokasi: <b class="text-emerald-700 font-semibold tabular-nums">{{ $dest['alokasi_info'] }}</b>
                  </div>
                </div>
                <div class="shrink-0">
                  @can('create', App\Models\Shipment::class)
                    <a href="{{ route('pengiriman.create') }}?tujuan={{ $dest['kode'] }}&komoditas={{ $komoditas->kode }}"
                       class="min-h-[44px] inline-flex items-center justify-center px-3.5 py-2 rounded-xl text-xs font-bold text-white bg-emerald-800 hover:bg-emerald-900 transition">
                      ▶ Buat DO {{ str_replace('Kab. ', '', $dest['nama']) }}
                    </a>
                  @else
                    <div class="min-h-[44px] flex items-center justify-center py-2 px-3 rounded-xl bg-slate-100 text-xs font-semibold text-slate-600">
                      Disposisi Mandat Distributor
                    </div>
                  @endcan
                </div>
              </div>
            @empty
              <div class="py-4 text-center text-xs text-slate-500">
                Tidak ada wilayah defisit di Jawa Tengah pada periode ini.
              </div>
            @endforelse
          </div>

          <!-- Pool Armada Terdekat -->
          <div class="mt-4 pt-3 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
            <div>
              <span class="font-bold text-slate-900">Pool Armada Terdekat: Sub-Depo PT Logistik Pangan Banyumas</span>
              <span class="text-slate-500 block sm:inline sm:ml-1">&bull; Kapasitas terdistribusi untuk pengalihan surplus</span>
            </div>
            @can('create', App\Models\Shipment::class)
              <a href="{{ route('pengiriman.create') }}" class="min-h-[44px] px-3.5 py-2 inline-flex items-center justify-center rounded-xl font-bold bg-emerald-800 hover:bg-emerald-900 text-white transition shrink-0">
                Panggil Operator Logistik
              </a>
            @endcan
          </div>
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
  const filename = `PRANALA_PANGAN_Rekap_12Bulan_{{ $kode }}_{{ $komoditas->kode }}.csv`;
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
