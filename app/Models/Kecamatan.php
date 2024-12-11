<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kecamatan extends Model
{
    use HasFactory;

    protected $table = 'districts';

    public function kabupaten()
    {
        return $this->belongsTo(Kabupaten::class, 'regency_id');
    }

    public function desa()
    {
        return $this->hasMany(Desa::class, 'district_id');
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
