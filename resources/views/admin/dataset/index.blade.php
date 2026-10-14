@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Breadcrumb & Subnav Admin --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 border-b border-slate-200 pb-5">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Dataset Historis Pangan</h1>
        </div>

        {{-- Navigasi Tab Admin --}}
        <nav class="flex flex-wrap items-center gap-1 bg-slate-100 p-1 rounded-xl border border-slate-200 text-xs font-medium" aria-label="Navigasi Panel Admin">
            <a href="{{ route('admin.pengguna.index') }}" class="min-h-[44px] px-3.5 py-2 rounded-lg text-slate-600 hover:text-slate-900 inline-flex items-center transition">
                Pengguna
            </a>
            <a href="{{ route('admin.dataset.index') }}" class="min-h-[44px] px-3.5 py-2 rounded-lg bg-white shadow-xs text-slate-900 font-semibold inline-flex items-center transition">
                Dataset Historis
            </a>
            <a href="{{ route('admin.forecast.index') }}" class="min-h-[44px] px-3.5 py-2 rounded-lg text-slate-600 hover:text-slate-900 inline-flex items-center transition">
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
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Total Baris Historis</p>
            <p class="text-2xl font-bold text-slate-900 tabular-nums">{{ number_format($totalRecords) }}</p>
            <p class="text-xs text-slate-500 mt-1">Kabupaten × komoditas × bulan</p>
        </div>
        <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-2xs">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Cakupan Wilayah</p>
            <p class="text-2xl font-bold text-slate-900 tabular-nums">{{ number_format($totalKabupaten) }}</p>
            <p class="text-xs text-slate-500 mt-1">Kabupaten/Kota aktif di dataset</p>
        </div>
        <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-2xs">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Rentang Periode</p>
            <p class="text-base font-bold text-slate-900 mt-1">
                @if ($minPeriode && $maxPeriode)
                    {{ \Carbon\Carbon::parse($minPeriode)->format('M Y') }} – {{ \Carbon\Carbon::parse($maxPeriode)->format('M Y') }}
                @else
                    <span class="text-slate-400 font-normal">Belum ada data</span>
                @endif
            </p>
            <p class="text-xs text-slate-500 mt-1">Dataset aktual bulanan</p>
        </div>
        <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-2xs">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Total Sesi Impor</p>
            <p class="text-2xl font-bold text-slate-900 tabular-nums">{{ number_format($imports->total()) }}</p>
            <p class="text-xs text-slate-500 mt-1">Riwayat unggah dataset</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
        {{-- Form Unggah CSV (Kiri) --}}
        <div class="lg:col-span-1">
            <div class="p-6 rounded-2xl bg-white border border-slate-200 shadow-2xs sticky top-28 space-y-4">
                <div class="flex items-center gap-2.5 pb-2 border-b border-slate-100">
                    <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    </div>
                    <h2 class="text-base font-bold text-slate-900">Unggah Dataset CSV</h2>
                </div>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Unggah berkas CSV dataset bulanan riil (format data historis). Nilai sel kosong akan diatur sebagai <code>NULL</code> dan <code>pasokan_masuk_kg</code> otomatis <code>0</code> bila kosong.
                </p>

                <form action="{{ route('admin.dataset.store') }}" method="POST" enctype="multipart/form-data" id="form-upload-dataset" class="space-y-4">
                    @csrf
                    <div>
                        <label for="dataset-file-input" class="block text-xs font-semibold text-slate-700 mb-2">Pilih Berkas CSV (.csv)</label>
                        <div class="border-2 border-dashed border-slate-300 hover:border-emerald-500 rounded-xl p-4 text-center transition bg-slate-50/50">
                            <input type="file" name="file" id="dataset-file-input" accept=".csv,text/csv,text/plain" required class="block w-full min-h-[44px] text-xs text-slate-500 file:mr-3 file:min-h-[44px] file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-900 file:text-white hover:file:bg-slate-800 cursor-pointer">
                        </div>
                        @error('file')
                            <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Accordion Ketentuan Kolom CSV --}}
                    <details class="group rounded-xl border border-slate-200 bg-slate-50/70 p-3.5 text-xs transition">
                        <summary class="flex items-center justify-between font-semibold text-slate-700 cursor-pointer list-none min-h-[36px] select-none hover:text-slate-900">
                            <span class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-emerald-600 transition group-open:rotate-90 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                <span>Lihat Ketentuan Kolom CSV</span>
                            </span>
                            <span class="text-xs font-normal text-slate-500 group-open:hidden">9 kolom wajib</span>
                        </summary>
                        <div class="mt-2.5 pt-2.5 border-t border-slate-200 space-y-2 text-slate-600">
                            <span class="font-semibold text-slate-800 block text-xs">Struktur Kolom Wajib CSV:</span>
                            <code class="text-slate-800 block break-all font-mono text-xs bg-white p-2 rounded border border-slate-200 select-all">kode_kabupaten, komoditas, periode, panen_kg, stok_awal_kg, pasokan_masuk_kg, supply_kg, demand_kg, curah_hujan_mm</code>
                            <p class="text-xs text-slate-500 leading-relaxed">
                                Format: UTF-8, pemisah koma (,), desimal titik (.), format periode YYYY-MM. Awalan nol kode wilayah tetap dijaga.
                            </p>
                        </div>
                    </details>

                    <button type="submit" id="btn-submit-dataset" class="w-full min-h-[44px] flex items-center justify-center gap-2 bg-slate-900 hover:bg-slate-800 text-white font-semibold py-2.5 px-4 rounded-xl transition text-xs sm:text-sm shadow-2xs">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                        <span>Mulai Impor Dataset</span>
                    </button>
                </form>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                    <span>CLI Artisan:</span>
                    <code class="bg-slate-100 px-1.5 py-0.5 rounded text-slate-700 font-mono text-xs">php artisan data:import-historis {file}</code>
                </div>
            </div>
        </div>

        {{-- Tabel Riwayat Impor (Kanan) --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
                <div class="p-5 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Riwayat Impor Dataset</h2>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-700 tabular-nums">
                        {{ $imports->total() }} Sesi
                    </span>
                </div>

                @if ($imports->isEmpty())
                    <div class="py-16 text-center text-slate-400">
                        <svg class="w-12 h-12 mx-auto mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <p class="text-sm font-semibold text-slate-600">Belum Ada Riwayat Impor</p>
                        <p class="text-xs text-slate-500 mt-1">Unggah berkas CSV pertama melalui formulir di sebelah kiri.</p>
                    </div>
                @else
                    <div class="overflow-x-auto" tabindex="0" role="region" aria-label="Tabel Riwayat Impor Dataset">
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
                                        <td class="py-3.5 px-4 text-center font-mono text-emerald-700 font-semibold tabular-nums text-xs">
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
