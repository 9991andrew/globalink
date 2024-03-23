<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestAnswer extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'quest_id',
        'name',
        'answer_string',
        'correct_bool',
        'question',
    ];

    /**
     * Quest that this belongs to
     */
    public function quest()
    {
        return $this->belongsTo(Quest::class, 'quest_id');
    }
}
