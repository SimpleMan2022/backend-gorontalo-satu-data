<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AntarWaktu extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $table = 'antar_waktu';

    public function indikator() {
        return $this->hasMany(Indikator::class);
    }
}
