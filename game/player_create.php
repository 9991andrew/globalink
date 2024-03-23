<?php
/**
 * Character Creation view
 */
$pageTitle = _("Create New Player");
include 'includes/mw_header.php';
?>

<style>

/* Die roll CSS */
#diceWrapper {
    position: relative;
    width: 300px;
    padding-top: 75px;
    margin: 0 auto;
	perspective: 100px;
}
#platform {
    width:75px;
    margin:auto;
    filter: drop-shadow(0 0 60px rgba(100,100,100,1));
    transform: translate(-15px, -63px) scale(0.25);

}

#dice span {
    position:absolute;
    margin:100px 0 0 100px;
    display: block;
    font-size: 2.5em;
    padding: 10px;
}
#dice {
    position: absolute;
    width: 200px;
    height: 200px;
    transform-style: preserve-3d;
    transform:rotateX(9deg) rotateY(7deg) rotateZ(2deg);
    transition: all 0.75s ease-out;
}
.side {
    position: absolute;
    width: 200px;
    height: 200px;
    background: #fff;
    box-shadow:inset 0 0 40px #ccc;
    border-radius: 40px;
}
#dice .cover, #dice .inner {
    background: #e0e0e0;
    box-shadow: none;
}
#dice .cover {
    border-radius: 0;
    transform: translateZ(0px);
}
#dice .cover.x {
    transform: rotateY(90deg);
}
#dice .cover.z {
    transform: rotateX(90deg);
}
#dice .front  {
    transform: translateZ(100px);
}
#dice .front.inner  {
    transform: translateZ(98px);
}
#dice .back {
    transform: rotateX(-180deg) translateZ(100px);
}
#dice .back.inner {
    transform: rotateX(-180deg) translateZ(98px);
}
#dice .right {
    transform: rotateY(90deg) translateZ(100px);
}
#dice .right.inner {
    transform: rotateY(90deg) translateZ(98px);
}
#dice .left {
    transform: rotateY(-90deg) translateZ(100px);
}
#dice .left.inner {
    transform: rotateY(-90deg) translateZ(98px);
}
#dice .top {
    transform: rotateX(90deg) translateZ(100px);
}
#dice .top.inner {
    transform: rotateX(90deg) translateZ(98px);
}
#dice .bottom {
    transform: rotateX(-90deg) translateZ(100px);
}
#dice .bottom.inner {
    transform: rotateX(-90deg) translateZ(98px);
}
.dot {
    position:absolute;
    width:46px;
    height:46px;
    border-radius:23px;
    background:#444;
    box-shadow:inset 5px 0 10px #000;
}
.dot.center {
    margin:77px 0 0 77px;
}
.dot.dtop {
    margin-top:20px;
}
.dot.dleft {
    margin-left:134px;
}
.dot.dright {
    margin-left:20px;
}
.dot.dbottom {
    margin-top:134px;
}
.dot.center.dleft {
    margin:77px 0 0 20px;
}
.dot.center.dright {
    margin:77px 0 0 134px;
}

#rerollButtonParent {
    display:flex;
    justify-content: center;
    align-items: center;
    margin-top: 20px;
    position:absolute;
    top:0;
    left:0;
    right:0;
    z-index:10;
}

#reroll {
    transition: opacity 0.1s ease-in;
    display:block;
    background-color:rgba(0,0,0,0.6);
}
#reroll:hover {
    background-color:rgba(0,0,0,0.9);
}

.light #reroll {
    background-color:rgba(255,255,255,0.6);
}
.light #reroll:hover {
    background-color:rgba(255,255,255,0.9);
}
</style>

<?php

$genders = Data::getGenders();
$races = Data::getRaces();
$birthplaces = Data::getBirthplaces();
$attributes = Data::getattributes();

?>

