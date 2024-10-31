<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RekapHasilResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama' => $this->nama,
            'nama_opd' => $this->periode->opd_provinsi->nama,
            'enumerator' => $this->enumerator->only('id', 'nama', 'jabatan', 'enumerator_level'),
            'periode' => $this->periode->only('id', 'nama', 'tanggal_mulai', 'tanggal_selesai'),
            'hasil_data' => $this->hasil_data->map(function ($hasil) {
                return [
                    'id' => $hasil->id,
                    'variabel' => $hasil->variabel->only('id', 'indikator_id', 'nama', 'order', 'tipe'),
                    'rekap_hasil_id' => $hasil->rekap_hasil_id,
                    'konten' => $hasil->konten,
                ];
            }),
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

        ];
    }
}
