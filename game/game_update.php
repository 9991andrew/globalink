<?php
/**
 * This handles submitting and returning updates to game state via AJAX requests.
 * The idea is for all requests to be coalesced and go through this file so that
 * there is a minimized number of HTTP requests going from client to server.
 */
include 'includes/mw_init.php';

header('Content-type: application/json');
$data = array (
	"message" => "",
	"error" => true
);

// In theory I should be able to load this from the session, but it's not working.
// I probably have to make a new player object anyways to make sure it's up to date.

try {
    if (!isset($_SESSION['playerId'])) throw new Exception (_('Your session has expired and you have been logged out.').'<br><a class="link" href="login.php">'._('Log in to').' MEGA World</a>');
    $player = new Player($_SESSION['playerId']);
    $guardian = new Guardian();
} catch (Exception $e) {
    $data['error'] = true;
    $data['message'] = $e->getMessage();
    echo json_encode($data);
    exit();
}

if (isset($_POST['srcSlot']) && isset($_POST['destSlot'])) {
    try {
        $player->getPlayerBag()->swapBagSlots((int)$_POST['srcSlot'], (int)$_POST['destSlot']);
    } catch (Exception $e) {
        $data['error'] = true;
        $data['message'] = $e->getMessage();
        echo json_encode($data);
        exit();
    }
}
//Money
if (isset($_POST['moneyUpdate']) && is_numeric($_POST['moneyUpdate'])) {
    $moneyUpdate = (int) $_POST['moneyUpdate'];
    try{
        $data['money'] = $player->addMoney($moneyUpdate);
        $data['message'] ="money changed successfully";
        $data['error'] = false;
    }catch(Exception $e) {
        $data['error '] = true;
        $data['message'] = $e->getMessage();    
    }
    echo json_encode($data);    
    exit();
}


// Move the player
if (isset($_POST['mvX'])) {
	$xInc=(int)$_POST['mvX'];
	$yInc=0;
	if (isset($_POST['mvY'])) $yInc=(int)$_POST['mvY'];

	$player->move($xInc, $yInc);
}

if (isset($_POST['usePortal'])) {
	if ($player->usePortal((int)$_POST['usePortal'])) {
		$data['usedPortal']=true;
	} else {
        $data['error'] = true;
        $data['message'] = _("You aren't allowed to use this portal.");
        echo json_encode($data);
        exit();
    }
}

// Force update of mapId if it changed
if ($player->getMapId() != $_SESSION['mapId']) {
	$_SESSION['mapId'] = $player->getMapId();
	$map = new Map($player->getMapId());
    $data['mapId'] = $map->getId();
	$data['mapName'] = $map->getName();
	$data['mapTiles'] = $map->getClientTilesData();
	$data['mapNPCs'] = $map->getClientMapNpcsData();
}

// Handle inserting new chat messages
// MapId is retrieved from the session... could probably do this with player x and y, however those aren't being updated now.
if (isset($_POST['newMessage'])) {
	$chatTo = null;

	if (isset($_POST['chatTo']) && strlen($_POST['chatTo'])) $chatTo = (int)$_POST['chatTo'];
	// Could use try catch here to handle error conditions
	$chatId = $player->sendMessage($_POST['newMessage'], $chatTo);
	$data['chatId'] = $chatId;
}

if (isset($_POST['guardianMessage'])) {
	$guardianChatId = $guardian->sendMessageGuardian($_POST['guardianMessage'],$player->getId(),$guardian->getGuardianIdByPlayerId($player->getId()));
    $data['guardianChatId'] = $guardianChatId;
    $data['guardianMessages'] = $guardian->getMessagesGuardian($player->getId());
    if($guardian->checkForSummary($player->getId())) {
        $data['guardianMessages'] = $guardian->getMessagesGuardian($player->getId());
    }
} else {
    $data['guardianChatId'] = null;
    $data['guardianMessages'] = array();
    if($guardian->checkForSummary($player->getId())) {
        $data['guardianMessages'] = $guardian->getMessagesGuardian($player->getId());
    }
}

// For sending the unique username for providing better localstorage
$data['userid'] = $player->getId();

// Check the player hash that we received.
if (isset($_POST['plH'])) {
	$playerData = $player->getClientPlayerData();
	$playerHash = md5(json_encode($playerData));

	// Send back fresh player data if the hashes don't match, meaning the client-side data is outdated
	if ($_POST['plH'] != $playerHash) {
	    $data['player'] = $playerData;
        $data['playerHash'] = $playerHash;
    }
}

// Check the player bag hash we received
if (isset($_POST['pbH'])) {
    $playerBagData = $player->getPlayerBag()->getClientSlotItemsData();
    $playerBagHash = md5(json_encode($playerBagData));

    // Send back fresh player data if the hashes don't match, meaning the client-side data is outdated
    if ($_POST['pbH'] != $playerBagHash) {
        $data['playerBag'] = $playerBagData;
        // originally we calculated these client-side but they are prone to being a bit different
        $data['playerBagHash'] = $playerBagHash;
    }
}

// Return the updated list of other players' coordinates
// I guess we only *really* need ones that have changed since last time, but the plan is also to use
// the absence of a player to determine that we need to remove them from the map
$data['others'] = $player->getVisiblePlayerPositions();

// If any of the IDs in 'others' is NOT found in $_SESSION['knownPlayerIds'], we return the data for those players
// and add them into the knownPlayerIds array
$data['newP'] = array();

foreach($data['others'] as $otherPlayer) {
	if (!in_array($otherPlayer[0], $_SESSION['knownPlayerIds'])) {
		// We have to get the information for this player and return it to the client
		$p = new Player( $otherPlayer[0], true);
		array_push( $data['newP'], $p->getClientOtherPlayerData());
		// Add to the array of players we know about
		array_push($_SESSION['knownPlayerIds'], $p->getId());
	}
}

// Get new chat messages
// If there isn't a lastChatId set in the SESSION, we can set it to NULL
//getMessages(int $mapId, int $playerId, int $afterChatId=null, string $startTime) {
if (!isset($_SESSION['lastChatId'])) $_SESSION['lastChatId'] = null;
if (!isset($_SESSION['loginTime'])) $_SESSION['loginTime'] = date('Y-m-d H:i:s');
$data['chatMessages'] = $player->getMessages((int)$_SESSION['lastChatId']);

// If the map changed, add an item that shows the player has entered a new map
if (isset($map)) {
    $mapNotificationMessage = array(
        'id'=>null,
        'map_id'=>$map->getId(),
        'message'=>'<span class="opacity-70">Entered</span> '.$map->getName(),
        'message_time'=>date('Y-m-d H:i:s'),
        'player_id_src'=>null,
        'player_id_tgt'=>null,
        'src_name'=>'SYSTEM_MESSAGE',
        'tgt_name'=>null,
        'x'=>null,
        'y'=>null,
    );
    array_push($data['chatMessages'], $mapNotificationMessage);
}

// Update the keylog
if (isset($_POST['keys'])) {
    $keys = json_decode($_POST['keys']);
    // Send this array to the logKeys() method
    try {
        Data::keylog($player->getId(), $keys);
    } catch (Exception $e) {
        $data['error'] = true;
        $data['message'] = $e->getMessage();
        echo json_encode($data);
        exit();
    }
}

// Include the received timestamp in the server response so we can invalidate outdated data.
if (isset($_POST['requestTime'])) {
    $data['requestTime'] = $_POST['requestTime'];
}

$data['error'] = false;
echo json_encode($data);
exit();