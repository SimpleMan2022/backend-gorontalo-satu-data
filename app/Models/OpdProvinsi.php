<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpdProvinsi extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'opd_provinsi';

    public function user_opd_provinsi()
    {
        return $this->hasMany(UserOpdProvinsi::class);
    }

    public function indikator()
    {
        return $this->hasMany(Indikator::class);
    }
    public function subjek()
    {
        return $this->hasMany(Subjek::class);
    }

    public function opd_kabupaten()
    {
        return $this->hasMany(OpdKabupaten::class);
    }

    public function periode()
    {
        return $this->hasMany(Periode::class);
    }

    public function hasil_spasial()
    {
        return $this->hasMany(HasilSpasial::class);
    }

    public function pivot_opd_sektor()
    {
        return $this->hasMany(PivotOpdSektor::class);
    }

    public function sektor()
    {
        return $this->belongsToMany(Sektor::class, 'pivot_opd_sektor', 'opd_provinsi_id', 'sektor_id');
    }
}
