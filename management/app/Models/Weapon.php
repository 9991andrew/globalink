<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Weapon extends Model
{
    use HasFactory;

    protected $table = 'weapon';

    protected $fillable = [
        'req_lv' ,
        'min_hp' ,
        'max_hp' ,
        'min_mp_consumtion' ,
        'max_mp_consumtion' ,
        'min_atk' ,
        'max_atk' ,
        'min_def' ,
        'max_def' ,
        'min_dex' ,
        'max_dex' ,
        'weapon_type' ,
        'attack_type' ,
        'ImageID' ,
    ];
}
