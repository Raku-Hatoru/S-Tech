@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Breadcrumb & Header Navigasi --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-5">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('panen.index') }}" class="hover:text-emerald-700 transition">Portal BUMDes</a>
                <span>/</span>
                <span class="text-slate-800 font-medium">Jadwal Tanam</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Rekomendasi Jadwal Tanam Komoditas</h1>
            <p class="text-xs text-slate-500 mt-0.5">Panduan koordinasi pola tanam BUMDes bersama Gapoktan untuk stabilitas pasokan pangan daerah.</p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('panen.index', ['kabupaten_id' => $kabupaten->id]) }}" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-bold transition shadow-2xs">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Kembali ke Input Panen
            </a>
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-amber-100 text-amber-900 border border-amber-300 text-xs font-extrabold uppercase tracking-wide">
                SIMULASI / DATA CONTOH
            </span>
        </div>
    </div>

    {{-- Mode Admin: Pemilih Kabupaten --}}
    @if (auth()->user()->role?->kode === 'admin')
        <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs">
            <span class="text-amber-900">
                <strong>Mode Admin:</strong> Pilih wilayah kabupaten untuk melihat simulasi rekomendasi jadwal tanam:
            </span>
            <form method="GET" action="{{ route('jadwal-tanam.index') }}">
                <select name="kabupaten_id" onchange="this.form.submit()" class="px-3 py-1.5 rounded-xl border border-amber-300 bg-white text-xs font-semibold text-slate-800">
                    @foreach ($semuaKabupaten as $kab)
                        <option value="{{ $kab->id }}" {{ $kab->id == $kabupaten->id ? 'selected' : '' }}>
                            {{ $kab->nama }} ({{ $kab->kode }})
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
    @endif

    {{-- Banner Utama: Label Wajib SIMULASI / DATA CONTOH (Sesuai Mockup Design) --}}
    <div class="bg-blue-50/70 border-2 border-blue-200 rounded-2xl p-6 shadow-xs flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-2xl bg-blue-100 text-blue-700 flex items-center justify-center shrink-0 border border-blue-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded text-[10px] font-black bg-blue-200 text-blue-900 uppercase tracking-wider">
                        SIMULASI / DATA CONTOH
                    </span>
                    <h2 class="text-base sm:text-lg font-extrabold text-blue-950 tracking-tight">
                        [SIMULASI / DATA CONTOH] Panduan Jadwal Tanam untuk Disampaikan kepada Petani agar Pasokan Bulanan Seimbang.
                    </h2>
                </div>
                <p class="text-xs text-blue-800 leading-relaxed max-w-3xl">
                    BUMDes meneruskan rekomendasi ini ke kelompok tani (Gapoktan) {{ $kabupaten->nama }} guna mencegah lonjakan panen berlebih (spoilage shock) di bulan yang sama serta menjaga kestabilan harga jual gabah di tingkat petani.
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2 shrink-0 self-start lg:self-auto">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white border border-blue-200 text-blue-900 text-xs font-bold shadow-2xs">
                <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Data Rekomendasi Algoritmik
            </span>
        </div>
    </div>

    {{-- Konten Utama: 2 Kolom (Tabel Jadwal Tanam Kiri + Sidebar BMKG Kanan) --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        {{-- Kolom Kiri: Tabel Kalender Rekomendasi Tanam (8 Kolom) --}}
        <div class="lg:col-span-8 bg-white rounded-2xl border border-slate-200 p-6 shadow-xs space-y-6">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Kalender Rotasi Tanam Binaan Wilayah {{ $kabupaten->nama }}</h3>
                    <p class="text-xs text-slate-500">Estimasi jadwal semai, perkiraan masa panen, serta rekomendasi agronomi musim 2026.</p>
                </div>
                <span class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-200 text-[11px] font-bold">
                    3 Komoditas Terjadwal
                </span>
            </div>

            {{-- Tabel Rekomendasi Tanam --}}
            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="w-full text-xs text-left border-collapse">
                    <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider font-bold border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3">KOMODITAS</th>
                            <th class="px-4 py-3 text-center">WAKTU TANAM DISARANKAN</th>
                            <th class="px-4 py-3 text-center">PERKIRAAN BULAN PANEN</th>
                            <th class="px-4 py-3 text-right">PROYEKSI KEBUTUHAN PASAR</th>
                            <th class="px-4 py-3 text-center">CURAH HUJAN RATA-RATA</th>
                            <th class="px-4 py-3">CATATAN AGRONOMI</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        @foreach ($recommendationsList as $rec)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="font-bold text-slate-900 text-sm">{{ $rec->komoditas_label }}</div>
                                    <div class="text-[11px] text-slate-500">{{ $rec->varietas }}</div>
                                </td>
                                <td class="px-4 py-4 text-center whitespace-nowrap">
                                    <span class="px-2.5 py-1 rounded-lg text-xs font-extrabold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                        {{ $rec->waktu_tanam }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-center whitespace-nowrap font-bold text-slate-800">
                                    {{ $rec->perkiraan_panen }}
                                </td>
                                <td class="px-4 py-4 text-right whitespace-nowrap font-bold text-slate-900">
                                    {{ $rec->proyeksi_kebutuhan }}
                                </td>
                                <td class="px-4 py-4 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-blue-50 text-blue-800 border border-blue-200">
                                        <svg class="w-3 h-3 text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1.323l3.954 1.582 1.599-.8a1 1 0 01.894 1.79l-1.233.616 1.738 5.42a1 1 0 01-.285 1.05A3.989 3.989 0 0115 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.715-5.349L11 6.477V16h2a1 1 0 110 2H7a1 1 0 110-2h2V6.477L6.237 7.582l1.715 5.349a1 1 0 01-.285 1.05A3.989 3.989 0 015 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.738-5.42-1.233-.617a1 1 0 01.894-1.789l1.599.799L9 4.323V3a1 1 0 011-1z" clip-rule="evenodd"/></svg>
                                        {{ $rec->curah_hujan }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-slate-600 text-xs max-w-xs leading-relaxed">
                                    {{ $rec->catatan_agronomi }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Saluran Komunikasi Edaran ke Petani / WhatsApp Button --}}
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-900">Kanal Edaran: Siaran WhatsApp Resmi Gapoktan & Papan Pengumuman Balai Desa</p>
                        <p class="text-[11px] text-slate-500">Kirim format terstruktur jadwal tanam langsung ke kontak 12 ketua kelompok tani binaan.</p>
                    </div>
                </div>

                <a href="{{ $waLink }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs shadow-sm transition whitespace-nowrap">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                    Teruskan Panduan ke Gapoktan
                </a>
            </div>
        </div>

        {{-- Kolom Kanan: Model BMKG & Koordinasi Lapangan (4 Kolom) --}}
        <div class="lg:col-span-4 space-y-6">

            {{-- Card Model Dinamika Pasokan BMKG --}}
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
                <div class="p-5 border-b border-slate-100 bg-slate-50/50">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                        <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Model Dinamika Pasokan BMKG • 2026</h4>
                    </div>
                    <p class="text-xs text-slate-600 leading-relaxed mt-2">
                        Penyesuaian waktu tanam MT-2 dirancang untuk mereduksi risiko oversupply komoditas beras pada Q3 2026, yang berpotensi memicu depresiasi harga gabah di tingkat petani di bawah HPP (Harga Pembelian Pemerintah).
                    </p>
                </div>

                {{-- Foto Lahan Binaan BUMDes --}}
                <div class="relative h-40 bg-slate-800">
                    <img src="https://images.unsplash.com/photo-1500382017468-9049fed747ef?q=80&w=800&auto=format&fit=crop"
                         alt="Lahan Binaan BUMDes {{ $kabupaten->nama }}"
                         class="w-full h-full object-cover opacity-80">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-transparent to-transparent flex items-end p-3.5">
                        <span class="px-2.5 py-1 rounded-lg bg-emerald-900/90 text-white text-[10px] font-bold backdrop-blur-xs border border-emerald-500/30">
                            Lahan Binaan BUMDes {{ $kabupaten->nama }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Card Siklus Koordinasi Lapangan 3 Langkah --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-4">
                <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider border-b border-slate-100 pb-2">
                    Siklus Koordinasi Lapangan:
                </h4>
                <div class="space-y-3 text-xs">
                    <div class="flex items-start gap-3">
                        <div class="w-6 h-6 rounded-full bg-slate-900 text-white flex items-center justify-center font-bold text-[11px] shrink-0">
                            1
                        </div>
                        <p class="text-slate-700 font-medium pt-0.5">BUMDes input sisa stok riil lumbung (F-09)</p>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="w-6 h-6 rounded-full bg-emerald-700 text-white flex items-center justify-center font-bold text-[11px] shrink-0">
                            2
                        </div>
                        <p class="text-slate-700 font-medium pt-0.5">AI evaluasi ambang kapasitas penyangga pangan</p>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="w-6 h-6 rounded-full bg-blue-700 text-white flex items-center justify-center font-bold text-[11px] shrink-0">
                            3
                        </div>
                        <p class="text-slate-700 font-medium pt-0.5">Jadwal tanam dikirimkan ke Gapoktan via edaran resmi</p>
                    </div>
                </div>
            </div>

            {{-- Card Sinkronisasi Jadwal BMKG --}}
            <div class="p-4 rounded-2xl bg-blue-50/60 border border-blue-200 text-xs text-blue-900 space-y-2">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 00-9.78 2.096A4.001 4.001 0 003 15z"/></svg>
                    <h5 class="font-bold text-blue-950">Sinkronisasi Jadwal BMKG</h5>
                </div>
                <p class="text-[11px] text-blue-800 leading-relaxed">
                    Data iklim stasiun klimatologi terintegrasi otomatis hingga Dasarian III April.
                </p>
                <div class="pt-1 flex items-center justify-between text-[11px] font-bold text-blue-900 border-t border-blue-200">
                    <span>Status Prakiraan:</span>
                    <span class="text-emerald-700">Normal Basah</span>
                </div>
            </div>

        </div>
    </div>

</div>
@endsection
