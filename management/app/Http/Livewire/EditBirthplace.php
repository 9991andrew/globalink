<?php

namespace App\Http\Livewire;
use App\Models\Map;
use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Str;

class EditBirthplace extends EditObject
{
    public $objectClass = 'Birthplace';
    public $mapTypeId = null;

    /**
     * Set all the rules so we can validate a birthplace.
     * For a field to be editable at all it has to appear here.
     * @return string[]
     */
    public function rules(): array
    {
        return [
        'editing.name' => ['required', 'max:255'],
        'editing.description' => '',
        'editing.map_id' => ['required', 'in:'.Map::pluck('id')->implode(',')],
        'editing.x' => 'required:numeric',
        'editing.y' => 'required:numeric',
        ];
    }

    public function updatedMapTypeId() {
        $this->editing->map_id=null;
    }

    /**
     * We need to get the mapTypeId when creating a new editing object.
     */
    public function setEditing($objectId)
    {
        // Added to clear any validation errors from a previous object
        if (is_numeric($objectId)) {
            $this->editing = $this->objectClass::find($objectId);
            $this->mapTypeId = $this->editing->map->map_type_id;
        } else {
            $this->editing = $this->makeBlankObject();
        }

        $this->showModal = true;
    }

}
