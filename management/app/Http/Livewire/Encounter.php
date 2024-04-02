<?php

namespace App\Http\Livewire;

use Livewire\Component;

class Encounter extends Component
{
    public $monsters =  [];
    public $items = [];
    public $description = [""];
    public $actions = [];
    public function mount()
    {
        $this->description = "You stumble upon a clearing, and your senses warn you of danger lurking.";
    
        // Fetch a random monster
        $this->monsters = \App\Models\Monster::inRandomOrder()->limit(1)->get();
    
        // Fetch random items (assuming similar methods can be used for each)
        $this->items = collect([
            \App\Models\Armor::inRandomOrder()->first(),
            \App\Models\Potion::inRandomOrder()->first(),
            \App\Models\Weapon::inRandomOrder()->first(),
        ])->whereNotNull(); // This ensures we only include items that were found
    
        // Define available actions for the player
        $this->actions = ['Attack', 'Flee', 'Use Item'];
    }

    public function render(){
        return view("livewire.encounter");
    }
}
