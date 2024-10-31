<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OlahanSpasial extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $table = 'olahan_spasial';

    public function opd_provinsi()
    {
        return $this->belongsTo(OpdProvinsi::class, 'opd_provinsi_id');
    }

    public function sektor_spasial()
    {
        return $this->belongsTo(SektorSpasial::class ,'sektor_spasial_id');
    }

    public function spasial_kabupaten()
    {
        return $this->hasMany(SpasialKabupaten::class);
    }
}
