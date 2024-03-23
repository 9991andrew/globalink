<?php
$pageTitle = "MEGA World "._('Login');
include 'includes/mw_header.php';

session_start();
?>

<div class="text-center my-8">
    <div class="m-auto w-60 mb-3">
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
        <img src="images/<?php echo($_SESSION['logo']);?>">
<?php
}
?>
    </div>
    <p class="font-ocr"><b class="text-xl">M</b>ultiplayer <b class="text-xl">E</b>ducational <b class="text-xl">G</b>ame&nbsp;for&nbsp;<b class="text-xl">A</b>ll</p>
    <?php if (! preg_match('/^en/', $_SESSION['locale'])) {
        // Show a localized version of the "MEGA" acronym
        echo '<p>'._('Multiplayer Educational Game for Everyone').'</p>';
    }?>
</div>

<h2 class="mb-2 mt-8 text-xl font-ocr text-center"><?=_('Enter')?> MEGA World</h2>

<form id="loginForm" class="validate noAutoValidation max-w-lg m-auto px-2" method="post" action="login_check.php">
    <div class="xs:grid xs:grid-cols-3 xs:items-center xs:border-t xs:border-gray-200/50 xs:dark:border-gray-700/50 py-3 px-2">
        <label for="username"><?=_('Username')?></label>
        <div class="mt-1 xs:mt-0 xs:col-span-2">
            <input id="username" name="username" type="text" class="required w-full" autofocus />
            <div class="error hidden" id="usernameError"><?=_('Username')?> <?=_('is required')?></div>
        </div>
    </div>

    <div class="xs:grid xs:grid-cols-3 xs:items-center xs:border-t xs:border-gray-200/50 xs:dark:border-gray-700/50 py-3 px-2">
        <label for="password"><?=_('Password')?></label>
        <div class="mt-1 xs:mt-0 xs:col-span-2">
            <input id="password" name="password" type="password" class="required w-full" />
            <div class="error hidden" id="passwordError"><?=_('Password')?> <?=_('is required')?></div>
        </div>
    </div>

    <div class="xs:grid xs:grid-cols-3 xs:items-center xs:border-t xs:border-gray-200/50 xs:dark:border-gray-700/50 py-3 px-2">
        <!-- The specific 157px width makes the left edge of the button line up with the left edge of the fields while centered -->
        <div class="w-full col-span-3 m-auto text-center" style="width:157px;">
            <input type="submit" class="btn highlight w-full" value="<?=_('Log In')?>" />
        </div>
    </div>

    <div>
        <div id="errorMessages" class="error text-center" style="min-height:3rem;">
        </div>
    </div>

</form>

<div class="text-center space-y-12">
    <div><a class="link inline-block text-xl" href="register.php"><?=_('Register New Account')?></a></div>
    <div><a class="link inline-block" href="index.php"><?=_('About')?> MEGA World</a></div>
</div>


<script>

document.getElementById('loginForm').addEventListener("submit", function(e) {
	e.preventDefault();

	// Clear old error messages
	errorMessages = document.getElementById('errorMessages');
	errorMessages.innerHTML = '';
	// First check that form validates correctly (fields are filled in)
	if (formValidate()) {
		errorMessages.style.display = '';
		// Now do an AJAX submission to check the given login credentials new FormData(this)
        fetch(this.action, {method: 'POST', body: new FormData(this)})
            .then(response => response.json()).then(data => {
            // Everything is cool, user can get redirected to character selection screen.
            if (data.error === false) {
                window.location.href = 'map.php';
            } else {
                errorMessages.innerText = 'An unknown error occurred.';
                if (data.message.length) errorMessages.innerHTML = data.message;
            }
        }).catch(err => {
            errorMessages.innerText = 'Something went wrong.';
            console.error(err)
        })
	}
});

</script>

<?php
include 'includes/mw_footer.php';