<?php
/**
 * quest_details.php
 * This handles showing information about a quest that a player has accepted.
 * Perhaps this can show related items, eventually. Also, showing rewards is likely useful.
 * 
 */
include 'includes/mw_init.php';

header('Content-type: application/json');
$data = array (
	"message" => "",
	"error" => false
);

if (!isset($_POST['questId'])) {
	$data['message'] = "No quest specified.";
	$data['error'] = true;
	echo json_encode($data);
	exit();	
}

// Load a player object to make sure we have up-to-date data
try {
    $player = new Player($_SESSION['playerId']);
} catch (Exception $e) {
    $data['message'] = $e->getMessage();
    $data['error'] = true;
    echo json_encode($data);
    exit();
}

$playerQuest = $player->getPlayerQuestWithId($_POST['questId']);
if ($playerQuest == false) {
    $data['message'] = _("You do not currently have this quest.");
    $data['error'] = true;
    echo json_encode($data);
    exit();
}

// Handle request to drop a quest
if (isset($_POST['action']) && $_POST['action']=='drop') {
    try {
        $playerQuest->drop();
    } catch (Exception $e) {
        $data['message'] = $e->getMessage();
        $data['error'] = true;
        echo json_encode($data);
        exit();
    }
    $data['message'] = _('Dropped Quest').' "'.$playerQuest->getName().'"';
    $data['error'] = false;
    echo json_encode($data);
    exit();
}


$html = '';

// Probably a little wasteful to instantiate an NPC object just to get the name, but I think it's important.
try {
    $npc = new Npc($playerQuest->getGiverNpcId());
} catch (InvalidNpcException $e) {
    $data['message'] = $e->getMessage();
    $data['error'] = true;
    echo json_encode($data);
    exit();
}

$html .= '<div id="npcInfo'.$npc->getId().'" class="flex justify-center xs:justify-start flex-wrap xs:flex-nowrap mt-5 mb-2">';
// I think I don't want the image of the NPC here, because I want this dialog to have
// a different feel than when interacting with an NPC.
// If I change my mind, uncomment the lines below.
//    $html .= '<div class="xs:pr-2">' .
//    '<img class="w-auto h-32 xs:w-40 xs:h-auto mx-auto xs:mx-0" alt="NPC Icon" src="'.$npc->getImageFilename().'" />' .
//    '</div>';

$html.='<div class="w-full"><span class="block text-lg font-bold mb-2">'.$npc->getName();
$html.='&nbsp; <span class="text-sm opacity-80 font-normal">'._("Level").'&nbsp;<span class="npcLevelNumber">'.$npc->getLevel().'</span></span></span>';

$html.='<div class="managementContent">'.$playerQuest->getContent().'</div>';

// Show required items
$questItems = $playerQuest->getQuestItems();
$itemInfo = "";
if (sizeof($questItems)) {
    $itemInfo = '<div class="mt-4 mb-2 rounded-lg p-2 bg-gray-500/20">'._("You need the following to complete this quest") .
        '<ul class="mx-1 ml-2">';
    foreach($questItems as $questItem) {
        $itemInfo.='<li>';
        if ($player->hasItem($questItem->getId())) {
            $itemInfo.='<i class="fas fa-check-circle text-green-500 dark:text-green-400" style="margin-right:8px"></i>';
        } else {
            $itemInfo.='<i class="fas fa-times-circle text-red-600 dark:text-red-500" style="margin-right:8px"></i>';
        }
        $itemInfo.=$questItem->getName().'</li>';
    }
    $itemInfo .= '</ul></div>';
}
$html.= $itemInfo;

// Show the quest rewards
if (($playerQuest->getRewardProfessionXP() + $playerQuest->getRewardXP() + $playerQuest->getRewardMoney() + sizeof($playerQuest->getRewardItems())) > 0) {
    $html .= '<div class="insetSection">';
    $html .= '<h3>'._("Reward").'</h3>';
    if ($playerQuest->getRewardMoney() > 0) {
        $html .= '<div><strong>'._("Money").': </strong>$'.$playerQuest->getRewardMoney().'</div>';
    }
    if ($playerQuest->getRewardXP() > 0) {
        $html .= '<div><strong>'._("Experience").': </strong>$'.$playerQuest->getRewardXP().'</div>';
    }
    if ($playerQuest->getRewardProfessionXP() > 0) {
        $html .= '<div><strong>'._("Profession XP").': </strong>$'.$playerQuest->getRewardProfessionXP().'</div>';
    }
    if (sizeof($playerQuest->getRewardItems()) > 0) {
        $html .= '<div><strong>'._("Items").': </strong></div>';
        $html .= '<ul>';
        foreach ($playerQuest->getRewardItems() as $item) {
            $html .= '<li class="flex items-center mr-2">';
            $html .= '<img class="h-6 mr-2" alt="'._("Item icon").'" src="'.$item->getImageFilename().'" />';
            $html .= $item->getName();
            if ($item->getAmount() > 1) {
                $html .= '<span class="ml-2 opacity-60 text-sm"> ('.$item->getAmount().')</span>';
            }
            $html .= '</li>';
        }
        $html .= '</ul>';
    }

    $html .= '</div>';//.insetSection
}

// Close NPC info
$html.='</div></div><!--npcInfo-->';

// Add a button to drop the quest.
$html .= '<div class="flex flex-col w-60 m-auto mt-4 space-y-3 items-center text-sm leading-4">
    <button type="button" class="btn destructive w-full" id="questDrop'.$playerQuest->getId().'">' .
    '<i class="fas fa-trash text-lg"></i><br>'._("Drop Quest").'</button>' .
    '</div>';
$data['html'] = $html;
$data['title'] = $playerQuest->getName();
$data['error'] = false;
echo json_encode($data);
exit();
