<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestItem extends Model
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
        'answer_bool',
        'answer_seq',
        'answer_string',
    ];

    /**
     * Quest that this belongs to
     */
    public function quest()
    {
        return $this->belongsTo(Quest::class);
    }

    /**
     * Item that is given when the quest is accepted
     */
    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
