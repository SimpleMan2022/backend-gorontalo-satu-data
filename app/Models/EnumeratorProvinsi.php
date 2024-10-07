<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EnumeratorProvinsi extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'enumerator_provinsi';

    public function opd_provinsi() {
        return $this->belongsTo(OpdProvinsi::class);
    }

    public function enumerator() {
        return $this->belongsTo(Enumerator::class);
    }
}
