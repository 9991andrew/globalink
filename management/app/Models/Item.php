<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
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
        'item_icon_id',
        'item_category_id',
        'item_effect_id',
        'effect_parameters',
        'amount',
        'max_amount',
        'price',
        'level',
        'required_level',
        'weight',
    ];

    public function itemCategory()
    {
        return $this->belongsTo(ItemCategory::class);
    }

    public function gender()
    {
        return $this->belongsTo(Gender::class);
    }

    public function race()
    {
        return $this->belongsTo(Race::class);
    }

    public function itemIcon()
    {
        return $this->belongsTo(ItemIcon::class);
    }

    public function itemEffect()
    {
        return $this->belongsTo(ItemEffect::class);
    }

    public function players()
    {
        return $this->belongsToMany(Player::class, 'player_items');
    }

    public function quests()
    {
        return $this->belongsToMany(Quest::class, 'quest_items');
    }

    public function tools()
    {
        return $this->hasMany(QuestTool::class);
    }

    public function npcs()
    {
        return $this->belongsToMany(Npc::class, 'npc_items')
            ->withPivot('quest_id')->withTimestamps();
    }

    public function itemVariables()
    {
        return $this->hasMany(ItemVariable::class);
    }

}
