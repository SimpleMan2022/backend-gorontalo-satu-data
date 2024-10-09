<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enumerator extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'enumerator';

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function enumerator_kabupaten() {
        return $this->hasMany(EnumeratorKabupaten::class);
    }

    public function enumerator_provinsi() {
        return $this->hasMany(EnumeratorProvinsi::class);
    }

    public function opd_kabupaten() {
        return $this->belongsTo(OpdKabupaten::class);
    }

    public function opd_provinsi() {
        return $this->belongsTo(OpdProvinsi::class);
    }

    public function pivot_enumerator_indikator() {
        return $this->hasMany(PivotEnumeratorIndikator::class);
    }
}
