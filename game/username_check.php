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
if (isset($_GET['username'])) {
	$userName = trim($_GET['username']);
} else {
	$data['message'] .= _("Error").': '._('No username was specified.');
	echo json_encode($data);
	exit();
}


// Create a user object with the specified username and password as constructor arguments.
// This will necessarily fail because we aren't using a password.
try {
	$user = new User($userName, '');

} catch(exception $e) {
	// This will most likely be a username/password validation error.
	$data['message'] .= $e->getMessage();	

	// In this case, we *want* an InvalidUserException, show a relevant message.
	if (get_class($e) == "InvalidUserException") {
		$data['message'] = _('Username is available!');
		$data['error'] = false;
	}

	else if (get_class($e) == "InvalidPasswordException") {
		$data['message'] = sprintf(_('Sorry, username "%s" is taken.'), $_GET['username']);
		$data['error'] = true;		
	}

	echo json_encode($data);
	exit();
}


$data['error'] = true;
$data['message'] = _("Something went wrong checking the username.");
echo json_encode($data);
exit();
