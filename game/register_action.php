<?php
/**
 * register_action.php
 * This page inserts the new user record into the DB and logs the user in.
 * If this is successful, the user will move on to the player creation screen.
 */

$pageTitle = _("User Registration");
include 'includes/mw_init.php';

// Normally this page isn't visible to the user unless an error occurs.
// In practice the validation is handled client-side, so this is only
// a check in case people try something shady.

// An error message and be build up into this variable.
$message = "";

if (isset($_GET['username']) && !isset($_POST['username'])) {
	$_POST['username'] = $_GET['username'];
}

// Validate that required data is present
if (!isset($_POST['username']) || trim(strlen($_POST['username'] )) == 0) {
	$message .= _("No username was specified.");
} else {
	$username = trim($_POST['username']);

    $email = trim($_POST['email']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $emailErr = _("Invalid email format");
        $email = null;
    }

    $language = trim($_POST['language']);
	// Check that the specified username does not exist

	// Create a user object with the specified username and password as constructor arguments.
	// This will necessarily fail because we aren't using a password.
	try {
		$user = new User($username, '');

	} catch(exception $e) {
		// In this case, we *want* an InvalidUserException, show a relevant message.
		if (get_class($e) != "InvalidUserException") {
			$message .= sprintf(_('The specified username, "%s", is not available.'), $username);
		}
	}



}

if (!isset($_POST['password']) || strlen($_POST['password'] ) < 6) {
	$message .= "<br />"._('No password was specified.');
} else {
	$password = trim($_POST['password']);
}

if (strlen($message) > 0) {
    include 'includes/mw_header.php';
	echo '<div class="error"><strong>'._('Error').':<br /></strong>';
	echo $message;
	echo '<br /><br />'._('Please go back and try your submission again.').'</div>';
	include 'includes/mw_footer.php';
	exit();
}


// If we made it this far, the username and password should be acceptable and we can create the new user.

try {
    $user = User::create($username, $password, $email, $language);
} catch (Exception $e) {
    // Will not localize, this is only likely hit if there's a bug.
    echo 'Problem creating user. Ensure username is unique and password requirements are met.';
    exit();
}

$_SESSION['user'] = $user;
$_SESSION['loginTime'] = $user->getLastLoginTime();

// We now have the new user, we are logged in, we can now proceed to the player creation page
header('Location: player_create.php');



