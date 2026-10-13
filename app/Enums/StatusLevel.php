<?php

namespace App\Enums;

enum StatusLevel: string
{
    case Defisit = 'defisit';
    case Seimbang = 'seimbang';
    case Waspada = 'waspada';
    case Penuh = 'penuh';
    case TanpaData = 'tanpa_data';

    /**
     * Label tampilan status dalam Bahasa Indonesia.
     */
    public function label(): string
    {
        return match ($this) {
            self::Defisit => 'Defisit',
            self::Seimbang => 'Seimbang',
            self::Waspada => 'Waspada',
            self::Penuh => 'Penuh / Surplus',
            self::TanpaData => 'Tanpa Data',
        };
    }

    /**
     * Warna Hex untuk peta atau chart.
     */
    public function warnaHex(): string
    {
        return match ($this) {
            self::Defisit => '#ef4444',    // Merah Defisit
            self::Seimbang => '#86efac',   // Hijau Muda Seimbang (PRD / rules.md section 18)
            self::Waspada => '#eab308',    // Kuning Waspada
            self::Penuh => '#b91c1c',      // Merah Gelap / Maroon Penuh
            self::TanpaData => '#94a3b8',  // Abu-abu
        };
    }

    /**
     * Kelas CSS badge Tailwind.
     */
    public function warnaBadge(): string
    {
        return match ($this) {
            self::Defisit => 'bg-rose-100 text-rose-800 border-rose-200',
            self::Seimbang => 'bg-emerald-100 text-emerald-800 border-emerald-200',
            self::Waspada => 'bg-amber-100 text-amber-800 border-amber-200',
            self::Penuh => 'bg-red-100 text-red-800 border-red-200',
            self::TanpaData => 'bg-slate-100 text-slate-600 border-slate-200',
        };
    }
}
