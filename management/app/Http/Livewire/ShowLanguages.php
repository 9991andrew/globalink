<?php

namespace App\Http\Livewire;
use App\Models\User;

class ShowLanguages extends DataTable
{
    public $objectClass = "Language";

    // Any filters we will use to search
    public $filters = [
        'id' => '',
        'username' => '',
    ];

}
