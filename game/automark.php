<?php
$logging = false; // For debugging.

if ($logging) {
    $file = fopen('automark.log', 'w');
    fwrite($file, 'Starting...'.PHP_EOL);
}

// Need 2 arguments, quest ID and player ID.
$qid = $argv[1];
$pid = $argv[2];

// Get connected to the database.
$config = parse_ini_file('/var/www/html/.megadb.ini');
$dsn = "mysql:host=".$config['DB_HOST'].";port=".$config['DB_PORT'].";dbname=".$config['DB_NAME'];
$pdo = new PDO($dsn, $config['DB_USER'], $config['DB_PASSWORD'],
               [PDO::MYSQL_ATTR_INIT_COMMAND =>"SET time_zone = '".date('P')."'"]);
$pdo->setAttribute(PDO::FETCH_ASSOC, PDO::ATTR_DEFAULT_FETCH_MODE);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, PDO::ERRMODE_EXCEPTION);

// Default configuration for WS-NLP service.
$wsnlp = [
    'maxbpm' => 0,
    'autobpm' => 0,
    'ngrampos' => 0,
    'canonical' => 0,
    'timeout' => 600,
    'lang' => 'en'
];

// Check database for custom general map configuration.
$query = "SELECT wc.* FROM wsnlp_config wc, quests, npcs ".
       "WHERE wc.quest_id = npcs.map_id ".
       "AND wc.ismap = 1 ".
       "AND npcs.id = quests.giver_npc_id ".
       "AND quests.id = ?;";
$stmt = $pdo->prepare($query);
$stmt->execute([$qid]);
if ($stmt->rowCount() > 0) {
    $wsnlp = $stmt->fetch();
}

// Check database for custom quest configuration.
$query = "SELECT * FROM wsnlp_config WHERE quest_id = ? AND ismap = 0;";
$stmt = $pdo->prepare($query);
$stmt->execute([$qid]);
if ($stmt->rowCount() > 0) {
    $wsnlp = $stmt->fetch();
}

if ($logging) {
    fwrite($file, 'WSNLP config: '.$qid.PHP_EOL);
    fwrite($file, var_export($wsnlp, true).PHP_EOL);
}

// Get the teacher and player answers for this quest.
$query = "SELECT qaa.id, qaa.question_id, qaa.answer, qa.answer_string " .
       "FROM quest_answer_automarks qaa, quest_answers qa " .
       "WHERE qaa.question_id = qa.id " .
       "AND qa.quest_id = ? " .
       "AND qaa.player_id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$qid, $pid]);

// To split a paragraph into sentences.
$regex = $wsnlp['lang'] == "en" ? '/(?<!Mr.|Mrs.|Dr.)(?<=[.?!;:])\s+/' : "(\?|\.|!|।)";

while ($row = $stmt->fetch()) {

    // Auto BPM means old method unless both answers are multiple sentences long.
    if ($wsnlp['autobpm']) {
        $keys = count(preg_split($regex, $row['answer_string'], -1, PREG_SPLIT_NO_EMPTY));
        $targets = count(preg_split($regex, $row['answer'], -1, PREG_SPLIT_NO_EMPTY));

        if ($keys > 1 && $targets > 1) {
            $wsnlp['maxbpm'] = 1;
        } else {
            $wsnlp['maxbpm'] = 0;
        }
        if ($logging) {
            fwrite($file, 'USING AUTOBPM = '.$wsnlp['maxbpm'].' '.$keys.' '.$targets.PHP_EOL);
        }
    }

    // Prepare for connecting to WS-NLP service.
    $json = array(
        'key' => $row['answer_string'],
        'target' => $row['answer'],
        'value' => 1, // 1 to get a number in the range of 0 to 1
        'method' => $wsnlp['maxbpm'] ? 'bpm' : 'old', // use Bipartite Matching algorithm
        'ngram_service' => $wsnlp['ngrampos'] ? 'ngrampos' : '', // use of N-gram POS service
        'canonical' => $wsnlp['canonical'] ? 'canonical' : '', // use of lemmatization
        'language' => $wsnlp['lang'], // en (English), fr (French), hi (Hindi)
        'email' => 'megaworld@vipresearch.ca'
    );
    $json = json_encode($json);

    $context = array('http' =>
                     array(
                         'method' => 'POST',
                         'timeout' => $wsnlp['timeout'],
                         'header' => 'Content-Type: application/json',
                         'content' => $json
                     )
    );
    $context = stream_context_create($context);

    // Get the calculation results and store them.
    $contents = file_get_contents('https://ws-nlp.vipresearch.ca/bridge/', false, $context);
    $contents = json_decode($contents);

    $sim = 0;
    if (isset($contents->similarity)) {
        $sim = $contents->similarity;
    }

    $query = "UPDATE quest_answer_automarks SET automark = ? WHERE id = ?;";
    $stmt2 = $pdo->prepare($query);
    $stmt2->execute([$sim, $row['id']]);

    if ($logging) {
        fwrite($file, 'Question '.$row['question_id'].' got mark '.$sim.PHP_EOL);
    }
}
if ($logging) {
    fclose($file);
}
?>
