<?php
/**
 * npc_object.php
 * Shows details about a particular object from an NPC.
 * These include items (to get/buy), professions (to enter), and quests(to accept or complete)
 *
 * NPC information will be shown if one is specified.
 *
 * Thorough sanity checking is performed in this file but this is mostly to guard against clever
 * hackers or bugs and most of these kinds of errors will not be localized.
 */
include 'includes/mw_init.php';

header('Content-type: application/json');
$data = array(
    "message" => "",
    "error" => false
);

// This makes it easier to test. This can be removed in production
if (isset($_GET['objectType']) && !isset($_POST['objectType'])) $_POST['objectType'] = $_GET['objectType'];
if (isset($_GET['objectId']) && !isset($_POST['objectId'])) $_POST['objectId'] = $_GET['objectId'];
if (isset($_GET['npc']) && !isset($_POST['npc'])) $_POST['npc'] = $_GET['npc'];
// Quantity when purchasing items, but a non-zero value also specifies committing to an action,
// such as accepting a quest or profession.
if (isset($_GET['qty']) && !isset($_POST['qty'])) $_POST['qty'] = $_GET['qty'];

if (!isset($_POST['qty'])) {
    // Default qty to null, which only shows information about an object
    $_POST['qty'] = null;
}
$qty = $_POST['qty'];

if (!isset($_POST['npc'])) {
    $data['message'] = "No npc ID was specified.";
    $data['error'] = true;
    echo json_encode($data);
    exit();
}

