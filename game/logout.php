<?php
/**
 * Simple page to handle logging user out
 */

$pageTitle = _("Log Out of")." MEGA World";
include 'includes/mw_header.php';

?>
<script>
// Remove user's chat history from localStorage.
localStorage.removeItem('chatHistory');
</script>

<?php
echo '<h2 class="mb-2 mt-8 text-xl font-ocr text-center">'.$pageTitle.'</h2>';

// Use the user's logout method so the logout is logged.
if (isset($_SESSION['playerId'])) {
    // Handle making the player leave the game
    $player = new Player($_SESSION['playerId']);
    $player->leave();
}
if (isset($_SESSION['user'])) $_SESSION['user']->logout();
else session_destroy();

?>

<div class="text-center space-y-8">

<?php
if (!isset($_GET['newlogo'])) {
    if (!isset($_COOKIE['logo'])) {
        // Could default to a theme here, just don't set one.
        setcookie("logo", "mwlogo.svg");
        $_SESSION['logo'] = "mwlogo.svg";
    } 
    else {
        $_SESSION['logo'] = $_COOKIE['logo'];
    }  
}
else {
    if ($_GET['newlogo'] == "0") {
        setcookie("logo", "mwlogo.svg");
         $_SESSION['logo'] = "mwlogo.svg";
    }
    else {
        setcookie("logo", "mwlogo".$_GET['newlogo'].".png");
         $_SESSION['logo'] = "mwlogo".$_GET['newlogo'].".png";
    }
}


setcookie("logo", "mwlogo1.png");
$_SESSION['logo'] = "mwlogo1.png";

if ($_SESSION['logo'] == "mwlogo.svg") {
?>
        <!-- Include the SVG so we can use CSS to alter it -->
        <?php include 'images/mwlogo.svg'; ?>
<?php
}
else {
?>
        <center><img align="middle" src="images/<?php echo($_SESSION['logo']);?>" width="25%"></center>
<?php
}
?>

    <p><?=_('You are now logged out. Thanks for playing')?> MEGA World!</p>

    <p><a class="link" href="login.php"><?=_('Log In to')?> MEGA World</a></p>

    <p><a class="link" href="index.php">MEGA World <?=_('Home')?></a></p>

    <p><?=_('Redirecting to the home page in a few seconds...')?></p>
</div>