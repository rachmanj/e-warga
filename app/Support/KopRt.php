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

    /**
     * @return list<string>
     */
    public static function barisKop(Rt $rt): array
    {
        $baris = [];

        if (self::terisi($rt->kecamatan)) {
            $baris[] = mb_strtoupper(trim((string) $rt->kecamatan));
        }

        if (self::terisi($rt->kelurahan)) {
            $baris[] = 'KELURAHAN '.mb_strtoupper(trim((string) $rt->kelurahan));
        }

        $sufiksRt = self::sufiksNamaRtUntukKop($rt->nama);
        if ($sufiksRt !== null) {
            $baris[] = 'RUKUN TETANGGA '.$sufiksRt;
        }

        $alamatKop = self::barisAlamatSekretariat($rt);
        if ($alamatKop !== null) {
            $baris[] = $alamatKop;
        }

        if (self::terisi($rt->no_hp)) {
            $baris[] = 'No. Hp. '.trim((string) $rt->no_hp);
        }

        return $baris;
    }

    public static function sufiksNamaRtUntukKop(string $nama): ?string
    {
        $nama = trim($nama);
        if ($nama === '') {
            return null;
        }

        if (preg_match('/\d+/', $nama, $matches)) {
            return $matches[0];
        }

        return $nama;
    }

    public static function barisAlamatSekretariat(Rt $rt): ?string
    {
        $bagian = [];

        if (self::terisi($rt->alamat_sekretariat)) {
            $bagian[] = trim((string) $rt->alamat_sekretariat);
        }

        if (self::terisi($rt->kode_pos)) {
            $bagian[] = trim((string) $rt->kode_pos);
        }

        if ($bagian === []) {
            return null;
        }

        return implode(' ', $bagian);
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
