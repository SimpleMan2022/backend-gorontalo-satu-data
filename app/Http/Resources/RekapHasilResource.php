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
            'nama_indikator' => $this->hasil_data->first()->variabel->indikator->nama,
            'enumerator' => $this->enumerator->only('id', 'nama', 'jabatan', 'enumerator_level'),
            'periode' => $this->periode->only('id', 'nama', 'tanggal_mulai', 'tanggal_selesai'),
            'variabel' => $this->hasil_data->map(function ($hasil) {
                return [
                    'id' => $hasil->variabel->id,
                    'nama' => $hasil->variabel->nama,
                    'tipe' => $hasil->variabel->tipe,
                ];
            })->unique('id')->values(),
            'hasil_data' => $this->hasil_data->groupBy('variabel_id')->map(function ($group) {
                return $group->map(function ($hasil) {
                    return [
                        'hasil_data_id' => $hasil->id,
                        'tipe' => $hasil->variabel->tipe,
                        'konten' => $hasil->konten,
                    ];
                })->values();
            })->values(),
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
