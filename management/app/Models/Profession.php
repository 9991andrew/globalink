<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profession extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'require_all_prerequisites',
    ];

    /**
     * Players that have this Profession
     */
    public function players()
    {
        return $this->belongsToMany(Player::class, 'player_professions');
    }

    /**
     * Players that have this Profession
     */
    public function npcs()
    {
        return $this->belongsToMany(Npc::class, 'npc_professions');
    }

    /**
     * This shows the prerequisite professions a player must have before they can obtain this profession
     */
    public function prerequisites()
    {
        return $this->hasMany(ProfessionPrerequisite::class);
    }

}
