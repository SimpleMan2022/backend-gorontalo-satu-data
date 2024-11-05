<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpdKabupaten extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'opd_kabupaten';

    public function user_opd_kabupaten() {
        return $this->hasMany(UserOpdKabupaten::class);
    }

    public function opd_provinsi() {
        return $this->belongsTo(OpdProvinsi::class);
    }

    public function kabupaten() {
        return $this->belongsTo(Kabupaten::class);
    }
}
