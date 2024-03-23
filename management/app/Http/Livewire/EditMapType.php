<?php

namespace App\Http\Livewire;
use Illuminate\Support\Str;

class EditMapType extends EditObject
{
    public $objectClass = 'MapType';

    /**
     * Set all the rules so we can validate a map type.
     * For a field to be editable at all it has to appear here.
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'editing.name' => ['required', 'max:50'],
            'editing.description' => ['required', 'max:10000'],
        ];
    }


}
