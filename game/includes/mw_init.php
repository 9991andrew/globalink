<?php
/*
This file is included with almost all pages in MEGA World.
This includes session handling code and redirection
*/
declare(strict_types = 1);

// automatically load any used classes
include_once 'class-autoload.inc.php';

date_default_timezone_set(Config::TIME_ZONE);

// Start or use the session
session_start();

// Handle localization using gettext()
// I am unsure if this needs to be run every time a page loads or only when the locale changes or app is started.
//null should be the user's detected locale
Helpers::initLocale($_SESSION['locale']??$_SERVER['HTTP_ACCEPT_LANGUAGE']);

// Check only allow the user to visit this list of URLs if they are not logged in.
// Otherwise the user will be redirected to the login page.
if (   strpos($_SERVER['REQUEST_URI'], '/login.php') === false
    && strpos($_SERVER['REQUEST_URI'], '/login_check.php') === false
    && strpos($_SERVER['REQUEST_URI'], '/username_check.php') === false
    && strpos($_SERVER['REQUEST_URI'], '/register.php') === false
    && strpos($_SERVER['REQUEST_URI'], '/register_action.php') === false
    && strpos($_SERVER['REQUEST_URI'], '/index.php') === false
    && strpos($_SERVER['REQUEST_URI'], '/guide.php') === false
    && strpos($_SERVER['REQUEST_URI'], '/language_update.php') === false
    && $_SERVER['REQUEST_URI'] != '/'
    && !isset($_SESSION['user'])  ) {
    header('Location: login.php');
    exit();
}

// Default to light theme if no theme is set
if (!isset($_SESSION['theme'])) {
    $_SESSION['theme'] = 'light';
}

if (!isset($pageTitle)) {
    $pageTitle = 'MEGA World';
}

