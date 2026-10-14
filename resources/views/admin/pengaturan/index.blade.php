@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Subnav Admin --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 border-b border-slate-200 pb-5">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Konfigurasi Ambang Batas &amp; Regulasi Sistem</h1>
        </div>

        {{-- Navigasi Tab Admin --}}
        <nav class="flex flex-wrap items-center gap-1 bg-slate-100 p-1 rounded-xl border border-slate-200 text-xs font-medium" aria-label="Navigasi Panel Admin">
            <a href="{{ route('admin.pengguna.index') }}" class="min-h-[44px] px-3.5 py-2 rounded-lg text-slate-600 hover:text-slate-900 inline-flex items-center transition">
                Pengguna
            </a>
            <a href="{{ route('admin.dataset.index') }}" class="min-h-[44px] px-3.5 py-2 rounded-lg text-slate-600 hover:text-slate-900 inline-flex items-center transition">
                Dataset Historis
            </a>
            <a href="{{ route('admin.forecast.index') }}" class="min-h-[44px] px-3.5 py-2 rounded-lg text-slate-600 hover:text-slate-900 inline-flex items-center transition">
                Prediksi Forecast
            </a>
            <a href="{{ route('admin.pengaturan.index') }}" class="min-h-[44px] px-3.5 py-2 rounded-lg bg-white shadow-xs text-slate-900 font-semibold inline-flex items-center transition">
                Pengaturan &amp; Ambang
            </a>
        </nav>
    </div>

    {{-- Notifikasi Sukses / Galat --}}
    @if (session('sukses'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 flex items-start gap-3 text-xs sm:text-sm text-emerald-900">
            <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div>
                <p class="font-semibold text-emerald-900">Perubahan Berhasil Disimpan</p>
                <p class="text-emerald-800 mt-0.5">{{ session('sukses') }}</p>
            </div>
        </div>
    @endif

    @if ($errors->any())
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-xs sm:text-sm text-rose-900">
            <p class="font-semibold text-rose-900 mb-1">Terjadi Kesalahan Validasi</p>
            <ul class="list-disc pl-5 space-y-1 text-rose-800">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

        {{-- Form Pengaturan & Ambang Batas (Kiri: 8 Kolom) --}}
        <div class="lg:col-span-8 bg-white rounded-2xl border border-slate-200 p-6 sm:p-7 shadow-2xs space-y-6">
            <div class="border-b border-slate-100 pb-4">
                <h2 class="text-lg font-bold text-slate-900">Parameter Logika Status Wilayah</h2>
            </div>

            <form method="POST" action="{{ route('admin.pengaturan.update') }}" class="space-y-6">
                @csrf
                @method('PUT')

                {{-- Ambang Batas Grid (Flattened Plain Rows) --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    {{-- Ambang Defisit --}}
                    <div>
                        <label for="ambang_defisit_max" class="block text-xs font-bold text-slate-700 mb-1.5">
                            ▼ Defisit di bawah
                        </label>
                        <input type="number" step="0.0001" min="0.01" max="2" name="ambang_defisit_max" id="ambang_defisit_max"
                               value="{{ old('ambang_defisit_max', $settings['ambang_defisit_max']) }}"
                               required
                               class="w-full min-h-[44px] px-3.5 py-2.5 rounded-xl border border-slate-300 bg-white text-xs sm:text-sm font-semibold text-slate-900 focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 shadow-2xs tabular-nums">
                    </div>

                    {{-- Ambang Waspada --}}
                    <div>
                        <label for="ambang_waspada_min" class="block text-xs font-bold text-slate-700 mb-1.5">
                            ▲ Waspada mulai
                        </label>
                        <input type="number" step="0.0001" min="0.01" max="2" name="ambang_waspada_min" id="ambang_waspada_min"
                               value="{{ old('ambang_waspada_min', $settings['ambang_waspada_min']) }}"
                               required
                               class="w-full min-h-[44px] px-3.5 py-2.5 rounded-xl border border-slate-300 bg-white text-xs sm:text-sm font-semibold text-slate-900 focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 shadow-2xs tabular-nums">
                    </div>

                    {{-- Ambang Penuh --}}
                    <div>
                        <label for="ambang_penuh_min" class="block text-xs font-bold text-slate-700 mb-1.5">
                            ◆ Penuh mulai
                        </label>
                        <input type="number" step="0.0001" min="0.01" max="2" name="ambang_penuh_min" id="ambang_penuh_min"
                               value="{{ old('ambang_penuh_min', $settings['ambang_penuh_min']) }}"
                               required
                               class="w-full min-h-[44px] px-3.5 py-2.5 rounded-xl border border-slate-300 bg-white text-xs sm:text-sm font-semibold text-slate-900 focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 shadow-2xs tabular-nums">
                    </div>
                </div>

                {{-- Batas Satuan Unit Switch Kg -> Ton --}}
                <div>
                    <label for="unit_switch_kg" class="block text-xs font-bold text-slate-700 mb-1.5" title="Nilai komoditas di atas ambang ini ditampilkan dalam ton, di bawahnya dalam kg">
                        Batas Ambang Unit Switch (Kg &rarr; Ton)
                    </label>
                    <div class="relative w-full sm:w-64">
                        <input type="number" min="100" max="100000" name="unit_switch_kg" id="unit_switch_kg"
                               value="{{ old('unit_switch_kg', $settings['unit_switch_kg']) }}"
                               required
                               title="Nilai komoditas di atas ambang ini ditampilkan dalam ton, di bawahnya dalam kg"
                               class="w-full min-h-[44px] px-3.5 py-2.5 rounded-xl border border-slate-300 bg-white text-xs sm:text-sm font-semibold text-slate-900 focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 pr-12 shadow-2xs tabular-nums">
                        <span class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-xs font-bold text-slate-500 pointer-events-none">
                            kg
                        </span>
                    </div>
                </div>

                {{-- Teks Disclaimer Resmi --}}
                <div class="space-y-1.5">
                    <label for="teks_disclaimer" class="block text-xs font-bold text-slate-800">
                        Teks Disclaimer Resmi Aplikasi
                    </label>
                    <textarea name="teks_disclaimer" id="teks_disclaimer" rows="2" required
                              class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm text-slate-800 focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 shadow-2xs">{{ old('teks_disclaimer', $settings['teks_disclaimer']) }}</textarea>
                </div>

                {{-- Pesan Dampak Surplus --}}
                <div class="space-y-1.5">
                    <label for="pesan_dampak_surplus" class="block text-xs font-bold text-slate-800">
                        Pesan Dampak Surplus Berisiko
                    </label>
                    <textarea name="pesan_dampak_surplus" id="pesan_dampak_surplus" rows="2" required
                              class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm text-slate-800 focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 shadow-2xs">{{ old('pesan_dampak_surplus', $settings['pesan_dampak_surplus']) }}</textarea>
                </div>

                {{-- Pesan Dampak Defisit --}}
                <div class="space-y-1.5">
                    <label for="pesan_dampak_defisit" class="block text-xs font-bold text-slate-800">
                        Pesan Dampak Defisit Pasokan
                    </label>
                    <textarea name="pesan_dampak_defisit" id="pesan_dampak_defisit" rows="2" required
                              class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm text-slate-800 focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 shadow-2xs">{{ old('pesan_dampak_defisit', $settings['pesan_dampak_defisit']) }}</textarea>
                </div>

                {{-- Submit Action --}}
                <div class="pt-4 border-t border-slate-100 flex items-center justify-end">
                    <button type="submit"
                            title="Menyimpan perubahan akan otomatis menghitung ulang status seluruh wilayah"
                            class="min-h-[44px] inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs sm:text-sm font-bold transition shadow-sm">
                        <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Simpan &amp; Hitung Ulang Semua Status</span>
                    </button>
                </div>
            </form>
        </div>

        {{-- Kolom Kanan: Versi Model dan Riwayat Impor (4 Kolom) --}}
        <div class="lg:col-span-4 space-y-6">

            {{-- Card Versi Model ML --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs space-y-3">
                <div class="border-b border-slate-100 pb-2">
                    <h3 class="text-xs font-bold text-slate-900">Versi Model Machine Learning</h3>
                </div>
                <div class="space-y-2">
                    @forelse ($modelVersions as $mv)
                        <div class="p-3 rounded-xl border {{ $mv->is_aktif ? 'bg-emerald-50/70 border-emerald-200' : 'bg-slate-50 border-slate-200' }} text-xs flex items-center justify-between">
                            <div>
                                <p class="font-bold text-slate-900">{{ $mv->nama }}</p>
                                <p class="text-xs text-slate-500">{{ strtoupper($mv->algoritma) }} • Dilatih {{ $mv->tanggal_latih ? \Carbon\Carbon::parse($mv->tanggal_latih)->format('d M Y') : '-' }}</p>
                            </div>
                            @if ($mv->is_aktif)
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                    Aktif
                                </span>
                            @else
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-slate-200 text-slate-700">
                                    Arsip
                                </span>
                            @endif
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 py-2">Belum ada versi model terdaftar.</p>
                    @endforelse
                </div>
            </div>

            {{-- Card Riwayat Impor Dataset Terakhir (Limit 3, tanpa FORECAST/HISTORIS tags) --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs space-y-3">
                <div class="border-b border-slate-100 pb-2">
                    <h3 class="text-xs font-bold text-slate-900">Riwayat Impor Dataset</h3>
                </div>
                <div class="space-y-2">
                    @forelse ($recentImports->take(3) as $imp)
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-900 truncate max-w-[180px]" title="{{ $imp->nama_file }}">{{ $imp->nama_file }}</span>
                                <span class="px-2 py-0.5 rounded text-xs font-bold {{ $imp->status === 'selesai' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                    {{ strtoupper($imp->status) }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-xs text-slate-500">
                                <span>{{ $imp->created_at->format('d M H:i') }} • <span class="tabular-nums">{{ number_format($imp->jumlah_berhasil) }}</span> baris</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 py-2">Belum ada riwayat impor tercatat.</p>
                    @endforelse
                </div>
            </div>

        </div>
    </div>

</div>
@endsection
