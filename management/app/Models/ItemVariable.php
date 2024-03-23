<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemVariable extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'item_id',
        'var_name',
        'min_value',
        'max_value',
    ];

    /**
     * Item that this belongs to
     */
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

}