<h2 class="mb-2 mt-8 text-xl font-ocr text-center"><?=$pageTitle?></h2>
<form action="player_create_action.php" id="createPlayerForm" class="validate noAutoValidation" method="post">
    <div class="max-w-lg m-auto px-2">
        <!-- Name-->
        <div class="xs:grid xs:grid-cols-3 xs:items-center xs:border-t xs:border-gray-200/50 xs:dark:border-gray-700/50 py-3 px-2">
            <label for="playerName"><?=_('Character Name')?></label>
            <div class="mt-1 xs:mt-0 xs:col-span-2">
                <input id="playerName" name="playerName" type="text" class="required w-full" autofocus>
            </div>
            <div></div>
            <div class="xs:col-span-2" style="min-height:1.5rem;">
                <div class="error" id="playerNameInUseError">&nbsp;</div>
                <div class="error hidden" id="playerNameError"><?=_('Enter a name')?></div>
            </div>
        </div>

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

        <!-- Birthplace -->
        <div class="xs:grid xs:grid-cols-3 xs:items-center xs:border-t xs:border-gray-200/50 xs:dark:border-gray-700/50 py-3 px-2">
            <label for="birthplace"><?=_('Birthplace')?></label>
            <div class="mt-1 xs:mt-0 xs:col-span-2">
                <select id="birthplace" name="birthplace" class="required w-full">
                    <option></option>
                    <?php
                    $lastMapType="";
                    foreach ($birthplaces as $birthplace) {
                        if ($lastMapType != $birthplace['map_type_name']) {
                            if (strlen($lastMapType)) echo '</optgroup>';
                            echo '<optgroup label="'.$birthplace['map_type_name'].'">';
                        }
                        echo '<option value="'.$birthplace['id'].'">';
                        echo $birthplace['name'];
                        if ($birthplace['map_name']!=$birthplace['name']) {
                            echo ' ('.$birthplace['map_name'].')';
                        }
                        echo '</option>';
                        $lastMapType = $birthplace['map_type_name'];
                    }
                    if (strlen($lastMapType)) echo '</optgroup>';
                    ?>
                </select>
                <div class="error hidden" id="birthplaceError"><?=_('Select a birthplace')?></div>
            </div>
        </div>

    </div><!-- constrained section -->

    <h3 class="mb-2 mt-8 text-lg font-ocr text-center"><?=_('Player Attributes')?></h3>

	<div id="attributes" class="flex flex-wrap justify-center items-start m-auto max-w-screen-lg">
		<?php
		// For now just set value to 1. I'll do this in a better way later
		$_SESSION['newAttributeValues'] = Array();
		foreach ($attributes as $i => $attribute) {
			$_SESSION['newAttributeValues'][$attribute['id']] = rand(1, 18);
			echo '<div id="attribute'.$attribute['id'].'" class="m-3 flex flex-wrap items-center text-left flex-1" style="min-width:240px;">';
			echo '<div class="w-full flex justify-between items-end mb-2">';
			echo '<span class="font-ocr flex-1">'._($attribute['name']).'</span>';
			echo '<span class="inline-block w-10 rounded-md border border-black dark:border-gray-300 text-center" id="attributeValue'.$attribute['id'].'">'.$_SESSION['newAttributeValues'][$attribute['id']].'</span>';
			echo '</div>';
			echo '<div class="text-sm">'._($attribute['description']).'</div>';
			echo '</div>'; // .attribute
		}
		?>
	</div>

	<div id="diceWrapper">
        <div id="rerollButtonParent" class="formItem text-center">
            <button type="button" class="btn w-40" id="reroll"><?=_('Reroll')?>&nbsp;<?=_('Attributes')?></button>
        </div>
	  <div id="platform">
	    <div id="dice">
	      <div class="side front">
	        <div class="dot center"></div>
	      </div>
	      <div class="side front inner"></div>
	      <div class="side top">
	        <div class="dot dtop dleft"></div>
	        <div class="dot dbottom dright"></div>
	      </div>
	      <div class="side top inner"></div>
	      <div class="side right">
	        <div class="dot dtop dleft"></div>
	        <div class="dot center"></div>
	        <div class="dot dbottom dright"></div>
	      </div>
	      <div class="side right inner"></div>
	      <div class="side left">
	        <div class="dot dtop dleft"></div>
	        <div class="dot dtop dright"></div>
	        <div class="dot dbottom dleft"></div>
	        <div class="dot dbottom dright"></div>
	      </div>
	      <div class="side left inner"></div>
	      <div class="side bottom">
	        <div class="dot center"></div>
	        <div class="dot dtop dleft"></div>
	        <div class="dot dtop dright"></div>
	        <div class="dot dbottom dleft"></div>
	        <div class="dot dbottom dright"></div>
	      </div>
	      <div class="side bottom inner"></div>
	      <div class="side back">
	        <div class="dot dtop dleft"></div>
	        <div class="dot dtop dright"></div>
	        <div class="dot dbottom dleft"></div>
	        <div class="dot dbottom dright"></div>
	        <div class="dot center dleft"></div>
	        <div class="dot center dright"></div>
	      </div>
	      <div class="side back inner"></div>
	      <div class="side cover x"></div>
	      <div class="side cover y"></div>
	      <div class="side cover z"></div>
	    </div>
	  </div>
	</div><!--diceWrapper-->


	<div class="text-center mt-8 space-y-8">
		<button class="btn highlight w-40" id="submitPlayer"><?=_('Next')?> &nbsp;<i class="fas fa-arrow-right"></i></button>
		<div id="errorMessages" class="error">&nbsp;</div>
	</div>
    <div class="mt-20 text-center">
        <a class="link" href="player_select.php"><?=_('Cancel')?> <?=_('and')?> <?=_('Return to Player Selection')?></a>
    </div>
