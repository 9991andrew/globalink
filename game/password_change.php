<?php
$pageTitle = "MEGA World: "._('Change Password');
include 'includes/mw_header.php';
?>


<h2 class="mb-2 mt-8 text-xl font-ocr text-center"><?=_('Change Password')?></h2>
<form id="passwordChangeForm" class="validate noAutoValidation max-w-lg m-auto px-2" method="post" action="password_change_action.php">
    <div class="xs:grid xs:grid-cols-3 xs:items-center xs:border-t xs:border-gray-200/50 xs:dark:border-gray-700/50 py-3 px-2">
        <label for="username"><?=_('Username')?></label>
        <div class="mt-1 xs:mt-0 xs:col-span-2">
            <span class="font-bold"><?=$_SESSION['user']->getUsername()?></span>
        </div>
        <div></div>
    </div>

    <div class="xs:grid xs:grid-cols-3 xs:items-center xs:border-t xs:border-gray-200/50 xs:dark:border-gray-700/50 py-3 px-2">
        <label for="current_password"><?=_('Current Password')?></label>
        <div class="mt-1 xs:mt-0 xs:col-span-2">
            <input id="current_password" name="current_password" type="password" class="required w-full" />
        </div>
        <div></div>
        <div class="xs:col-span-2">
            <div class="error hidden" id="current_passwordError"><?=_('Password')?> <?=_('is required')?></div>
        </div>
    </div>

    <div class="xs:grid xs:grid-cols-3 xs:items-center xs:border-t xs:border-gray-200/50 xs:dark:border-gray-700/50 py-3 px-2">
        <label for="password"><?=_('New Password')?></label>
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
        <div class="col-span-3 m-auto text-center">
            <button class="btn highlight w-full"><?=_('Change Password')?></button>
        </div>
    </div>
</form>

<div class="text-center mt-6">
    <a class="link" href="map.php"><?=_('Cancel')?> &mdash; <?=_('Return to Game')?></a>
</div>

<script>
function checkPassword() {
	var valid = true;
	var password = document.getElementById('password').value;
	var passwordStatus = document.getElementById('passwordStatus');
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
	var valid = true;
	var pwConfirmError = document.getElementById('passwordConfirmError');
	var pwConfirmMatchError = document.getElementById('passwordConfirmMatchError');
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
    document.getElementById('passwordChangeForm').addEventListener('submit', function (e) {
        // Clear old error messages
        // First check that form validates correctly (fields are filled in)
        let valid = true;
        if (!formValidate()) valid = false;
        if (!checkPassword()) valid = false;
        if (!confirmPassword()) valid = false;

        if (!valid) e.preventDefault();
    });

});// DOMContentLoaded

</script>

<?php
include 'includes/mw_footer.php';