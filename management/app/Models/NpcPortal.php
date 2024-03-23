<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NpcPortal extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'npc_id',
        'dest_map_id',
        'dest_x',
        'dest_y',
        'price',
        'name',
        'level'
    ];

    /**
     * NPC that this belongs to
     */
    public function npc()
    {
        return $this->belongsTo(Npc::class, 'npc_id');
    }

    public function destMap()
    {
        return $this->belongsTo(Map::class, 'dest_map_id');
    }
}
