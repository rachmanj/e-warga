<?php

namespace App\Support;

class FormatUang
{
    public static function penuh(mixed $nilai): string
    {
        $angka = (float) $nilai;

        return 'Rp '.number_format($angka, 0, ',', '.');
    }

    public static function ringkas(mixed $nilai): string
    {
        $angka = abs((float) $nilai);
        $negatif = (float) $nilai < 0;

        if ($angka >= 1_000_000) {
            $jt = $angka / 1_000_000;
            $teks = 'Rp '.number_format($jt, 1, ',', '.').' jt';
        } elseif ($angka >= 1_000) {
            $rb = $angka / 1_000;
            $teks = 'Rp '.number_format($rb, 0, ',', '.').' rb';
        } else {
            $teks = self::penuh($angka);
        }

        return $negatif ? '-'.$teks : $teks;
    }
}
