<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quest extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'repeatable',
        'repeatable_cd_in_minute',
        'level',
        'giver_npc_id',
        'target_npc_id',
        'prologue',
        'content',
        'target_npc_prologue',
        'npc_has_quest_item',
        'min_quest_item_amount',
        'max_quest_item_amount',
        'quest_result_type_id',
        'old_result_type_id',
        'success_words',
        'failure_words',
        'waiting_words',
        'cd_in_minute',
        'result',
        'result_map_id',
        'result_x',
        'result_y',
        'base_reward_automark_percentage',
        'reward_profession_xp',
        'reward_xp',
        'reward_money',
    ];

    public function giverNpc()
    {
        return $this->belongsTo(Npc::class, 'giver_npc_id');
    }

    public function targetNpc()
    {
        return $this->belongsTo(Npc::class, 'target_npc_id');
    }

    public function questResultType()
    {
        return $this->belongsTo(QuestResultType::class);
    }

    public function prerequisiteProfessions()
    {
        return $this->hasMany(QuestPrerequisiteProfession::class);
    }

    public function prerequisiteQuests()
    {
        return $this->hasMany(QuestPrerequisiteQuest::class);
    }

    public function questAnswers()
    {
        return $this->hasMany(QuestAnswer::class);
    }

    public function questVariables()
    {
        return $this->hasMany(QuestVariable::class);
    }

    // This is only really used for quest_result_type 13
    public function resultMap()
    {
        return $this->belongsTo(Map::class, 'result_map_id');
    }

    public function questItems()
    {
        return $this->hasMany(QuestItem::class);
    }

    public function questTools()
    {
        return $this->hasMany(QuestTool::class);
    }

    public function rewardItems()
    {
        return $this->hasMany(QuestRewardItem::class);
    }

    public function npcItems()
    {
        return $this->hasMany(NpcItem::class);
    }


}
