<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Variabel extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $table = 'variabel';

    public function indikator() {
        return $this->belongsTo(Indikator::class);
    }

    public function pivot_indikator_subjek() {
        return $this->hasMany(PivotIndikatorSubjek::class);
    }
}
