<?php

/* Accepts submission from avatar_edit to save new appearance of avatar


*/

include 'includes/mw_init.php';

// confirm that the current user 'owns' the player

// Get the list of avatar item categories and loop through them, looking for matching post elements
$imageTypes = AvatarImage::getAvatarImageTypes();

if (isset($_POST['playerId'])) {

	$player = new Player((int)$_POST['playerId']);

	if ($player->getUserID() != $_SESSION['user']->getId()) {
        echo '<p class="error">'._('Error').': '._("The specified player does not belong to the logged in user.").'</p>';
		exit();		
	}

	foreach ($imageTypes as $imageType) {
		$typeId = (int)$imageType['id'];
		if (isset($_POST['itemType'.$typeId])) {
		    for ($i=1;$i<=4;$i++) {
                ${'color'.$i} = $_POST['type'.$typeId.'color'.$i]??null;
                if (!is_null(${'color'.$i}) && strlen(${'color'.$i}) == 0)
                    ${'color'.$i} == null;
            }
			$player->addAvatarImage((int)$_POST['itemType'.$typeId], $color1, $color2, $color3, $color4);
		}
	}


} else {
	echo '<p class="error">No playerId was specified.</p>';
	exit();
}
// If save  is successful, return to avatar selection screen.
header('Location: player_select.php');