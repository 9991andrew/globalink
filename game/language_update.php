<?php
/**
 * This file simply receives a post parameter for a localeId, sets it for the user and session
 * and then redirects back to map.php.
 */
include 'includes/mw_init.php';
// If a language was specified for this page, set that in the session now.
if (isset($_POST['localeId'])) {
    $_SESSION['locale'] = $_POST['localeId'];
    if (isset($_SESSION['user'])) {
        $_SESSION['user']->setLocaleId($_POST['localeId']);
    }
}
// Redirect back to the requesting page
header('Location: '.$_SERVER['HTTP_REFERER']??'map.php');