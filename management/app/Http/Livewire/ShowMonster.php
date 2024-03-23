<?php

namespace App\Http\Livewire;

class ShowMonster extends DataTable
{
    public $objectClass = "App\Models\Monster";

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
    public function delete()
    {
        $this->objectClass::find($this->confirmId)->delete();
        $this->showConfirm = false;
        $this->emit('resetEditing');
    }
}
