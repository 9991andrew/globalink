<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NpcItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'quest_id',
        'npc_id',
        'item_id',
    ];

    public function quest()
    {
        return $this->belongsTo(Quest::class);
    }

    /**
     * NPC that this belongs to
     */
    public function npc()
    {
        return $this->belongsTo(Npc::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

}
