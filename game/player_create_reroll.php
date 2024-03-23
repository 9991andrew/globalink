<?php
/* Allows player to reroll values. Values are stored in a session so players
cannot tamper with the values when creating their characters */
include 'includes/mw_init.php';

// Reset the newAttributeValues array
$_SESSION['newAttributeValues'] = Array();
for ($i=1; $i<=6; $i++) {
	$_SESSION['newAttributeValues'][$i] = rand(1, 18);
}

header('Content-Type: application/json');
echo json_encode($_SESSION['newAttributeValues']);
