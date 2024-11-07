<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnumeratorResource extends JsonResource
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
            'email' => $this->user->email,
            'username' => $this->user->username,
            'nama' => $this->nama,
            'jabatan' => $this->jabatan,
            'enumerator_level' => $this->when(
            $this->enumerator_kabupaten->isNotEmpty() || $this->enumerator_provinsi->isNotEmpty(),
            function () {
                // Cek apakah enumerator berada di tingkat kabupaten atau provinsi
                if ($this->enumerator_kabupaten->isNotEmpty()) {
                return 'kabupaten';
                }

                if ($this->enumerator_provinsi->isNotEmpty()) {
                return 'provinsi';
                }

                return 'unknown'; // Jika tidak ditemukan, bisa diisi dengan 'unknown' atau nilai lain
            }
            ),
            'opd' => $this->when(
            $this->enumerator_kabupaten->isNotEmpty() || $this->enumerator_provinsi->isNotEmpty(),
            function () {
                if ($this->enumerator_kabupaten->isNotEmpty()) {
                $opd = $this->enumerator_kabupaten->first()->opd_kabupaten;
                } else {
                $opd = $this->enumerator_provinsi->first()->opd_provinsi;
                }
                return [
                'id' => $opd->id,
                'nama' => $opd->nama,
                ];
            }
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->created_at
        ];
    }
}
