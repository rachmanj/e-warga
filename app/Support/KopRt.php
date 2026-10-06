<?php

namespace App\Support;

use App\Models\Rt;

class KopRt
{
    public static function terisi(?string $nilai): bool
    {
        if ($nilai === null) {
            return false;
        }

        $teks = trim($nilai);

        if ($teks === '') {
            return false;
        }

        return ! preg_match('/^-+$/', $teks);
    }

    public static function barisWilayah(Rt $rt): string
    {
        $bagian = [];

        if (self::terisi($rt->rw)) {
            $bagian[] = 'RW '.trim((string) $rt->rw);
        }

        if (self::terisi($rt->kelurahan)) {
            $bagian[] = 'Kel. '.trim((string) $rt->kelurahan);
        }

        if (self::terisi($rt->kecamatan)) {
            $bagian[] = 'Kec. '.trim((string) $rt->kecamatan);
        }

        return implode(' · ', $bagian);
    }

    public static function barisKota(Rt $rt): ?string
    {
        if (! self::terisi($rt->kota)) {
            return null;
        }

        return trim((string) $rt->kota);
    }

    public static function labelPilihRtAktif(Rt $rt): string
    {
        $detail = [];

        if (self::terisi($rt->rw)) {
            $detail[] = 'RW '.trim((string) $rt->rw);
        }

        if (self::terisi($rt->kelurahan)) {
            $detail[] = trim((string) $rt->kelurahan);
        }

        if ($detail === []) {
            return $rt->nama;
        }

        return $rt->nama.' ('.implode(', ', $detail).')';
    }
}
