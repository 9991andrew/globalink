<?php
/**
 * tile_details.php
 * Shows various features of the tile that the player is on.
 * - Interactions that can be performed with NPCs at a given tile.
 * TODO: Show other players
 * 
 * NPC information will be shown, obviously.
 * Any items, quests, quest targets, or professions will be shown here.
 * accepts a list of NPC ids to show information for (often there is more than one)
 */
include 'includes/mw_init.php';

header('Content-type: application/json');
$data = array (
	"message" => "",
	"error" => false
);

// This makes it easier to test. This can be removed in production
//if (isset($_GET['npcs']) && !isset($_POST['npcs'])) {
//	$_POST['npcs'] = $_GET['npcs'];
//}

if (!isset($_POST['npcs'])) {
    // Don't localize
	$data['message'] = "No NPCs were specified.";
	$data['error'] = true;
	echo json_encode($data);
	exit();	
}

// Load a player object to make sure we have up-to-date data
$player = new Player($_SESSION['playerId']);

$mapId = $player->getMapId();
$x = $player->getX();
$y = $player->getY();

$npcs = array();
// We only check NPCs if there were any NPCs passed (if we use this page for other functionality)
if (strlen($_POST['npcs'])) {
    $npcIds = explode(",", $_POST['npcs']);

    foreach ($npcIds as $npcId) {
        try {
            array_push($npcs, new Npc((int)$npcId));

        } catch (Exception $e) {
            $data['error'] = true;
            $data['message'] .= $e->getMessage() . "\n<br>";
        }
    }
}
$detailsHtml = array();

// Get all the details for all the NPCs
foreach($npcs as $key=>$npc) {
	// Check that the NPC lives on the player's current maptile
	if (! $npc->isNpcFoundAt($mapId, $x, $y) ) {
		$data['error'] = true;
		// Don't localize
		$data['message'].="NPC ".$npc->getId()." does not exist on the current tile.<br>";
		continue;		
	}

	// Show the NPC's image, name, and level
	// Should the HTML be generated here, or should I just do that completely with JS?
	// I'm kind of leaning towards generating all the raw HTML here and sending it to the client
	// instead of raw data that needs to be rendered into HTML by JS
    $html = '';
	$html .= '<div id="npcInfo'.$npc->getId().'" class="flex justify-center xs:justify-start flex-wrap xs:flex-nowrap mt-5 mb-2">' .
        '<div class="xs:pr-2">' .
        '<img class="w-auto h-32 xs:w-40 xs:h-auto mx-auto xs:mx-0" alt="NPC Icon" src="'.$npc->getImageFilename().'" />' .
        '</div>';
	$html.='<div class="w-full text-center xs:text-left"><span class="block text-lg font-bold mb-2">'.$npc->getName();
	$html.='&nbsp; <span class="text-sm block leading-3 xs:inline opacity-80 font-normal">'._("Level").'&nbsp;<span class="npcLevelNumber">'.$npc->getLevel().'</span></span></span>';


	// Get any portals the character has. I'm not limiting it to showing levels the player can use just yet.
	$portals = $npc->getNpcPortals();
	if (sizeof($portals)) {
        $html.='<div class="insetSection">';
        $html.='<h3>'.ngettext("Destination", "Destinations", sizeof($portals)).'</h3>';
		$html.='<ul class="text-left">';

		foreach($portals as $npcPortal) {
			$html.='<li class="itemName">';
			$html.='<i class="fas fa-map-marked-alt mr-2"></i><span class="inline-block ml-2 font-lg text-right" style="min-width:1.5rem;">$'.$npcPortal->getPrice().'</span>&nbsp;';
			$html.='<button type="button" class="npcObject link" data-objecttype="portal" data-objectid="'.$npcPortal->getId().'" data-npc="'.$npc->getId().'">'.$npcPortal->getName().'</button>';
			$html.='&nbsp;<span class="text-sm opacity-80">'._("Level").' '.$npcPortal->getLevel().'</span></li>';
		}
		$html.='</ul>';
		$html.='</div>';
	}

	// Get any items the character has
	// icons: directions, comment-dollar, gamepad, globe, hat-wizard, map-signs
	$items = $npc->getItemsForPlayer($_SESSION['playerId']);
	if (sizeof($items)) {
	    $html.='<div class="insetSection">';
		$html.='<h3>'.ngettext("Item", "Items", sizeof($items)).' '._('for You').'</h3>';
		$html.='<ul class="text-left">';

		foreach($items as $item) {
			$html.='<li class="itemName">';
            $html.='<img src="'.$item->getImageFilename().'" class="inline-block w-6 h-6 mr-1"><button type="button" class="npcObject link" data-objecttype="item" data-objectid="'.$item->getId().'" data-npc="'.$npc->getId().'">'.$item->getName().'</button>';
            $html.='</li>';
		}
		$html.='</ul>';
        $html.='</div>';
	}

	// Get any quests the character has
	$questTargets = $npc->getTargetQuestsForPlayer($_SESSION['playerId']);
	if (sizeof($questTargets)) {
        $html.='<div class="insetSection">';
        $html.='<h3>'.ngettext("Quest", "Quests", sizeof($questTargets)).' '._('in Progress').'</h3>';
		$html.='<ul class="text-left">';

		foreach($questTargets as $quest) {
			$html.='<li> ';
			$html.='<i class="fas fa-map-signs mr-2"></i><button type="button" class="npcObject link" data-objecttype="questcheck" data-objectid="'.$quest->getId().'" data-npc="'.$npc->getId().'">'.$quest->getName().'</button>';
			$html.='</li>';
		}
		$html.='</ul>';
		$html.='</div>';
	}

	// Get any quests the character can accept
	$quests = $npc->getQuestsForPlayer($_SESSION['playerId']);
	if (sizeof($quests)) {
        $html.='<div class="insetSection">';
        $html.='<h3>'.ngettext("Quest", "Quests", sizeof($quests)).' '._('for You').'</h3>';
		$html.='<ul class="text-left">';

		foreach($quests as $quest) {
			$html.='<li>'; // Normally I prefer these being buttons (as they don't "link" to anything, but instead invoke JS, but if they get really long I have undesirable wrapping behaviour. Unsure how to fix so far.
            $html.='<i class="fas fa-map-signs mr-2 text-black dark:text-gray-200"></i><a href="javascript:void(0);" class="npcObject link" data-objecttype="quest" data-objectid="'.$quest->getId().'" data-npc="'.$npc->getId().'">'.$quest->getName().'</a>';
            $html.='</li>';
		}
		$html.='</ul>';
        $html.='</div>';
	}

	// Get any professions the NPC can give
	$professions = $npc->getProfessionsForPlayer($_SESSION['playerId']);
	if (sizeof($professions)) {
        $html.='<div class="insetSection">';
        $html.='<h3>'._('Join').' '.ngettext("Profession", "Professions", sizeof($professions)).'</h3>';
		$html.='<ul class="text-left">';

		foreach($professions as $profession) {
			$html.='<li>';
			$html.='<i class="fas fa-graduation-cap mr-2"></i><button type="button" class="npcObject link" data-objecttype="profession" data-objectid="'.$profession->getId().'" data-npc="'.$npc->getId().'">'.$profession->getName().'</button>';
            $html.='</li>';
		}
		$html.='</ul>';
        $html.='</div>';
	}

	// Close the divs
	$html.='</div></div><!--npcInfo-->';
	array_push($detailsHtml, $html);
}//end foreach $npcs

