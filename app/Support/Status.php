<?php

namespace App\Support;

/**
 * Label dan warna status sensor, mengikuti ambang batas pada desain Figma.
 * tone: success | warning | error | info
 */
class Status
{
    public static function temperature(float $celsius): array
    {
        if ($celsius > 30) {
            return ['label' => 'Tinggi', 'tone' => 'error', 'icon' => 'warning'];
        }
        if ($celsius > 28) {
            return ['label' => 'Perlu perhatian', 'tone' => 'warning', 'icon' => 'warning'];
        }

        return ['label' => 'Normal', 'tone' => 'success', 'icon' => 'check'];
    }

    public static function humidity(int $percent): array
    {
        if ($percent < 40 || $percent > 70) {
            return ['label' => 'Perlu perhatian', 'tone' => 'warning', 'icon' => 'warning'];
        }

        return ['label' => 'Normal', 'tone' => 'success', 'icon' => 'check'];
    }

    public static function light(int $lux): array
    {
        if ($lux < 250) {
            return ['label' => 'Redup', 'tone' => 'warning', 'icon' => 'warning'];
        }
        if ($lux > 600) {
            return ['label' => 'Terlalu terang', 'tone' => 'warning', 'icon' => 'warning'];
        }

        return ['label' => 'Cukup', 'tone' => 'success', 'icon' => 'check'];
    }

    public static function air(int $aqi): array
    {
        if ($aqi <= 50) {
            return ['label' => 'Baik', 'tone' => 'success', 'icon' => 'check'];
        }
        if ($aqi <= 100) {
            return ['label' => 'Sedang', 'tone' => 'warning', 'icon' => 'warning'];
        }

        return ['label' => 'Buruk', 'tone' => 'error', 'icon' => 'warning'];
    }

    /**
     * Ubah deretan nilai menjadi titik-titik polyline untuk SVG berukuran 272x66.
     * Mengembalikan ['points' => '0,58 34,51 ...', 'x' => ..., 'y' => ...] (x,y = titik terakhir).
     */
    public static function chart(array $values, int $maxPoints = 48): array
    {
        $values = array_values(array_map('floatval', $values));
        $count = count($values);

        if ($count < 2) {
            return ['points' => '', 'x' => 272, 'y' => 33];
        }

        // Kurangi jumlah titik supaya grafik tetap ringan.
        if ($count > $maxPoints) {
            $sampled = [];
            for ($i = 0; $i < $maxPoints; $i++) {
                $sampled[] = $values[(int) round($i * ($count - 1) / ($maxPoints - 1))];
            }
            $values = $sampled;
            $count = $maxPoints;
        }

        $width = 272;
        $top = 8;
        $bottom = 58;
        $min = min($values);
        $max = max($values);

        if (($max - $min) < 1) {
            $middle = ($max + $min) / 2;
            $min = $middle - 1;
            $max = $middle + 1;
        }

        $points = [];
        $lastX = $width;
        $lastY = 33;

        foreach ($values as $i => $value) {
            $lastX = round($width * $i / ($count - 1), 1);
            $lastY = round($bottom - (($value - $min) / ($max - $min)) * ($bottom - $top), 1);
            $points[] = $lastX.','.$lastY;
        }

        return ['points' => implode(' ', $points), 'x' => $lastX, 'y' => $lastY];
    }

    /**
     * Label tren dari deretan nilai (pertama vs terakhir).
     */
    public static function trend(array $values, string $steadyLabel = 'Stabil'): string
    {
        $values = array_values($values);

        if (count($values) < 2) {
            return 'Belum ada data';
        }

        $delta = (float) end($values) - (float) $values[0];

        if ($delta <= -0.5) {
            return 'Menurun';
        }
        if ($delta >= 0.5) {
            return 'Meningkat';
        }

        return $steadyLabel;
    }
}
