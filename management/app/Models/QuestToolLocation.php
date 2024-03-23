<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestToolLocation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'quest_tool_id',
        'item_id', // Item that is found at this location
        'item_amount', // Quantity of the item found at this location
        'map_id',
        'x',
        'y',
        'success_message',
        'quest_complete', // If all these are complete, the quest is finished
    ];

    /**
     * Tool the item is 'found' with
     */
    public function questTool()
    {
        return $this->belongsTo(QuestTool::class);
    }

    /**
     * Item that this tool finds
     */
    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Map that item is found on
     */
    public function map()
    {
        return $this->belongsTo(Map::class);
    }

}
