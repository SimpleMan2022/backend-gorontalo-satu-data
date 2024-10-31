<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HasilSpasial extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $table = 'hasil_spasial';

    public function opd_provinsi()
    {
        return $this->belongsTo(OpdProvinsi::class);
    }

    public function sektor_spasial()
    {
        return $this->belongsTo(SektorSpasial::class);
    }
}
