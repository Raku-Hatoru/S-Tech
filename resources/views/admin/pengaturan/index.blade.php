@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Breadcrumb & Subnav Admin --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-5">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <span>Panel Administrator</span>
                <span>/</span>
                <span class="text-slate-800 font-medium">Pengaturan & Ambang Batas</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Konfigurasi Ambang Batas & Regulasi Sistem</h1>
            <p class="text-sm text-slate-500 mt-0.5">Kelola parameter klasifikasi status ketahanan pangan, ambang surplus/defisit, dan teks disclaimer resmi.</p>
        </div>

        {{-- Navigasi Tab Admin --}}
        <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-xl border border-slate-200 text-xs font-medium">
            <a href="{{ route('admin.pengguna.index') }}" class="px-3 py-1.5 rounded-lg text-slate-600 hover:text-slate-900 transition">
                Pengguna
            </a>
            <a href="{{ route('admin.dataset.index') }}" class="px-3 py-1.5 rounded-lg text-slate-600 hover:text-slate-900 transition">
                Dataset Historis
            </a>
            <a href="{{ route('admin.forecast.index') }}" class="px-3 py-1.5 rounded-lg text-slate-600 hover:text-slate-900 transition">
                Prediksi Forecast
            </a>
            <a href="{{ route('admin.pengaturan.index') }}" class="px-3 py-1.5 rounded-lg bg-white shadow-xs text-slate-900 font-semibold transition">
                Pengaturan & Ambang
            </a>
        </div>
    </div>

    {{-- Notifikasi Sukses / Galat --}}
    @if (session('sukses'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 flex items-start gap-3 text-sm text-emerald-800">
            <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div>
                <p class="font-semibold text-emerald-900">Perubahan Berhasil Disimpan</p>
                <p class="text-emerald-700 mt-0.5 text-xs">{{ session('sukses') }}</p>
            </div>
        </div>
    @endif

    @if ($errors->any())
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-sm text-rose-800">
            <p class="font-semibold text-rose-900 mb-1">Terjadi Kesalahan Validasi</p>
            <ul class="list-disc pl-5 text-xs text-rose-700 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

        {{-- Form Pengaturan & Ambang Batas (Kiri: 8 Kolom) --}}
        <div class="lg:col-span-8 bg-white rounded-2xl border border-slate-200 p-6 sm:p-7 shadow-xs space-y-6">
            <div class="border-b border-slate-100 pb-4">
                <div class="flex items-center gap-2 mb-1">
                    <span class="px-2 py-0.5 rounded text-[10px] font-black bg-slate-900 text-white uppercase tracking-wider">
                        PRD 8.3 & 8.4
                    </span>
                    <h2 class="text-lg font-bold text-slate-900">Parameter Logika Status Wilayah</h2>
                </div>
                <p class="text-xs text-slate-500">
                    Nilai ambang batas rasio supply terhadap demand untuk mengelompokkan status wilayah menjadi Defisit, Seimbang, Waspada, dan Penuh/Surplus.
                </p>
            </div>

            <form method="POST" action="{{ route('admin.pengaturan.update') }}" class="space-y-6">
                @csrf
                @method('PUT')

                {{-- Ambang Batas Grid --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    {{-- Ambang Defisit --}}
                    <div class="p-4 rounded-xl bg-red-50/50 border border-red-200">
                        <label for="ambang_defisit_max" class="block text-xs font-bold text-red-950 mb-1">
                            Ambang Defisit Max
                        </label>
                        <p class="text-[10px] text-red-700 mb-2">Rasio &lt; nilai ini diklasifikasikan sebagai <strong>Defisit</strong>.</p>
                        <input type="number" step="0.0001" min="0.01" max="2" name="ambang_defisit_max" id="ambang_defisit_max"
                               value="{{ old('ambang_defisit_max', $settings['ambang_defisit_max']) }}"
                               required
                               class="w-full px-3 py-2 rounded-lg border border-red-300 bg-white text-sm font-bold text-slate-900 focus:ring-2 focus:ring-red-500 focus:border-red-500 shadow-2xs">
                        <span class="text-[10px] text-slate-400 mt-1 block">Default: 0.7000 (70%)</span>
                    </div>

                    {{-- Ambang Waspada --}}
                    <div class="p-4 rounded-xl bg-amber-50/50 border border-amber-200">
                        <label for="ambang_waspada_min" class="block text-xs font-bold text-amber-950 mb-1">
                            Ambang Waspada Min
                        </label>
                        <p class="text-[10px] text-amber-700 mb-2">Rasio &ge; nilai ini dan &lt; penuh diklasifikasikan <strong>Waspada</strong>.</p>
                        <input type="number" step="0.0001" min="0.01" max="2" name="ambang_waspada_min" id="ambang_waspada_min"
                               value="{{ old('ambang_waspada_min', $settings['ambang_waspada_min']) }}"
                               required
                               class="w-full px-3 py-2 rounded-lg border border-amber-300 bg-white text-sm font-bold text-slate-900 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 shadow-2xs">
                        <span class="text-[10px] text-slate-400 mt-1 block">Default: 0.9000 (90%)</span>
                    </div>

                    {{-- Ambang Penuh --}}
                    <div class="p-4 rounded-xl bg-purple-50/50 border border-purple-200">
                        <label for="ambang_penuh_min" class="block text-xs font-bold text-purple-950 mb-1">
                            Ambang Penuh Min
                        </label>
                        <p class="text-[10px] text-purple-700 mb-2">Rasio &ge; nilai ini diklasifikasikan sebagai <strong>Penuh/Surplus</strong>.</p>
                        <input type="number" step="0.0001" min="0.01" max="2" name="ambang_penuh_min" id="ambang_penuh_min"
                               value="{{ old('ambang_penuh_min', $settings['ambang_penuh_min']) }}"
                               required
                               class="w-full px-3 py-2 rounded-lg border border-purple-300 bg-white text-sm font-bold text-slate-900 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 shadow-2xs">
                        <span class="text-[10px] text-slate-400 mt-1 block">Default: 1.0000 (100%)</span>
                    </div>
                </div>

                {{-- Batas Satuan Unit Switch Kg -> Ton --}}
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                    <label for="unit_switch_kg" class="block text-xs font-bold text-slate-800 mb-1">
                        Batas Ambang Unit Switch (Kg &rarr; Ton)
                    </label>
                    <p class="text-xs text-slate-500 mb-2">
                        Jumlah komoditas di atas nilai ini akan ditampilkan dalam satuan <strong>ton</strong> dengan 1 desimal, di bawahnya dalam <strong>kg</strong>.
                    </p>
                    <div class="relative w-full sm:w-64">
                        <input type="number" min="100" max="100000" name="unit_switch_kg" id="unit_switch_kg"
                               value="{{ old('unit_switch_kg', $settings['unit_switch_kg']) }}"
                               required
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white text-sm font-bold text-slate-900 focus:ring-2 focus:ring-slate-500 pr-12 shadow-2xs">
                        <span class="absolute inset-y-0 right-0 pr-3 flex items-center text-xs font-bold text-slate-400 pointer-events-none">
                            kg
                        </span>
                    </div>
                </div>

                {{-- Teks Disclaimer Resmi --}}
                <div>
                    <label for="teks_disclaimer" class="block text-xs font-bold text-slate-800 mb-1.5">
                        Teks Disclaimer Resmi Aplikasi (Muncul di Banner Pengiriman & Footer)
                    </label>
                    <textarea name="teks_disclaimer" id="teks_disclaimer" rows="2" required
                              class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs text-slate-800 focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 shadow-2xs">{{ old('teks_disclaimer', $settings['teks_disclaimer']) }}</textarea>
                </div>

                {{-- Pesan Dampak Surplus --}}
                <div>
                    <label for="pesan_dampak_surplus" class="block text-xs font-bold text-slate-800 mb-1.5">
                        Pesan Dampak Surplus Berisiko (PRD 8.4)
                    </label>
                    <textarea name="pesan_dampak_surplus" id="pesan_dampak_surplus" rows="2" required
                              class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs text-slate-800 focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 shadow-2xs">{{ old('pesan_dampak_surplus', $settings['pesan_dampak_surplus']) }}</textarea>
                </div>

                {{-- Pesan Dampak Defisit --}}
                <div>
                    <label for="pesan_dampak_defisit" class="block text-xs font-bold text-slate-800 mb-1.5">
                        Pesan Dampak Defisit Pasokan (PRD 8.4)
                    </label>
                    <textarea name="pesan_dampak_defisit" id="pesan_dampak_defisit" rows="2" required
                              class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs text-slate-800 focus:ring-2 focus:ring-emerald-600 focus:border-emerald-600 shadow-2xs">{{ old('pesan_dampak_defisit', $settings['pesan_dampak_defisit']) }}</textarea>
                </div>

                {{-- Submit Action --}}
                <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                    <p class="text-[11px] text-slate-500 italic">
                        *Menyimpan perubahan akan otomatis memicu kalkulasi ulang status wilayah seluruh Indonesia.
                    </p>
                    <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-emerald-950 text-white text-xs font-bold transition shadow-sm">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Simpan & Hitung Ulang Semua Status
                    </button>
                </div>
            </form>
        </div>

        {{-- Kolom Kanan: Versi Model, Riwayat Impor, dan Trigger Manual (4 Kolom) --}}
        <div class="lg:col-span-4 space-y-6">

            {{-- Card Pemicu Manual Hitung Ulang --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-3">
                <div class="flex items-center gap-2 text-slate-900 font-bold text-sm">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <h3>Hitung Ulang Status Wilayah</h3>
                </div>
                <p class="text-xs text-slate-500 leading-relaxed">
                    Jalankan kalkulasi status_wilayah secara menyeluruh tanpa mengubah konfigurasi ambang batas.
                </p>
                <form method="POST" action="{{ route('admin.status.recalc') }}">
                    @csrf
                    <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold transition shadow-2xs">
                        Jalankan Kalkulasi Ulang Sekarang
                    </button>
                </form>
            </div>

            {{-- Card Versi Model ML --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Versi Model Machine Learning</h3>
                    <a href="{{ route('admin.forecast.index') }}" class="text-[11px] text-emerald-700 hover:underline font-semibold">Kelola</a>
                </div>
                <div class="space-y-2">
                    @forelse ($modelVersions as $mv)
                        <div class="p-3 rounded-xl border {{ $mv->is_aktif ? 'bg-emerald-50/70 border-emerald-200' : 'bg-slate-50 border-slate-200' }} text-xs flex items-center justify-between">
                            <div>
                                <p class="font-bold text-slate-900">{{ $mv->nama }}</p>
                                <p class="text-[10px] text-slate-500">{{ strtoupper($mv->algoritma) }} • Dilatih {{ $mv->tanggal_latih ? \Carbon\Carbon::parse($mv->tanggal_latih)->format('d M Y') : '-' }}</p>
                            </div>
                            @if ($mv->is_aktif)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-200 text-emerald-900">
                                    Aktif
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-200 text-slate-700">
                                    Arsip
                                </span>
                            @endif
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 py-2">Belum ada versi model terdaftar.</p>
                    @endforelse
                </div>
            </div>

            {{-- Card Riwayat Impor Dataset Terakhir --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Riwayat Impor Dataset Terakhir</h3>
                    <a href="{{ route('admin.dataset.index') }}" class="text-[11px] text-emerald-700 hover:underline font-semibold">Semua</a>
                </div>
                <div class="space-y-2">
                    @forelse ($recentImports as $imp)
                        <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-900 truncate max-w-[180px]">{{ $imp->nama_file }}</span>
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold {{ $imp->status === 'selesai' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                    {{ strtoupper($imp->status) }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-[10px] text-slate-500">
                                <span>{{ $imp->created_at->format('d M H:i') }} • {{ number_format($imp->jumlah_berhasil) }} baris</span>
                                <span class="uppercase font-semibold text-slate-600">{{ $imp->tipe }}</span>
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
