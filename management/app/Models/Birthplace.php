<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Birthplace extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'map_id',
    ];

    public function map()
    {
        return $this->belongsTo(Map::class);
    }

    public function players()
    {
        return $this->hasMany(Player::class);
    }
}
