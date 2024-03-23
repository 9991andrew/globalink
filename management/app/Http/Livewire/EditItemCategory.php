<?php

namespace App\Http\Livewire;

class EditItemCategory extends EditObject
{
    public $objectClass = 'ItemCategory';

    /**
     * Set all the rules so we can validate an item
     * For a field to be editable at all it has to appear here.
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'editing.name' => ['required', 'max:200'],
            'editing.layer_index' => ['numeric'],
            'editing.display_seq' => ['numeric'],
            'editing.disabled' => ['boolean'],
        ];
    }

}
