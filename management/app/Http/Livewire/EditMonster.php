<?php

namespace App\Http\Livewire;
use App\Models\Item;
use App\Models\Map;


class EditMonster extends EditObject
{
    public $objectClass = 'App\Models\Monster';
    public $name;
    public $hp;
    public $item_id;
    public $drop_rate;
    public $items;
    public $map_id;
    public $maps;

    public function mount()
    {
        $this->editing = $this->makeBlankObject();
        $this->items = Item::all();
        $this->maps = Map::all();
    }

    public function rules(): array
    {
        return [
            'editing.name' => ['required', 'max:255'],
            'editing.hp' => ['nullable', 'max:255'],
            'editing.item_id' => ['nullable','max:255'],
            'editing.drop_rate' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'editing.map_id'=>['nullable', 'max:255'],
        ];
    }

    /**
     * Specify defaults for new items
     * @return mixed
     */
    public function makeBlankObject()
    {
        return $this->objectClass::make([
            'name'=> '',
            'hp'=> 0,
            'item_id'=>0,
            'drop_rate'=>0,
            'map_id'=>0,
        ]);
    }

    public function useItem() {
        }
    public function save()
    {
    
        $this->validate();
        $this->editing->save();
        $this->showModal = false;
        $this->emit('rerenderParent');
        $this->cleanup();
    }

}
