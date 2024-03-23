<?php

namespace App\Http\Livewire;
use App\Models\MapType;
use Illuminate\Support\Str;

class EditMap extends EditObject
{
    public $objectClass = 'Map';

    /**
     * Set all the rules so we can validate a map type.
     * For a field to be editable at all it has to appear here.
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'editing.name' => ['required', 'max:30'],
            'editing.description' => ['required', 'max:2000'],
            'editing.map_type_id' => ['required', 'in:'.MapType::pluck('id')->implode(',')],
        ];
    }
}
