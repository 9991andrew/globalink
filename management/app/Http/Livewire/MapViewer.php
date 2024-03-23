<?php

namespace App\Http\Livewire;

use App\Models\Map;
use Livewire\Component;

class MapViewer extends Component
{
    public $map;

    public function mount(Map $map)
    {
        $this->map = $map;
    }
    public function render()
    {
        return view('livewire.map-viewer');
    }
}
