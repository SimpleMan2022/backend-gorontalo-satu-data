<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BeritaAcara extends Model
{
    use HasFactory;
    
    protected $guarded = [];
    protected $table = 'berita_acara';

    public function opd_kabupaten() {
        return $this->belongsTo(OpdKabupaten::class);
    }

    public function indikator() {
        return $this->belongsTo(Indikator::class);
    }
}
