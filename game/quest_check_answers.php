<?php
/**
 * quest_check_answers.php
 * Receives a player's answers for a quest that requires a form submission and evaluates whether the
 * answers are correct. If so, the quest is completed and the player receives an award.
 *
 * Returns the message indicating success, npc response, and rewards.
 *
 * If there's a failure, return an error message.
 */
include 'includes/mw_init.php';

header('Content-type: application/json');
$data = array(
    "message" => "",
    "error" => false
);

if (!isset($_POST['questId'])) {
    // Don't localize
    $data['message'] = "No Quest ID was specified.";
    $data['error'] = true;
    echo json_encode($data);
    exit();
}

// Load a player object to make sure we have up-to-date data
try {
    $player = new Player($_SESSION['playerId']);
} catch (Exception $e) {
    $data['error'] = true;
    $data['message'] .= $e->getMessage() . "\n<br>";
    echo json_encode($data);
    exit();
}

$playerId = $player->getId();
$mapId = $player->getMapId();
$x = $player->getX();
$y = $player->getY();



// Make a PlayerQuest object.
try {
    $playerQuest = new PlayerQuest((int)$_POST['questId'], $playerId);
} catch (Exception $e) {
    $data['error'] = true;
    $data['message'] .= $e->getMessage() . "\n<br>";
    echo json_encode($data);
    exit();
}

// If this is a coordinates quest, we handle it a bit differently as all that is required is to be at the correct location.
if ($playerQuest->getResultTypeId() == 13) {
    // We use try to handle errors if the quest is not configured correctly as most won't be at first.
    try {
        if ($playerQuest->isCorrectLocation($player->getMapId(), $player->getX(), $player->getY())) {
            // We can skip this, and go straight to success
            // $html = $playerQuest->checkAnswers($_POST);
            $html = $playerQuest->questSuccess();
            $data['title'] = $playerQuest->getName();
            $data['html'] = $html;
            echo json_encode($data);
            exit();
        } else {
            $data['message'] = _("You are not at the right location to complete this quest.");
            $data['error'] = true;
        }
        echo json_encode($data);
        exit();
    } catch(Exception $e) {
        $data['error'] = true;
        $data['message'].=$e->getMessage()."\n<br>";
        echo json_encode($data);
        exit();
    }
}



// Sanity check to avoid creative hackers from completing quests they aren't eligible for completion
// Quests have a target NPC unless they are coordinate quests which don't apply here.
if ( is_null($playerQuest->getTargetNpcId()) ) {
    $data['error'] = true;
    // Don't localize
    $data['message'] = "This quest does not have a target NPC so answers cannot be checked.";
    echo json_encode($data);
    exit();
}

// Make an NPC object. If the NPC doesn't exist, throw an error and handle it gracefully.
try {
    $targetNpc = new Npc($playerQuest->getTargetNpcId());
} catch (Exception $e) {
    $data['error'] = true;
    $data['message'] .= $e->getMessage() . "\n<br>";
    echo json_encode($data);
    exit();
}

// If there is a target NPC, check that the NPC lives on the player's current maptile
if (!$targetNpc->isNpcFoundAt($mapId, $x, $y)) {
    $data['error'] = true;
    // Don't localize
    $data['message'] .= "NPC " . $targetNpc->getId() . " does not exist on the current tile.<br>";
    echo json_encode($data);
    exit();
}

$html = '';
// Show the NPC's image, name, and level
$html .= '<div id="npcInfo'.$targetNpc->getId().'" class="flex justify-center xs:justify-start flex-wrap xs:flex-nowrap mt-5 mb-2">' .
    '<div class="xs:pr-2">' .
    '<img class="w-auto h-32 xs:w-40 xs:h-auto mx-auto xs:mx-0" alt="NPC Icon" src="'.$targetNpc->getImageFilename().'" />' .
    '</div>';
$html .= '<div class="w-full"><span class="block text-lg font-bold mb-2">' . $targetNpc->getName();
$html .= '&nbsp; <span class="text-sm opacity-80 font-normal">'._("Level").'&nbsp;<span class="npcLevelNumber">' . $targetNpc->getLevel() . '</span></span></span>';

// PlayerQuests accepts the submitted data and determines if the answers were correct.
// It will handle quest completion if successful and return HTML of the results, pass or fail.
$html .= $playerQuest->checkAnswers($_POST);

$html.='</div></div><!--npcInfo-->';
if (isset($commitButton)) $html.=$commitButton;
$data['html'] = $html;
$data['title'] = $playerQuest->getName();
echo json_encode($data);
exit();

