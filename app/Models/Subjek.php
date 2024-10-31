<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subjek extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $table = 'subjek';

    public function pivot_indikator_subjek() {
        return $this->hasMany(PivotIndikatorSubjek::class);
    }

    public function indikators()
    {
        return $this->belongsToMany(Indikator::class, 'pivot_indikator_subjek', 'subjek_id', 'indikator_id');
    }

}
