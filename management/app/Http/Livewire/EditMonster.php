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
        $this->editing = $this->makeBlankObject();

        $configs = Wsnlp::select()->get();
        $ids = [];
        foreach ($configs as &$c) {
            $ids[] = $c->quest_id;
        }

        $questsDB = Quest::whereIn('quest_result_type_id', [11, 14], 'or')->whereNotIn('id', $ids, 'and')->orderBy('name')->get();
        $this->quests = [];
        foreach ($questsDB as &$q) {
            $this->quests[] = [$q->id, $q->name];
        }

        if (! isset($this->languages)) {
            $contents = file_get_contents('https://ws-nlp.vipresearch.ca/bridge/language_list.php');
            $this->languages = (array) json_decode($contents);
        }
    }

    public function rules(): array
    {
        return [
            'editing.quest_id' => ['required', 'numeric', 'gt:0'],
            'editing.name' => ['required', 'max:255'],
            'editing.maxbpm' => ['nullable', 'boolean'],
            'editing.autobpm' => ['nullable', 'boolean'],
            'editing.ngrampos' => ['nullable', 'boolean'],
            'editing.canonical' => ['nullable', 'boolean'],
            'editing.timeout' => ['numeric', 'required'],
            'editing.lang' => ['max:5', 'required'],
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
        // remove this quest from the list
        foreach ($this->quests as $k => $v) {
            if ($this->editing->quest_id == $v[0]) {
                unset($this->quests[$k]);
            }
        }
        $this->showModal = false;
        $this->emit('rerenderParent');
        $this->cleanup();
    }

    public function updateName()
    {
        $name = 'undefined';
        foreach ($this->quests as $k => $v) {
            if ($this->editing->quest_id == $v[0]) {
                $name = $v[1];
                break;
            }
        }
        $this->editing->name = $name;

        // Since adding the map config option, there may be defaults for this quest.
        $quest = Quest::where('id', $this->editing->quest_id)->get();
        foreach ($quest as $q) {
            $npcid = $q->giver_npc_id;
        }

        $npc = Npc::where('id', $npcid)->get();
        foreach ($npc as $n) {
            $mapid = $n->map_id;
        }

        $config = Wsnlp::where('quest_id', $mapid)->where('ismap', 1)->get();
        if ($config->count() == 0) {
                $this->editing->autobpm = 0;
                $this->editing->maxbpm = 0;
                $this->editing->ngrampos = 0;
                $this->editing->canonical = 0;
                $this->editing->lang = 'en';
                $this->editing->timeout = 600;
        } else {
            foreach ($config as $c) {
                $this->editing->autobpm = $c->autobpm;
                $this->editing->maxbpm = $c->maxbpm;
                $this->editing->ngrampos = $c->ngrampos;
                $this->editing->canonical = $c->canonical;
                $this->editing->lang = $c->lang;
                $this->editing->timeout = $c->timeout;
            }
        }
    }
}
