<?php

namespace App\Http\Livewire;
use App\Models\Wsnlp;
use App\Models\Quest;
use App\Models\Npc;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Illuminate\Support\Str;

class EditPotions extends EditObject
{
    public $objectClass = 'App\Models\Potions';
    public $quests;
    public $languages;

    public function mount()
    {
        $this->editing = $this->makeBlankObject();

    }

    public function rules(): array
    {
        return [
            'editing.req_lv' => ['required', 'numeric', 'gt:0'],
            'editing.hp' => ['numeric', 'max:255'],
            'editing.mp' => ['numeric', 'max:255'],
            'editing.atk' => ['numeric', 'max:255'],
            'editing.def' => ['numeric', 'max:255'],
            'editing.dex' => ['numeric', 'max:255'],
            'editing.ImageID' => ['numeric', 'max:255'],
        ];
    }

    /**
     * Specify defaults for new items
     * @return mixed
     */
    public function makeBlankObject()
    {
        return $this->objectClass::make([
            'req_lv' => 0,
            'hp' => '',
            'mp' => 0,
            'atk' => 0,
            'def' => 0,
            'dex' => 0,
            'ImageID' => 1,
        ]);
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
