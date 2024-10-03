<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PivotIndikatorSubjek extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $table = 'pivot_indikator_subjek';

    public function indikator() {
        return $this->belongsTo(Indikator::class);
    }

    public function subjek() {
        return $this->belongsTo(Subjek::class);
    }
}
