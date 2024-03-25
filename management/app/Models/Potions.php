<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Potions extends Model
{
    use HasFactory;

    protected $table = 'potions';

    protected $fillable = [
        'quest_id',
        'maxbpm',
        'autobpm',
        'ngrampos',
        'canonical',
        'timeout',
        'lang',
        'name',
        'ismap'
    ];
}
