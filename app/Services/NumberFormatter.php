<?php

namespace App\Services;

use App\Models\Setting;

class NumberFormatter
{
    /**
     * Tampilkan besaran kg atau ton secara otomatis berdasarkan ambang batas unit_switch_kg.
     *
     * Contoh:
     * - 1850    => "1.850 kg"
     * - 2000    => "2.000 kg"
     * - 2500    => "2,5 ton"
     * - 18300000=> "18.300,0 ton"
     * - null    => "—"
     */
    public static function tampilkan(?float $kg, ?float $unitSwitch = null): string
    {
        if ($kg === null) {
            return '—';
        }

        $switch = $unitSwitch ?? (float) Setting::nilai('unit_switch_kg', 2000);

        if ($kg <= $switch) {
            return number_format(round($kg), 0, ',', '.').' kg';
        }

        $ton = $kg / 1000.0;

        return number_format($ton, 1, ',', '.').' ton';
    }

    /**
     * Tampilkan selalu dalam satuan kg dengan pemisah ribuan titik.
     */
    public static function formatKg(?float $kg): string
    {
        if ($kg === null) {
            return '—';
        }

        return number_format(round($kg), 0, ',', '.').' kg';
    }

    /**
     * Tampilkan selalu dalam satuan ton dengan pemisah ribuan titik dan koma desimal.
     */
    public static function formatTon(?float $kg, int $decimals = 1): string
    {
        if ($kg === null) {
            return '—';
        }

        $ton = $kg / 1000.0;

        return number_format($ton, $decimals, ',', '.').' ton';
    }

    /**
     * Tampilkan rasio sebagai persentase format Indonesia (mis. 1.0511 => "105,11%").
     */
    public static function formatPersen(?float $rasio, int $decimals = 2): string
    {
        if ($rasio === null) {
            return '—';
        }

        $persen = $rasio * 100.0;

        return number_format($persen, $decimals, ',', '.').'%';
    }

    /**
     * Format angka murni Indonesia (titik ribuan, koma desimal).
     */
    public static function formatAngka(?float $val, int $decimals = 0): string
    {
        if ($val === null) {
            return '—';
        }

        return number_format($val, $decimals, ',', '.');
    }
}
