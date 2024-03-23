<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Npc extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'npc_icon_id',
        'map_id',
        'y_top',
        'x_right',
        'y_bottom',
        'x_left',
        'level',
        'icon_id',
    ];

    // Never use the top, bottom, left, right values, use these instead to always ensure you're using
    // the correct values, as they can be entered in backwards and are confusing as hell anyways.
    public function getXMaxAttribute()
    {
        return max($this->x_left, $this->x_right);
    }

    public function getXMinAttribute()
    {
        return min($this->x_left, $this->x_right);
    }

    public function getYMaxAttribute()
    {
        return max($this->y_top, $this->y_bottom);
    }

    public function getYMinAttribute()
    {
        return min($this->y_top, $this->y_bottom);
    }

    protected $appends = ['probability'];

    public function map()
    {
        return $this->belongsTo(Map::class);
    }

    public function npcIcon()
    {
        return $this->belongsTo(NpcIcon::class);
    }

//    /**
//     * Professions that the NPC has and can give players
//     */
//    public function professions()
//    {
//        return $this->belongsToMany(Profession::class, 'npc_professions');
//    }

    /**
     * This shows the prerequisite professions a player must have before they can obtain this profession
     */
    public function npcProfessions()
    {
        return $this->hasMany(NpcProfession::class);
    }

    /**
     * Portals that an NPC can send a player to other locations with
     */
    public function npcPortals()
    {
        return $this->hasMany(NpcPortal::class);
    }

    /**
     * Any items that the player can give
     */
    public function npcItems()
    {
        return $this->hasMany(NpcItem::class);
    }

    /**
     * Any quests that this NPC can give
     */
    public function quests()
    {
        return $this->hasMany(Quest::class, 'giver_npc_id');
    }

    /**
     * Return the probability percentage of this NPC appearing on one of the tiles in their 'home area'
     */
    public function getProbabilityAttribute()
    {
        $probabilityFactor = 2;
        $area = (1+abs(($this->y_bottom-$this->y_top))) * (1+abs(($this->x_right-$this->x_left)));
        $probability=round(1/$area*100);

        // Adjust probability of npc being present by a factor. Previously the game multiplied it by 2.
        // Perhaps this makes sense to be a variable particular to each NPC, depending on how easy or hard
        // you want them to be to find
        return $probability*=$probabilityFactor;
    }


}