if (!isset($_POST['objectType']) || !isset($_POST['objectId'])) {
    $data['message'] = "No object type and object ID was specified.";
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

$mapId = $player->getMapId();
$x = $player->getX();
$y = $player->getY();

// Make an NPC object. If the NPC doesn't exist, throw an error and handle it gracefully.
try {
    $npc = new Npc((int)$_POST['npc']);

} catch (Exception $e) {
    $data['error'] = true;
    $data['message'] .= $e->getMessage() . "\n<br>";
    echo json_encode($data);
    exit();
}

// Check that the NPC lives on the player's current maptile
if (!$npc->isNpcFoundAt($mapId, $x, $y)) {
    $data['error'] = true;
    $data['message'] .= "NPC " . $npc->getId() . " does not exist on the current tile.<br>";
    echo json_encode($data);
    exit();
}

// Placeholder for title. Will be replaced based on what type this object is.
$data['title'] = "";
$html = '';
// Show the NPC's image, name, and level
$htmlOpen = '<div id="npcInfo'.$npc->getId().'" class="flex justify-center xs:justify-start flex-wrap xs:flex-nowrap mt-5 mb-2">';
$npcImgDiv = '<div class="xs:pr-2">' .
                '<img class="w-auto h-32 xs:w-40 xs:h-auto mx-auto xs:mx-0" alt="NPC '._('Icon').'" src="'.$npc->getImageFilename().'" />' .
                '</div>';
$hideNpcImgDiv = false;
$html .= '<div class="w-full"><span class="block text-lg font-bold mb-2">' . $npc->getName();
$html .= '&nbsp; <span class="text-sm opacity-80 font-normal">'._('Level').'&nbsp;<span class="npcLevelNumber">' . $npc->getLevel() . '</span></span></span>';
// Placeholder for commitButton text
$commitButton = '';
// Now we get the details for the specific item type
switch($_POST['objectType']) {
    case 'portal':
        // Get any portals the character has. I'm not limiting it to showing levels the player can use just yet.
        $npcPortalId = $_POST['objectId'];
        $portals = $npc->getNpcPortals();
        $npcPortal = null; // The portal will be assigned here if we find it in the list.
        foreach ($portals as $np) {
            if ($np->getId() == $npcPortalId) {
                $npcPortal = $np;
                break;
            }
        }

        if (is_null($npcPortal)) {
            $data['error'] = true;
            $data['message'] = "The specified NPC Portal ID is not available from this NPC.";
            echo json_encode($data);
            exit();
        }

        // We could call a player method that uses an NPC portal and takes their money, perhaps...
        if ($player->getMoney() < $npcPortal->getPrice()) {
            $html .=sprintf(_("It's going to cost $%d for me to get you there."), $npcPortal->getPrice())."<br>"._("You don't have enough money right now.");
            break;
        } else if ($player->getMaxLevel() < $npcPortal->getLevel()) {
            $html .= sprintf(_("You must at least be level %d for me to get you there."), $npcPortal->getLevel()).'<br>'._("Come back when you've levelled up.");
            break;
        } else {
            // Use the portal
            try {
                $player->useNpcPortal($npcPortal, $npc);
                $html .= _("Thanks! Let's get on our way!");

            } catch (Exception $e) {
                $data['error'] = true;
                $data['message'] .= $e->getMessage() . "\n<br>";
                echo json_encode($data);
                exit();
            }
        }
        break;
    case 'profession':
        $professionId = $_POST['objectId'];
        // Get any professions the character has
        $professions = $npc->getProfessionsForPlayer($_SESSION['playerId']);

        $profession = null; // The profession will be assigned here if we find it in the list.
        foreach ($professions as $prof) {
            if ($prof->getId() == $professionId) {
                $profession = $prof;
                break;
            }
        }
        if (is_null($profession)) {
            $data['error'] = true;
            $data['message'] = "The specified profession ID is not available from this NPC.";
            echo json_encode($data);
            exit();
        }

        // Now we have the profession object. Let's print out what we need from it.
        // If there's no qty, we just show information about this object.
        if (is_null($qty)) {
            $data['title'] = $profession->getName();
            $html .= $profession->showProfessionRequirements($_SESSION['playerId']);
            if ($profession->playerIsEligible($_SESSION['playerId'])) {
                if (sizeof($profession->getRequiredProfessions())) $html .= _("You are ready. Excellent!")."<br><br>";
                $html .='<div class="">'._("Would you like to join?").'</div>';
                // Annoying, this has to go outside of npcInfo to look correct
                $commitButton = '<div class="text-center mt-5 text-sm"><button type="button" class="btn npcObject" data-objecttype="profession" data-objectid="' . $profession->getId() . '" data-npc="' . $npc->getId() . '" data-qty="1">';
                $commitButton .= '<i class="fas fa-graduation-cap text-lg"></i><br>'._("Become").' ' . $profession->getName();
                $commitButton .= '</button></div>';
            } else {
                $html .= _("I'm afraid you don't meet the requirements yet.").'<br>'._("Come back when you're ready.");
            }
        } else {
            // if there is a quantity, we try to sign up for the profession.
            try {
                $player->addProfession($profession);
                $data['title'] = $profession->getName();
            } catch (Exception $e) {
                $data['error'] = true;
                $data['message'] .= $e->getMessage() . "\n<br>";
                echo json_encode($data);
                exit();
            }

            // Show some kind of congratulatory message.
            $html .= _("Congratulations on your new profession!");
            // This just looks kind of awkward here.
            // $html .= '<h3 class="mt-3 font-bold text">'.$profession->getName().'</h3>';
        }
        break;

    case 'item':
        $data['title'] = _('Item');
        $itemId = $_POST['objectId'];
        // Get any items the character has
        $items = $npc->getItemsForPlayer($_SESSION['playerId']);

        $item = null; // The item will be assigned here if we find it in the list.
        foreach ($items as $itm) {
            if ($itm->getId() == $itemId) {
                $item = $itm;
                break;
            }
        }

        if (is_null($qty)) {
            // No quantity, Present the item information to the player

            // Could do a greeting, seems a bit contrived
            if ($item->getPrice() == 0) {
                $data['title'] = $item->getName();
                $html .= _("Would you like this?");
            } else {
                $html .= _("Would you like to buy this?");
            }
            $html .= '<div class="mt-2 text-center bg-gray-400/10 dark:bg-black/10 p-3 rounded-lg shadow-inner-dark">';
                $html .= '<img class="max-h-28 m-auto" alt="Item icon" src="' . $item->getImageFilename() . '" />';
                $html .= '<div class="font-bold text-xl leading-tight">' . $item->getName() . '</div>';
                $html .= '<strong>'._('Price').': </strong>&nbsp;$' . $item->getPrice();
                if ($item->getWeight() > 0) $html .= '&nbsp;&nbsp;&nbsp;<strong>'._('Weight').': </strong>&nbsp;' . round($item->getWeight() / 1000) . 'g';
                $html .= '<div class="text-sm">' .
                         sprintf(_('You must be <strong>Level %d</strong> to use this item.'), $item->getRequiredLevel()) .
                         '</div>';

                $html .= '<p class="mt-2">' . $item->getDescription() . '</p>';

                // Annoying, this has to go outside of npcInfo to look correct
                $html.= '<div class="mt-4 text-center w-60 flex items- m-auto space-x-3">';
                $html.= '<button type="button" class="btn decr" onclick="incrBuy(-1);"><i class="text-lg fas fa-minus"></i></button>';
                if ($item->getPrice() == 0) {$buyLabel = _("Get");}
                else {$buyLabel = _("Buy").' 1 &nbsp;($' . $item->getPrice().')';}
                $html.= '<button id="buyButton" type="button" class="npcObject btn flex-1 h-full" data-objecttype="item" data-objectid="' . $item->getId() . '" data-npc="' . $npc->getId() . '" data-price="' . $item->getPrice() . '" data-qty="1">'.$buyLabel. '</button>' .
                    '<button type="button" class="btn incr" onclick="incrBuy(1);"><i class="text-lg fas fa-plus"></i></button></div>';

            $html .= '</div>';

        } else {
            // There's a quantity. Try to buy the item.
            try {
                $player->buyItem($item, (int)$qty, (int)$npc->getId(), $item->getQuestId());
                if ($item->getPrice() == 0) {
                    $html .= _('Here you go.');
                } else $html .= _('Thanks for your purchase!');
            } catch (Exception $e) {
                // Probably don't handle this as an error, but just return an NPC html message.
                $html .= '<span class="text-red-600 dark:text-red-500">'.$e->getMessage() . "</span>\n<br>";
            }
        }
        break;

    case 'quest':

        $questId = $_POST['objectId'];
        // Get any quests the character has
        $quests = $npc->getQuestsForPlayer($_SESSION['playerId']);
        $quest = null;
        foreach ($quests as $q) {
            if ($q->getId() == $questId) {
                $quest = $q;
                break;
            }
        }
        // a truthy $qty specifies the player is accepting the quest. Otherwise it's a request for more information.
        if (is_null($qty)) {
            $data['title'] = $quest->getName();
            $html .= '<div class="managementContent">'.$quest->getPrologue().'</div>';
            // add button to accept quest.
            $commitButton = '<div class="text-center mt-5 text-sm"><button type="button" id="acceptButton" class="npcObject btn text-sm highlight" data-objecttype="quest" data-objectid="' . $quest->getId() . '" data-npc="' . $npc->getId() . '" data-qty="1"><i class="fas fa-map-signs text-lg"></i><br>'._("Accept Quest").'</button></div>';
        } else if ($qty) {
            try {
                $notification = $player->takeQuest($quest, $npc);
                // Not too sure I should show this here, this is what shows when the player completes the quest.
                // It does work this way in the old version.
                // We have to get the playerQuest in order to see the player's variables (ie for calculation quests).
                $playerQuest = new PlayerQuest($quest->getId(), $player->getId());
                $data['title'] = $playerQuest->getName();
                $html .= '<div class="managementContent">'.$playerQuest->getContent().'</div>';
                // Perhaps I could show a badge in the quests panel, similar to chat messages.

                // Append the notification to the quest information.
                if (strlen($notification)) {
                    $html .= '<div class="mt-4 mb-2 rounded-lg p-2 bg-gray-500/20">'.$notification.'</div>';
                }

            } catch (Exception $e) {
                // Throw an exception if the player does something dodgy or there is a bug.
                $data['error'] = true;
                $data['message'] .= $e->getMessage() . "\n<br>";
                echo json_encode($data);
                exit();
            }
        }
    break;

    case 'questcheck':
        $questId = $_POST['objectId'];
        $questTargets = $npc->getTargetQuestsForPlayer($_SESSION['playerId']);
        $playerQuest = null;
        foreach ($questTargets as $pq) {
            if ($pq->getId() == $questId) {
                $playerQuest = $pq;
                break;
            }
        }
        if (is_null($playerQuest)) {
            $data['error'] = true;
            $data['message'] = "The specified quest is not available for completion from this NPC.";
            echo json_encode($data);
            exit();
        } else if ($qty) {
            // a truthy $qty specifies the player is completing the quest.
            // - check that player is able to complete the quest given the cooldown time and previous attempts
            // - check that player has all the required items.
            $reasonNotReady = $playerQuest->getReasonPlayerNotReady();
            $data['title'] = $playerQuest->getName();
            if (strlen(trim($reasonNotReady))) {
                // echo 'There is a reason you are not ready.';
                $html .= '<div class="reasonNotReady">'.$reasonNotReady.'</div>';
            } else {
                // This message doesn't currently show. It could...
                // $data['message'] = _("Quest complete.");
                // We hide the NPC's image on this view because some quests require a lot of width.
                $hideNpcImgDiv = true;
                $html .= $playerQuest->attemptToCompleteQuest();
            }

        } else if (is_null($qty)) {
            $data['title'] = $playerQuest->getName();
            $waitingForAutomarking = false;

            // For long answer and conversation quests.
            if ($playerQuest->getResultTypeId() == 11 ||
                $playerQuest->getResultTypeId() == 14 ||
                $playerQuest->getResultTypeId() == 16) {

                $useQuest = $playerQuest->getResultTypeId() == 16 ? false : true;
                $rows = $playerQuest->getAutomarks($useQuest);

                if (count($rows) > 0) { // Player is waiting for automarks for this quest.
                    $waitingForAutomarking = true;
                    $finished = true;
                    $maxScore = 0;
                    $score = 0;
                    $markIds = [];

                    foreach ($rows as $row) {
                        $maxScore++;
                        if ($row['automark'] === NULL) {
                            $finished = false;
                            break;
                        }
                        if ($row['automark'] >= $playerQuest->getBaseRewardAutomarkPercentage() / 100) {
                            $score++;
                        }
                        $markIds[] = $row['id'];
                    }

                    if ($finished) {
                        if ($score >= $maxScore) {
                            // Handle successful completion of quest
                            $html .= $playerQuest->questSuccess();
                        } else {
                            // Mark quest as having been failed
                            $html .= $playerQuest->questFail();
                        }
                        // Player might repeat quest, so delete old answers.
                        $playerQuest->deleteOldAutomarks($markIds);

                    } else { // Still waiting for automarks.
                        $html .='<div class="managementContent">'.$playerQuest->questWaiting().'</div>';
                    }
                }
            }
            if (!$waitingForAutomarking) {
                $html .= '<div class="managementContent">'.$playerQuest->getTargetNpcPrologue().'</div>';
                // add button to complete quest.
                $commitButton = '<div class="mt-5 text-center">' .
                              '<button type="button" id="acceptButton" class="npcObject btn text-sm highlight" ' .
                              'data-objecttype="questcheck" data-objectid="' . $playerQuest->getId() .
                              '" data-npc="' . $npc->getId() . '" data-qty="1">' .
                              '  <i class="fas fa-check text-lg"></i><br>'._("Complete Quest") .
                              '</button>' .
                              '</div>';
            }
        }

} // end switch

$html.='</div></div><!--npcInfo-->';
// If we don't want the NPC img we can comment this out
if(!$hideNpcImgDiv) $html = $npcImgDiv.$html;

// Put the opening tags on the HTML now that we know if the image is being added
$html = $htmlOpen.$html;

if (isset($commitButton)) $html.=$commitButton;
$data['html'] = $html;
echo json_encode($data);
exit();