// Get the details of a building at this location if there is one
try {
    $building = new Building($mapId, $x, $y);
    $html = '';
    $html .= '<div class="insetSection mt-6">';
    $html .= '<h3><i class="fas fa-building mr-2"></i>' . (strlen($building->getName()) ? $building->getName() : _('Building')) . '</h3>';

    if ($building->playerAllowed($player->getId())) {
        if (!is_null($building->getExternalLink())) {
            $html .= '<div class="text-center my-2"><i class="fas fa-link mr-2"></i> <a class="link break-all" target="_blank" href="' . $building->getExternalLink() . '">' . $building->getExternalLink() . '</a></div>';
        }

        if (is_int($building->getDestMapId()) && is_int($building->getX()) && is_int($building->getX())) {
            $html .= '<div class="text-center">';
            $html .= '  <div class="text-lg">';
            $html .= '    <span class="opacity-70">'._('To').' </span>' . $building->getDestMapName();
            $html .= '    <span class="opacity-70">(' . $building->getX() . ', ' . $building->getY() . ')</span>';
            $html .= '  </div>';
            $html .= '  <button type="button" class="btn block w-32 my-3 m-auto items-center" onclick="usePortal(' . $building->getId() . ')">'._('Travel').'</button>';
            $html .= '</div>';
        }
    } else { // Player is not allowed
        // Show the list of allowed professions
        $html .= '<ul class="error text-center"><li><strong class="opacity-80">'._('Allowed Professions').'</strong></li>';
        foreach ($building->getAllowedProfessionIds() as $professionId) {
            $profession = new Profession($professionId);
            $html .= '<li>' . $profession->getName() . '</li>';
        }
        $html .= '</ul>';
    }


    // Should add profession limitation details
    array_push($detailsHtml, $html);
} catch (Exception $e) {
    // No need to do anything here
}

// Get the details of quests that can be completed at this location (if any)
foreach($player->getPlayerQuests() as $playerQuest) {
    $html = '';
    // If the quest result type is 12 (location), determine if this location matches
    if ($playerQuest->getResultTypeId() == 13) {
        try {
            if ($playerQuest->isCorrectLocation($player->getMapId(), $player->getX(), $player->getY())) {
                $html =  '<form class="insetSection" onsubmit="checkAnswers(event, this)" method="post">';
                $html .= '  <input type="hidden" name="questId" value="'.$playerQuest->getId().'">';
                $html .= '  <h3><i class="fas fa-map-signs mr-2"></i>'.$playerQuest->getName().'</h3>';
                $html .= '  <p class="text-center my-3 mb-4">'._('This seems to be the correct location.').'</p>';
                $html .= '  <button type="submit" class="btn block my-3 m-auto text-sm highlight whitespace-nowrap"><i class="fas fa-check text-lg"></i><br>'.('Complete Quest').'</button>';
                $html .= '</form>';
                array_push($detailsHtml, $html);
            }
        } catch(Exception $e) {
            $data['error'] = true;
            $data['message'].=$e->getMessage()."\n<br>";
            echo json_encode($data);
            exit();
        }
    }
}


// BONUS: Get list of players at this location

$data['detailsHtml'] = $detailsHtml;


// $data['error'] = false;

echo json_encode($data);
exit();
