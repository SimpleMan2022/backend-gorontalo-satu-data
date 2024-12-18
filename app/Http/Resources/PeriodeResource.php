<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PeriodeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array       
    {
        $now = date('Y-m-d');
        
        $isAktif = (
            $this->tanggal_mulai <= $now && 
            $this->tanggal_selesai >= $now
        );
        return [
            'id' => $this->id,
            'nama' => $this->nama,
            'tanggal_mulai' => $this->tanggal_mulai,
            'tanggal_selesai' => $this->tanggal_selesai,
            'opd_provinsi' => $this->opd_provinsi->only('id','nama', 'lokasi'),
            'is_aktif' => $isAktif,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
