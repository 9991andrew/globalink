<?php
include 'includes/mw_init.php';

$playerQuest = new PlayerQuest((int)$_POST['quest_id'], (int)$_POST['player_id']);
$playerQuest->addToAutomarks((int)$_POST['quest_id'], (int)$_POST['player_id'], '');

echo json_encode(['error' => false]);
exit();
?>
