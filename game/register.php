<?php
$pageTitle = "MEGA World: "._('Register New Account');
include 'includes/mw_header.php';
?>


<div class="text-center my-8 font-ocr">
    <div class="m-auto w-60 mb-3">
        <!-- Include the SVG so we can use CSS to alter it -->
        <?php include 'images/mwlogo.svg'; ?>
    </div>
    <p><b class="text-xl">M</b>ultiplayer <b class="text-xl">E</b>ducational <b class="text-xl">G</b>ame&nbsp;for&nbsp;<b class="text-xl">A</b>ll</p>
    <?php if (! preg_match('/^en/', $_SESSION['locale'])) {
        // Show a localized version of the "MEGA" acronym
        echo '<p>'._('Multiplayer Educational Game for Everyone').'</p>';
    }?>
</div>

<h2 class="mb-2 mt-8 text-xl font-ocr text-center"><?=_('Register New Account')?></h2>
<form id="registerForm" class="validate noAutoValidation max-w-lg m-auto px-2" method="post" action="register_action.php">
    <div class="xs:grid xs:grid-cols-3 xs:items-center xs:border-t xs:border-gray-200/50 xs:dark:border-gray-700/50 py-3 px-2">
        <label for="username"><?=_('Username')?></label>
        <div class="mt-1 xs:mt-0 xs:col-span-2">
            <input id="username" name="username" type="text" class="required w-full" />
        </div>
        <div></div>
        <div class="xs:col-span-2" style="min-height:1.5rem;">
            <div id="usernameError" class="error hidden"><?=_('Username')?> <?=_('is required')?></div>
            <div id="usernameStatus" class="transition-opacity duration-200 ease-in-out"></div>
        </div>
    </div>

    <div class="xs:grid xs:grid-cols-3 xs:items-center xs:border-t xs:border-gray-200/50 xs:dark:border-gray-700/50 py-3 px-2">
        <label for="password"><?=_('Password')?></label>
        <div class="mt-1 xs:mt-0 xs:col-span-2">
            <input id="password" name="password" type="password" class="required w-full" placeholder="<?=_('At least 6 characters')?>" />
        </div>
        <div></div>
        <div class="xs:col-span-2">
            <div class="error hidden" id="passwordError"><?=_('Password')?> <?=_('is required')?></div>
            <div id="passwordStatus" class="error"></div>
        </div>

        <div class="col-span-3 m-2"></div>

        <label for="passwordConfirm"><?=_('Confirm Password')?></label>
        <div class="mt-1 xs:mt-0 xs:col-span-2">
            <input id="passwordConfirm" name="passwordConfirm" type="password" class="required w-full" />
            <div class="error hidden" id="passwordConfirmError"><?=_('Re-enter your password')?></div>
            <div class="error hidden" id="passwordConfirmMatchError"><?=_('Your passwords do not match')?></div>
        </div>
    </div>

    <div class="xs:grid xs:grid-cols-3 xs:items-center xs:border-t xs:border-gray-200/50 xs:dark:border-gray-700/50 py-3 px-2">
        <label for="email"><?=_('Email')?> <span class="ml-2 text-xs opacity-70"><?=_('Optional')?></span></label>
        <div class="mt-1 xs:mt-0 xs:col-span-2">
            <input id="email" name="email" type="text" class="email w-full" />
        </div>
        <div></div>
        <div class="xs:col-span-2 mt-1 text-xs"><?=_('This email address may be used for password resets.')?></div>
        <div class="xs:col-span-2" style="min-height:1.5rem;">
            <div id="emailError" class="error hidden"><?=_('A valid email address is required')?></div>
            <div id="emailStatus" class="transition-opacity duration-200 ease-in-out"></div>
        </div>
    </div>

    <div class="xs:grid xs:grid-cols-3 xs:items-center xs:border-t xs:border-gray-200/50 xs:dark:border-gray-700/50 py-3 px-2">
        <label for="language"><?=_('Language')?></label>
        <div class="mt-1 xs:mt-0 xs:col-span-2">
            <select id="language" name="language" type="text" class="required w-full">
            <?php
                foreach(Data::getLanguages() as $language) {
                    echo '<option value="'.$language['locale_id'].'" '.($language['locale_id']==Config::DEFAULT_LOCALE?'selected':'').'>'.
                        $language['name'].($language['name']!=$language['native_name']?' ('.$language['native_name'].')':'').'</option>';
                }
            ?>
            </select>
        </div>
    </div>

    <div class="xs:grid xs:grid-cols-3 xs:items-center xs:border-t xs:border-gray-200/50 xs:dark:border-gray-700/50 py-3 px-2">
        <div class="w-full col-span-3 m-auto text-center" style="width:157px;">
            <button class="btn highlight w-full"><?=_('Register')?></button>
        </div>

    </div>

    <div id="errorMessages" class="formGroup error text-center" style="min-height:2.5rem;"></div>

