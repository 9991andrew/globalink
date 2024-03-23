<?php
/**
 * Character Creation view
 */
$pageTitle = _("Create New Guardian");
include 'includes/mw_header.php';
?>

<?php

// Use either post or get for id
if (!isset($_POST['id']) && isset($_GET['id'])) {
	$_POST['id'] = $_GET['id'];
}

// if no playerid is specified, show error
if (isset($_POST['id'])) {
	$player = new Player((int)$_POST['id']);
} elseif (isset($_SESSION['playerId'])) {
	$player = new Player((int)$_SESSION['playerId']);
}

// Confirm that the current user owns the specified player
if ($player->getUserId() != $_SESSION['user']->getId()) {
    echo '<p class="error">'._('Error').': '._("The specified player does not belong to the logged in user.").'</p>';
	exit();	
}

$genders = Data::getGenders();
$races = Data::getRaces();

?>

<h2 class="mb-2 mt-8 text-xl font-ocr text-center"><?=$pageTitle?></h2>
<?php
echo  ' <form action="guardian_create_action.php?id='.$_GET['id'].'" id="createPlayerForm" class="validate noAutoValidation" method="post">'
?>
    <div class="max-w-lg m-auto px-2">
        <!-- Gender -->
        <div class="xs:grid xs:grid-cols-3 xs:items-center xs:border-t xs:border-gray-200/50 xs:dark:border-gray-700/50 py-3 px-2">
            <label for="gender"><?=_('Gender')?></label>
            <div class="mt-1 xs:mt-0 xs:col-span-2">
                <select id="gender" name="gender" class="required w-full">
                    <option></option>
                    <?php
                    // These are here so they will be scanned by xgettext
                    _('Male');
                    _('Female');
                    foreach ($genders as $i => $gender) {
                        echo '<option value="'.$gender['id'].'">'._($gender['name']).'</option>';
                    }?>
                </select>
                <div class="error hidden" id="genderError"><?=_('Select a gender')?></div>
            </div>
        </div>

        <!-- Race -->
        <div class="xs:grid xs:grid-cols-3 xs:items-center xs:border-t xs:border-gray-200/50 xs:dark:border-gray-700/50 py-3 px-2">
            <label for="race"><?=_('Race')?></label>
            <div class="mt-1 xs:mt-0 xs:col-span-2">
                <select id="race" name="race" class="required w-full">
                    <option></option>
                    <?php foreach ($races as $i => $race) {
                        echo '<option value="'.$race['id'].'">'._($race['name']).'</option>';
                    }?>
                </select>
                <div class="error hidden" id="raceError"><?=_('Select a race')?></div>
            </div>
        </div>

        <!-- color -->
        <div class="xs:grid xs:grid-cols-3 xs:items-center xs:border-t xs:border-gray-200/50 xs:dark:border-gray-700/50 py-3 px-2">
            <label for="color"><?=_('Color')?></label>
            <div class="mt-1 xs:mt-0 xs:col-span-2">
                <input value="#1919f5" type="color" name="color" class="required w-full"><br>
                <div class="error hidden" id="colorError"><?=_('Pick a color')?></div>
            </div>
        </div>

    </div><!-- constrained section -->

	<div class="text-center mt-8 space-y-8">
		<button class="btn highlight w-40" id="submitPlayer"><?=_('Next')?> &nbsp;<i class="fas fa-arrow-right"></i></button>
		<div id="errorMessages" class="error">&nbsp;</div>
	</div>
    <div class="mt-20 text-center">
        <a class="link" href="map.php"><?=_('Cancel')?> <?=_('and')?> <?=_('Return to Map')?></a>
    </div>
</form>
<?php
    $guardian = new Guardian();
    $IDs = $guardian->getGuardianIds();
    echo '<h2 class="mb-2 mt-8 text-xl font-ocr text-center">Use A Popular Guardian Avatar</h2>';
    echo '<div id="playerList" class="flex justify-center space-x-6 flex-wrap">';
    $popularGuardian = array_count_values($IDs);
    foreach ($popularGuardian as $value => $count ) {
        $hl = "";
        echo '<div class="flex flex-col items-center m-5">';
        echo '<a href="guardian_popular_action.php?guardian='.$value.'&id='.$_GET['id'].'">';
        echo '<button class="btn'.$hl.' w-40 p-0 rounded-lg ">';
        // echo '<a id="player'.$player->getId().'" tabindex="-1" href="player_select_action.php?player='.$player->getId().'" class="flex justify-center flex-wrap shadow-lg">';
        echo $guardian->showGuardianAvatarById($value);
        echo '<div class="w-full bg-gray-100 dark:bg-gray-900 rounded-b-lg shadow-lg" style="color: '.$guardian->getGuardianColor($value).'">Guardian</div>';
        echo '<a class="link text-xs mt-2 mb-4 inline-block">Used By '.$count.'</a>';
        echo '</a>';
        echo '</button>';
        echo '</a>';
        echo '</div>';
    }
    echo '</div><!--playerList-->';
?>

<?php
    $guardian = new Guardian();
    $IDs = $guardian->getAllGuardianIds();
    echo '<h2 class="mb-2 mt-8 text-xl font-ocr text-center">All Guardians</h2>';
    echo '<div id="playerList" class="flex justify-center space-x-6 flex-wrap">';
    foreach ($IDs as $Id) {
        $hl = "";
        echo '<div class="flex flex-col items-center m-5">';
        echo '<a href="guardian_popular_action.php?guardian='.$Id.'&id='.$_GET['id'].'">';
        echo '<button class="btn'.$hl.' w-40 p-0 rounded-lg ">';
        // echo '<a id="player'.$player->getId().'" tabindex="-1" href="player_select_action.php?player='.$player->getId().'" class="flex justify-center flex-wrap shadow-lg">';
        echo $guardian->showGuardianAvatarById($Id);
        echo '<div class="w-full bg-gray-100 dark:bg-gray-900 rounded-b-lg shadow-lg" style="color: '.$guardian->getGuardianColor($Id).'">Guardian</div>';
        echo '</a>';
        echo '</button>';
        echo '</a>';
        echo '</div>';
    }
    echo '</div><!--playerList-->';
?>

<script>
let validPlayerName = false;
// Run this code when the DOM is loaded, like jQuery $(document).ready()
document.addEventListener("DOMContentLoaded", function() {
	document.getElementById('createPlayerForm').addEventListener("submit", function(e) {
		// Clear old error messages
		errorMessages = document.getElementById('errorMessages');
		errorMessages.innerHTML = '';
		errorMessages.style.display = 'none';
		// First check that form validates correctly (fields are filled in)
		if (formValidate()) {
		} else {
				e.preventDefault();
				errorMessages.style.display = '';
				errorMessages.innerHTML = '<?=_("You must select all required fields above.")?>';
		}
	});
}); // end DOMContentLoaded
</script>

<?php

include 'includes/mw_footer.php';