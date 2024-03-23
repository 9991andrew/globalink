<?php

/* Accepts submission from player_select to set current player id in the session. */

declare(strict_types = 1);
// Automatically load any used classes
include 'includes/class-autoload.inc.php';
// Use the session
session_start();


// confirm that the current user 'owns' the player
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
}

if (!isset($_POST['player']) && isset($_GET['player'])) $_POST['player'] = $_GET['player'];

if (isset($_POST['player'])) {

	$player = new Player((int)$_POST['player']);

	if ($player->getUserId() != $_SESSION['user']->getId()) {
        echo '<p class="error">'._('Error').': '._("The specified player does not belong to the logged in user.").'</p>';
		exit();		
	} else {
        // echo 'Setting selected player '.$_POST['player'];
		$_SESSION['playerId'] = (int)$_POST['player'];
        // If the user's previous player choice was different, we can handle some cleanup
        if ($_SESSION['user']->getLastPlayerId() != $_SESSION['playerId']) {
            // Clear the chat log. I don't think I can do that on this page because we just redirect instantly without loading any HTML
            $_SESSION['playerChanged'] = true;
            // Set the selection in the DB
            // Remember which player this user selected.
            $_SESSION['user']->setLastPlayerId($_SESSION['playerId']);
        }
		// Log selection of player
        $player->logSelect();


	}

} 
// If setting the player was successful, go to main game interface
// map.php is temporarily used until the full UI is complete
header('Location: map.php');