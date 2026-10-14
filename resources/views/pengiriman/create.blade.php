@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-[#f8fafc] py-6 sm:py-8 text-slate-800">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

    <!-- Subheader: Breadcrumb & Title & Top Action Tabs -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 border-b border-slate-200/80 pb-5">
      <div>
        <div class="flex items-center gap-2 flex-wrap text-xs">
          <span class="px-2.5 py-0.5 rounded font-extrabold uppercase bg-emerald-100 text-emerald-800 tracking-wider">
            MODUL DISTRIBUTOR LOGISTIK
          </span>
          <span class="text-slate-400">•</span>
          <span class="font-bold text-slate-500">Standar Logistik Pangan</span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1.5 tracking-tight">
          Portal Manajemen Distribusi Pangan Logistik
        </h1>
        <div class="flex items-center gap-2.5 text-xs text-slate-500 mt-1 flex-wrap">
          <span class="flex items-center gap-1 font-semibold text-slate-700">
            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            {{ auth()->user()->organisasi ?? 'PT Pangan Nusantara Mandiri' }}
          </span>
          <span class="text-slate-400">•</span>
          <span class="flex items-center gap-1">
            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            {{ auth()->user()->name }} (Penanggung Jawab Distribusi)
          </span>
          <span class="text-slate-400">•</span>
          <span class="font-mono text-emerald-700 font-semibold">
            ID Operator: DST-{{ $defaultAsal->kode ?? '3301' }}-8842
          </span>
        </div>
      </div>

      <!-- Navigation Tabs (Sesuai Mockup) -->
      <div class="flex items-center gap-2 self-start lg:self-center">
        <a href="{{ route('pengiriman.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-[#064e3b] shadow-sm transition">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
          <span>1. Buat Pengiriman Baru</span>
        </a>
        <a href="{{ route('pengiriman.index') }}"
           class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200/90 shadow-xs transition">
          <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
          <span>2. Daftar & Lacak Resi</span>
          <span class="px-1.5 py-0.2 bg-emerald-100 text-emerald-800 rounded-full text-[10px] font-black">
            {{ \App\Models\Shipment::where('user_id', auth()->id())->count() }}
          </span>
        </a>
      </div>
    </div>

    @if ($errors->any())
      <div class="rounded-xl bg-red-50 border border-red-200 p-4 text-xs text-red-800">
        <div class="font-bold mb-1">Periksa kembali data pengiriman:</div>
        <ul class="list-disc list-inside space-y-0.5">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <!-- Main Grid: Form Dispatch (Left) + Telemetry & Corridor (Right) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

      <!-- Kolom Kiri: Formulir Dispatch Logistik Bahan Pokok (7/12) -->
      <div class="lg:col-span-7 bg-white rounded-2xl shadow-xs border border-slate-200/90 overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
          <div class="flex items-center gap-2.5">
            <svg class="w-5 h-5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <h2 class="text-base font-extrabold text-slate-900 tracking-tight">
              Formulir Dispatch Logistik Bahan Pokok
            </h2>
          </div>
          <span class="px-2.5 py-1 rounded-md text-[11px] font-bold bg-sky-50 text-sky-800 border border-sky-200">
            F-07: Entry Dispatch
          </span>
        </div>

        <form id="form-dispatch" action="{{ route('pengiriman.store') }}" method="POST" class="p-6 space-y-5">
          @csrf

          <!-- Field 1: Nomor Resi Unik (Waybill) -->
          <div>
            <div class="flex items-center justify-between">
              <label for="no_resi" class="block text-xs font-bold text-slate-800 uppercase tracking-wide">
                Nomor Resi Unik (Waybill)
              </label>
              <button type="button" onclick="buatResiAcak()" class="text-xs font-bold text-emerald-700 hover:text-emerald-900 flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Buat Resi Acak
              </button>
            </div>
            <div class="mt-1.5 relative rounded-xl shadow-xs">
              <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
              </div>
              <input type="text"
                     name="no_resi"
                     id="no_resi"
                     value="{{ old('no_resi', $suggestedResi) }}"
                     required
                     placeholder="RESI-CONTOH-0001"
                     class="block w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 font-mono text-sm font-bold text-slate-900 focus:bg-white focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 transition" />
            </div>
            <p class="text-[11px] text-slate-500 mt-1">
              Identitas unik tracking alur rantai dingin/distribusi pangan nasional.
            </p>
          </div>

          <!-- Field 2 & 3: Komoditas & Jumlah Pengiriman (kg) -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label for="komoditas_id" class="block text-xs font-bold text-slate-800 uppercase tracking-wide">
                Pilihan Komoditas Pangan
              </label>
              <select name="komoditas_id"
                      id="komoditas_id"
                      onchange="onFilterChange()"
                      required
                      class="mt-1.5 block w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 text-xs font-semibold text-slate-900 focus:bg-white focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 transition">
                @foreach ($komoditasList as $k)
                  <option value="{{ $k->id }}" data-kode="{{ $k->kode }}" {{ old('komoditas_id', $activeKomoditas->id) == $k->id ? 'selected' : '' }}>
                    {{ $k->nama }}
                  </option>
                @endforeach
              </select>
              <p class="text-[11px] text-slate-500 mt-1">Pilih jenis bahan pangan pokok yang diangkut.</p>
            </div>

            <div>
              <div class="flex items-center justify-between">
                <label for="jumlah_kg" class="block text-xs font-bold text-slate-800 uppercase tracking-wide">
                  Jumlah Pengiriman (kg)
                </label>
                <span id="badge-ton-preview" class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-emerald-800 text-white">
                  = 2,5 ton
                </span>
              </div>
              <div class="mt-1.5 relative rounded-xl shadow-xs">
                <input type="number"
                       name="jumlah_kg"
                       id="jumlah_kg"
                       min="1"
                       max="100000000"
                       step="1"
                       value="{{ old('jumlah_kg', 2500) }}"
                       oninput="updateTonPreview(this.value)"
                       required
                       placeholder="2500"
                       class="block w-full pl-3.5 pr-12 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 text-sm font-bold text-slate-900 focus:bg-white focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 transition" />
                <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-xs font-bold text-slate-400">
                  KG
                </div>
              </div>
              <p class="text-[11px] text-slate-500 mt-1">Konversi metrik ton dihitung otomatis per kwintal/ton.</p>
            </div>
          </div>

          <!-- Field 4 & 5: Asal & Tujuan -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label for="asal_kabupaten_id" class="block text-xs font-bold text-slate-800 uppercase tracking-wide">
                Kabupaten Asal (Sumber Pasokan)
              </label>
              <select name="asal_kabupaten_id"
                      id="asal_kabupaten_id"
                      required
                      class="mt-1.5 block w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 text-xs font-semibold text-slate-900 focus:bg-white focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 transition">
                @foreach ($kabupatenList as $kab)
                  <option value="{{ $kab->id }}" data-kode="{{ $kab->kode }}" data-nama="{{ $kab->nama }}" {{ old('asal_kabupaten_id', $defaultAsal->id ?? '') == $kab->id ? 'selected' : '' }}>
                    {{ $kab->kode }} - {{ $kab->nama }}, {{ $kab->provinsi->nama ?? '' }}
                  </option>
                @endforeach
              </select>
              <div class="mt-1 flex items-center gap-1.5 text-[11px] text-emerald-700 font-medium">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                <span>Status Asal: Surplus Terkelola (Stok Aman)</span>
              </div>
            </div>

            <div>
              <label for="tujuan_kabupaten_id" class="block text-xs font-bold text-slate-800 uppercase tracking-wide">
                Kabupaten Tujuan (Penerima)
              </label>
              <select name="tujuan_kabupaten_id"
                      id="tujuan_kabupaten_id"
                      onchange="onFilterChange()"
                      required
                      class="mt-1.5 block w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 text-xs font-semibold text-slate-900 focus:bg-white focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 transition">
                @foreach ($kabupatenList as $kab)
                  <option value="{{ $kab->id }}" data-kode="{{ $kab->kode }}" data-nama="{{ $kab->nama }}" {{ old('tujuan_kabupaten_id', $defaultTujuan->id ?? '') == $kab->id ? 'selected' : '' }}>
                    {{ $kab->kode }} - {{ $kab->nama }}, {{ $kab->provinsi->nama ?? '' }}
                  </option>
                @endforeach
              </select>
              <div id="subtext-tujuan" class="mt-1 flex items-center gap-1.5 text-[11px] text-red-600 font-medium">
                <span class="w-1.5 h-1.5 rounded-full bg-red-600"></span>
                <span>Wilayah Tujuan Berisiko Early-Warning!</span>
              </div>
            </div>
          </div>

          <!-- Field 6 & 7: Tanggal Berangkat & Estimasi Tiba -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label for="tanggal_kirim" class="block text-xs font-bold text-slate-800 uppercase tracking-wide">
                Tanggal Berangkat
              </label>
              <input type="date"
                     name="tanggal_kirim"
                     id="tanggal_kirim"
                     value="{{ old('tanggal_kirim', date('Y-m-d')) }}"
                     onchange="onFilterChange()"
                     required
                     class="mt-1.5 block w-full px-3.5 py-2 rounded-xl border border-slate-200 bg-slate-50/50 text-xs font-semibold text-slate-900 focus:bg-white focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 transition" />
            </div>

            <div>
              <label for="estimasi_tiba" class="block text-xs font-bold text-slate-800 uppercase tracking-wide">
                Estimasi Tiba (ETA)
              </label>
              <input type="date"
                     name="estimasi_tiba"
                     id="estimasi_tiba"
                     value="{{ old('estimasi_tiba', date('Y-m-d', strtotime('+1 day'))) }}"
                     class="mt-1.5 block w-full px-3.5 py-2 rounded-xl border border-slate-200 bg-slate-50/50 text-xs font-semibold text-slate-900 focus:bg-white focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 transition" />
            </div>
          </div>

          <!-- Container Alert Early-Warning (Dinamis sesuai respon /api/wilayah/{kode}/cek) -->
          <div id="container-early-warning" class="rounded-xl p-5 border space-y-4 bg-rose-50 border-rose-200">
            <div class="flex items-start gap-3.5">
              <div class="w-9 h-9 rounded-lg bg-red-600 text-white flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
              </div>
              <div class="space-y-1">
                <div class="flex items-center gap-2 flex-wrap">
                  <span id="badge-warning-level" class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider bg-red-600 text-white">
                    ALERT EARLY-WARNING
                  </span>
                  <span id="label-warning-threat" class="text-xs font-bold text-red-700">
                    Tingkat Bahaya: Surplus Spoilage
                  </span>
                </div>
                <h3 id="heading-warning" class="text-xs sm:text-sm font-extrabold text-red-900 leading-snug">
                  PERINGATAN DINI PASOKAN: Wilayah tujuan (Kab. Banyumas) saat ini berstatus PENUH (Rasio Keterisian: 105,1%).
                </h3>
                <p id="desc-warning" class="text-xs text-red-800 leading-relaxed">
                  Pangan berisiko basi atau terbuang dan menghasilkan emisi gas rumah kaca bila pasokan melebihi kapasitas serap wilayah.
                </p>
              </div>
            </div>

            {{-- Disclaimer Resmi --}}
            <div class="p-3.5 rounded-lg bg-white border border-amber-300 flex items-start gap-2.5 text-xs text-slate-700">
              <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
              <div>
                <b class="text-slate-900">Perhatian:</b> Sistem ini bukan layanan penjualan komersial, melainkan platform pemantauan, tracing, dan mitigasi kelebihan suplai logistik pangan nasional.
              </div>
            </div>

            <!-- Checkbox Konfirmasi Tanggung Jawab Distributor (Non-Blocking) -->
            <label class="flex items-start gap-2.5 p-3 rounded-lg bg-white/80 border border-rose-200 cursor-pointer select-none">
              <input type="checkbox"
                     name="konfirmasi_peringatan"
                     id="konfirmasi_peringatan"
                     value="1"
                     onchange="toggleSubmitState()"
                     class="mt-0.5 rounded text-red-600 focus:ring-red-500 w-4 h-4 border-slate-300" />
              <span class="text-xs text-slate-800 font-medium leading-relaxed">
                Saya memahami risiko kelebihan pasokan di wilayah tujuan (<span id="span-tujuan-nama">Kab. Banyumas</span>) dan bertanggung jawab penuh secara operasional untuk tetap melanjutkan pengiriman ini.
              </span>
            </label>

            <!-- 2 Tombol Aksi di Bawah Form Peringatan -->
            <div class="pt-2 flex flex-col sm:flex-row items-center gap-3">
              <button type="button"
                      onclick="rerouteKeWilayahDefisit()"
                      class="w-full sm:w-1/2 inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-slate-800 bg-white hover:bg-slate-100 border border-slate-300 shadow-xs transition">
                <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                <span>Ganti Wilayah Tujuan ke Wilayah Defisit (<span id="btn-label-reroute">Kab. Tegal</span>)</span>
              </button>

              <button type="submit"
                      id="btn-submit-dispatch"
                      class="w-full sm:w-1/2 inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-slate-500 hover:bg-slate-600 shadow-xs transition cursor-not-allowed"
                      title="Harap centang persetujuan disclaimer di atas untuk melanjutkan">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                <span>Lanjutkan Simpan Pengiriman</span>
              </button>
            </div>
          </div>

          <!-- Fallback Tombol Simpan (jika wilayah tujuan seimbang/tanpa peringatan) -->
          <div id="container-normal-submit" class="hidden pt-2">
            <button type="submit"
                    class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl text-xs font-bold text-white bg-emerald-800 hover:bg-emerald-900 shadow-sm transition">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
              <span>Simpan & Daftarkan Pengiriman</span>
            </button>
          </div>

        </form>
      </div>

      <!-- Kolom Kanan: Telemetri Kapasitas Silo/Pasar & Koridor Transportasi (5/12) -->
      <div class="lg:col-span-5 space-y-6">

        <!-- Card 1: Telemetri Kapasitas Silo / Pasar -->
        <div class="bg-white rounded-2xl p-6 shadow-xs border border-slate-200/90 space-y-5">
          <div class="flex items-center justify-between">
            <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-800">
              TELEMETRI KAPASITAS SILO / PASAR
            </h3>
            <span id="badge-sidebar-status" class="px-2.5 py-0.5 rounded text-[10px] font-black uppercase bg-red-600 text-white">
              STATUS: OVERFLOW
            </span>
          </div>

          <!-- Big Metric Box -->
          <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-red-100 text-red-600 flex items-center justify-center shrink-0">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </div>
            <div>
              <div id="sidebar-wilayah-nama" class="text-xs font-bold text-slate-700">
                Kab. Banyumas (Gudang Induk Karanglewas)
              </div>
              <div id="sidebar-ratio-big" class="text-3xl font-black text-slate-900 tracking-tight">
                105,1%
              </div>
              <div id="sidebar-capacity-detail" class="text-[11px] text-slate-500 font-medium">
                Kapasitas Maksimal: 1.200 Ton • Terisi: 1.261 Ton
              </div>
            </div>
          </div>

          <!-- Progress Bar Multi-Ambang Keamanan Pangan -->
          <div class="space-y-1.5">
            <div class="flex justify-between text-xs">
              <span class="text-slate-600 font-semibold">Ambang Batas Keamanan Pangan</span>
              <span id="sidebar-overflow-info" class="text-red-600 font-bold">+61 Ton Melebihi Kuota</span>
            </div>
            <div class="w-full h-3 rounded-full bg-slate-100 flex overflow-hidden">
              <div class="bg-emerald-500 h-full" style="width: 70%" title="Normal (0-70%)"></div>
              <div class="bg-amber-400 h-full" style="width: 20%" title="Waspada (70-90%)"></div>
              <div class="bg-red-600 h-full" style="width: 10%" title="Penuh / Overflow (>90%)"></div>
            </div>
            <div class="flex justify-between text-[10px] text-slate-500 pt-0.5">
              <span>0%</span>
              <span class="text-emerald-700 font-semibold">Normal (70%)</span>
              <span class="text-amber-700 font-semibold">Waspada (90%)</span>
              <span class="text-red-700 font-black">100%+ (Penuh)</span>
            </div>
          </div>

          {{-- Rekomendasi Rerouting Otomatis --}}
          <div class="p-4 rounded-xl bg-sky-50 border border-sky-200 space-y-2">
            <div class="flex items-center gap-2 text-xs font-bold text-sky-950">
              <svg class="w-4 h-4 text-sky-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
              <span>Rekomendasi Pengalihan Rute Otomatis</span>
            </div>
            <p id="reroute-desc" class="text-xs text-sky-900 leading-relaxed">
              <b>Kab. Tegal (3328)</b> mengalami defisit beras sebanyak <b class="text-rose-700">-340 ton</b>. Insentif subsidi ongkos angkut pangan provinsi tersedia jika armada dialihkan.
            </p>
          </div>
        </div>

        <!-- Card 2: Koridor Transportasi Cilacap -> Banyumas -->
        <div class="bg-white rounded-2xl p-6 shadow-xs border border-slate-200/90 space-y-4">
          <div class="flex items-center justify-between">
            <h3 id="koridor-title" class="text-xs font-extrabold uppercase tracking-wider text-slate-800">
              KORIDOR TRANSPORTASI CILACAP → BANYUMAS
            </h3>
            <span class="text-xs font-bold text-slate-600">Jarak: ~54,2 km</span>
          </div>

          <!-- Visual Rute Logistik -->
          <div class="relative w-full h-44 rounded-xl overflow-hidden bg-slate-900 border border-slate-200 flex items-center justify-center">
            <!-- Canvas peta rute -->
            <svg class="w-full h-full opacity-60" viewBox="0 0 400 200" fill="none">
              <!-- Jalan arteri -->
              <path d="M 50 150 Q 150 120 220 80 T 350 50" stroke="#10b981" stroke-width="4" stroke-dasharray="6,4" fill="none"/>
              <!-- Titik Asal -->
              <circle cx="50" cy="150" r="8" fill="#10b981"/>
              <circle cx="50" cy="150" r="14" stroke="#10b981" stroke-width="1.5" opacity="0.6"/>
              <text x="35" y="180" fill="#ffffff" font-size="11" font-weight="bold">Cilacap (Asal)</text>
              <!-- Titik Transit -->
              <circle cx="220" cy="80" r="5" fill="#f59e0b"/>
              <text x="210" y="65" fill="#fcd34d" font-size="9">Pasar Purwojati</text>
              <!-- Titik Tujuan -->
              <circle cx="350" cy="50" r="8" fill="#ef4444"/>
              <circle cx="350" cy="50" r="14" stroke="#ef4444" stroke-width="1.5" opacity="0.6"/>
              <text x="290" y="40" fill="#ffffff" font-size="11" font-weight="bold">Banyumas (Tujuan)</text>
            </svg>

            <!-- Pill Status Jalur & Waktu -->
            <div class="absolute bottom-3 left-3 px-2.5 py-1 rounded-md bg-emerald-950/80 backdrop-blur-xs border border-emerald-500/40 text-[10px] font-bold text-emerald-300 flex items-center gap-1.5">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
              <span>Jalur Arteri Selatan (Lancar)</span>
            </div>
            <div class="absolute bottom-3 right-3 px-2.5 py-1 rounded-md bg-slate-900/80 backdrop-blur-xs border border-slate-700 text-[10px] font-bold text-slate-300">
              Waktu Tempuh: 1j 45m
            </div>
          </div>
        </div>

      </div>

    </div>

  </div>
</div>

@push('scripts')
<script>
// ── Preview Otomatis Kg ke Ton ────────────────────────────────────────────────
function updateTonPreview(val) {
  const kg = parseFloat(val) || 0;
  const badge = document.getElementById('badge-ton-preview');
  if (kg <= 2000) {
    badge.textContent = `= ${kg.toLocaleString('id-ID')} kg`;
  } else {
    badge.textContent = `= ${(kg / 1000).toLocaleString('id-ID', {minimumFractionDigits: 1, maximumFractionDigits: 1})} ton`;
  }
}

// ── Buat Resi Acak Otomatis ───────────────────────────────────────────────────
function buatResiAcak() {
  const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
  let rand = '';
  for (let i = 0; i < 4; i++) {
    rand += chars.charAt(Math.floor(Math.random() * chars.length));
  }
  const dateStr = new Date().toISOString().slice(0, 10).replace(/-/g, '');
  document.getElementById('no_resi').value = `RESI-${dateStr}-${rand}`;
}

// ── Toggle Submit State Berdasarkan Checkbox Disclaimer ──────────────────────
function toggleSubmitState() {
  const chk = document.getElementById('konfirmasi_peringatan');
  const btn = document.getElementById('btn-submit-dispatch');
  if (chk.checked) {
    btn.disabled = false;
    btn.classList.remove('bg-slate-500', 'hover:bg-slate-600', 'cursor-not-allowed');
    btn.classList.add('bg-emerald-800', 'hover:bg-emerald-900', 'cursor-pointer');
    btn.title = 'Lanjutkan simpan pengiriman';
  } else {
    btn.disabled = true;
    btn.classList.remove('bg-emerald-800', 'hover:bg-emerald-900', 'cursor-pointer');
    btn.classList.add('bg-slate-500', 'hover:bg-slate-600', 'cursor-not-allowed');
    btn.title = 'Harap centang persetujuan disclaimer di atas untuk melanjutkan';
  }
}

// ── Reroute ke Wilayah Defisit ────────────────────────────────────────────────
function rerouteKeWilayahDefisit() {
  const selTujuan = document.getElementById('tujuan_kabupaten_id');
  // Cari opsi Kab. Tegal (3328) atau Banjarnegara (3304)
  let targetOption = Array.from(selTujuan.options).find(o => o.dataset.kode === '3328' || o.dataset.kode === '3304');
  if (targetOption) {
    selTujuan.value = targetOption.value;
    onFilterChange();
  }
}

// ── Dynamic Status Check via /api/wilayah/{kode}/cek ─────────────────────────
async function onFilterChange() {
  const selTujuan = document.getElementById('tujuan_kabupaten_id');
  const selKomoditas = document.getElementById('komoditas_id');
  const inputTanggal = document.getElementById('tanggal_kirim').value;

  const optTujuan = selTujuan.options[selTujuan.selectedIndex];
  const kodeTujuan = optTujuan ? optTujuan.dataset.kode : '3302';
  const optKomoditas = selKomoditas.options[selKomoditas.selectedIndex];
  const kodeKomoditas = optKomoditas ? optKomoditas.dataset.kode : 'beras';

  document.getElementById('span-tujuan-nama').textContent = optTujuan ? optTujuan.dataset.nama : 'Wilayah Tujuan';

  try {
    const res = await fetch(`/api/wilayah/${kodeTujuan}/cek?komoditas=${kodeKomoditas}&tanggal_kirim=${inputTanggal}`);
    if (!res.ok) return;
    const data = await res.json();

    const containerWarning = document.getElementById('container-early-warning');
    const containerNormal = document.getElementById('container-normal-submit');
    const subtextTujuan = document.getElementById('subtext-tujuan');

    // Sidebar elements
    const sidebarStatus = document.getElementById('badge-sidebar-status');
    const sidebarWilayah = document.getElementById('sidebar-wilayah-nama');
    const sidebarRatio = document.getElementById('sidebar-ratio-big');
    const sidebarDetail = document.getElementById('sidebar-capacity-detail');
    const koridorTitle = document.getElementById('koridor-title');

    sidebarWilayah.textContent = `${data.nama} (Gudang Distribusi)`;
    sidebarRatio.textContent = data.rasio_persen || '—';
    koridorTitle.textContent = `KORIDOR TRANSPORTASI ASAL → ${data.nama.toUpperCase()}`;

    if (data.status === 'penuh' || data.status === 'waspada') {
      containerWarning.classList.remove('hidden');
      containerNormal.classList.add('hidden');

      subtextTujuan.className = 'mt-1 flex items-center gap-1.5 text-[11px] text-red-600 font-medium';
      subtextTujuan.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-red-600"></span><span>Wilayah Tujuan Berisiko Early-Warning!</span>';

      sidebarStatus.className = 'px-2.5 py-0.5 rounded text-[10px] font-black uppercase bg-red-600 text-white';
      sidebarStatus.textContent = 'STATUS: OVERFLOW';

      document.getElementById('heading-warning').textContent = `PERINGATAN DINI PASOKAN: Wilayah tujuan (${data.nama}) saat ini berstatus ${data.label.toUpperCase()} (Rasio Keterisian: ${data.rasio_persen}).`;
      document.getElementById('desc-warning').textContent = data.pesan_dampak || 'Pangan berisiko basi atau terbuang bila pasokan melebihi kapasitas serap wilayah.';

      toggleSubmitState();
    } else if (data.status === 'defisit') {
      containerWarning.classList.remove('hidden');
      containerNormal.classList.add('hidden');

      subtextTujuan.className = 'mt-1 flex items-center gap-1.5 text-[11px] text-emerald-700 font-medium';
      subtextTujuan.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span><span>Wilayah Tujuan Sedang Defisit Pasokan (Prioritas)</span>';

      sidebarStatus.className = 'px-2.5 py-0.5 rounded text-[10px] font-black uppercase bg-rose-600 text-white';
      sidebarStatus.textContent = 'STATUS: DEFISIT';

      document.getElementById('badge-warning-level').className = 'px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider bg-emerald-700 text-white';
      document.getElementById('badge-warning-level').textContent = 'PRIORITAS REDISTRIBUSI';
      document.getElementById('label-warning-threat').className = 'text-xs font-bold text-emerald-800';
      document.getElementById('label-warning-threat').textContent = 'Kebutuhan Pasokan Mendesak';

      document.getElementById('heading-warning').textContent = `INFO KETAHANAN PANGAN: Wilayah tujuan (${data.nama}) saat ini berstatus DEFISIT (Rasio: ${data.rasio_persen}).`;
      document.getElementById('desc-warning').textContent = 'Pasokan pangan sangat dibutuhkan di wilayah ini guna menjaga keseimbangan pasokan dan mencegah kelangkaan.';

      // Otomatis aktifkan tombol kirim jika defisit
      const btn = document.getElementById('btn-submit-dispatch');
      btn.disabled = false;
      btn.classList.remove('bg-slate-500', 'hover:bg-slate-600', 'cursor-not-allowed');
      btn.classList.add('bg-emerald-800', 'hover:bg-emerald-900', 'cursor-pointer');
    } else {
      // Seimbang atau tanpa data
      containerWarning.classList.add('hidden');
      containerNormal.classList.remove('hidden');

      subtextTujuan.className = 'mt-1 flex items-center gap-1.5 text-[11px] text-slate-500 font-medium';
      subtextTujuan.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span><span>Status Pasokan Terpantau Normal</span>';

      sidebarStatus.className = 'px-2.5 py-0.5 rounded text-[10px] font-black uppercase bg-emerald-600 text-white';
      sidebarStatus.textContent = 'STATUS: SEIMBANG';
    }

  } catch (err) {
    console.error(err);
  }
}

document.addEventListener('DOMContentLoaded', () => {
  toggleSubmitState();
  updateTonPreview(document.getElementById('jumlah_kg').value);
});
</script>
@endpush
@endsection
