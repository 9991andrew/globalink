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

// Create a new guardian entery and link player with it
$guardian = new Guardian();
$guardianId = $guardian->fetchMaxGuardianId()+1;
$guardian->create($_GET['id'], $guardianId, (int)$_POST['race'], (int)$_POST['gender'], (string)$_POST['color']);


// If Guardian creation is successful, move on to guardian_edit.php
header('Location: guardian_edit.php');