</form>

<script>
let validPlayerName = false;
// Run this code when the DOM is loaded, like jQuery $(document).ready()
document.addEventListener("DOMContentLoaded", function() {
	// When clicking on the reroll button, do an ajax request to reroll to refresh the values then update them on the page
	document.getElementById('reroll').addEventListener('click', function() {
	    const reroll = this;
        reroll.style.opacity='0';
        reroll.style.transition='opacity 0.1s ease-in';
        setTimeout(function(){
            reroll.style.transition='opacity 0.8s ease-out';
            reroll.style.opacity='1';
        },900);
		rollDice();

        // Fetch new stats from the server (and set them in a session where the user can't mess with them)
        fetch('player_create_reroll.php').then(response => response.json()).then(data => {
            for (const key in data) {
                document.getElementById('attributeValue'+key).textContent = data[key];
            }
        }).catch(err => {
            console.error('There was an error getting attribute values.');
            console.error(err);
        })
	});

	// Animate die when clicked
	document.getElementById('dice').addEventListener('click', function() {
		rollDice();
		var e = document.createEvent('MouseEvents');
		e.initMouseEvent('click');
		document.getElementById('reroll').dispatchEvent(e);
	});

	document.getElementById('playerName').addEventListener('change', checkPlayerName);

	document.getElementById('createPlayerForm').addEventListener("submit", function(e) {
		// Clear old error messages
		errorMessages = document.getElementById('errorMessages');
		errorMessages.innerHTML = '';
		errorMessages.style.display = 'none';
		// First check that form validates correctly (fields are filled in)
		if (formValidate()) {
			// To make this work properly I should be using a promise.
			// A user could potentially chante the name and click the button so an invalid name isn't caught
			if (validPlayerName !== true) {
				e.preventDefault();
				errorMessages.style.display = '';
				document.getElementById('playerNameInUseError').style.display='';
				errorMessages.innerHTML = '<?=_("You must choose a different player name.")?>';
			} else {
				return true;
			}
		} else {
				e.preventDefault();
				errorMessages.style.display = '';
				errorMessages.innerHTML = '<?=_("You must select all required fields above.")?>';
		}
	});

}); // end DOMContentLoaded

function rollDice() {
    // If the die laid completely flat, Safari would show it throuh the button.
    // This looks a little more natural this way anyways.
	var x=Math.floor(Math.random()*8)*90+90+2;
	var y=Math.floor(Math.random()*8)*90+180+2;
	var z=Math.floor(Math.random()*8)*90+180+Math.floor(Math.random()*10);
	document.getElementById('dice').style.transform='rotateX('+x+'deg) rotateY('+y+'deg) rotateZ('+z+'deg)';	
}

function checkPlayerName() {
    const inUseError = document.getElementById('playerNameInUseError');
    const submitBtn = document.getElementById('submitPlayer');

    function handleError(err = 'Something went wrong while checking for used player names.') {
        inUseError.innerHTML = err;
        inUseError.classList = 'error';
        submitBtn.classList.remove('highlight');
        validPlayerName = false;
        inUseError.style.display = '';
    }

    fetch('playername_check.php', { method: 'POST', body: new FormData(document.forms[0]) })
        .then(response => response.json()).then(data => {
            if (data.error === false) {
                inUseError.innerHTML = data.message;
                inUseError.classList = 'text-green-500 dark:text-green-400';
                submitBtn.classList.add('highlight');
                validPlayerName = true;
                inUseError.style.display = '';
            } else {
                // If data.error isn't false, we display some kind of error message
                handleError(data.message);
            }
        })
        .catch(err => {
            console.error(err);
            handleError(err);
        });

    // after the first time this is run, just run it on every input event
    document.getElementById('playerName').addEventListener('input', checkPlayerName);
}

</script>


<?php

include 'includes/mw_footer.php';