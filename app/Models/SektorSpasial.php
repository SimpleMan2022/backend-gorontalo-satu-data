<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SektorSpasial extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $table = 'sektor_spasial';

    public function sektor() {
        return $this->belongsTo(Sektor::class);
    }

    public function olahan_spasial() {
        return $this->hasMany(OlahanSpasial::class);
    }
}
