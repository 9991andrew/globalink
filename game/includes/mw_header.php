<?php
// Only include this if it hasn't already been included
include_once 'includes/mw_init.php';

if (!isset($_COOKIE['darkmode'])) {
    // Could default to a theme here, just don't set one.
} elseif (isset($_COOKIE['darkmode']) && $_COOKIE['darkmode'] == 'true') {
    $_SESSION['theme'] = 'dark';
} else {
    $_SESSION['theme'] = 'light';
}


if (!isset($_GET['newicon'])) {
    if (!isset($_COOKIE['favicon'])) {
        // Could default to a theme here, just don't set one.
        setcookie("favicon", "favicons");
        $_SESSION['favicon'] = "favicons";
    } 
    else {
        $_SESSION['favicon'] = $_COOKIE['favicon'];
    }  
}
else {
    if ($_GET['newicon'] == "0") {
        setcookie("favicon", "favicons");
         $_SESSION['favicon'] = "favicons";
    }
    else {
        setcookie("favicon", "favicon_io".$_GET['newicon']);
         $_SESSION['favicon'] = "favicon_io".$_GET['newicon'];
    }
}

setcookie("favicon", "favicon_io5");
$_SESSION['favicon'] = "favicon_io5";


// Support for prefetching images
if (isset($prefetch)) echo $prefetch;

// Function to show language select
function languageSelect() {
    echo '<form class="block mb-8 text-center" action="language_update.php" method="post">';
    echo '<label class="block text-lg" for="localeId">'._('Language');
    echo '  <select id="localeId" name="localeId" class="ml-2 pl-3 pr-6" onchange="submit()">';
                foreach(Data::getLanguages() as $language) {
                    echo '<option value="'.$language['locale_id'].'.utf8" '.($language['locale_id'].".utf8"==$_SESSION['locale']?'selected':'').'>'.
                        $language['name'].($language['name']!=$language['native_name']?' / '.$language['native_name']:'').'</option>';
                }
    echo '  </select>';
    echo '</label>';
    echo '</form>';
}

?>
<!DOCTYPE html>
<html lang="<?=preg_replace('/(.*)\.utf8/', "$1" , $_SESSION['locale'])??Config::DEFAULT_LOCALE?>" class="<?=$_SESSION['theme']?>">

<head>
<title><?=$pageTitle??'MEGA World'?></title>
<meta charset="utf-8" />
<!-- These links may have to be adjusted if v3 gets moved to somewhere other than the root -->
<?php $iconPath = "/".$_SESSION['favicon'];
if (preg_match("/\/v3\//i", $_SERVER['REQUEST_URI'])) {
    $iconPath = "/v3/".$_SESSION['favicon'];
}
?>
<?php
if ($_SESSION['favicon'] == "favicons") {
?>
<link rel="apple-touch-icon" sizes="57x57" href="<?php echo($iconPath);?>/apple-icon-57x57.png">
<link rel="apple-touch-icon" sizes="60x60" href="<?php echo($iconPath);?>/apple-icon-60x60.png">
<link rel="apple-touch-icon" sizes="72x72" href="<?php echo($iconPath);?>/apple-icon-72x72.png">
<link rel="apple-touch-icon" sizes="76x76" href="<?php echo($iconPath);?>/apple-icon-76x76.png">
<link rel="apple-touch-icon" sizes="114x114" href="<?php echo($iconPath);?>/apple-icon-114x114.png">
<link rel="apple-touch-icon" sizes="120x120" href="<?php echo($iconPath);?>/apple-icon-120x120.png">
<link rel="apple-touch-icon" sizes="144x144" href="<?php echo($iconPath);?>/apple-icon-144x144.png">
<link rel="apple-touch-icon" sizes="152x152" href="<?php echo($iconPath);?>/apple-icon-152x152.png">
<link rel="apple-touch-icon" sizes="180x180" href="<?php echo($iconPath);?>/apple-icon-180x180.png">
<link rel="icon" type="image/png" sizes="192x192"  href="<?php echo($iconPath);?>/android-icon-192x192.png">
<link rel="icon" type="image/png" sizes="32x32" href="<?php echo($iconPath);?>/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="96x96" href="<?php echo($iconPath);?>/favicon-96x96.png">
<link rel="icon" type="image/png" sizes="16x16" href="<?php echo($iconPath);?>/favicon-16x16.png">
<link rel="icon" type="image/svg+xml" href="<?php echo($iconPath);?>/favicon.svg">
<link rel="manifest" href="<?php echo($iconPath);?>/manifest.json">
<?php
}
else {
?>
<link rel="apple-touch-icon" sizes="180x180" href="<?php echo($iconPath);?>/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="<?php echo($iconPath);?>/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="<?php echo($iconPath);?>/favicon-16x16.png">
<link rel="manifest" href="<?php echo($iconPath);?>/site.webmanifest">
<?php
}
?>

