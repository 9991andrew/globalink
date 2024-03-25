<?php

namespace App\Http\Livewire;
use App\Models\Wsnlp;
use App\Models\Quest;
use App\Models\Npc;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Illuminate\Support\Str;

class EditArmors extends EditObject
{
    public $objectClass = 'App\Models\Armors';

    public function mount()
    {
        $this->editing = $this->makeBlankObject();
    }

    public function rules(): array
    {
        return [
            'editing.req_lv' => ['required', 'max:255'],
            'editing.min_hp' => ['numeric', 'boolean'],
            'editing.max_hp' => ['numeric', 'boolean'],
            'editing.min_mp_consumtion' => ['numeric', 'boolean'],
            'editing.max_mp_consumtion' => ['numeric', 'boolean'],
            'editing.min_atk' => ['numeric', 'required'],
            'editing.max_atk' => ['numeric', 'required'],
            'editing.min_def' => ['numeric', 'required'],
            'editing.max_def' => ['numeric', 'required'],
            'editing.min_dex' => ['numeric', 'required'],
            'editing.max_dex' => ['numeric', 'required'],
            'editing.armor_type' => ['numeric', 'required'],
            'editing.ImageID' => ['numeric', 'nullable'],
        ];
    }

    /**
     * Specify defaults for new items
     * @return mixed
     */
    public function makeBlankObject()
    {
        return $this->objectClass::make([
            'req_lv' => ['required', 'max:255'],
            'min_hp' => ['numeric', 'boolean'],
            'max_hp' => ['numeric', 'boolean'],
            'min_mp_consumtion' => ['numeric', 'required'],
            'max_mp_consumtion' => ['numeric', 'required'],
            'min_atk' => ['numeric', 'required'],
            'max_atk' => ['numeric', 'required'],
            'min_def' => ['numeric', 'required'],
            'max_def' => ['numeric', 'required'],
            'min_dex' => ['numeric', 'required'],
            'max_dex' => ['numeric', 'required'],
            'weapon_type' => ['numeric', 'required'],
            'attack_type' => ['numeric', 'required'],
            'ImageID' => ['numeric', 'nullable'],
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
