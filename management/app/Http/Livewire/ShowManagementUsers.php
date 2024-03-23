<?php

namespace App\Http\Livewire;

use App\Models\ManagementUser;

class ShowManagementUsers extends DataTable
{
    public $objectClass = "ManagementUser";

    // Any filters we will use to search
    public $filters = [
        'id' => '',
        'name' => '',
    ];

    // Add any rules on top of the default ones
    protected function rules(): array
    {
        return array_merge(parent::rules(), [
            'filters.name' => '',
            'filters.id' => 'numeric',
        ]);
    }

}
