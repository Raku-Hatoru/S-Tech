<?php

namespace App\Enums;

enum ShipmentStatus: string
{
    case Dibuat = 'dibuat';
    case DiterimaDiAsal = 'diterima_di_asal';
    case Diproses = 'diproses';
    case DalamPerjalanan = 'dalam_perjalanan';
    case TibaDiTujuan = 'tiba_di_tujuan';
    case Diterima = 'diterima';
    case Dibatalkan = 'dibatalkan';

    /**
     * Label tampilan status pengiriman (gaya pelacakan JNE).
     */
    public function label(): string
    {
        return match ($this) {
            self::Dibuat => 'Pengiriman Dibuat',
            self::DiterimaDiAsal => 'Barang Diterima di Asal',
            self::Diproses => 'Diproses di Pusat Transit',
            self::DalamPerjalanan => 'Dalam Perjalanan (In Transit)',
            self::TibaDiTujuan => 'Tiba di Tujuan',
            self::Diterima => 'Selesai / Diterima',
            self::Dibatalkan => 'Dibatalkan',
        };
    }

    /**
     * Kelas badge warna Tailwind.
     */
    public function warnaBadge(): string
    {
        return match ($this) {
            self::Dibuat => 'bg-slate-100 text-slate-700 border-slate-200',
            self::DiterimaDiAsal => 'bg-sky-100 text-sky-800 border-sky-200',
            self::Diproses => 'bg-indigo-100 text-indigo-800 border-indigo-200',
            self::DalamPerjalanan => 'bg-amber-100 text-amber-800 border-amber-200',
            self::TibaDiTujuan => 'bg-teal-100 text-teal-800 border-teal-200',
            self::Diterima => 'bg-emerald-100 text-emerald-800 border-emerald-200',
            self::Dibatalkan => 'bg-rose-100 text-rose-800 border-rose-200',
        };
    }

    /**
     * Cek apakah status adalah status akhir (tidak dapat diubah lagi).
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::Diterima, self::Dibatalkan], true);
    }
}
