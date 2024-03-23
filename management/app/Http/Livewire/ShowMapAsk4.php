<?php

namespace App\Http\Livewire;

use App\Models\Ask4;
use App\Models\Map;
use Livewire\Component;

class ShowMapAsk4 extends Component
{
    public $objectClass = "App\Models\Ask4";
    public $data = [];
    public $map_id;
    public $course_name;
    public $availableCourses = [];

    protected $rules = [
        'course_name' => 'array',
    ];

    public function mount($map) {

        $this->map_id = (int) $map;

        $map = Map::where('id', $this->map_id)->get();
        $mapName = '';
        foreach ($map as $m) {
            $mapName = $m->name;
        }

        $courses = Ask4::where('map_id', $this->map_id)->get();
        foreach ($courses as $c) {
            $this->course_name[] = $c->course_name;
        }

        $contents = file_get_contents('https://ask4summary.vipresearch.ca/.lib_conf.php');
        $server = explode(',', $contents)[1];

        $contents = file_get_contents('https://'.$server.'/.course_list.php');
        $this->availableCourses = json_decode($contents);

        $this->data = [
            'selectedCourses' => $this->course_name,
            'availableCourses' => $this->availableCourses,
            'mapName' => $mapName
        ];
    }

    public function render() {

        return view('livewire.show-map-ask4', $this->data);
    }

    public function updated($propertyName) {

        $data = $this->validateOnly($propertyName);
    }

    public function save() {

        $this->validate();

        Ask4::where('map_id', $this->map_id)->delete();

        foreach ($this->course_name as $cn) {
            $model = $this->objectClass::make([
                'map_id' => $this->map_id,
                'course_name' => $cn
            ]);

            $model->save();
        }
        redirect()->to('/maps');
    }
}
