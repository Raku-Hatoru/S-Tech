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
          <a href="{{ route('panen.index') }}" class="min-h-[44px] inline-flex items-center hover:text-emerald-700 transition">
            Portal BUMDes
          </a>
          <span>/</span>
          <span class="min-h-[44px] inline-flex items-center text-slate-900 font-bold">
            Jadwal Tanam
          </span>
        </nav>
        <h1 class="text-xl sm:text-2xl lg:text-3xl font-extrabold text-slate-900 tracking-tight">
          Rekomendasi Jadwal Tanam Komoditas
        </h1>
        <div class="text-xs text-slate-600 mt-1 font-medium">
          Wilayah: <b class="text-slate-900 font-bold">{{ $kabupaten->nama }}, {{ $kabupaten->provinsi->nama ?? 'Jawa Tengah' }}</b>
        </div>
      </div>

      <!-- Action Tabs -->
      <div class="flex items-center gap-2.5 self-start lg:self-center flex-wrap">
        <a href="{{ route('panen.index', ['kabupaten_id' => $kabupaten->id]) }}"
           class="min-h-[44px] inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 shadow-2xs transition">
          <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
          <span>Kembali ke Input Panen</span>
        </a>
      </div>
    </div>

    {{-- Mode Admin: Pemilih Kabupaten --}}
    @if (auth()->user()->role?->kode === 'admin')
      <div class="p-4 rounded-2xl bg-amber-50/90 border border-amber-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
        <div class="flex items-center gap-2 text-amber-950 font-medium">
          <svg class="w-4 h-4 text-amber-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <span><b>Mode Administrator:</b> Pilih wilayah kabupaten untuk melihat simulasi rekomendasi jadwal tanam:</span>
        </div>
        <form method="GET" action="{{ route('jadwal-tanam.index') }}">
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

    {{-- Banner Utama: Label Wajib SIMULASI / DATA CONTOH (PRD F-10) --}}
    <div class="bg-blue-50/80 border border-blue-200 rounded-2xl p-4 sm:p-5 shadow-2xs flex items-center gap-3">
      <span class="px-2.5 py-1 rounded text-xs font-extrabold bg-blue-200 text-blue-900 uppercase tracking-wide shrink-0">
        [SIMULASI / DATA CONTOH]
      </span>
      <span class="text-xs sm:text-sm text-blue-950 font-medium">
        Panduan jadwal tanam untuk disampaikan kepada petani agar pasokan bulanan seimbang.
      </span>
    </div>

    {{-- Konten Utama: 2 Kolom (Tabel Kiri 8/12 + Info Kanan 4/12) --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

      {{-- Kolom Kiri: Tabel Kalender Rekomendasi Tanam (8/12) --}}
      <div class="lg:col-span-8 bg-white rounded-2xl shadow-2xs border border-slate-200/90 overflow-hidden space-y-4">
        <div class="p-5 sm:p-6 border-b border-slate-100 flex items-center justify-between">
          <h3 class="text-base sm:text-lg font-extrabold text-slate-900 tracking-tight">
            Kalender Rotasi Tanam Binaan Wilayah {{ $kabupaten->nama }}
          </h3>
          <span class="px-2.5 py-1 rounded-md text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
            {{ count($recommendationsList) }} Komoditas
          </span>
        </div>

        {{-- Tabel Rekomendasi Tanam --}}
        <div class="overflow-x-auto" tabindex="0" role="region" aria-label="Tabel Kalender Rotasi Tanam">
          <table class="w-full text-left text-xs text-slate-600">
            <thead class="bg-slate-50/90 text-slate-700 uppercase font-bold text-xs tracking-wider border-b border-slate-200">
              <tr>
                <th class="px-5 py-3.5">Komoditas</th>
                <th class="px-4 py-3.5 text-center">Waktu Tanam Disarankan</th>
                <th class="px-4 py-3.5 text-center">Perkiraan Panen</th>
                <th class="px-4 py-3.5 text-right">Proyeksi Kebutuhan</th>
                <th class="px-4 py-3.5 text-center">Curah Hujan</th>
                <th class="px-5 py-3.5">Catatan Agronomi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-medium">
              @foreach ($recommendationsList as $rec)
                <tr class="hover:bg-slate-50/80 transition">
                  <td class="px-5 py-3.5 whitespace-nowrap">
                    <div class="font-bold text-slate-900">{{ $rec->komoditas_label }}</div>
                    <div class="text-xs text-slate-500">{{ $rec->varietas }}</div>
                  </td>
                  <td class="px-4 py-3.5 text-center whitespace-nowrap">
                    <span class="px-2.5 py-1 rounded-md text-xs font-bold bg-emerald-100 text-emerald-900 border border-emerald-200">
                      {{ $rec->waktu_tanam }}
                    </span>
                  </td>
                  <td class="px-4 py-3.5 text-center whitespace-nowrap font-bold text-slate-800 tabular-nums">
                    {{ $rec->perkiraan_panen }}
                  </td>
                  <td class="px-4 py-3.5 text-right whitespace-nowrap font-bold text-slate-900 tabular-nums">
                    {{ $rec->proyeksi_kebutuhan }}
                  </td>
                  <td class="px-4 py-3.5 text-center whitespace-nowrap">
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-xs font-semibold bg-blue-50 text-blue-800 border border-blue-200">
                      {{ $rec->curah_hujan }}
                    </span>
                  </td>
                  <td class="px-5 py-3.5 text-slate-600 leading-relaxed max-w-xs">
                    {{ $rec->catatan_agronomi }}
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        {{-- WhatsApp Action Box --}}
        <div class="p-5 sm:p-6 bg-slate-50/80 border-t border-slate-200/90 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center shrink-0">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            </div>
            <div>
              <p class="text-xs font-bold text-slate-900">Saluran Edaran Jadwal Tanam Gapoktan</p>
              <p class="text-xs text-slate-500 font-medium">Teruskan rekomendasi kalender tanam resmi kepada pengurus kelompok tani.</p>
            </div>
          </div>

          <a href="{{ $waLink }}"
             target="_blank"
             rel="noopener noreferrer"
             class="min-h-[44px] inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-800 hover:bg-emerald-900 text-white font-bold text-xs shadow-2xs transition whitespace-nowrap">
            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
            <span>Teruskan Panduan ke Gapoktan</span>
          </a>
        </div>
      </div>

      {{-- Kolom Kanan: Siklus Koordinasi & Info Iklim (4/12) --}}
      <div class="lg:col-span-4 space-y-6">

        {{-- Card Siklus Koordinasi --}}
        <div class="bg-white rounded-2xl p-5 sm:p-6 shadow-2xs border border-slate-200/90 space-y-4">
          <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider border-b border-slate-100 pb-2">
            Siklus Koordinasi Lapangan:
          </h4>
          <div class="space-y-3 text-xs">
            <div class="flex items-start gap-3">
              <div class="w-6 h-6 rounded-full bg-slate-900 text-white flex items-center justify-center font-bold text-xs shrink-0">
                1
              </div>
              <p class="text-slate-700 font-medium pt-0.5">BUMDes input hasil panen & sisa stok riil lumbung.</p>
            </div>
            <div class="flex items-start gap-3">
              <div class="w-6 h-6 rounded-full bg-emerald-800 text-white flex items-center justify-center font-bold text-xs shrink-0">
                2
              </div>
              <p class="text-slate-700 font-medium pt-0.5">Sistem mengevaluasi status keseimbangan pasokan wilayah.</p>
            </div>
            <div class="flex items-start gap-3">
              <div class="w-6 h-6 rounded-full bg-blue-800 text-white flex items-center justify-center font-bold text-xs shrink-0">
                3
              </div>
              <p class="text-slate-700 font-medium pt-0.5">Rekomendasi jadwal tanam disosialisasikan ke Gapoktan.</p>
            </div>
          </div>
        </div>

        {{-- Card Sinkronisasi BMKG --}}
        <div class="bg-white rounded-2xl p-5 sm:p-6 shadow-2xs border border-slate-200/90 space-y-3">
          <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 00-9.78 2.096A4.001 4.001 0 003 15z"/></svg>
            <h5 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Sinkronisasi Iklim BMKG</h5>
          </div>
          <p class="text-xs text-slate-600 font-medium leading-relaxed">
            Prakiraan agroklimat stasiun meteorologi terintegrasi untuk penyesuaian periode semai komoditas strategis.
          </p>
          <div class="pt-2 flex items-center justify-between text-xs font-bold border-t border-slate-100">
            <span class="text-slate-500">Status Prakiraan:</span>
            <span class="text-emerald-800">Normal Basah</span>
          </div>
        </div>

      </div>

    </div>

  </div>
</div>
@endsection
