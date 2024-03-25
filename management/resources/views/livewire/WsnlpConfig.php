<?php

namespace App\Http\Livewire;
use Livewire\Component;

class WsnlpConfig extends Component
{
    public $data = [];
    public $quest_id;
    public $maxbpm;
    public $ngrampos;
    public $canonical;
    public $timeout;
    public $language;
    public $autobpm;

    protected $rules = [
        'quest_id' => 'numeric|required',
        'maxbpm' => 'nullable|boolean',
        'autobpm' => 'nullable|boolean',
        'ngrampos' => 'nullable|boolean',
        'canonical' => 'nullable|boolean',
        'timeout' => 'numeric|required',
        'language' => 'required|max:2',
    ];

    /**
     * Loads the view for this page, which needs languages available and long answer quests.
     */
    public function render() {

        $contents = file_get_contents('https://ws-nlp.vipresearch.ca/bridge/language_list.php');
        $languages = json_decode($contents);

        $db = new \mysqli(env('DB_HOST'), env('DB_USERNAME'), env('DB_PASSWORD'), env('DB_DATABASE'), env('DB_PORT'));

        $query = "SELECT id, name FROM quests WHERE quest_result_type_id = 11 OR quest_result_type_id = 14 ORDER BY name;";
        $quests = $db->query($query)->fetch_all();

        if (!isset($this->quest_id)) {
            $this->checkConfig($quests[0][0], $db);
        }

        $this->data = [
            'languages' => $languages,
            'quests' => $quests,
            'autobpm' => $this->autobpm
        ];

        return view('livewire.wsnlp-config', $this->data);
    }

    /**
     * Gets any config data from the DB for this quest, if exists. Else, defaults.
     */
    private function checkConfig($qid, $db = null) {

        if (!$db) {
            $db = new \mysqli(env('DB_HOST'), env('DB_USERNAME'), env('DB_PASSWORD'), env('DB_DATABASE'), env('DB_PORT'));
        }

        $query = "SELECT * FROM wsnlp_config WHERE quest_id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param('i', $qid);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        $this->quest_id = $qid;

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();

            $this->maxbpm = $row['maxbpm'];
            $this->autobpm = $row['autobpm'];
            $this->ngrampos = $row['ngrampos'];
            $this->canonical = $row['canonical'];
            $this->timeout = $row['timeout'];
            $this->language = $row['lang'];

        } else {
            $this->maxbpm = 0;
            $this->autobpm = 0;
            $this->ngrampos = 0;
            $this->canonical = 0;
            $this->timeout = 600;
            $this->language = 'en';
        }

        $result->close();
        $db->close();
    }

    /**
     * Validate the input. If the map has changed, the form should relfect the 
     * DB values for the selected map.
     */
    public function updated($propertyName, $value) {

        $data = $this->validateOnly($propertyName);

        if ($propertyName == 'quest_id') {
            $this->checkConfig($this->quest_id);
        }

        if ($propertyName == 'autobpm') {
            $this->autobpm = $value ? 1 : 0;
        }
    }

    /**
     * Form has been submitted. If valid input, then save to DB.
     */
    public function save() {

        $data = $this->validate();

        $qid = (int)$data['quest_id'];
        $bpm = $data['maxbpm'] ? 1 : 0;
        $autobpm = (int)$data['autobpm'];
        $ngram = $data['ngrampos'] ? 1 : 0;
        $canon = $data['canonical'] ? 1 : 0;
        $to = (int)$data['timeout'];
        $lang = $data['language'];

        $db = new \mysqli(env('DB_HOST'), env('DB_USERNAME'), env('DB_PASSWORD'), env('DB_DATABASE'), env('DB_PORT'));

        $query = "SELECT * FROM wsnlp_config WHERE quest_id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param('i', $qid);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows > 0) {
            $query = "UPDATE wsnlp_config SET maxbpm=?, ngrampos=?, canonical=?, timeout=?, lang=?, autobpm=? WHERE quest_id=?";
            $stmt = $db->prepare($query);
            $stmt->bind_param('iiiisii', $bpm, $ngram, $canon, $to, $lang, $autobpm, $qid);

        } else {
            $query = "INSERT INTO wsnlp_config (quest_id, maxbpm, ngrampos, canonical, timeout, lang, autobpm) VALUES (?,?,?,?,?,?,?)";
            $stmt = $db->prepare($query);
            $stmt->bind_param('iiiiisi', $qid, $bpm, $ngram, $canon, $to, $lang, $autobpm);

        }
        $result->close();
        $stmt->execute();
        $stmt->close();
        $db->close();

        redirect('/');
    }
}
