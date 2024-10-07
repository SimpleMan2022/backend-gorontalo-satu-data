<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArtikelKawasan extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $table = 'artikel_kawasan';

    public function opd_provinsi()
    {
        return $this->belongsTo(OpdProvinsi::class);
    }
}
