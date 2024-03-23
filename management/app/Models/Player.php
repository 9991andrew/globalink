<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'birthplace_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function birthplace()
    {
        return $this->belongsTo(Birthplace::class);
    }

    public function map()
    {
        return $this->belongsTo(Map::class);
    }

    public function gender()
    {
        return $this->belongsTo(Gender::class);
    }

    public function race()
    {
        return $this->belongsTo(Race::class);
    }

    /**
     * Professions that the Player has
     */
    public function professions()
    {
        return $this->belongsToMany(Profession::class, 'player_professions')
            ->withPivot('profession_xp', 'profession_level')->withTimestamps();
    }

    /**
     * Quests that the Player has
     */
    public function quests()
    {
        return $this->belongsToMany(Quest::class, 'player_quests')
            ->withPivot('pickup_time', 'success_time')->withTimestamps();
    }

    /**
     * Items that the Player has
     */
    public function playerItems()
    {
        return $this->hasMany(PlayerItem::class);
    }

    /**
     * Skills the player has
     */
    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'player_skills')
            ->withTimestamps();
    }


}
