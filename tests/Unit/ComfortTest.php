<?php

namespace Tests\Unit;

use App\Support\Comfort;
use PHPUnit\Framework\TestCase;

class ComfortTest extends TestCase
{
    public function test_kondisi_ideal_bernilai_penuh(): void
    {
        $this->assertSame(100, Comfort::score(26, 55, 30, 400));
    }

    public function test_makin_panas_skor_tidak_naik(): void
    {
        $previous = 101;

        foreach ([26, 28, 29, 30, 32, 34, 36, 40] as $temperature) {
            $score = Comfort::score($temperature, 55, 30, 400);
            $this->assertLessThanOrEqual($previous, $score);
            $previous = $score;
        }
    }

    public function test_sensor_yang_tidak_ada_tidak_mengurangi_skor(): void
    {
        $this->assertSame(Comfort::score(26, 55, 30, 400), Comfort::score(26));
        $this->assertSame(Comfort::score(31, 55, 30, 400), Comfort::score(31, null, null, null));
    }

    public function test_kelembapan_dan_udara_buruk_menurunkan_skor_hanya_bila_ada(): void
    {
        $this->assertLessThan(Comfort::score(26), Comfort::score(26, 20));
        $this->assertLessThan(Comfort::score(26), Comfort::score(26, null, 150));
    }

    public function test_label_dan_arah_tren(): void
    {
        $this->assertSame('Nyaman', Comfort::label(80)['label']);
        $this->assertSame('Cukup nyaman', Comfort::label(79)['label']);
        $this->assertSame('Kurang nyaman', Comfort::label(59)['label']);

        $this->assertSame('naik', Comfort::direction(Comfort::change([26, 26, 26], [29, 29, 29])));
        $this->assertSame('turun', Comfort::direction(Comfort::change([29, 29, 29], [26, 26, 26])));
        $this->assertSame('stabil', Comfort::direction(Comfort::change([27, 27, 27], [27.4, 27.4, 27.4])));
        $this->assertNull(Comfort::direction(Comfort::change([27, 27], [29, 29, 29]))); // data lama kurang dari 3
    }
}
