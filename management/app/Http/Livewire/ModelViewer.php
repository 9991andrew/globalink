<?php

namespace App\Http\Livewire;

use Livewire\Component;

class ModelViewer extends Component
{
    public $m_id;

    public function mount($m_id)
    {
        $this->m_id = $m_id;
    }

    public function render()
    {
        return view('livewire.model-viewer');
    }

}
