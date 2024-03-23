<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */

    public $timestamps = false;

    protected $fillable = [
        'name',
        'native_name',
        'country',
        'locale_id',
        'supported'
    ];


    /**
     * Get users who have a locale selected
     */
    public function users()
    {
        return $this->hasMany(User::class, 'locale_id', 'locale_id');
    }

}
