<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlayerItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'guid',
        'player_id',
        'item_id',
        'is_equipped',
        'bag_slot_id',
        'bag_slot_amount',
    ];

    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

}
