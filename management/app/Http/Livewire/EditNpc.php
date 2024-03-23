<?php

namespace App\Http\Livewire;
use App\Models\Item;
use App\Models\Map;
use App\Models\NpcIcon;
use App\Models\NpcProfession;
use App\Models\NpcPortal;
use App\Models\Profession;
use App\Models\Quest;
use App\Models\NpcItem;
use Livewire\Component;
use Illuminate\Support\Str;

class EditNpc extends EditObject
{
    public $objectClass = 'Npc';
    public $npc_id;
    public $mapTypeId;
    public $profession_id;
    public $dest_map_id;
    public $dest_x;
    public $dest_y;
    public $name;
    public $price;
    public $level;
    public $item_id;
    public $quest_id;

    /**
     * Set all the rules so we can validate an item
     * For a field to be editable at all it has to appear here.
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'editing.name' => ['required', 'max:255'],
            'editing.map_id' => ['in:'.Map::pluck('id')->implode(',')],
            'editing.y_top' => ['required', 'numeric'],
            'editing.x_right' => ['required', 'numeric'],
            'editing.y_bottom' => ['required', 'numeric'],
            'editing.x_left' => ['required', 'numeric'],
            'editing.level' => ['required', 'numeric'],
            'editing.npc_icon_id' => ['required', 'in:'.NpcIcon::pluck('id')->implode(',')],
        ];
    }

    /**
     * If you want to specify defaults for new objects to be created, override this method.
     * @return mixed
     */
    public function makeBlankObject()
    {
        return $this->objectClass::make([
            'id' => '',
            'name' => '',
            'portal_keeper' => 0,
            'trainer' => 0,
        ]);
    }

    public function professionRules(): array
    {
        // Make sure we don't allow these professions to be selected
        if (isset($this->npc_id))
            $existingProfs = NpcProfession::where('npc_id', $this->editing->id)
                ->pluck('profession_id')->toArray();
        else
            $existingProfs = [];
        return [
            'npc_id' => ['required', 'numeric'],
            'profession_id' => ['required', 'in:'.Profession::whereNotIn('id', $existingProfs)->pluck('id')->implode(',')],
        ];
    }

    public function addProfession()
    {
        $this->npc_id = $this->editing->id;
        $validatedData = $this->validate($this->professionRules());

        $npcProfession = NpcProfession::create($validatedData);
        $npcProfession->npc_id = $this->editing->id;
        $npcProfession->save();

        $this->profession_id = null;
        $this->emit('rerenderParent');
        $this->emit('editorRefresh');
    }


    public function portalRules(): array
    {
        return [
            'npc_id' => ['required', 'numeric'],
            'dest_map_id' => ['required', 'in:'.Map::pluck('id')->implode(',')],
            'dest_x' => ['required', 'numeric'],
            'dest_y' => ['required', 'numeric'],
            'level' => ['required', 'numeric'],
            'price' => ['required', 'numeric'],
            'name' => ['required'],
        ];
    }

    public function addPortal()
    {
        $this->npc_id = $this->editing->id;
        $validatedData = $this->validate($this->portalRules());

        $npcPortal = NpcPortal::create($validatedData);
        $npcPortal->npc_id = $this->editing->id;
        $npcPortal->save();

        $this->dest_map_id = null;
        $this->dest_x = null;
        $this->dest_y = null;
        $this->emit('rerenderParent');
        $this->emit('editorRefresh');
    }

    public function itemRules(): array
    {
        return [
            'npc_id' => ['required', 'numeric'],
            'item_id' => ['required', 'in:'.Item::pluck('id')->implode(',')],
            'quest_id' => ['nullable', 'in:'.Quest::pluck('id')->implode(',')],
        ];
    }

    public function addItem()
    {
        $this->npc_id = $this->editing->id;
        $validatedData = $this->validate($this->itemRules());

        $npcItem = NpcItem::create($validatedData);
        $npcItem->npc_id = $this->editing->id;
        $npcItem->save();

        $this->quest_id = null;
        $this->item_id = null;
        $this->emit('rerenderParent');
        $this->emit('editorRefresh');
    }


    public function updatePickerItem($selectedId, $field)
    {
        if ($field == 'npc_icon_id') {
            $this->editing->npc_icon_id = $selectedId;
        } else if ($field == 'item_id') {
            $this->item_id = $selectedId;
        }
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
            if (isset($this->editing->map_id)) {
                $this->mapTypeId = $this->editing->map->map_type_id;
            }
            // Why validate here?
             $this->validate();
        } else {
            $this->editing = $this->makeBlankObject();
        }

        $this->showModal = true;
    }

}
