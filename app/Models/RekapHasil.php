<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RekapHasil extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'rekap_hasil';
}
