<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enumerator extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'enumerator';

    public function user() {
        return $this->belongsTo(User::class);
    }
}
