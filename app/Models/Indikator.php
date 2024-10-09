<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Indikator extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $table = 'indikator';

    public function antar_waktu() {
        return $this->belongsTo(AntarWaktu::class);
    }
    public function sektor() {
        return $this->belongsTo(Sektor::class);
    }
    public function opd_provinsi() {
        return $this->belongsTo(OpdProvinsi::class);
    }
    public function pivot_indikator_subjek() {
        return $this->hasMany(PivotIndikatorSubjek::class);
    }
    public function variabel() {
        return $this->hasMany(Variabel::class);
    }
    public function pivot_enumerator_indikator() {
        return $this->hasMany(PivotEnumeratorIndikator::class);
    }
}
