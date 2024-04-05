<?php
include 'includes/mw_init.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-type: application/json');
$data = array(
    "message" => "",
    "error" => false,
);


// Load a player object to make sure we have up-to-date data
try {
    $player = new Player($_SESSION['playerId']);
} catch (Exception $e) {
    $data['error'] = true;
    $data['message'] .= $e->getMessage() . "\n<br>";
    echo json_encode($data);
    exit();
}

if (!isset($_POST["mapId"])) {
    $data["message"] = "no map id given";
    $data["error"] = true;
}

$mapId = $player->getMapId();
$x = $player->getX();
$y = $player->getY();
$map = new Map($mapId);
$monsters = [];
$monsters = Monster::fetchAllMonstersForMap($mapId);
$tileProbability = 5;
$spawnedMonsters = Monster::spawnMonsters($monsters, $tileProbability, $map->getTiles(), count($monsters));


if (!empty($spawnedMonsters)) {
    $data['monsters'] = array_map(function($monster) {
        $itemDetails = $monster->getItemDetailsById($monster->getItemId());
        return [
            'id' => $monster->getId(),
            'name' => $monster->getName(),
            'hp' => $monster->getHp(),
            'item' => [ 
                'id' => $monster->getItemId(),
                'name' => $itemDetails['name'],
                'description' => $itemDetails['description'], 
            ],
            'drop_rate' => $monster->getDropRate(),
            'x' => $monster->getX(),
            'y' => $monster->getY(),
        ];
    }, $spawnedMonsters);

    $data['message'] = "Monsters encountered!";
    $data['error'] = false;
} else {
    $data['message'] = "No monsters encountered.";
    $data['error'] = true;
}



echo json_encode($data);
exit();

