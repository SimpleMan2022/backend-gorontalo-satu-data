<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserOpdKabupaten extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'user_opd_kabupaten';

    public function opd_kabupaten()
    {
        return $this->belongsTo(OpdKabupaten::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function opd_provinsi()
    {
        return $this->belongsTo(OpdProvinsi::class);
    }
}
