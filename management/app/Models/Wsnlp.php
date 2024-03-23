<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wsnlp extends Model
{
    use HasFactory;

    protected $table = 'wsnlp_config';

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
