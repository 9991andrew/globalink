<?php
// login_check.php
// Accepts form submission from login.php and validates credentials against the DB.
// If login was successful, sets appropriate session variables
// Returns JSON indicating status along with any error messages.
include 'includes/mw_init.php';

header('Content-type: application/json');
$data = array (
	"message" => "",
	"error" => true
);

// Ensure post is set if GET is used
if (!isset($_POST['playerName']) && isset($_GET['playerName'])) $_POST['playerName'] = $_GET['playerName'];

// We don't want spaces hanging on the end
$_POST['playerName'] = trim($_POST['playerName']);

// Sanity check. Are username and password defined and have a length?
// In practice this won't really be used by actual users because this will be done client-side with JS,
// this is more to help developers
if (isset($_POST['playerName'])) {
	$playerName = trim($_POST['playerName']);
} else {
	$data['message'] .= _("Error").": No username was specified.";
}

if (strlen($data['message'])) {
	echo json_encode($data);
	exit();
}


// Get the count of players with the given playername
$count = Player::countPlayersWithName($playerName);
if ($count == 0) {
	$data['message'] = _("Name is available!");
	$data['error'] = false;
}
else {
	$data['message'] = sprintf(_('Sorry, the name "%s" is taken.'), $playerName);
	$data['error'] = true;		
}

echo json_encode($data);