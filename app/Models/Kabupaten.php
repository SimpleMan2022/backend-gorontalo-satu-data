<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kabupaten extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'regencies';

    public function opd_kabupaten()
    {
        return $this->hasMany(OpdKabupaten::class);
    }

    public function kecamatan()
    {
        return $this->hasMany(Kecamatan::class, 'regency_id');
    }

    public function wilayah_enumerator()
    {
        return $this->morphMany(Enumerator::class, 'wilayah_id');
    }

    public function wilayah_operator_kabupaten()
    {
        return $this->morphMany(UserOpdKabupaten::class, 'wilayah_id');
    }
}
