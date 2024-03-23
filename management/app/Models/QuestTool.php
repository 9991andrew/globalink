<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestTool extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'quest_id',
        'item_id',
        'item_amount',
    ];

    /**
     * Quest that this belongs to
     */
    public function quest()
    {
        return $this->belongsTo(Quest::class);
    }

    /**
     * Tool item
     */
    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Tool item
     */
    public function questToolLocations()
    {
        return $this->hasMany(QuestToolLocation::class);
    }




}
