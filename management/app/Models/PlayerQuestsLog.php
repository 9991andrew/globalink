<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlayerQuestsLog extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = 'player_quests_log';

    protected $fillable = [
        'player_id',
        'quest_id',
        'event_datetime',
        'quest_event_id',
        'npc_id',
        'map_id',
        'x',
        'y',
        'ratio',
    ];

    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    public function quest()
    {
        return $this->belongsTo(Quest::class);
    }

    public function npc()
    {
        return $this->belongsTo(Npc::class);
    }

    public function map()
    {
        return $this->belongsTo(Map::class);
    }

}
