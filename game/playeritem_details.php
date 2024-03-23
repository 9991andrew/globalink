<?php
/**
 * playeritem_details.php
 * Shows information about a player item (from a player bag slot)
 * 
 */
include 'includes/mw_init.php';

header('Content-type: application/json');
$data = array (
	"message" => "",
	"error" => false
);


if (!isset($_POST['itemGUID'])) {
	$data['message'] = "No itemGUID was specified.";
	$data['error'] = true;
	echo json_encode($data);
	exit();	
}


// Load a player object to make sure we have up-to-date data
$player = new Player($_SESSION['playerId']);

$mapID = $player->getMapID();
$x = $player->getX();
$y = $player->getY();

try {
    $playerItem = new PlayerItem($_POST['itemGUID']);
} catch(Exception $e) {
    $data['error'] = true;
    $data['message'].=$e->getMessage()."\n<br>";
    echo json_encode($data);
    exit();
}

// Ensure that the current player is the actual owner of this item to prevent tampering.
if ($playerItem->getOwnerPlayerID() != $_SESSION['playerId']) {
    $data['error'] = true;
    $data['message'].=_("You aren't the owner of this item.").'<br>';
    echo json_encode($data);
    exit();
}

if (isset($_POST['action'])) {
    $action = $_POST['action'];
    try {
        if ($action == 'drop') {
            $data['message'] = $playerItem->getName().' '._("has been dropped.");
            $playerItem->drop();
        }
        if ($action == 'use') {
            $data['title'] = $playerItem->getName();
            $data['html'] = $player->useItem($playerItem);
        }
        if ($action == 'split') {
            if (!isset($_POST['qty'])) $_POST['qty']=1;
            $player->splitItem($playerItem, (int)$_POST['qty']);
            $data['message'] = $playerItem->getName().': '.$_POST['qty'].' '.ngettext('has been', 'have been', (int)$_POST['qty']).' '._('split to an empty slot.');
        }
        if ($action == 'stack') {
            $data['message'] = $player->stackItem($playerItem);
        }
    } catch (Exception $e) {
        $data['error'] = true;
        $data['message']=$e->getMessage();
        echo json_encode($data);
        exit();
    }
    $data['error'] = false;
    echo json_encode($data);
    exit();

}

$html = '';

// Now render out html for the item details page. This will be reminiscent of the npc_object item view.
$html .= '<div class="mt-2 text-center">'
.'<img src="' . $playerItem->getImageFilename() . '" class="m-auto max-h-32" />'
.'<div class="font-bold text-lg leading-tight">' . $playerItem->getName()
.'<div class="text-sm opacity-80 font-normal">'._("Level").' ' . $playerItem->getLevel() . '</div>'
.'</div>';

if ($playerItem->getSlotAmount() > 1) $html .= '<div style="margin-bottom:4px;"><strong>'._('Quantity').': </strong>'.$playerItem->getSlotAmount().'</span></div>';
// $html .= '<strong>Price: </strong>&nbsp;$' . $playerItem->getPrice();
$weightGrams = (int)round($playerItem->getWeight() / 1000);
if ($playerItem->getWeight() > 0) $html .= '<span class="text-sm opacity-80"><strong>'._('Weight').': </strong> '.$weightGrams.' '.ngettext('gram each', 'grams each', $weightGrams).'</span>';
if ($playerItem->getRequiredLevel() > 0) {
    $html .= '<div style="margin-top:10px;font-size:14px;">'._('You must be').' <strong>'._("Level").' ' . $playerItem->getRequiredLevel() . '</strong> '._('to use this item.').'</div>';
}
$html .= '</div>';//itemInfo
$html .= '<p>' . $playerItem->getDescription() . '</p>';
$html .= '<div class="flex flex-col w-60 m-auto mt-4 space-y-3 items-center text-sm leading-4">';

// Use
if (!is_null($playerItem->getEffectID())) {
    $html .= '<div class="w-full"><button class="itemInteraction btn w-full" data-action="use" data-itemguid="'.$playerItem->getGUID().'">'.$playerItem->getEffectUseString().'</button></div>';
}

// Split Off
if ($playerItem->getSlotAmount() > 1) {
    $html .= '<div class="w-full flex space-x-3">';
    if ($playerItem->getSlotAmount() > 2) $html .= '<button class="btn" onclick="incrSplit(-1);"><i class="text-lg fas fa-minus"></i></button>';
    $html .= '<button class="itemInteraction btn flex-1" id="splitButton" data-action="split" data-qty="1" data-max="'.$playerItem->getSlotAmount().'" data-itemguid="'.$playerItem->getGUID().'"><i class="text-lg fas fa-sign-out-alt"></i><br>'._('Split Off').' 1</button>';
    if ($playerItem->getSlotAmount() > 2) $html .= '<button class="btn" onclick="incrSplit(1);"><i class="text-lg fas fa-plus"></i></button>';
    $html .= '</div>';
}

// Stack
// We must have fewer items than the max stack and have other items of this kind (that themselves are not maxed out).
if ($playerItem->getSlotAmount() < $playerItem->getMaxAmount() &&
    sizeof($player->getPlayerBag()->getItemsWithSameID($playerItem, false)) ) {
    $html .= '<div class="w-full">';
    $html .= '<button type="button" class="itemInteraction btn w-full" data-action="stack" data-itemguid="'.$playerItem->getGUID().'"><i class="text-lg fas fa-sign-in-alt"></i><br>'._('Stack Items').'</button>';
    $html .= '</div>';
}

// Drop Item
$html .= '<div class="text-center w-full"><button type="button" class="itemInteraction btn destructive block w-full" data-action="drop" data-itemguid="'.$playerItem->getGUID().'"><i class="text-lg fas fa-trash"></i><br>'._('Drop Item').'</button></div>';
$html .= '</div><!--.buttonCol-->';
$data['title'] = _('Bag Item');
//$data['title'] = $playerItem->getName();
$data['html'] = $html;


// $data['error'] = false;
echo json_encode($data);
exit();
