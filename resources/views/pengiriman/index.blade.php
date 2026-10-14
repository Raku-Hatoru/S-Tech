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
        </div>
      </div>

      <!-- Navigation Tabs -->
      <div class="flex items-center gap-2 self-start lg:self-center">
        @can('create', App\Models\Shipment::class)
          <a href="{{ route('pengiriman.create') }}"
             class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200/90 shadow-xs transition">
            <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            <span>1. Buat Pengiriman Baru</span>
          </a>
        @endcan
        <a href="{{ route('pengiriman.index') }}"
           class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-[#064e3b] shadow-sm transition">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
          <span>2. Daftar & Lacak Resi</span>
          <span class="px-1.5 py-0.2 bg-emerald-100 text-emerald-800 rounded-full text-[10px] font-black">
            {{ $kpi['total'] }}
          </span>
        </a>
      </div>
    </div>

    @if (session('status'))
      <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-xs font-bold text-emerald-800 flex items-center gap-2 shadow-xs">
        <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span>{{ session('status') }}</span>
      </div>
    @endif

    <!-- 4 KPI Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-200 flex flex-col justify-between">
        <div class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Pengiriman</div>
        <div class="mt-2 text-2xl font-black text-slate-900">{{ $kpi['total'] }}</div>
        <div class="mt-2 text-[11px] text-slate-500">Seluruh manifest dispatch terdaftar</div>
      </div>

      <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-200 flex flex-col justify-between">
        <div class="text-xs font-bold uppercase tracking-wider text-slate-500">Dalam Perjalanan (In Transit)</div>
        <div class="mt-2 text-2xl font-black text-amber-600">{{ $kpi['dalam_perjalanan'] }}</div>
        <div class="mt-2 text-[11px] text-slate-500">Sedang diproses & armada di jalan</div>
      </div>

      <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-200 flex flex-col justify-between">
        <div class="text-xs font-bold uppercase tracking-wider text-slate-500">Tiba di Tujuan / Diterima</div>
        <div class="mt-2 text-2xl font-black text-emerald-700">{{ $kpi['tiba_tujuan'] }}</div>
        <div class="mt-2 text-[11px] text-slate-500">Berhasil sampai di gudang penerima</div>
      </div>

      <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-200 flex flex-col justify-between">
        <div class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Muatan Logistik</div>
        <div class="mt-2 text-2xl font-black text-slate-900">{{ $kpi['total_muatan_formatted'] }}</div>
        <div class="mt-2 text-[11px] text-slate-500">Akumulasi tonase pangan aktif</div>
      </div>
    </div>

    <!-- Filter Bar Card -->
    <div class="bg-white rounded-2xl p-4 shadow-xs border border-slate-200/90">
      <form method="GET" action="{{ route('pengiriman.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
        <!-- Pencarian Resi / Wilayah -->
        <div class="sm:col-span-5 relative">
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          </div>
          <input type="text"
                 name="q"
                 value="{{ $filters['q'] }}"
                 placeholder="Cari nomor resi (waybill) atau kabupaten..."
                 class="block w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-900 bg-slate-50/50 focus:bg-white focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 transition" />
        </div>

        <!-- Filter Status -->
        <div class="sm:col-span-3">
          <select name="status"
                  class="block w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-900 bg-slate-50/50 focus:bg-white focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 transition">
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
                  class="block w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-900 bg-slate-50/50 focus:bg-white focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 transition">
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
                  class="w-full py-2 px-3 rounded-xl bg-emerald-800 hover:bg-emerald-900 text-white font-bold text-xs shadow-xs transition">
            Filter
          </button>
          @if ($filters['q'] || $filters['status'] || $filters['komoditas'])
            <a href="{{ route('pengiriman.index') }}"
               class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 transition" title="Reset Filter">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </a>
          @endif
        </div>
      </form>
    </div>

    <!-- Table Daftar Resi & Pengiriman -->
    <div class="bg-white rounded-2xl shadow-xs border border-slate-200/90 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-600">
          <thead class="bg-slate-50/80 text-slate-500 uppercase font-bold text-[10px] tracking-wider border-b border-slate-200">
            <tr>
              <th class="px-5 py-3.5">Nomor Resi (Waybill)</th>
              <th class="px-4 py-3.5">Komoditas & Muatan</th>
              <th class="px-4 py-3.5">Rute (Asal → Tujuan)</th>
              <th class="px-4 py-3.5">Jadwal (Kirim / ETA)</th>
              <th class="px-4 py-3.5 text-center">Status Terkini</th>
              <th class="px-4 py-3.5 text-center">Status Wilayah Tujuan</th>
              <th class="px-4 py-3.5 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 font-medium">
            @forelse ($shipments as $s)
              <tr class="hover:bg-slate-50/80 transition">
                <!-- Resi -->
                <td class="px-5 py-3.5">
                  <a href="{{ route('pengiriman.show', $s->id) }}" class="font-mono font-bold text-emerald-800 hover:text-emerald-950 flex items-center gap-1.5 group">
                    <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-emerald-700 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    <span>{{ $s->no_resi }}</span>
                  </a>
                  <div class="text-[10px] text-slate-400 mt-0.5">Dibuat: {{ $s->created_at->format('d/m/Y H:i') }}</div>
                </td>

                <!-- Komoditas & Muatan -->
                <td class="px-4 py-3.5">
                  <div class="font-bold text-slate-900">{{ $s->komoditas->nama }}</div>
                  <div class="text-[11px] font-semibold text-emerald-700">
                    {{ \App\Services\NumberFormatter::formatTon((float) $s->jumlah_kg, 1) }}
                    <span class="text-slate-400 font-normal">({{ number_format($s->jumlah_kg, 0, ',', '.') }} kg)</span>
                  </div>
                </td>

                <!-- Rute -->
                <td class="px-4 py-3.5">
                  <div class="flex items-center gap-1.5 text-slate-800 font-semibold">
                    <span>{{ $s->asal->nama }}</span>
                    <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    <span class="text-slate-900 font-bold">{{ $s->tujuan->nama }}</span>
                  </div>
                  <div class="text-[10px] text-slate-400 mt-0.5">
                    {{ $s->asal->provinsi->nama ?? '' }} → {{ $s->tujuan->provinsi->nama ?? '' }}
                  </div>
                </td>

                <!-- Jadwal -->
                <td class="px-4 py-3.5">
                  <div class="text-slate-800">Kirim: <b>{{ $s->tanggal_kirim->format('d M Y') }}</b></div>
                  <div class="text-[11px] text-slate-500">
                    ETA: {{ $s->estimasi_tiba ? $s->estimasi_tiba->format('d M Y') : '—' }}
                  </div>
                </td>

                <!-- Status Terkini -->
                <td class="px-4 py-3.5 text-center">
                  <span class="px-2.5 py-1 rounded-md text-[10px] font-black uppercase tracking-wider {{ $s->status->warnaBadge() }}">
                    {{ $s->status->label() }}
                  </span>
                </td>

                <!-- Level Peringatan Tujuan Saat Dibuat -->
                <td class="px-4 py-3.5 text-center">
                  @if ($s->level_peringatan)
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $s->level_peringatan->warnaBadge() }}">
                      {{ $s->level_peringatan->label() }}
                    </span>
                    @if ($s->konfirmasi_peringatan)
                      <div class="text-[9px] text-amber-700 font-semibold mt-0.5">Disclaimer Terkonfirmasi</div>
                    @endif
                  @else
                    <span class="text-slate-400 text-xs">—</span>
                  @endif
                </td>

                <!-- Aksi -->
                <td class="px-5 py-3.5 text-right">
                  <a href="{{ route('pengiriman.show', $s->id) }}"
                     class="inline-flex items-center gap-1 text-xs font-bold text-emerald-700 hover:text-emerald-900 hover:underline">
                    <span>Lacak & Update</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                  <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                  </div>
                  <div class="text-sm font-bold text-slate-700">Belum ada data pengiriman terdaftar</div>
                  <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                    Klik tombol "Buat Pengiriman Baru" di atas untuk mendaftarkan manifest distribusi pangan.
                  </p>
                  @can('create', App\Models\Shipment::class)
                    <div class="mt-4">
                      <a href="{{ route('pengiriman.create') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-800 text-white font-bold text-xs shadow-xs hover:bg-emerald-900 transition">
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
