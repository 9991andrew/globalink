<?php
/**
 * password_change_action.php
 * Updates the user's password if they entered the original correctly.
 */

$pageTitle = _("Change Password");
include 'includes/mw_init.php';

// Normally this page isn't visible to the user unless an error occurs.
// In practice the validation is handled client-side, so this is only
// a check in case people try something shady.

// An error message and be build up into this variable.
$message = "";

// Validate that required data is present
if (!isset($_SESSION['user'])) {
	$message .= _("No user is logged in.");
}

if (!isset($_POST['current_password']) || strlen($_POST['current_password'] ) < 6) {
	$message .= "<br />"._('No password was specified.');
} else {
    $currentPasswordHash = sha1(trim($_POST['current_password']));
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

// Create a user object with the specified username and password as constructor arguments
try {
    $user = new User($_SESSION['user']->getUsername(), $currentPasswordHash);

    // If all is good that should allow the password change
    $user->changePassword($_POST['password']);
    // We now have the new user, we are logged in, we can now proceed to the player creation page
    header('Location: password_change_success.php');

} catch(exception $e) {

    $pageTitle=_("Error");
    include 'includes/mw_header.php';
    echo '<h2 class="mb-2 mt-8 text-xl font-ocr text-center">'._('Error').'</h2>';
    echo '<div class="error text-center">'.$e->getMessage().'</div>';
    echo '<div class="text-center mt-4"><a class="link" href="password_change.php">'._('Try Again').'</a>';
    include 'includes/mw_footer.php';

    exit();
}





