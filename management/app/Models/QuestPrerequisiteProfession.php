<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestPrerequisiteProfession extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'quest_id',
        'profession_id',
    ];

    /**
     * Quest that this belongs to
     */
    public function quest()
    {
        return $this->belongsTo(Quest::class, 'quest_id');
    }

    /**
     * A profession that this Quest requires
     */
    public function profession()
    {
        return $this->belongsTo(Profession::class, 'profession_id');
    }

}
