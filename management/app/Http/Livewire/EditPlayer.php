<?php

namespace App\Http\Livewire;

use App\Models\Birthplace;
use App\Models\Player;
use App\Models\Profession;
use App\Models\Quest;
use App\Models\Skill;
use App\Models\User;
use App\Models\Weapon;
use App\Models\Armors;
use App\Models\Potions;
use Carbon\Carbon;
use Livewire\Component;

class EditPlayer extends EditObject
{
    public $objectClass = 'Player';
    public $player_id;
    public $profession_id;
    public $weapon_id;
    public $potion_id;
    public $armor_id;
    public $skill_id;
    public $quest_id;
    public $profession_xp=0;
    public $profession_level=0;
    public $weapons;
    public $potions;
    public $armors;

    /**
     * Set all the rules so we can validate a player exists.
     * For a field to be editable at all it has to appear here.
     * @return string[]
     */
    public function rules(): array
    { return [
        'editing.user_id' => 'numeric|required|in:'.User::pluck('id')->implode(','),
        'editing.name' => ['required', 'unique:players,name,'.$this->editing->id],
        'editing.birthplace_id' => 'required|in:'.Birthplace::pluck('id')->implode(','),
        'editing.map_id' => 'required',
        'editing.x' => 'numeric:required',
        'editing.y' => 'numeric:required',
        'editing.health' => 'numeric|required|max:100',
        // health_max could be different per-player, but it doesn't seem to be in practice
        'editing.money' => 'numeric|required',
        'editing.movement' => 'numeric|required',
    ];}



    public function professionRules(): array
    {
        // Make sure we don't allow these professions to be selected
        if (isset($this->player_id))
            $existingProfs = $this->editing->professions->pluck('id')->toArray();
        else
            $existingProfs = [];
        return [
            'player_id' => ['required', 'numeric'],
            'profession_id' => ['required', 'in:'.Profession::whereNotIn('id', $existingProfs)->pluck('id')->implode(',')],
            'profession_xp' => ['required', 'numeric', 'integer'],
            'profession_level' => ['required', 'numeric', 'integer'],

        ];
    }
    public function weaponsRules():array {
        if(isset($this->player_id))
            $existingWeapons = $this->editing->weapons()->pluck('id')->toArray();
        else
           $existingWeapons=[];
        return [
            'weapon_id' => ['required', 'numeric', 'exists:weapon,id', 'not_in:' . implode(',', $existingWeapons)],        ];
    }
    public function armorsRules():array {
        if(isset($this->player_id))
            $existingArmors = $this->editing->armor()->pluck('id')->toArray();
        else
           $existingArmors=[];
           return [
            'player_id' => ['required', 'numeric'],
            'armor_id' => ['required', 'in:'.Weapon::whereNotIn('id', $existingArmors)->pluck('id')->implode(',')],
        ];
    }

    public function potionRules():array {
        if(isset($this->player_id))
            $existingPotions = $this->editing->potion()->pluck('id')->toArray();
        else
           $existingPotions=[];
           return [
            'player_id' => ['required', 'numeric'],
            'potion_id' => ['required', 'in:'.Potions::whereNotIn('id', $existingPotions)->pluck('id')->implode(',')],
        ];
    }

    public function addProfession()
    {
        $this->player_id = $this->editing->id;
        $validatedData = $this->validate($this->professionRules());
        $this->editing->professions()->attach($this->profession_id, ['profession_xp' => 0, 'profession_level' => 0]);

        $this->profession_id = null;
        $this->emit('rerenderParent');
        $this->emit('editorRefresh');
    }
    public function addArmor() {
        $armor = \App\Models\Armors::find($this->armor_id);
        if ($armor) {
            $armor->id = $this->editing->id;
            $armor->save();
            
            $this->armor_id = null; // Resetting weapon_id
            $this->emit('rerenderParent'); // Potentially to refresh the list of weapons
            $this->emit('editorRefresh'); // Assuming this is for UI refresh
        }
    }
    public function addWeapon() {
        // Assuming $this->weapon_id is the ID of an existing weapon
        $weapon = \App\Models\Weapon::find($this->weapon_id);
        if ($weapon) {
            $weapon->id = $this->editing->id;
            $weapon->save();
            
            $this->weapon_id = null; // Resetting weapon_id
            $this->emit('rerenderParent'); // Potentially to refresh the list of weapons
            $this->emit('editorRefresh'); // Assuming this is for UI refresh
        }
    }
    
    public function addPotion() {
        // Assuming $this->weapon_id is the ID of an existing weapon
        $potion = \App\Models\Potions::find($this->potion_id);
        if ($potion) {
            $potion->player_id = $this->editing->id;
            $potion->save();
            
            $this->potion_id = null; // Resetting weapon_id
            $this->emit('rerenderParent'); // Potentially to refresh the list of weapons
            $this->emit('editorRefresh'); // Assuming this is for UI refresh
        }
    }


    public function skillRules(): array
    {
        // Make sure we don't allow these professions to be selected
        if (isset($this->player_id))
            $existingSkills = $this->editing->skills->pluck('id')->toArray();
        else
            $existingSkills = [];
        return [
            'player_id' => ['required', 'numeric'],
            'skill_id' => ['required', 'in:'.Skill::whereNotIn('id', $existingSkills)->pluck('id')->implode(',')],
        ];
    }

    public function addSkill()
    {
        $this->player_id = $this->editing->id;
        $validatedData = $this->validate($this->skillRules());
        $this->editing->skills()->attach($this->skill_id);

        $this->skill_id = null;
        $this->emit('rerenderParent');
        $this->emit('editorRefresh');
    }


    public function questRules(): array
    {
        // Make sure we don't allow these quests to be selected
        if (isset($this->npc_id))
            $existingProfs = $this->editing->quests->pluck('id')->toArray();
        else
            $existingProfs = [];
        return [
            'player_id' => ['required', 'numeric'],
            'quest_id' => ['required', 'in:'.Quest::pluck('id')->implode(',')],
        ];
    }

    public function addQuest()
    {
        $this->player_id = $this->editing->id;
        $validatedData = $this->validate($this->questRules());
        $this->editing->quests()->attach($this->quest_id, ['pickup_time' => Carbon::now()]);

        $this->quest_id = null;
        $this->emit('rerenderParent');
        $this->emit('editorRefresh');
    }

    // First without saving. Should this just save?
    public function returnToBirthplace() {
        $this->editing->map_id = $this->editing->birthplace->map_id;
        $this->editing->x = $this->editing->birthplace->x;
        $this->editing->y = $this->editing->birthplace->y;
        $this->editing->save();
        $this->emit('rerenderParent');
    }
    public function getAvailableWeapons()
{
    return Weapon::whereNotIn('id', $this->editing->weapons()->pluck('id')->toArray())->get();
}

public function getAvailablePotions()
{
    return Potions::whereNotIn('id', $this->editing->potion()->pluck('id')->toArray())->get();
}

public function getAvailableArmors()
{
    return Armors::whereNotIn('id', $this->editing->armor()->pluck('id')->toArray())->get();
}

}
