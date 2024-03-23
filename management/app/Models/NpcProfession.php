<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NpcProfession extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'npc_id',
        'profession_id',
    ];

    /**
     * NPC that this belongs to
     */
    public function npc()
    {
        return $this->belongsTo(Npc::class, 'npc_id');
    }

    /**
     * Profession that this NPC has
     */
    public function profession()
    {
        return $this->belongsTo(Profession::class, 'profession_id');
    }


}
