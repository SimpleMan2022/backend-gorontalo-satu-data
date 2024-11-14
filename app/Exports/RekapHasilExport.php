<?php

namespace App\Exports;

use App\Models\RekapHasil;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class RekapHasilExport implements FromCollection, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */

    protected $rekap_hasil;

    // Terima data rekap_hasil melalui konstruktor
    public function __construct($rekap_hasil)
    {
        $this->rekap_hasil = $rekap_hasil;
    }

    public function collection()
    {
        $body = [];

        foreach ($this->rekap_hasil as $rekap) {            
            // Loop untuk mendapatkan semua 'konten' dari 'hasil_data'
            foreach ($rekap->hasil_data as $item) {
                $row['Konten ' . $item->variabel_id] = $item->konten;
            }

            $body[] = $row;
        }

        return collect($body);
    }

    public function headings(): array
    {
        $headings = [];

        foreach ($this->rekap_hasil->first()->indikator->variabel as $variabel_detail) {
            $headings[] = $variabel_detail->nama;
        }

        return $headings;
    }
}
