<?php
/*
 * Accepts submission from guardian_edit to save new appearance of Guardian avatar
*/

include 'includes/mw_init.php';

// Get the list of avatar item categories and loop through them, looking for matching post elements
$imageTypes = AvatarImage::getAvatarImageTypes();

$player = new Player((int)$_POST['playerId']);
$guardian = new Guardian();

$currId = $guardian->getGuardianIdByPlayerId($player->getId());
$guardianId = $guardian->fetchMaxGuardianId()+1;
if($currId == 1){
    $guardianId = $guardian->fetchMaxGuardianId()+1;
    // Inserting a new Guardian Id into Guardians table
    $guardian->insertNewGuardian($guardianId, $guardian->getGenderId($player->getId()), $guardian->getRaceId($player->getId()), $guardian->getColor($player->getId()));
    // Linking the player with the new created Guardian
    $guardian->linkPlayerToGuardian($player->getId(),$guardianId);
} else {
    $guardianId = $currId;
    // check if multiple people own the same guardian
    $checker = array();
    $checker = $guardian->checkGaurdianOwnership($guardianId);
    if(count($checker) != 1){
        $guardianId = $guardian->fetchMaxGuardianId()+1;
        // Inserting a new Guardian Id into Guardians table
        $guardian->insertNewGuardian($guardianId, $guardian->getGenderId($player->getId()), $guardian->getRaceId($player->getId()), $guardian->getColor($player->getId()));
        // Linking the player with the new Guardian ID
        $guardian->linkPlayerToGuardian($player->getId(),$guardianId);
    } else {
        // Updating current guardian
        $guardian->updateCurrGuardian($guardianId, $guardian->getGenderId($player->getId()), $guardian->getRaceId($player->getId()));
    }
}


foreach ($imageTypes as $imageType) {
    $typeId = (int)$imageType['id'];
    if (isset($_POST['itemType'.$typeId])) {
        for ($i=1;$i<=4;$i++) {
            ${'color'.$i} = $_POST['type'.$typeId.'color'.$i]??null;
            if (!is_null(${'color'.$i}) && strlen(${'color'.$i}) == 0)
                ${'color'.$i} == null;
        }
        // Storing avatar image for the edited/ created Guardian
        $guardian->addAvatarImage((int)$_POST['itemType'.$typeId], $color1, $color2, $color3, $color4, $guardianId);
    }
}
// If save  is successful, return to avatar selection screen.
header('Location: map.php');