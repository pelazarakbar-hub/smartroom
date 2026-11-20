<?php

namespace App\Support;

/**
 * Perhitungan skor kenyamanan ruangan (0-100) dan tren dari data sensor.
 * Murni hitungan angka, tanpa database, supaya mudah diuji.
 */
class Comfort
{
    /** Rentang nyaman: suhu 22-28 °C, kelembapan 40-70 %, kualitas udara <= 50, cahaya 250-600 lx. */
    public static function score(float $temperature, int $humidity, int $airQuality, int $light): int
    {
        $penalty = 0.0;

        if ($temperature > 28) {
            $penalty += min(45, ($temperature - 28) * 10);
        } elseif ($temperature < 22) {
            $penalty += min(25, (22 - $temperature) * 5);
        }

        if ($humidity < 40) {
            $penalty += min(20, (40 - $humidity) * 0.8);
        } elseif ($humidity > 70) {
            $penalty += min(20, ($humidity - 70) * 0.8);
        }

        if ($airQuality > 50) {
            $penalty += min(25, ($airQuality - 50) * 0.3);
        }

        if ($light < 250) {
            $penalty += min(10, (250 - $light) / 25);
        } elseif ($light > 600) {
            $penalty += min(10, ($light - 600) / 60);
        }

        return (int) round(max(0, min(100, 100 - $penalty)));
    }

    /** @return array{label: string, tone: string} */
    public static function label(int $score): array
    {
        if ($score >= 80) {
            return ['label' => 'Nyaman', 'tone' => 'success'];
        }
        if ($score >= 60) {
            return ['label' => 'Cukup nyaman', 'tone' => 'warning'];
        }

        return ['label' => 'Kurang nyaman', 'tone' => 'error'];
    }

    /**
     * Selisih rata-rata nilai terbaru terhadap nilai sebelumnya.
     * Null bila salah satu kelompok punya kurang dari $minimum data (belum cukup untuk menilai tren).
     *
     * @param  array<int, float|int|string>  $older
     * @param  array<int, float|int|string>  $newer
     */
    public static function change(array $older, array $newer, int $minimum = 3): ?float
    {
        if (count($older) < $minimum || count($newer) < $minimum) {
            return null;
        }

        return round(array_sum($newer) / count($newer) - array_sum($older) / count($older), 1);
    }

    /** 'naik' | 'turun' | 'stabil' | null (belum cukup data). Ambang 1 °C. */
    public static function direction(?float $change): ?string
    {
        if ($change === null) {
            return null;
        }
        if ($change >= 1.0) {
            return 'naik';
        }
        if ($change <= -1.0) {
            return 'turun';
        }

        return 'stabil';
    }
}
