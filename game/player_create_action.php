<?php

/* Accepts submission from player_create and creates a new player for
the logged-in user with the specified Gender, Race, Birthplace,
and uses the attributes stored in the $_SESSION['newAttributeValues']
array (so the user can't tamper with the form submission).

The following attributes will be randomly assigned to the new player
from the pool of items that applies to their race and gender:

Skin, Hair, Eyebrows, Eyes, Nose, Mustache, Mouth, Top, Bottom, Shoes

SELECT * FROM item_categories WHERE layer_index IS NOT NULL ORDER BY layer_index;

-- Example: Available Male Human parts
SELECT * FROM items WHERE item_category_id
IN (SELECT item_category_id FROM item_categories WHERE layer_index IS NOT NULL)
AND disabled=0
AND (gender_id = 1 OR gender_id IS NULL)
AND (race_id = 1 OR race_id IS NULL)


The user will then be directed to the avatar customization interface
where the user can customize these things using dropdowns.
*/
include 'includes/mw_init.php';

$_POST['playerName'] = trim($_POST['playerName']);

if (Player::countPlayersWithName($_POST['playerName']) != 0) {
	$pageTitle = "Error Creating New Player";
	include 'includes/mw_header.php';
	// This is backup server-side validation, translation not needed.
	echo '<p class="error">Error: The player name you chose is already in use. Please go back and try a different name.</p>';
	include 'includes/mw_footer.php';
}

$player = Player::create($_SESSION['user']->getId(), $_POST['playerName'], (int)$_POST['birthplace'], (int)$_POST['race'], (int)$_POST['gender'], $_SESSION['newAttributeValues']);

$_SESSION['playerId'] = $player->getId();

// If player creation is successful, move on to avatar_edit.php
header('Location: avatar_edit.php');