</form>

<div class="text-center mt-8">
    <a class="link" href="index.php"><?=_('Return to Home Page')?></a>
</div>

<script>
let validUsername = false;
// When a user enters a username, check against the database to ensure it's not already taken
// This uses an AJAX request so I'd have to return a promise, I can't just return a boolean
function checkUsername() {
	// use xhr to fetch username_check.php
	const usernameError = document.getElementById('usernameError');
	const usernameStatus = document.getElementById('usernameStatus');
	const username=document.getElementById('username').value.trim();

	if (username.length === 0) {
		usernameError.classList.remove('hidden');
		// don't check that a blank string user exists
        usernameError.style.display='block';
        usernameStatus.innerHTML='';
		return;
	} else {
        usernameError.style.display='none';
    }

    // JDL 2021-12-23: Rewritten with the fetch API
    fetch('username_check.php?username='+username).then(response => response.json()).then(data => {
        let htmlClass = 'text-red-600 dark:text-red-500';
        if (typeof data.message==='string' && typeof data.error === 'boolean') {
            if (data.error === false) {
                htmlClass = 'text-green-500 dark:text-green-400';
                validUsername = true;
            } else validUsername = false;

            usernameStatus.innerHTML=`<span class="${htmlClass}">${data.message}</span>`;
        } else {
            // I don't localize this error text, it is for the developer if something is broken
            usernameStatus.innerHTML='No message was received from username check.';
        }
    }).catch(err => {
        // We reached our target server, but it returned an error
        usernameStatus.innerHTML='There was an error getting username info.';
        console.error(err);
    });
}

document.getElementById('username').addEventListener('input', checkUsername);

function checkPassword() {
	let valid = true;
	const password = document.getElementById('password').value;
	const passwordStatus = document.getElementById('passwordStatus');
	passwordStatus.innerHTML = '';
	document.getElementById('passwordStatus').style.display='';
	// Check that password is over 6 characters
	
	if (password.length > 0) document.getElementById('passwordError').style.display='none';
	if (password.length <= 6) {
		passwordStatus.innerHTML = '<?=_('Must be at least 6 characters.')?>';
		valid = false;
	}

	return valid;
}

// Ensure password requirements are met
document.getElementById('password').addEventListener('change', function(){
	checkPassword();
});

function confirmPassword() {
	let valid = true;
	const pwConfirmError = document.getElementById('passwordConfirmError');
	const pwConfirmMatchError = document.getElementById('passwordConfirmMatchError');
	pwConfirmError.style.display = 'none';
	pwConfirmMatchError.style.display = 'none';
	if (document.getElementById('password').value !== document.getElementById('passwordConfirm').value) {
		pwConfirmMatchError.classList.remove('hidden');
		document.getElementById('passwordConfirmMatchError').style.display = '';
		valid = false;
	}

	return valid;
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('passwordConfirm').addEventListener('change', function () {
        confirmPassword();
    });

    // Handle submit validation
    document.getElementById('registerForm').addEventListener("submit", function (e) {
        // Clear old error messages
        // First check that form validates correctly (fields are filled in)
        let valid = true;
        if (!formValidate()) valid = false;
        if (!checkPassword()) valid = false;
        if (!confirmPassword()) valid = false;

        if (!validUsername) valid = false;

        if (!valid) e.preventDefault();
    });

});// DOMContentLoaded

</script>

<?php
include 'includes/mw_footer.php';