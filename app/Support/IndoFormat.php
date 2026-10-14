<?php

namespace App\Support;

class IndoFormat
{
    /**
     * Format angka murni standar Indonesia (pemisah ribuan titik, desimal koma).
     */
    public static function number(float|int|string|null $num, int $decimals = 0): string
    {
        if ($num === null || $num === '' || ! is_numeric($num)) {
            return '—';
        }

        return number_format((float) $num, $decimals, ',', '.');
    }

    /**
     * Format persentase standar Indonesia (desimal koma, diakhiri simbol %).
     *
     * Contoh:
     * - 82.4                  => "82,4%"
     * - 0.824 (isRatio=true)  => "82,4%"
     * - 3.8 (showSign=true)   => "+3,8%"
     * - -14.2                 => "-14,2%"
     * - "105.1%"              => "105,1%"
     */
    public static function percent(float|int|string|null $val, int $decimals = 1, bool $isRatio = false, bool $showSign = false): string
    {
        if ($val === null || $val === '' || $val === '—') {
            return '—';
        }

        if (is_string($val) && str_contains($val, '%')) {
            return str_replace('.', ',', $val);
        }

        if (! is_numeric($val)) {
            return '—';
        }

        $num = (float) $val;
        if ($isRatio) {
            $num *= 100.0;
        }

        $formatted = number_format(abs($num), $decimals, ',', '.').'%';

        if ($showSign) {
            return ($num > 0 ? '+' : ($num < 0 ? '-' : '')).$formatted;
        }

        return ($num < 0 ? '-' : '').$formatted;
    }

    /**
     * Konversi besaran kg ke ton dengan pemisah ribuan titik dan koma desimal.
     *
     * Contoh:
     * - 18500000 => "18.500,0 ton"
     * - 900000 (showSign=true) => "+900,0 ton"
     */
    public static function ton(float|int|string|null $kg, int $decimals = 1, bool $showSign = false): string
    {
        if ($kg === null || $kg === '' || ! is_numeric($kg)) {
            return '—';
        }

        $tonVal = (float) $kg / 1000.0;
        $str = number_format(abs($tonVal), $decimals, ',', '.').' ton';

        if ($showSign) {
            return ($tonVal > 0 ? '+' : ($tonVal < 0 ? '-' : '')).$str;
        }

        return ($tonVal < 0 ? '-' : '').$str;
    }

    /**
     * Tampilkan dalam satuan kg dengan pemisah ribuan titik.
     *
     * Contoh:
     * - 18500000 => "18.500.000 kg"
     */
    public static function kg(float|int|string|null $kg): string
    {
        if ($kg === null || $kg === '' || ! is_numeric($kg)) {
            return '—';
        }

        return number_format(round((float) $kg), 0, ',', '.').' kg';
    }

    /**
     * Tampilkan ringkas: <= switch kg tetap kg, > switch kg menjadi ton.
     */
    public static function ringkas(float|int|string|null $kg, float $switch = 2000.0): string
    {
        if ($kg === null || $kg === '' || ! is_numeric($kg)) {
            return '—';
        }

        $val = (float) $kg;
        if ($val <= $switch) {
            return self::kg($val);
        }

        return self::ton($val, 1);
    }
}
