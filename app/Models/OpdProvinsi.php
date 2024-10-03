<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpdProvinsi extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'opd_provinsi';

    public function user_opd_provinsi() {
        return $this->hasMany(UserOpdProvinsi::class);
    }

    public function indikator() {
        return $this->hasMany(Indikator::class);
    }
}
