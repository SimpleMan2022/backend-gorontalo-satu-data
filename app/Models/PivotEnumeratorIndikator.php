<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PivotEnumeratorIndikator extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $table = 'pivot_enumerator_indikator';

    public function enumerator() {
        return $this->belongsTo(Enumerator::class);
    }

    public function indikator() {
        return $this->belongsTo(Indikator::class);
    }
}
