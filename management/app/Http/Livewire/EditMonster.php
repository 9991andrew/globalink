<?php

namespace App\Http\Livewire;
use App\Models\Wsnlp;
use App\Models\Quest;
use App\Models\Npc;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Illuminate\Support\Str;

class EditMonster extends EditObject
{
    public $objectClass = 'App\Models\Monster';
    public $quests;
    public $languages;

    public function mount()
    {
       // $this->editing = $this->makeBlankObject();

    }

    public function rules(): array
    {
        return [
            'editing.name' => ['required', 'max:255'],
            'editing.hp' => ['nullable', 'max:255'],
            'editing.item_id' => ['max:255'],
            'editing.drop_rate' => ['max:255'],
        ];
    }

    /**
     * Specify defaults for new items
     * @return mixed
     */
    public function makeBlankObject()
    {
        return $this->objectClass::make([
            'quest_id' => 0,
            'name' => '',
            'maxbpm' => 0,
            'autobpm' => 0,
            'ngrampos' => 0,
            'canonical' => 0,
            'timeout' => 600,
            'lang' => 'en',
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
