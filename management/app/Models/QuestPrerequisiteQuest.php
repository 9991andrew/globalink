<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestPrerequisiteQuest extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'quest_id',
        'prerequisite_quest_id',
    ];

    /**
     * Quest that this belongs to
     */
    public function quest()
    {
        return $this->belongsTo(Quest::class, 'quest_id');
    }

    /**
     * Quest that this Quest requires
     */
    public function prerequisiteQuest()
    {
        return $this->belongsTo(Quest::class, 'prerequisite_quest_id');
    }

}
