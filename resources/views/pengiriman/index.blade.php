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
            Portal Logistik Pengiriman
          </span>
        </nav>
        <h1 class="text-xl sm:text-2xl lg:text-3xl font-extrabold text-slate-900 tracking-tight">
          Portal Manajemen Distribusi Pangan Logistik
        </h1>
      </div>

      <!-- Action Tabs -->
      <div class="flex items-center gap-2.5 self-start lg:self-center flex-wrap">
        @can('create', App\Models\Shipment::class)
          <a href="{{ route('pengiriman.create') }}"
             class="min-h-[44px] inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-emerald-800 hover:bg-emerald-900 shadow-2xs transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            <span>1. Buat Pengiriman Baru</span>
          </a>
        @endcan
        <span class="min-h-[44px] inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-slate-800 bg-white border border-slate-200 shadow-2xs">
          <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
          <span>Daftar & Lacak Resi</span>
          <span class="px-2 py-0.5 bg-slate-100 text-slate-800 rounded-md text-xs font-black border border-slate-200">
            {{ $kpi['total'] }}
          </span>
        </span>
      </div>
    </div>

    @if (session('status'))
      <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-xs font-bold text-emerald-800 flex items-center gap-2 shadow-2xs">
        <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span>{{ session('status') }}</span>
      </div>
    @endif

    <!-- 4 KPI Summary Strip (Bersih, Ramping, Tanpa Sub-Teks Panjang) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
      <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-2xs border border-slate-200/90 flex flex-col justify-between">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-600">Total Pengiriman</span>
        <div class="mt-2 text-2xl font-black text-slate-900 tracking-tight tabular-nums">{{ $kpi['total'] }}</div>
      </div>

      <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-2xs border border-slate-200/90 flex flex-col justify-between">
        <span class="text-xs font-bold uppercase tracking-wider text-amber-700">Dalam Perjalanan</span>
        <div class="mt-2 text-2xl font-black text-amber-600 tracking-tight tabular-nums">{{ $kpi['dalam_perjalanan'] }}</div>
      </div>

      <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-2xs border border-slate-200/90 flex flex-col justify-between">
        <span class="text-xs font-bold uppercase tracking-wider text-emerald-800">Tiba di Tujuan</span>
        <div class="mt-2 text-2xl font-black text-emerald-700 tracking-tight tabular-nums">{{ $kpi['tiba_tujuan'] }}</div>
      </div>

      <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-2xs border border-slate-200/90 flex flex-col justify-between">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-600">Total Muatan Logistik</span>
        <div class="mt-2 text-2xl font-black text-slate-900 tracking-tight tabular-nums">{{ $kpi['total_muatan_formatted'] }}</div>
      </div>
    </div>

    <!-- Filter Bar Card: Sejajar, Proporsional, Min-Height 44px -->
    <div class="bg-white rounded-2xl p-4 shadow-2xs border border-slate-200/90">
      <form method="GET" action="{{ route('pengiriman.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
        <!-- Pencarian Resi / Wilayah -->
        <div class="sm:col-span-5 relative">
          <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          </div>
          <input type="text"
                 name="q"
                 value="{{ $filters['q'] }}"
                 placeholder="Cari nomor resi atau kabupaten..."
                 aria-label="Cari nomor resi atau kabupaten"
                 class="block w-full min-h-[44px] pl-10 pr-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-900 bg-slate-50/50 focus:bg-white focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 transition" />
        </div>

        <!-- Filter Status -->
        <div class="sm:col-span-3">
          <select name="status"
                  aria-label="Filter status pengiriman"
                  class="block w-full min-h-[44px] px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-900 bg-slate-50/50 focus:bg-white focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 transition">
            <option value="">Semua Status Pengiriman</option>
            @foreach (\App\Enums\ShipmentStatus::cases() as $st)
              <option value="{{ $st->value }}" {{ $filters['status'] === $st->value ? 'selected' : '' }}>
                {{ $st->label() }}
              </option>
            @endforeach
          </select>
        </div>

        <!-- Filter Komoditas -->
        <div class="sm:col-span-2">
          <select name="komoditas"
                  aria-label="Filter komoditas pangan"
                  class="block w-full min-h-[44px] px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-900 bg-slate-50/50 focus:bg-white focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 transition">
            <option value="">Semua Komoditas</option>
            @foreach ($komoditasList as $k)
              <option value="{{ $k->kode }}" {{ $filters['komoditas'] === $k->kode ? 'selected' : '' }}>
                {{ $k->nama }}
              </option>
            @endforeach
          </select>
        </div>

        <!-- Action Buttons -->
        <div class="sm:col-span-2 flex items-center gap-2">
          <button type="submit"
                  class="w-full min-h-[44px] py-2.5 px-4 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs shadow-2xs transition">
            Filter
          </button>
          @if ($filters['q'] || $filters['status'] || $filters['komoditas'])
            <a href="{{ route('pengiriman.index') }}"
               aria-label="Reset filter pencarian"
               class="min-h-[44px] min-w-[44px] inline-flex items-center justify-center rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition" title="Reset Filter">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </a>
          @endif
        </div>
      </form>
    </div>

    <!-- Table Daftar Resi & Pengiriman -->
    <div class="bg-white rounded-2xl shadow-2xs border border-slate-200/90 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-600">
          <thead class="bg-slate-50/90 text-slate-700 uppercase font-bold text-xs tracking-wider border-b border-slate-200">
            <tr>
              <th class="px-5 py-3.5">Nomor Resi (Waybill)</th>
              <th class="px-4 py-3.5">Komoditas & Muatan</th>
              <th class="px-4 py-3.5">Rute (Asal → Tujuan)</th>
              <th class="px-4 py-3.5">Jadwal Kirim / ETA</th>
              <th class="px-4 py-3.5 text-center">Status Pengiriman</th>
              <th class="px-4 py-3.5 text-center">Status Tujuan</th>
              <th class="px-5 py-3.5 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 font-medium">
            @forelse ($shipments as $s)
              @php
                $destLevel = $s->level_peringatan;
                $isPenuhDest = $destLevel && $destLevel->value === 'penuh';
                $isWaspadaDest = $destLevel && $destLevel->value === 'waspada';
                $isDefisitDest = $destLevel && $destLevel->value === 'defisit';
                $isSeimbangDest = $destLevel && $destLevel->value === 'seimbang';

                $destBadgeClass = $isPenuhDest ? 'bg-purple-100 text-purple-900 border-purple-200' :
                  ($isWaspadaDest ? 'bg-amber-100 text-amber-900 border-amber-200' :
                  ($isDefisitDest ? 'bg-rose-100 text-rose-900 border-rose-200' :
                  ($isSeimbangDest ? 'bg-emerald-100 text-emerald-900 border-emerald-200' : 'bg-slate-100 text-slate-700 border-slate-200')));
                $destIcon = $isPenuhDest ? '◆' : ($isWaspadaDest ? '▲' : ($isDefisitDest ? '▼' : ($isSeimbangDest ? '✔' : '—')));
              @endphp
              <tr class="hover:bg-slate-50/80 transition">
                <!-- Resi -->
                <td class="px-5 py-3.5">
                  <a href="{{ route('pengiriman.show', $s->id) }}" class="min-h-[44px] inline-flex items-center gap-1.5 font-mono font-bold text-emerald-800 hover:text-emerald-950 transition">
                    <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    <span>{{ $s->no_resi }}</span>
                  </a>
                  <div class="text-xs text-slate-500">{{ $s->created_at->format('d/m/Y H:i') }}</div>
                </td>

                <!-- Komoditas & Muatan -->
                <td class="px-4 py-3.5">
                  <div class="font-bold text-slate-900">{{ $s->komoditas->nama }}</div>
                  <div class="text-xs font-semibold text-emerald-700 tabular-nums">
                    {{ \App\Services\NumberFormatter::formatTon((float) $s->jumlah_kg, 1) }}
                    <span class="text-slate-500 font-normal">({{ number_format($s->jumlah_kg, 0, ',', '.') }} kg)</span>
                  </div>
                </td>

                <!-- Rute -->
                <td class="px-4 py-3.5">
                  <div class="flex items-center gap-1.5 text-slate-800 font-semibold">
                    <span>{{ $s->asal->nama }}</span>
                    <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    <span class="text-slate-900 font-bold">{{ $s->tujuan->nama }}</span>
                  </div>
                  <div class="text-xs text-slate-500">
                    {{ $s->asal->provinsi->nama ?? '' }} → {{ $s->tujuan->provinsi->nama ?? '' }}
                  </div>
                </td>

                <!-- Jadwal -->
                <td class="px-4 py-3.5">
                  <div class="text-slate-800">Kirim: <b class="font-bold tabular-nums">{{ $s->tanggal_kirim->format('d/m/Y') }}</b></div>
                  <div class="text-xs text-slate-500">
                    ETA: <span class="tabular-nums font-medium">{{ $s->estimasi_tiba ? $s->estimasi_tiba->format('d/m/Y') : '—' }}</span>
                  </div>
                </td>

                <!-- Status Pengiriman -->
                <td class="px-4 py-3.5 text-center">
                  <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold uppercase tracking-wider {{ $s->status->warnaBadge() }}">
                    {{ $s->status->label() }}
                  </span>
                </td>

                <!-- Level Peringatan Tujuan (Warna & Simbol Baru) -->
                <td class="px-4 py-3.5 text-center">
                  @if ($destLevel)
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-bold uppercase border {{ $destBadgeClass }}">
                      <span>{{ $destIcon }}</span>
                      <span>{{ $destLevel->label() }}</span>
                    </span>
                  @else
                    <span class="text-slate-400 text-xs">—</span>
                  @endif
                </td>

                <!-- Aksi -->
                <td class="px-5 py-3.5 text-right">
                  <a href="{{ route('pengiriman.show', $s->id) }}"
                     class="min-h-[44px] inline-flex items-center gap-1 text-xs font-bold text-emerald-700 hover:text-emerald-900 hover:underline">
                    <span>Lacak & Update</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                  <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                  </div>
                  <div class="text-sm font-bold text-slate-800">Belum ada data pengiriman terdaftar</div>
                  <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                    Klik tombol "Buat Pengiriman Baru" di atas untuk mendaftarkan manifest distribusi pangan.
                  </p>
                  @can('create', App\Models\Shipment::class)
                    <div class="mt-4">
                      <a href="{{ route('pengiriman.create') }}" class="min-h-[44px] inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-emerald-800 text-white font-bold text-xs shadow-xs hover:bg-emerald-900 transition">
                        + Buat Pengiriman Pertama
                      </a>
                    </div>
                  @endcan
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      @if ($shipments->hasPages())
        <div class="p-4 bg-slate-50 border-t border-slate-200">
          {{ $shipments->links() }}
        </div>
      @endif
    </div>

  </div>
</div>
@endsection
