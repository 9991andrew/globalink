<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfessionPrerequisite extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'profession_id',
        'profession_id_req',
        'profession_xp_req'
    ];

    /**
     * Profession that this is assigning prerequisites to
     */
    public function profession()
    {
        return $this->belongsTo(Profession::class, 'profession_id');
    }

    /**
     * The prerequisite professions that is required
     */
    public function professionRequired()
    {
        return $this->belongsTo(Profession::class, 'profession_id_req');
    }

}
