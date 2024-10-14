<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HasilData extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'hasil_data';

    public function variabel() {
        return $this->belongsTo(Variabel::class);
    }

    public function rekap_hasil() {
        return $this->belongsTo(RekapHasil::class);
    }
}
