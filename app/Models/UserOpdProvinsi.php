<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserOpdProvinsi extends Model
{
    use HasFactory;
    
    protected $guarded = ['id'];
    protected $table = 'user_opd_provinsi';

    public function opd_provinsi() {
        return $this->belongsTo(OpdProvinsi::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }
}
