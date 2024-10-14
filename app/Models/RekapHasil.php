<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RekapHasil extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'rekap_hasil';

    public function hasil_data() {
        return $this->hasMany(HasilData::class);
    }

    public function enumerator() {
        return $this->belongsTo(Enumerator::class);
    }

    public function periode() {
        return $this->belongsTo(Periode::class);
    }
}
