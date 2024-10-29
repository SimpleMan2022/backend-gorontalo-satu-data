<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpasialKabupaten extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $table = 'spasial_kabupaten';

    public function olahan_spasial() {
        return $this->belongsTo(OlahanSpasial::class,'olahan_spasial_id');
    }

    public function kabupaten(){
        return $this->belongsTo(Kabupaten::class,'kabupaten_id');
    }
}
