<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Desa extends Model
{
    use HasFactory;

    protected $table = 'villages';

    public function kecamatan()
    {
        return $this->belongsTo(Kecamatan::class, 'district_id');
    }

    public function wilayah_enumerator()
    {
        return $this->morphMany(Enumerator::class, 'wilayah_id');
    }

    public function wilayah_operator_kabupaten()
    {
        return $this->morphMany(UserOpdKabupaten::class, 'wilayah_id');
    }

    public function wilayah_rekap_hasil()
    {
        return $this->morphMany(RekapHasil::class, 'wilayah_id');
    }
}
