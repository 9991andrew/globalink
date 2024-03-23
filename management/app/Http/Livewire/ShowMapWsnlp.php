<?php

namespace App\Http\Livewire;

use App\Models\Wsnlp;
use App\Models\Map;
use Livewire\Component;

class ShowMapWsnlp extends Component
{
    public $objectClass = "App\Models\Wsnlp";
    public $data = [];
    public $questId;
    public $name;
    public $maxbpm;
    public $ngrampos;
    public $canonical;
    public $timeout;
    public $language;
    public $autobpm;

    protected $rules = [
        'questId' => 'numeric|required',
        'maxbpm' => 'nullable|boolean',
        'autobpm' => 'nullable|boolean',
        'ngrampos' => 'nullable|boolean',
        'canonical' => 'nullable|boolean',
        'timeout' => 'numeric|required',
        'language' => 'required|max:2',
    ];

    public function mount($map) {

        $this->questId = (int) $map;

        $map = Map::where('id', $this->questId)->get();
        foreach ($map as $m) {
            $this->name = $m->name;
        }

        $contents = file_get_contents('https://ws-nlp.vipresearch.ca/bridge/language_list.php');
        $languages = json_decode($contents);

        $row = [
            'name' => $this->name,
            'maxbpm' => 0,
            'autobpm' => 0,
            'ngrampos' => 0,
            'canonical' => 0,
            'timeout' => 600,
            'lang' => 'en'
        ];

        $config = Wsnlp::where('quest_id', $this->questId)->where('ismap', 1)->get();
        foreach ($config as &$c) {
            $row = $c;
        }

        $this->maxbpm = $row['maxbpm'];
        $this->autobpm = $row['autobpm'];
        $this->ngrampos = $row['ngrampos'];
        $this->canonical = $row['canonical'];
        $this->timeout = $row['timeout'];
        $this->language = $row['lang'];

        $this->data = [
            'config' => $row,
            'languages' => $languages,
            'mapName' => $this->name
        ];
    }

    /**
     * Loads the view for this page, which needs languages available and long answer quests.
     */
    public function render() {

        return view('livewire.show-map-wsnlp', $this->data);
    }

    /**
     * Validate the input. If the map has changed, the form should relfect the 
     * DB values for the selected map.
     */
    public function updated($propertyName, $value = null) {

        $data = $this->validateOnly($propertyName);

        if ($propertyName == 'autobpm') {
            $this->autobpm = $value ? 1 : 0;

        } else if ($propertyName == 'maxbpm') {
            $this->maxbpm = $value ? 1 : 0;

        } else if ($propertyName == 'ngrampos') {
            $this->ngrampos = $value ? 1 : 0;

        } else if ($propertyName == 'canonical') {
            $this->canonical = $value ? 1 : 0;
        }
    }

    /**
     * Form has been submitted. If valid input, then save to DB.
     */
    public function save() {

        $this->validate();
        Wsnlp::where('quest_id', $this->questId)->where('ismap', 1)->delete();

        $model = $this->objectClass::make([
            'quest_id' => $this->questId,
            'name' => $this->name,
            'maxbpm' => $this->maxbpm,
            'autobpm' => $this->autobpm,
            'ngrampos' => $this->ngrampos,
            'canonical' => $this->canonical,
            'timeout' => $this->timeout,
            'lang' => $this->language,
            'ismap' => 1
        ]);

        $model->save();
        redirect()->to('/maps');
    }
}
