<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PivotOpdSektor extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $table = 'pivot_opd_sektor';

    public function opd_provinsi() {
        return $this->belongsTo(OpdProvinsi::class);
    }

    public function sektor() {
        return $this->belongsTo(Sektor::class);
    }
}
