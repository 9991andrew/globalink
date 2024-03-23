<?php
include 'includes/mw_init.php';

$conversation_id = $_GET["conversation_id"];
$player_id = (int)$_GET["player_id"];
$quest_id = (int)$_GET["quest_id"];
$mark = $_GET["mark"];
$crc = $_GET["crc"];

$playerQuest = new PlayerQuest($quest_id, $player_id);
$content = $playerQuest->getContent();

$salt = "Kuan-Hsing Wu & Pei-Shan Yu";
$crc1 = md5($quest_id . $salt . $player_id . $content);

$plainText = $quest_id . $crc1 . $player_id . $conversation_id . $mark;
$crc2 = md5($plainText);

if ($crc2 == $crc) {
    $playerQuest->updateAutomarks($quest_id, $player_id, $mark);
}
header('Location: map.php');
?>
