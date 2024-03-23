<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ask4 extends Model
{
    use HasFactory;

    protected $table = 'ask4';

    protected $fillable = [
        'map_id',
        'course_name'
    ];
}
