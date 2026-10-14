@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Breadcrumb & Subnav Admin --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 border-b border-slate-200 pb-5">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <span>Panel Administrator</span>
                <span>/</span>
                <span class="text-slate-800 font-medium">Prediksi Forecast</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Hasil Prediksi XGBoost & Versi Model</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Kelola keluaran model proyeksi (panen, pasokan masuk, kebutuhan) dan versi model aktif.</p>
        </div>

        {{-- Navigasi Tab Admin --}}
        <nav class="flex flex-wrap items-center gap-1 bg-slate-100 p-1 rounded-xl border border-slate-200 text-xs font-medium" aria-label="Navigasi Panel Admin">
            <a href="{{ route('admin.pengguna.index') }}" class="min-h-[44px] px-3.5 py-2 rounded-lg text-slate-600 hover:text-slate-900 inline-flex items-center transition">
                Pengguna
            </a>
            <a href="{{ route('admin.dataset.index') }}" class="min-h-[44px] px-3.5 py-2 rounded-lg text-slate-600 hover:text-slate-900 inline-flex items-center transition">
                Dataset Historis
            </a>
            <a href="{{ route('admin.forecast.index') }}" class="min-h-[44px] px-3.5 py-2 rounded-lg bg-white shadow-xs text-slate-900 font-semibold inline-flex items-center transition">
                Prediksi Forecast
            </a>
            <a href="{{ route('admin.pengaturan.index') }}" class="min-h-[44px] px-3.5 py-2 rounded-lg text-slate-600 hover:text-slate-900 inline-flex items-center transition">
                Pengaturan & Ambang
            </a>
        </nav>
    </div>

    {{-- Notifikasi Sukses / Galat --}}
    @if (session('sukses'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 flex items-start gap-3 text-xs sm:text-sm text-emerald-900">
            <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div>
                <p class="font-semibold text-emerald-900">Proses Berhasil</p>
                <p class="text-emerald-800 mt-0.5">{{ session('sukses') }}</p>
            </div>
        </div>
    @endif

    @if (session('galat'))
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 flex items-start gap-3 text-xs sm:text-sm text-rose-900">
            <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="2"/><line x1="12" y1="8" x2="12" y2="12" stroke-width="2"/><line x1="12" y1="16" x2="12.01" y2="16" stroke-width="2"/></svg>
            <div>
                <p class="font-semibold text-rose-900">Perhatian / Gagal</p>
                <p class="text-rose-800 mt-0.5 whitespace-pre-line">{{ session('galat') }}</p>
            </div>
        </div>
    @endif

    {{-- Kartu Ringkasan Statistik --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-2xs">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Total Titik Forecast</p>
            <p class="text-2xl font-bold text-slate-900 tabular-nums">{{ number_format($totalForecasts) }}</p>
            <p class="text-xs text-slate-500 mt-1">Titik proyeksi kabupaten × komoditas</p>
        </div>
        <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-2xs">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Versi Model Aktif</p>
            <div class="flex items-center gap-2 mt-1">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shrink-0"></span>
                <p class="text-base font-bold text-slate-900 truncate">
                    {{ $activeVersion?->nama ?? 'Belum ada model aktif' }}
                </p>
            </div>
            <p class="text-xs text-slate-500 mt-1">Algoritma: {{ strtoupper($activeVersion?->algoritma ?? 'XGBoost') }}</p>
        </div>
        <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-2xs">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Rentang Target Horizon</p>
            <p class="text-base font-bold text-slate-900 mt-1">
                @if ($minTarget && $maxTarget)
                    {{ \Carbon\Carbon::parse($minTarget)->format('M Y') }} – {{ \Carbon\Carbon::parse($maxTarget)->format('M Y') }}
                @else
                    <span class="text-slate-400 font-normal">Belum ada data</span>
                @endif
            </p>
            <p class="text-xs text-slate-500 mt-1">Horizon 1–12 bulan ke depan</p>
        </div>
        <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-2xs">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Total Sesi Impor</p>
            <p class="text-2xl font-bold text-slate-900 tabular-nums">{{ number_format($imports->total()) }}</p>
            <p class="text-xs text-slate-500 mt-1">Riwayat impor berkas forecast</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
        {{-- Form Unggah CSV (Kiri) --}}
        <div class="lg:col-span-1">
            <div class="p-6 rounded-2xl bg-white border border-slate-200 shadow-2xs sticky top-28 space-y-4">
                <div class="flex items-center gap-2.5 pb-2 border-b border-slate-100">
                    <div class="w-8 h-8 rounded-lg bg-purple-100 text-purple-800 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                    </div>
                    <h2 class="text-base font-bold text-slate-900">Unggah Hasil Prediksi CSV</h2>
                </div>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Unggah berkas CSV hasil pipeline model proyeksi. Data prediksi baru tidak menghapus histori sebelumnya agar evaluasi akurasi tetap terjaga.
                </p>

                <form action="{{ route('admin.forecast.store') }}" method="POST" enctype="multipart/form-data" id="form-upload-forecast" class="space-y-4">
                    @csrf
                    <div>
                        <label for="forecast-file-input" class="block text-xs font-semibold text-slate-700 mb-2">Pilih Berkas CSV (.csv)</label>
                        <div class="border-2 border-dashed border-slate-300 hover:border-purple-500 rounded-xl p-4 text-center transition bg-slate-50/50">
                            <input type="file" name="file" id="forecast-file-input" accept=".csv,text/csv,text/plain" required class="block w-full min-h-[44px] text-xs text-slate-500 file:mr-3 file:min-h-[44px] file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-900 file:text-white hover:file:bg-slate-800 cursor-pointer">
                        </div>
                        @error('file')
                            <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Accordion Ketentuan Kolom CSV --}}
                    <details class="group rounded-xl border border-slate-200 bg-slate-50/70 p-3.5 text-xs transition">
                        <summary class="flex items-center justify-between font-semibold text-slate-700 cursor-pointer list-none min-h-[36px] select-none hover:text-slate-900">
                            <span class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-purple-600 transition group-open:rotate-90 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                <span>Lihat Ketentuan Kolom CSV</span>
                            </span>
                            <span class="text-xs font-normal text-slate-500 group-open:hidden">10 kolom wajib</span>
                        </summary>
                        <div class="mt-2.5 pt-2.5 border-t border-slate-200 space-y-2 text-slate-600">
                            <span class="font-semibold text-slate-800 block text-xs">Struktur Kolom Wajib CSV:</span>
                            <code class="text-slate-800 block break-all font-mono text-xs bg-white p-2 rounded border border-slate-200 select-all">kode_kabupaten, komoditas, periode_dasar, periode_target, horizon_bulan, panen_pred_kg, pasokan_masuk_pred_kg, stok_awal_pred_kg, demand_pred_kg, versi_model</code>
                            <p class="text-xs text-slate-500 leading-relaxed">
                                Format: UTF-8, pemisah koma (,), desimal titik (.), format periode YYYY-MM. Nilai supply dihitung otomatis bergulir; status dipetakan berdasarkan rasio dan ambang sistem.
                            </p>
                        </div>
                    </details>

                    <button type="submit" id="btn-submit-forecast" class="w-full min-h-[44px] flex items-center justify-center gap-2 bg-slate-900 hover:bg-slate-800 text-white font-semibold py-2.5 px-4 rounded-xl transition text-xs sm:text-sm shadow-2xs">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                        <span>Mulai Impor Forecast</span>
                    </button>
                </form>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                    <span>CLI Artisan:</span>
                    <code class="bg-slate-100 px-1.5 py-0.5 rounded text-slate-700 font-mono text-xs">php artisan forecast:import {file}</code>
                </div>
            </div>
        </div>

        {{-- Kolom Kanan: Versi Model & Riwayat Impor --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- 1. Daftar Versi Model Terdaftar --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
                <div class="p-5 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Versi Model Machine Learning</h2>
                        <p class="text-xs text-slate-500">Versi model yang digunakan untuk kalibrasi prediksi dan label transparansi sistem.</p>
                    </div>
                </div>

                @if ($modelVersions->isEmpty())
                    <div class="py-8 text-center text-slate-400 text-xs">
                        Belum ada versi model terdaftar. Model otomatis terbuat saat berkas forecast diimpor.
                    </div>
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach ($modelVersions as $version)
                            <div class="p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 hover:bg-slate-50/60 transition">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 {{ $version->is_aktif ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-xs sm:text-sm text-slate-900">{{ $version->nama }}</span>
                                            @if ($version->is_aktif)
                                                <span class="px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800 uppercase tracking-wider">Model Aktif</span>
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-2 text-xs text-slate-500 mt-0.5">
                                            <span>Algoritma: <strong class="text-slate-700">{{ strtoupper($version->algoritma) }}</strong></span>
                                            <span>•</span>
                                            <span class="tabular-nums">{{ number_format($version->forecasts_count) }} titik prediksi</span>
                                        </div>
                                    </div>
                                </div>

                                <form action="{{ route('admin.forecast.toggle-model', $version) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="min-h-[44px] text-xs px-4 py-2 rounded-xl border font-semibold transition inline-flex items-center justify-center {{ $version->is_aktif ? 'border-slate-300 text-slate-700 hover:bg-slate-100' : 'border-emerald-600 text-emerald-800 bg-emerald-50 hover:bg-emerald-100' }}">
                                        {{ $version->is_aktif ? 'Nonaktifkan' : 'Jadikan Model Aktif' }}
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- 2. Tabel Riwayat Impor Forecast --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
                <div class="p-5 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Riwayat Impor Forecast</h2>
                        <p class="text-xs text-slate-500">Catatan audit riwayat proses berkas hasil prediksi.</p>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-700 tabular-nums">
                        {{ $imports->total() }} Sesi
                    </span>
                </div>

                @if ($imports->isEmpty())
                    <div class="py-16 text-center text-slate-400">
                        <svg class="w-12 h-12 mx-auto mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <p class="text-sm font-semibold text-slate-600">Belum Ada Riwayat Impor Forecast</p>
                        <p class="text-xs text-slate-500 mt-1">Unggah berkas prediksi pertama melalui formulir di sebelah kiri.</p>
                    </div>
                @else
                    <div class="overflow-x-auto" tabindex="0" role="region" aria-label="Tabel Riwayat Impor Forecast">
                        <table class="w-full text-left text-xs sm:text-sm text-slate-700">
                            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase tracking-wider font-semibold text-xs">
                                <tr>
                                    <th class="py-3 px-4">Waktu</th>
                                    <th class="py-3 px-4">Nama Berkas</th>
                                    <th class="py-3 px-4 text-center">Total</th>
                                    <th class="py-3 px-4 text-center">Berhasil</th>
                                    <th class="py-3 px-4 text-center">Gagal</th>
                                    <th class="py-3 px-4 text-center">Status</th>
                                    <th class="py-3 px-4">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($imports as $import)
                                    <tr class="hover:bg-slate-50/60 transition">
                                        <td class="py-3.5 px-4 font-mono text-xs text-slate-600 whitespace-nowrap">
                                            {{ $import->created_at->format('d/m/Y H:i') }}
                                            <span class="block text-xs text-slate-500 font-sans">{{ $import->user?->name ?? 'CLI / Sistem' }}</span>
                                        </td>
                                        <td class="py-3.5 px-4 font-medium text-slate-900 max-w-[180px] truncate" title="{{ $import->nama_file }}">
                                            {{ $import->nama_file }}
                                        </td>
                                        <td class="py-3.5 px-4 text-center font-mono font-medium tabular-nums text-xs">
                                            {{ number_format($import->jumlah_baris) }}
                                        </td>
                                        <td class="py-3.5 px-4 text-center font-mono text-purple-700 font-semibold tabular-nums text-xs">
                                            {{ number_format($import->jumlah_berhasil) }}
                                        </td>
                                        <td class="py-3.5 px-4 text-center font-mono {{ $import->jumlah_gagal > 0 ? 'text-rose-700 font-semibold' : 'text-slate-500' }} tabular-nums text-xs">
                                            {{ number_format($import->jumlah_gagal) }}
                                        </td>
                                        <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                            @if ($import->status === 'selesai')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800">
                                                    Selesai
                                                </span>
                                            @elseif ($import->status === 'berjalan')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-800 animate-pulse">
                                                    Berjalan
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-rose-100 text-rose-800">
                                                    Gagal
                                                </span>
                                            @endif
                                        </td>
                                        <td class="py-3.5 px-4 max-w-[200px]">
                                            @if ($import->ringkasan_galat)
                                                <details class="text-xs text-rose-700 cursor-pointer">
                                                    <summary class="font-medium hover:underline min-h-[32px] inline-flex items-center">Lihat Catatan ({{ $import->jumlah_gagal }})</summary>
                                                    <div class="mt-2 p-2 rounded bg-rose-50 border border-rose-200 font-mono text-xs whitespace-pre-wrap max-h-36 overflow-y-auto">
                                                        {{ $import->ringkasan_galat }}
                                                    </div>
                                                </details>
                                            @else
                                                <span class="text-slate-400 text-xs">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($imports->hasPages())
                        <div class="p-4 border-t border-slate-200">
                            {{ $imports->links() }}
                        </div>
                    @endif
                @endif
            </div>

        </div>
    </div>
</div>
@endsection
