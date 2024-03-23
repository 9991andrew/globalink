<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Skill
 * Certain map tiles require a skill to move onto theml
 * @package App\Models
 */
class Skill extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
    ];

    public function mapTileTypes()
    {
        return $this->hasMany(MapTileType::class, 'skill_id_req');
    }

    public function players()
    {
        return $this->belongsToMany(Player::class, 'player_skills');
    }

}
