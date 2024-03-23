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

// Sanity check. Are username and password defined and have a length?
// In practice this won't really be used by actual users because this will be done client-side with JS,
// this is more to help developers

// These lines can be uncommented for debugging
// if (!isset($_POST['username']) && isset($_GET['username'])) $_POST['username']=$_GET['username'];
// if (!isset($_POST['password']) && isset($_GET['password'])) $_POST['password']=$_GET['password'];

if (isset($_POST['username']) && strlen($_POST['username'])) {
	$userName = trim($_POST['username']);
} else {
	$data['message'] .= _("Error").": No username was specified.";

}

if (isset($_POST['password']) && strlen($_POST['password'])) {
	$passwordHash = sha1(trim($_POST['password']));
} else {
	$data['message'] .= '<br>'._("Error").": No password was specified.";
}

if (strlen($data['message'])) {
	echo json_encode($data);
	exit();
}


// Create a user object with the specified username and password as constructor arguments
try {
	$user = new User($userName, $passwordHash);

} catch(exception $e) {
	// This will most likely be a username/password validation error.
	$data['message'] .= $e->getMessage();
	echo json_encode($data);
	exit();
}


// If we successfully created the $user object, validation must have been successful.
// We can store the user object into the session with all its data.
// I think storing the entire object is unnecessary and stores more information than is necessary. I'm concerned about how it stores the DB password. How do I avoid that?
$_SESSION['user'] = $user;
$_SESSION['locale'] = $user->getLocaleId();
// Store loginTime as human readable time. Maybe I could just use mktime() timestamp?

$_SESSION['loginTime'] = $user->getLastLoginTime();

// Default the user to the last player they selected
if(!is_null($user->getLastPlayerID())) $_SESSION['playerId'] = $user->getLastPlayerID();
else unset($_SESSION['playerId']);
$data['error'] = false;
echo json_encode($data);
exit();
