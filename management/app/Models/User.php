<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'username',
        'password',
        'email',
        'locale_id'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Get players for a user
     */
    public function players()
    {
        return $this->hasMany(Player::class);
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'locale_id', 'locale_id');
    }

}
