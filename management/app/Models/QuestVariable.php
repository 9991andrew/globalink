<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestVariable extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'quest_id',
        'var_name',
        'min_value',
        'max_value',
    ];

    /**
     * Quest that this belongs to
     */
    public function quest()
    {
        return $this->belongsTo(Quest::class, 'quest_id');
    }

}
