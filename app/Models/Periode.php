<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Periode extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $table = 'periode';

    public function opd_provinsi()
    {
        return $this->belongsTo(OpdProvinsi::class);
    }
}
