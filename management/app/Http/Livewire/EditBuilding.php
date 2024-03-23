<?php

namespace App\Http\Livewire;
use App\Models\Map;
use App\Models\NpcProfession;
use App\Models\Profession;
use Illuminate\Support\Str;

class EditBuilding extends EditObject
{
    public $objectClass = 'Building';
    public $mapTypeId = null;
    public $destMapTypeId = null;
    public $building_id = null;
    public $profession_id = null;

    /**
     * Set all the rules so we can validate a birthplace.
     * For a field to be editable at all it has to appear here.
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'editing.name' => ['required', 'max:255'],
            'editing.map_id' => ['required', 'in:'.Map::pluck('id')->implode(',')],
            'editing.x' => ['required', 'numeric'],
            'editing.y' => ['required', 'numeric'],
            'editing.dest_map_id' => ['nullable', 'in:'.Map::pluck('id')->implode(',')],
            'editing.dest_x' => ['nullable', 'numeric'],
            'editing.dest_y' => ['nullable', 'numeric'],
            'editing.level' => ['required', 'numeric', 'integer'],
            'editing.external_link' => ['nullable', 'max:2000'],

        ];
    }

    public function updatedMapTypeId() {
        $this->editing->map_id=null;
    }

    public function updatedDestMapTypeId() {
        $this->editing->dest_map_id=null;
    }

    public function professionRules(): array
    {
        // Make sure we don't allow these professions to be selected
        if (isset($this->building_id))
            $existingProfs = $this->editing->professions->pluck('id')->toArray();
        else
            $existingProfs = [];
        return [
            'building_id' => ['required', 'numeric'],
            'profession_id' => ['required', 'in:'.Profession::whereNotIn('id', $existingProfs)->pluck('id')->implode(',')],
        ];
    }

    public function addProfession()
    {
        $this->building_id = $this->editing->id;
        $validatedData = $this->validate($this->professionRules());
        $this->editing->professions()->attach($this->profession_id);

        $this->profession_id = null;
        $this->emit('rerenderParent');
        $this->emit('editorRefresh');
    }


    /**
     * Defaults for buildings
     * @return mixed
     */
    public function makeBlankObject()
    {
        return $this->objectClass::make([
            'id' => '',
            'name' => '',
            'level' => '0',
        ]);
    }

    /**
     * We need to get the mapTypeId when creating a new editing object.
     */
    public function setEditing($objectId)
    {
        // Added to clear any validation errors from a previous object
        if (is_numeric($objectId)) {
            $this->editing = $this->objectClass::find($objectId);
            if (isset($this->editing->map_id)) {
                $this->mapTypeId = $this->editing->map->map_type_id;
            }
            if (isset($this->editing->dest_map_id)) {
                $this->destMapTypeId = $this->editing->destMap->map_type_id;
            }
        } else {
            $this->editing = $this->makeBlankObject();
        }

        $this->showModal = true;
    }

}
