<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestResultType extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'uses_items',
        'uses_bool',
        'uses_string',
        'uses_answers',
        'uses_variables',
    ];

    public function quests()
    {
        return $this->hasMany(Quest::class);
    }

}
