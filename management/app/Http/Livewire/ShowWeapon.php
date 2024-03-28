<?php

namespace App\Http\Livewire;

use App\Models\Map;
use Livewire\Component;

class ShowWeapon extends DataTable
{
    public $objectClass = "App\Models\Weapon";

    // Any filters we will use to search
    public $filters = [
        'id' => '',
    ];

    // Add any rules on top of the default ones
    protected function rules(): array
    {
        return array_merge(parent::rules(), [
            'filters.id' => 'numeric',
        ]);
    }
    public function delete()
    {
        $this->objectClass::find($this->confirmId)->delete();
        $this->showConfirm = false;
        $this->emit('resetEditing');
    }
}
