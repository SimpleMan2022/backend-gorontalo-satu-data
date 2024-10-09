<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EnumeratorKabupaten extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'enumerator_kabupaten';

    public function opd_kabupaten() {
        return $this->belongsTo(OpdKabupaten::class);
    }

    public function enumerator() {
        return $this->belongsTo(Enumerator::class);
    }
}
