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
    $data["error"] = false;
}

if(isset($_POST['monsterDefeated']) && $_POST['monsterDefeated'] == 'true' && isset($_POST['itemId'])) {

    $itemId = (int) $_POST['itemId'];
    if($itemId) {
        try{
            $player->addItem($itemId);
            $data['playerUpdated'] = true;
            $data['message'] = "Monster defeated and item added";
        }catch(Exception $e){
            $data['playerUpdated'] = false;
            $data['message'] = "Failed to add item to inventory: " . $e->getMessage();
        }
    } else {
        $data['playerUpdated'] = false;
        $data['message'] = "Monster defeated, but no item dropped.";
    }
    echo json_encode($data);
    exit();
} else if(isset($_POST['monsterDefeated']) && $_POST['monsterDefeated'] == 'false' && isset($_POST['playerHealth'])) {
    $playerHealth = (int) $_POST['playerHealth'];
    
        try {
            $player->setHealth($playerHealth);
            $data['playerUpdated'] = true;
            $data['message'] = "HP of player was updated";
            $data['playerHP'] = $player->getHealth();
            echo json_encode($data);
            exit();
        } catch(Exception $e) {
            $data['playerUpdated'] = false;
            $data['message'] = "Monster was defeated but something happened.";
            echo json_encode($data);
            exit();
        }
    }
    
    $mapId = $player->getMapId();
    $x = $player->getX();
    $y = $player->getY();
    $map = new Map($mapId);

    $monsters = [];
    $monsters = Monster::fetchAllMonstersForMap($mapId);
    $tileProbability = 5;
    $spawnedMonsters = Monster::spawnMonsters($monsters, $tileProbability, $map->getTiles(), count($monsters));
    $gearForPlayer = Monster::getGearForPlayer($_SESSION['playerId']);

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
            'drop_rate' => ($monster->getDropRate()) ,
            'x' => $monster->getX(),
            'y' => $monster->getY(),
        ];
    }, $spawnedMonsters);
    $data['gear'] = $gearForPlayer;
    $data['message'] = "Monsters encountered!";
    $data['error'] = false;
    echo json_encode($data);
    exit();
    } else {
        $data['error'] = false;
        $data['message'] = "No monsters on map";
        echo json_encode($data);
        exit();
}   






