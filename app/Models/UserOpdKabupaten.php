<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserOpdKabupaten extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'user_opd_kabupaten';

    public function opd_kabupaten()
    {
        return $this->belongsTo(OpdKabupaten::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function wilayah()
    {
        return $this->morphTo(null, 'tipe_wilayah', 'wilayah_id');
    }

    // public function kecamatan()
    // {
    //     if ($this->tipe_wilayah === Desa::class) {
    //         return $this->wilayah->kecamatan; // Ambil kabupaten dari kecamatan
    //     }

    //     if ($this->tipe_wilayah === Kecamatan::class) {
    //         return $this->wilayah; // Sudah langsung kabupaten
    //     }

    //     return null; // Jika tipe lainnya
    // }
    

    public function opd_provinsi()
    {
        return $this->belongsTo(OpdProvinsi::class);
    }
}
