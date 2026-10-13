@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-[#f8fafc] py-6 sm:py-8 text-slate-800">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

    <!-- Breadcrumb & Nav -->
    <nav class="flex items-center gap-2 text-xs text-slate-500 font-medium">
      <a href="{{ route('pengiriman.index') }}" class="hover:text-emerald-700 transition">Portal Pengiriman</a>
      <span class="text-slate-400">/</span>
      <span class="text-slate-800 font-bold">Lacak Resi Waybill: {{ $shipment->no_resi }}</span>
    </nav>

    @if (session('status'))
      <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-xs font-bold text-emerald-800 flex items-center gap-2 shadow-xs">
        <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span>{{ session('status') }}</span>
      </div>
    @endif

    <!-- Top Card: Header Resi & Status Utama -->
    <div class="bg-white rounded-2xl p-6 shadow-xs border border-slate-200/90 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <div>
        <div class="flex items-center gap-2.5 flex-wrap">
          <span class="px-2.5 py-0.5 rounded text-[11px] font-black uppercase tracking-wider {{ $shipment->status->warnaBadge() }}">
            {{ $shipment->status->label() }}
          </span>
          <span class="text-xs text-slate-400 font-medium">
            Terdaftar: {{ $shipment->created_at->translatedFormat('d F Y, H:i') }} WIB
          </span>
          @if ($shipment->level_peringatan)
            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $shipment->level_peringatan->warnaBadge() }}">
              Tujuan: {{ $shipment->level_peringatan->label() }}
            </span>
          @endif
        </div>
        <div class="mt-2 flex items-center gap-3">
          <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight font-mono">
            {{ $shipment->no_resi }}
          </h1>
          <button onclick="salinResi('{{ $shipment->no_resi }}')"
                  class="p-1.5 text-slate-400 hover:text-emerald-700 bg-slate-50 hover:bg-slate-100 rounded-lg border border-slate-200 transition"
                  title="Salin Nomor Resi">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
          </button>
        </div>
        <p class="text-xs text-slate-500 mt-1">
          Distribusi pangan dari <b>{{ $shipment->asal->nama }}</b> menuju <b>{{ $shipment->tujuan->nama }}</b> • Muatan: <b class="text-emerald-800">{{ \App\Services\NumberFormatter::formatTon((float) $shipment->jumlah_kg, 1) }} ({{ number_format($shipment->jumlah_kg, 0, ',', '.') }} kg)</b>
        </p>
      </div>

      <div class="flex items-center gap-3 self-start md:self-center">
        <a href="{{ route('pengiriman.index') }}"
           class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 transition">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
          Kembali ke Daftar
        </a>
      </div>
    </div>

    <!-- Stepper Milestone Gaya Tracking JNE -->
    @php
      $milestones = [
        ['key' => 'dibuat', 'label' => 'Dibuat'],
        ['key' => 'diterima_di_asal', 'label' => 'Diterima di Asal'],
        ['key' => 'diproses', 'label' => 'Diproses'],
        ['key' => 'dalam_perjalanan', 'label' => 'Dalam Perjalanan'],
        ['key' => 'tiba_di_tujuan', 'label' => 'Tiba di Tujuan'],
        ['key' => 'diterima', 'label' => 'Diterima'],
      ];
      $currentKey = $shipment->status->value;
      $currentIdx = 0;
      foreach ($milestones as $i => $m) {
        if ($m['key'] === $currentKey) { $currentIdx = $i; break; }
      }
      $isCancelled = $currentKey === 'dibatalkan';
    @endphp

    <div class="bg-white rounded-2xl p-6 shadow-xs border border-slate-200/90 overflow-x-auto">
      <div class="min-w-[620px]">
        @if ($isCancelled)
          <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-center text-xs font-bold text-rose-800">
            ⚠ Pengiriman ini telah DIBATALKAN. Tidak ada proses perjalanan lebih lanjut.
          </div>
        @else
          <div class="flex items-center justify-between relative">
            <div class="absolute left-6 right-6 top-4 h-1 bg-slate-100 z-0">
              <div class="h-full bg-emerald-600 transition-all duration-500" style="width: {{ count($milestones) > 1 ? ($currentIdx / (count($milestones) - 1)) * 100 : 0 }}%"></div>
            </div>

            @foreach ($milestones as $idx => $m)
              @php
                $isDone = $idx < $currentIdx;
                $isCurrent = $idx === $currentIdx;
              @endphp
              <div class="relative z-10 flex flex-col items-center text-center w-24">
                <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs transition-all duration-300 {{ $isDone ? 'bg-emerald-600 text-white shadow-xs' : ($isCurrent ? 'bg-emerald-800 text-white ring-4 ring-emerald-100' : 'bg-slate-200 text-slate-500') }}">
                  @if ($isDone)
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                  @else
                    {{ $idx + 1 }}
                  @endif
                </div>
                <div class="mt-2 text-[11px] font-bold leading-tight {{ $isCurrent ? 'text-emerald-900 font-extrabold' : ($isDone ? 'text-slate-800' : 'text-slate-400') }}">
                  {{ $m['label'] }}
                </div>
              </div>
            @endforeach
          </div>
        @endif
      </div>
    </div>

    <!-- 2 Column Layout: Timeline Riwayat & Form Update (Kiri) vs Info Manifest (Kanan) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

      <!-- Kolom Kiri: Riwayat Perjalanan (Tracking Events) & Form Checkpoint (7/12) -->
      <div class="lg:col-span-7 space-y-6">

        <!-- Timeline Riwayat Perjalanan (Gaya JNE) -->
        <div class="bg-white rounded-2xl p-6 shadow-xs border border-slate-200/90 space-y-5">
          <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <div class="flex items-center gap-2">
              <svg class="w-5 h-5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              <h2 class="text-base font-extrabold text-slate-900 tracking-tight">
                Riwayat Perjalanan (Tracking Timeline)
              </h2>
            </div>
            <span class="text-xs text-slate-500 font-medium">
              {{ $shipment->events->count() }} Checkpoint Terdata
            </span>
          </div>

          <!-- Timeline List (Urutan terbaru di atas) -->
          <div class="relative pl-6 space-y-6 before:absolute before:left-2.5 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200">
            @forelse ($shipment->events as $evt)
              <div class="relative">
                <!-- Marker Icon -->
                <div class="absolute -left-6 top-1 w-3 h-3 rounded-full bg-emerald-600 ring-4 ring-emerald-50"></div>

                <div class="bg-slate-50/80 rounded-xl p-4 border border-slate-200/80 space-y-1.5">
                  <div class="flex items-center justify-between flex-wrap gap-2 text-xs">
                    <span class="font-bold text-slate-900 flex items-center gap-1.5">
                      <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                      {{ $evt->lokasi ?: 'Pusat Logistik' }}
                    </span>
                    <span class="text-[11px] font-mono text-slate-500">
                      {{ $evt->waktu_kejadian->translatedFormat('d M Y, H:i') }} WIB
                    </span>
                  </div>

                  <p class="text-xs text-slate-700 leading-relaxed font-medium">
                    {{ $evt->keterangan }}
                  </p>

                  <div class="pt-1 flex items-center justify-between text-[10px] text-slate-400 border-t border-slate-200/60">
                    <span class="uppercase font-bold tracking-wider text-slate-600">{{ str_replace('_', ' ', $evt->status) }}</span>
                    <span>Dicatat oleh: {{ $evt->dicatatOleh->name ?? 'Sistem' }}</span>
                  </div>
                </div>
              </div>
            @empty
              <div class="text-xs text-slate-400 italic">Belum ada riwayat tercatat.</div>
            @endforelse
          </div>
        </div>

        <!-- Form Update Checkpoint Status / Tambah Riwayat -->
        @can('update', $shipment)
          <div class="bg-white rounded-2xl p-6 shadow-xs border border-slate-200/90 space-y-4">
            <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
              <svg class="w-5 h-5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
              <h3 class="text-base font-extrabold text-slate-900 tracking-tight">
                Perbarui Status & Tambah Checkpoint Perjalanan
              </h3>
            </div>

            <form action="{{ route('pengiriman.event', $shipment->id) }}" method="POST" class="space-y-4 text-xs">
              @csrf

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label for="status" class="block font-bold text-slate-800 uppercase tracking-wide">
                    Pilih Status Baru
                  </label>
                  <select name="status" id="status" required class="mt-1.5 block w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 font-semibold text-slate-900 focus:bg-white focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 transition">
                    @foreach ($statuses as $st)
                      <option value="{{ $st->value }}" {{ $shipment->status === $st ? 'selected' : '' }}>
                        {{ $st->label() }}
                      </option>
                    @endforeach
                  </select>
                </div>

                <div>
                  <label for="lokasi" class="block font-bold text-slate-800 uppercase tracking-wide">
                    Lokasi Checkpoint
                  </label>
                  <input type="text"
                         name="lokasi"
                         id="lokasi"
                         required
                         placeholder="Contoh: Gudang Transit Banyumas"
                         value="{{ old('lokasi', $shipment->tujuan->nama ?? '') }}"
                         class="mt-1.5 block w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 font-semibold text-slate-900 focus:bg-white focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 transition" />
                </div>
              </div>

              <div>
                <label for="keterangan" class="block font-bold text-slate-800 uppercase tracking-wide">
                  Keterangan Perjalanan (Log)
                </label>
                <textarea name="keterangan"
                          id="keterangan"
                          rows="2"
                          placeholder="Contoh: Muatan beras telah tiba di gudang tujuan dan sedang dalam proses verifikasi timbang..."
                          class="mt-1.5 block w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-900 focus:bg-white focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 transition"></textarea>
              </div>

              <div class="flex items-center justify-between pt-1">
                <span class="text-[11px] text-slate-500">
                  Waktu checkpoint otomatis dicatat saat disimpan.
                </span>
                <button type="submit"
                        class="px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-emerald-800 hover:bg-emerald-900 shadow-xs transition flex items-center gap-1.5">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                  <span>Simpan Riwayat Baru</span>
                </button>
              </div>
            </form>
          </div>
        @endcan

      </div>

      <!-- Kolom Kanan: Informasi Manifest & Analisis Wilayah Tujuan (5/12) -->
      <div class="lg:col-span-5 space-y-6">

        <!-- Card Manifest Detail -->
        <div class="bg-white rounded-2xl p-6 shadow-xs border border-slate-200/90 space-y-4">
          <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-800 border-b border-slate-100 pb-3">
            Informasi Manifest Logistik
          </h3>

          <dl class="space-y-2.5 text-xs">
            <div class="flex justify-between py-1 border-b border-slate-100">
              <dt class="text-slate-500">Komoditas Bahan Pokok:</dt>
              <dd class="font-bold text-slate-900">{{ $shipment->komoditas->nama }}</dd>
            </div>
            <div class="flex justify-between py-1 border-b border-slate-100">
              <dt class="text-slate-500">Jumlah Muatan:</dt>
              <dd class="font-bold text-emerald-800">
                {{ \App\Services\NumberFormatter::formatTon((float) $shipment->jumlah_kg, 1) }}
                <span class="text-slate-500 font-normal">({{ number_format($shipment->jumlah_kg, 0, ',', '.') }} kg)</span>
              </dd>
            </div>
            <div class="flex justify-between py-1 border-b border-slate-100">
              <dt class="text-slate-500">Kabupaten Asal:</dt>
              <dd class="font-bold text-slate-900 text-right">
                {{ $shipment->asal->nama }}
                <div class="text-[10px] text-slate-400 font-normal">{{ $shipment->asal->provinsi->nama ?? '' }}</div>
              </dd>
            </div>
            <div class="flex justify-between py-1 border-b border-slate-100">
              <dt class="text-slate-500">Kabupaten Tujuan:</dt>
              <dd class="font-bold text-slate-900 text-right">
                {{ $shipment->tujuan->nama }}
                <div class="text-[10px] text-slate-400 font-normal">{{ $shipment->tujuan->provinsi->nama ?? '' }}</div>
              </dd>
            </div>
            <div class="flex justify-between py-1 border-b border-slate-100">
              <dt class="text-slate-500">Tanggal Dispatch:</dt>
              <dd class="font-bold text-slate-900">{{ $shipment->tanggal_kirim->format('d M Y') }}</dd>
            </div>
            <div class="flex justify-between py-1 border-b border-slate-100">
              <dt class="text-slate-500">Estimasi Tiba (ETA):</dt>
              <dd class="font-bold text-slate-900">{{ $shipment->estimasi_tiba ? $shipment->estimasi_tiba->format('d M Y') : '—' }}</dd>
            </div>
            <div class="flex justify-between py-1 border-b border-slate-100">
              <dt class="text-slate-500">Distributor Terdaftar:</dt>
              <dd class="font-bold text-slate-900 text-right">
                {{ $shipment->user->organisasi ?? 'Mitra Logistik Pangan' }}
                <div class="text-[10px] text-slate-400 font-normal">{{ $shipment->user->name }}</div>
              </dd>
            </div>
            @if ($shipment->catatan)
              <div class="pt-1">
                <dt class="text-slate-500 mb-1">Catatan Operasional:</dt>
                <dd class="p-2.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-700 italic">
                  "{{ $shipment->catatan }}"
                </dd>
              </div>
            @endif
          </dl>
        </div>

        <!-- Card Status Ketahanan Pangan Tujuan Saat Dibuat -->
        <div class="bg-white rounded-2xl p-6 shadow-xs border border-slate-200/90 space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-800">
              Kondisi Tujuan Saat Dibuat
            </h3>
            <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase {{ $shipment->level_peringatan ? $shipment->level_peringatan->warnaBadge() : 'bg-slate-100 text-slate-600' }}">
              {{ $shipment->level_peringatan ? $shipment->level_peringatan->label() : 'Tanpa Data' }}
            </span>
          </div>

          <div class="space-y-2 text-xs">
            <div class="flex justify-between">
              <span class="text-slate-500">Rasio Keterisian Tujuan:</span>
              <span class="font-bold text-slate-900">
                {{ $shipment->rasio_tujuan !== null ? \App\Services\NumberFormatter::formatPersen((float) $shipment->rasio_tujuan) : '—' }}
              </span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500">Konfirmasi Disclaimer:</span>
              <span class="font-bold {{ $shipment->konfirmasi_peringatan ? 'text-emerald-700' : 'text-slate-600' }}">
                {{ $shipment->konfirmasi_peringatan ? '✓ Terverifikasi Distributor' : 'Tanpa Konfirmasi' }}
              </span>
            </div>
          </div>

          <div class="pt-2">
            <a href="{{ route('wilayah.detail', ['kode' => $shipment->tujuan->kode, 'komoditas' => $shipment->komoditas->kode]) }}"
               class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs transition">
              <span>Lihat Detail Wilayah {{ $shipment->tujuan->nama }} &rarr;</span>
            </a>
          </div>
        </div>

      </div>

    </div>

  </div>
</div>

@push('scripts')
<script>
function salinResi(text) {
  navigator.clipboard.writeText(text).then(() => {
    alert(`Nomor Resi ${text} berhasil disalin ke clipboard!`);
  }).catch(() => {
    prompt('Salin nomor resi berikut:', text);
  });
}
</script>
@endpush
@endsection
