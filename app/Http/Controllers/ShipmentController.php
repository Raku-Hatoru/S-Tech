<?php

namespace App\Http\Controllers;

use App\Enums\ShipmentStatus;
use App\Enums\StatusLevel;
use App\Models\Kabupaten;
use App\Models\Komoditas;
use App\Models\Shipment;
use App\Models\ShipmentEvent;
use App\Services\NumberFormatter;
use App\Services\StatusService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ShipmentController extends Controller
{
    /**
     * Dashboard Pengiriman & Pelacakan Resi (PRD F-08, Increment 6).
     * Distributor hanya melihat pengiriman miliknya; Admin dapat melihat semua.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        // 1. Query dasar dengan scoping pemilik
        $query = Shipment::with(['komoditas', 'asal.provinsi', 'tujuan.provinsi', 'user'])
            ->latest();

        if (! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        // 2. Filter Pencarian Resi
        if ($search = $request->input('q')) {
            $search = trim($search);
            $query->where(function ($q) use ($search) {
                $q->where('no_resi', 'like', "%{$search}%")
                    ->orWhereHas('tujuan', fn ($kt) => $kt->where('nama', 'like', "%{$search}%"))
                    ->orWhereHas('asal', fn ($ka) => $ka->where('nama', 'like', "%{$search}%"));
            });
        }

        // 3. Filter Status
        if ($statusFilter = $request->input('status')) {
            $query->where('status', $statusFilter);
        }

        // 4. Filter Komoditas
        if ($komoditasFilter = $request->input('komoditas')) {
            $query->whereHas('komoditas', fn ($k) => $k->where('kode', $komoditasFilter));
        }

        // 5. Statistik KPI Ringkasan
        $baseStatsQuery = Shipment::query();
        if (! $user->isAdmin()) {
            $baseStatsQuery->where('user_id', $user->id);
        }

        $totalPengiriman = (clone $baseStatsQuery)->count();
        $dalamPerjalanan = (clone $baseStatsQuery)
            ->whereIn('status', [
                ShipmentStatus::DiterimaDiAsal->value,
                ShipmentStatus::Diproses->value,
                ShipmentStatus::DalamPerjalanan->value,
            ])
            ->count();
        $tibaTujuan = (clone $baseStatsQuery)
            ->whereIn('status', [
                ShipmentStatus::TibaDiTujuan->value,
                ShipmentStatus::Diterima->value,
            ])
            ->count();
        $totalMuatanKg = (float) (clone $baseStatsQuery)
            ->where('status', '<>', ShipmentStatus::Dibatalkan->value)
            ->sum('jumlah_kg');

        $shipments = $query->paginate(10)->withQueryString();
        $komoditasList = Komoditas::orderBy('nama')->get(['id', 'kode', 'nama']);

        return view('pengiriman.index', [
            'shipments' => $shipments,
            'komoditasList' => $komoditasList,
            'kpi' => [
                'total' => $totalPengiriman,
                'dalam_perjalanan' => $dalamPerjalanan,
                'tiba_tujuan' => $tibaTujuan,
                'total_muatan_formatted' => NumberFormatter::formatTon($totalMuatanKg, 1),
            ],
            'filters' => [
                'q' => $search ?? '',
                'status' => $statusFilter ?? '',
                'komoditas' => $komoditasFilter ?? '',
            ],
        ]);
    }

    /**
     * Form Buat Pengiriman Baru dengan Telemetri & Banner Early-Warning (PRD F-07).
     * Sesuai rancangan visual design/Portal Distributor & Tracking Resi JNE.png.
     */
    public function create(Request $request, StatusService $statusService): View
    {
        Gate::authorize('create', Shipment::class);

        $komoditasList = Komoditas::orderBy('nama')->get();
        $kabupatenList = Kabupaten::with('provinsi')->orderBy('nama')->get();

        // Parameter pra-pilihan dari peta heatmap atau link "Buat DO"
        $preselectTujuanKode = $request->query('tujuan');
        $preselectKomoditasKode = $request->query('komoditas', 'beras');

        $kabupatenTujuanPreselect = $preselectTujuanKode
            ? Kabupaten::with('provinsi')->where('kode', $preselectTujuanKode)->first()
            : null;

        // Default Asal: Kabupaten akun (jika ada) atau Kab. Cilacap (3301 - Jawa Tengah) sebagai showcase
        $defaultAsal = Kabupaten::where('kode', '3301')->first()
            ?? $kabupatenList->first();

        // Default Tujuan: Banyumas (3302) jika tidak ada preselect
        $defaultTujuan = $kabupatenTujuanPreselect
            ?? Kabupaten::where('kode', '3302')->first()
            ?? $kabupatenList->get(1);

        // Rekomendasi Resi Otomatis / Acak
        $suggestedResi = 'RESI-'.date('Ymd').'-'.strtoupper(Str::random(4));
        if (Shipment::where('no_resi', 'RESI-CONTOH-0001')->doesntExist()) {
            $suggestedResi = 'RESI-CONTOH-0001';
        }

        // Cek status wilayah tujuan awal untuk telemetri kanan
        $activeKomoditas = Komoditas::where('kode', $preselectKomoditasKode)->first() ?? $komoditasList->first();
        $periodeDate = Carbon::now()->startOfMonth()->format('Y-m-01');

        $statusWilayahTujuan = null;
        if ($defaultTujuan && $activeKomoditas) {
            $statusWilayahTujuan = $statusService->cekWilayah($defaultTujuan->id, $activeKomoditas->id, $periodeDate);
        }

        return view('pengiriman.create', [
            'komoditasList' => $komoditasList,
            'kabupatenList' => $kabupatenList,
            'defaultAsal' => $defaultAsal,
            'defaultTujuan' => $defaultTujuan,
            'activeKomoditas' => $activeKomoditas,
            'suggestedResi' => $suggestedResi,
            'statusWilayahTujuan' => $statusWilayahTujuan,
        ]);
    }

    /**
     * Simpan Pengiriman Baru (PRD F-07, Sequence UML 9.3).
     * Server menghitung ulang level_peringatan dan rasio_tujuan secara independen.
     */
    public function store(Request $request, StatusService $statusService): RedirectResponse
    {
        Gate::authorize('create', Shipment::class);

        $validated = $request->validate([
            'no_resi' => ['required', 'string', 'max:60', 'unique:shipments,no_resi'],
            'komoditas_id' => ['required', 'exists:komoditas,id'],
            'jumlah_kg' => ['required', 'numeric', 'min:1', 'max:100000000'],
            'asal_kabupaten_id' => ['required', 'exists:kabupaten,id', 'different:tujuan_kabupaten_id'],
            'tujuan_kabupaten_id' => ['required', 'exists:kabupaten,id'],
            'tanggal_kirim' => ['required', 'date'],
            'estimasi_tiba' => ['nullable', 'date', 'after_or_equal:tanggal_kirim'],
            'catatan' => ['nullable', 'string', 'max:1000'],
            'konfirmasi_peringatan' => ['nullable', 'boolean'],
        ], [
            'no_resi.unique' => 'Nomor resi ini sudah terdaftar. Silakan gunakan nomor resi lain.',
            'asal_kabupaten_id.different' => 'Kabupaten asal dan tujuan tidak boleh sama.',
            'jumlah_kg.min' => 'Jumlah muatan minimal 1 kg.',
            'estimasi_tiba.after_or_equal' => 'Estimasi tiba tidak boleh lebih awal dari tanggal kirim.',
        ]);

        $periodeDate = Carbon::parse($validated['tanggal_kirim'])->format('Y-m-01');

        // Evaluasi status wilayah tujuan secara independen di server
        $statusTujuan = $statusService->cekWilayah(
            (int) $validated['tujuan_kabupaten_id'],
            (int) $validated['komoditas_id'],
            $periodeDate
        );

        $levelPeringatan = $statusTujuan?->status ?? StatusLevel::TanpaData;
        $rasioTujuan = $statusTujuan?->rasio;

        $shipment = DB::transaction(function () use ($request, $validated, $levelPeringatan, $rasioTujuan, $statusService, $periodeDate) {
            $created = Shipment::create([
                'user_id' => $request->user()->id,
                'no_resi' => strtoupper(trim($validated['no_resi'])),
                'komoditas_id' => $validated['komoditas_id'],
                'jumlah_kg' => $validated['jumlah_kg'],
                'asal_kabupaten_id' => $validated['asal_kabupaten_id'],
                'tujuan_kabupaten_id' => $validated['tujuan_kabupaten_id'],
                'tanggal_kirim' => $validated['tanggal_kirim'],
                'estimasi_tiba' => $validated['estimasi_tiba'] ?? null,
                'status' => ShipmentStatus::Dibuat,
                'level_peringatan' => $levelPeringatan,
                'rasio_tujuan' => $rasioTujuan,
                'konfirmasi_peringatan' => (bool) $request->input('konfirmasi_peringatan', false),
                'catatan' => $validated['catatan'] ?? null,
            ]);

            // Buat entri riwayat perjalanan pertama
            $asal = Kabupaten::find($created->asal_kabupaten_id);
            ShipmentEvent::create([
                'shipment_id' => $created->id,
                'status' => ShipmentStatus::Dibuat->value,
                'lokasi' => $asal?->nama ?? 'Titik Asal Dispatch',
                'keterangan' => 'Pengiriman dispatch berhasil didaftarkan di sistem logistik ketahanan pangan.',
                'waktu_kejadian' => now(),
                'dicatat_oleh' => $request->user()->id,
            ]);

            // Hitung ulang status wilayah asal dan tujuan (UML Sequence 9.3)
            $statusService->recalc($created->asal_kabupaten_id, $created->komoditas_id, $periodeDate);
            $statusService->recalc($created->tujuan_kabupaten_id, $created->komoditas_id, $periodeDate);

            return $created;
        });

        return redirect()->route('pengiriman.show', $shipment->id)
            ->with('status', "Pengiriman dengan nomor resi '{$shipment->no_resi}' berhasil didaftarkan.");
    }

    /**
     * Detail Pelacakan Pengiriman Gaya JNE & Riwayat Perjalanan (PRD F-08).
     */
    public function show(int $id): View
    {
        $shipment = Shipment::with([
            'komoditas',
            'asal.provinsi',
            'tujuan.provinsi',
            'user.role',
            'events.dicatatOleh',
        ])->findOrFail($id);

        Gate::authorize('view', $shipment);

        $statuses = ShipmentStatus::cases();

        return view('pengiriman.show', [
            'shipment' => $shipment,
            'statuses' => $statuses,
        ]);
    }

    /**
     * Tambah Event / Update Status Riwayat Pengiriman (UML Sequence 9.4).
     */
    public function tambahEvent(int $id, Request $request, StatusService $statusService): RedirectResponse
    {
        $shipment = Shipment::findOrFail($id);

        Gate::authorize('update', $shipment);

        $validated = $request->validate([
            'status' => ['required', Rule::enum(ShipmentStatus::class)],
            'lokasi' => ['required', 'string', 'max:150'],
            'keterangan' => ['nullable', 'string', 'max:500'],
            'waktu_kejadian' => ['nullable', 'date'],
        ]);

        $newStatus = ShipmentStatus::from($validated['status']);
        $waktu = ! empty($validated['waktu_kejadian']) ? Carbon::parse($validated['waktu_kejadian']) : now();
        $keterangan = ! empty($validated['keterangan']) ? $validated['keterangan'] : $newStatus->label();

        DB::transaction(function () use ($shipment, $newStatus, $validated, $waktu, $keterangan, $request, $statusService) {
            // Update status utama shipment
            $shipment->update(['status' => $newStatus]);

            // Catat checkpoint riwayat perjalanan baru
            ShipmentEvent::create([
                'shipment_id' => $shipment->id,
                'status' => $newStatus->value,
                'lokasi' => $validated['lokasi'],
                'keterangan' => $keterangan,
                'waktu_kejadian' => $waktu,
                'dicatat_oleh' => $request->user()->id,
            ]);

            // Jika status dibatalkan, panggil recalc agar pasokan asal dan tujuan dikoreksi
            if ($newStatus === ShipmentStatus::Dibatalkan) {
                $periodeDate = Carbon::parse($shipment->tanggal_kirim)->format('Y-m-01');
                $statusService->recalc($shipment->asal_kabupaten_id, $shipment->komoditas_id, $periodeDate);
                $statusService->recalc($shipment->tujuan_kabupaten_id, $shipment->komoditas_id, $periodeDate);
            }
        });

        return redirect()->route('pengiriman.show', $shipment->id)
            ->with('status', "Status pengiriman berhasil diperbarui ke: '{$newStatus->label()}'.");
    }
}
