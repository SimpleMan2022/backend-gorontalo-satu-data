<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sektor extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $table = 'sektor';

    public function indikator()
    {
        return $this->hasMany(Indikator::class);
    }

    public function pivot_opd_sektor()
    {
        return $this->hasMany(PivotOpdSektor::class);
    }

    public function sektor_spasial()
    {
        return $this->hasMany(SektorSpasial::class);
    }
}