<meta name="msapplication-TileColor" content="#ffffff">
<meta name="msapplication-TileImage" content="/ms-icon-144x144.png">
<meta name="theme-color" content="#ffffff">

<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.5, viewport-fit=cover" />

<link rel="stylesheet" href="css/tailwind.css">
<?php
	// Redirect to landing page/homepage after 10 seconds
	if ($_SERVER['REQUEST_URI'] == '/logout.php' || $_SERVER['REQUEST_URI'] == '/v3/logout.php') {
		echo '<meta http-equiv="refresh" content="10;url=index.php" />';
	}
?>

<!--- Font Awesome gives us a big set of icons we can use.--->
<!-- I may not end up using these in production but they help in development -->
<link rel="stylesheet" href="css/fontawesome-free-5.1.3.all.css" />

<?php
// For pages other than the home page, show an IE warning
if ($_SERVER['REQUEST_URI'] != '/index.php' && $_SERVER['REQUEST_URI'] != '/v3/index.php'
	&& $_SERVER['REQUEST_URI'] != '/' && $_SERVER['REQUEST_URI'] != '/v3/') {
?>

<script>
document.addEventListener("DOMContentLoaded", function() {
	// Shows warning for Internet Explorer
	if (window.navigator.userAgent.indexOf("MSIE ") > 0 || !!navigator.userAgent.match(/Trident.*rv\:11\./)) {
		var ieWarn = document.createElement("div");
		ieWarn.setAttribute("class","rounded-xl m-3");
		ieWarn.innerHTML = '<div id="browserChoice" class="relative text-center rounded-xl p-2" style="background:#DDDDDD"><h2 class="font-bold text-lg" style="color:darkred;"><?=_("Sorry, Internet Explorer does not work with MEGA World")?></h2>\
	<p><?=_('You must use an up-to-date browser such as')?></p>\
	<table class="m-auto"><tr>\
			<td><a href="https://www.google.com/chrome/"><img src="images/chrome.svg"></a></td>\
			<td><a href="https://www.microsoft.com/edge/"><img src="images/edge.svg"></a></td>\
			<td><a href="https://www.mozilla.org/firefox/new/"><img src="images/firefox.svg"></a></td>\
			<td><a href="https://opera.com/download"><img src="images/opera.svg"></a></td></tr>\
		<tr>\
			<td><a href="https://www.google.com/chrome">Chrome</a></td>\
			<td><a href="https://www.microsoft.com/edge">Edge</a></td>\
			<td><a href="https://www.mozilla.org/firefox/new">Firefox</a></td>\
			<td><a href="https://opera.com/download">Opera</a></td></tr></table></div>';
		var main = document.getElementsByTagName('main')[0];
		main.insertBefore(ieWarn, main.childNodes[0]);
	}
});
</script>

<?php } ?>
<!-- MathJAX to support complex mathematical notation in MEGA World -->
<!-- TODO: Is there a way to load this dynamically since most users won't need it (150kB+)? -->
<script type="text/javascript" id="MathJax-script" async
        src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js">
</script>
<script>
    // Localization strings used in mw.js
    const invalid_email = `<?=_('Invalid email address.')?>`;

</script>
<script src="js/mw.js"></script>


</head>

<body class="dark:bg-gray-900 dark:text-gray-200">


<main class="<?=isset($removePadding)?'':'px-2.5'?>">
<!-- Page specific content goes here --->
