<?php
/**
 * Character Creation view
 */
$pageTitle = _("Edit Avatar");
include 'includes/mw_header.php';
?>

<style>
/* Be careful with this on touch interfaces */
.next, .prev {
    transition: all .05s ease-in-out;
}

.next:hover, .prev:hover {
	color:#ffe505;
	text-shadow: 0px 0px 4px rgba(0,0,0,0.9);
}

.next:active, .prev:active, .next.active, .prev.active  {
    color:#ffe505;
    text-shadow: 0px 0px 2px rgba(0,0,0,0.9);
    transform: scale(0.95);
}

.swatch.selected {
    border-color:black;
    border-width:2px;
    box-shadow: rgba(0,0,0,0.4) 1px 1px 2px;
}

.dark .swatch.selected {
    border-color:white;
}
</style>

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
	
if (!isset($player)) {
	echo '<p class="error">'._('Error').': '._("No player has been specified.").'</p>';
	exit();
}

// Make assoc array of player avatar images with typeid as key
// This allows us to access all the information about the player's current avatar
$playerImages = array();

foreach($player->getPlayerAvatarImages() as $playerAvatarImage) {
    $imageInfo = array();
    $imageInfo['aid'] = $playerAvatarImage->getId();
    $imageInfo['color1'] = $playerAvatarImage->getColor1();
    $imageInfo['color2'] = $playerAvatarImage->getColor2();
    $imageInfo['color3'] = $playerAvatarImage->getColor3();
    $imageInfo['color4'] = $playerAvatarImage->getColor4();
    $playerImages[$playerAvatarImage->getAvatarImageTypeId()] = $imageInfo;
}
//echo '<pre><code>'.var_dump($playerImages).'</code></pre>';


// Confirm that the current user owns the specified player
if ($player->getUserId() != $_SESSION['user']->getId()) {
    echo '<p class="error">'._('Error').': '._("The specified player does not belong to the logged in user.").'</p>';
	exit();	
}

echo '<h2 class="mb-2 mt-8 text-xl font-ocr text-center">'.$pageTitle.'</h2>';

if (isset($_GET['updated'])) {
?>
    <p class="text-center">
        <?=_('Welcome back to')?> MEGA World!<br>
        <?=_('Since you are new to V3, your avatar has been randomly regenerated. You can update features and colours to your liking.')?><br>
        <?=_('Come back and change your avatar any time from the')?>&nbsp;<a href="player_select.php"><?=_('Player Selection Screen')?></a>.
    </p>
<?php
}

echo '<h3 class="mt-4 mb-6 text-center"><div class="banner">'.$player->getName().'</div></h3 class="text-center">';
// Could print race and/or gender here but it's not too important

echo '<div class="flex justify-center flex-wrap">';

echo '<div class="w-full xs:w-80">';
	echo $player->showAvatar();
echo '</div>';

echo '<form class="sm:w-80" action="avatar_edit_action.php" method="post">';


// Get array of Item Types
$itemTypes = AvatarImage::getAvatarImageTypes();

foreach ($itemTypes as $itemType) {
	$typeId = $itemType['id'];
	$imageList = AvatarImage::getAvatarImages($typeId, $player->getGenderId(), $player->getRaceId());
	// We don't show beards for females, etc
	if (sizeof($imageList) > 0) {
	?>
        <div class="xs:grid xs:grid-cols-3 xs:items-center py-1">
            <label class="font-bold font-ocr" for="itemType<?=$typeId?>"><?=_($itemType['name'])?></label>
            <!-- If at least one option, show a select and picker interface -->
            <?php if (sizeof($imageList) >= 1) { ?>
			<div class="itemPicker mt-1 xs:w-52 xs:mt-0 xs:col-span-2 flex items-center space-x-3">
                <span class="prev" id="prev<?=$typeId?>"><i class="fas fa-angle-left text-3xl"></i></span>
				<select id="itemType<?=$typeId?>" <?php echo ($typeId==1)?'autofocus':''?> name="itemType<?=$typeId?>" class="flex-1 w-full">
					<?php
					// I need to determine if the player has any of these items.
					// Could check the existing array of itemsEquipped.
					// Could make a function to test of the player has a certain itemid
					// Could make a function to determine which items a player has of a certain category?
					foreach ($imageList as $image) {
						$s="";
						if ($playerImages[$typeId]['aid'] == $image->getId()) $s = ' selected="selected" ';
						echo '<option value="'.$image->getId().'" '.$s.' data-filename="'.$image->getFilename().'">'.$image->getName().'</option>';
					}
					?>
				</select>
                <span class="next" id="next<?=$typeId?>"><i class="fas fa-angle-right text-3xl"></i></span>
			</div>
            <?php }
            // If there are color options, show them here. (There could be around 50 for some items)
            $colors = AvatarImage::getAvatarImageTypeColors($typeId) ;
            // I need to determine what the currently selected image is and show the right number of color pickers

            // Show color pickers for each avatar part. Depending on which item is selected, 0-3 of these may be shown.
            if (sizeof($colors)) {
                for ($i=1; $i<=4; $i++) {
                    echo '<div class="swatches w-full block col-span-3 my-3 hidden" id="swatches'.$typeId.'color'.$i.'">';
                    echo '<input type="hidden"
                        name="type'.$typeId.'color'.$i.'"
                        id="type'.$typeId.'color'.$i.'"
                        value="'.(isset($playerImages[$typeId])?$playerImages[$typeId]['color'.$i]:'').'" />';
                    foreach ($colors as $color) {
                        $s = "";
                        if ( isset($playerImages[$typeId]) && $playerImages[$typeId]['color'.$i] == $color ) {
                            $s=" selected";
                        }
                        echo '<span class="swatch inline-block border border-2 rounded-full w-5 h-5 dark:border-black'.$s.'" style="background-color:' . $color . '"></span>';
                    }
                    echo '</div>';
                }
            }
            ?>
		</div>
	<?php
	}
}

?>

	<div class="my-8 flex justify-around">
		<button class="btn highlight" id="submitAvatar"><?=_('Save Avatar')?></button>
		<a class="btn" id="submitAvatar" href="player_select.php"><?=_('Cancel')?></a>
	</div>
    <p><?=_('Use')?> <kbd class="inline-block border-2 rounded shadow px-1 bg-gray-500/30"><i class="fas fa-arrow-left"></i></kbd>&nbsp;<?=_('and')?> <kbd class="inline-block border-2 rounded shadow px-1 bg-gray-500/30"><i class="fas fa-arrow-right"></i></kbd>&nbsp; <?=_('keys to browse options.')?></p>
	<input type="hidden" name="playerId" id="playerId" value="<?=$player->getId();?>" />
<?php

echo '</form>';
?>

</div><!-- end form/avatarView div -->

<script>

// Get structure with all embedded SVG in it
const avatarImages=<?php
    $imageArray = array();
    foreach(AvatarImage::getAvatarImageTypes() as $type) {
        $images = AvatarImage::getAvatarImages($type['id'], $player->getGenderId(), $player->getRaceId());
        $imagesArray = array();
        foreach ($images as $image) {
            $imageData = array();
            $imageData['id'] = $image->getId();
            $imageData['svg'] = $image->getSvgCode();
            $imageData['filename'] = $image->getFilename();
            $imageData['colors'] = $image->getColorQty();
            $imagesArray['image'.$image->getId()] = $imageData;
        }
        $imageArray['type'.$type['id']] = $imagesArray;
    }

    echo json_encode($imageArray);

?>;

// Run this code when the DOM is loaded, like jQuery $(document).ready()
document.addEventListener("DOMContentLoaded", function() {

	let itemPickers = document.querySelectorAll('.itemPicker select');

	itemPickers.forEach(
		function(picker) {
			picker.addEventListener('change', updateAvatar, false);
		}
	);

    itemPickers.forEach(
        function(picker) {
            picker.addEventListener('focus', showCurrentItemSwatches, false);
        }
    );

	// Handle clicking on the next and prev arrows
	let nexts = document.querySelectorAll('.next');
	nexts.forEach(
		function(next) {
			next.addEventListener('click', incr);
		}
	);
	
	let prevs = document.querySelectorAll('.prev');
	prevs.forEach(
		function(prev) {
			prev.addEventListener('click', decr);
		}
	);

	let swatches = document.querySelectorAll(".swatch");
	swatches.forEach(
	    function(swatch) {
	        swatch.addEventListener('click', setItemColor, false);
        }
    );

	// We want mouseover events on swatches to change the color, then set it back
    swatches.forEach(
        function(swatch) {
            swatch.addEventListener('mouseenter', setItemColor, false);
        }
    );

    swatches.forEach(
        function(swatch) {
            swatch.addEventListener('mouseleave', resetItemColor, false);
        }
    );



	// Ensure the first item's swatches are displayed
    showCurrentItemSwatches();


});

function decr() {
    let catid;
    if (this.classList?.contains('prev')) catid = this.id.replace('prev', '');
    else catid=document.activeElement.id.replace('itemType', '');
    var sel = document.getElementById('itemType'+catid);
    sel.selectedIndex-=1;
    if (sel.selectedIndex < 0) sel.selectedIndex=sel.options.length-1;
    triggerChange(sel);
    // This is very annoying in iOS Safari as it pops open the select every time
    // document.getElementById('itemType'+catid).focus();
}

function incr() {
    let catid;
    if (this.classList?.contains('next')) catid = this.id.replace('next', '');
    else catid=document.activeElement.id.replace('itemType', '');
    var sel = document.getElementById('itemType'+catid);
    sel.selectedIndex+=1;
    if (sel.selectedIndex < 0) sel.selectedIndex+=1;
    triggerChange(sel);
    // This is very annoying in iOS Safari as it pops open the select every time
    // document.getElementById('itemType'+catid).focus();
}

// TODO: Add support for using images in addition to SVG
function updateAvatar() {
	// var filename = this.options[this.selectedIndex].getAttribute("data-filename");
	let typeId = this.id.replace('itemType', '');
    let imageId = this.value;
	let svg = avatarImages["type"+typeId]["image"+imageId]["svg"];

	this.focus();

	// replace the contents of the svg groups with the relevant class with this new code
    svgSelector = document.querySelector('.ait'+typeId);
    svgSelector.innerHTML = svg;

    // trigger select on color radio buttons for this item to reapply the fill colors
    for (let i=1; i<=4; i++) {
        let colorInput = document.querySelector('#type'+typeId+'color'+i);
        if (colorInput) {
            svgSelector.querySelectorAll('.color'+i).forEach((e)=>e.setAttribute('fill', colorInput.value));
        }
    }

	// var relatedImage=document.querySelector('.avatarItemImg.avatarItemCat'+catid);
	// relatedImage.src=filename;
}

function setItemColor() {
    let color = this.style.backgroundColor;
    // Sets the item color
    let typeId = this.parentElement.id.replace(/swatches(\d+)(\w+)/, '$1');
    let colorClass = this.parentElement.id.replace(/swatches(\d+)(\w+)/, '$2');
    // This is supposed to ensure a non-existing item is added.
    // document.getElementById('itemType'+typeId).dispatchEvent(new Event('change'));
    let svgSelector = '.ait'+typeId+' .'+colorClass;
    console.log(svgSelector);

    if (event.type=="click") {
        this.parentElement.querySelectorAll('.swatch').forEach((swatch)=>swatch.classList.remove('selected'));
        this.classList.add('selected');
        this.parentElement.querySelector('input[type="hidden"]').value=color;
    }
    document.querySelectorAll(svgSelector).forEach((e)=>e.setAttribute('fill', color));
}

function resetItemColor() {
    let svgSelector = '.ait'+this.parentElement.id.replace(/swatches(\d+)(\w+)/, '$1 .$2');
    let color = this.parentElement.querySelector('input[type="hidden"]').value;
    document.querySelectorAll(svgSelector).forEach((e)=>e.setAttribute('fill', color));
}

function triggerChange(select) {
	const event = new Event('change');
	select.dispatchEvent(event);
}

document.onkeydown = checkKey;
// Keyboard handling to cycle through items.
// Note to prevent iOS safari from cycling through items, I'm using
// e.preventDefault(), which could potentially interfere with some keyboard use.\
// Another option is just to check for browser version and immediately return
// since this isn't necessary there
function checkKey(e) {
// Disable keyboard shortcuts if we are in a form.
    const itemType = document.activeElement.id.replace('itemType', '');
    if (!itemType) return;
    const prev = document.getElementById('prev'+itemType);
    const next = document.getElementById('next'+itemType);
    e = e || window.event;

    if (e.keyCode == '37' || e.keyCode == '65') {
        e.preventDefault();
        // left arrow
        prev.classList.add('active');
        decr();
        setTimeout(function(){
            prev.classList.remove('active');
        }, 60);

    } else if (e.keyCode == '39' || e.keyCode == '68') {
        e.preventDefault();
        // right arrow
        next.classList.add('active');
        incr();
        setTimeout(function(){
            next.classList.remove('active');
        }, 60);

    }
}

// Hide all swatches except for the focused item that is selected
function showCurrentItemSwatches() {
    document.querySelectorAll('.swatches').forEach((el) => el.style.display = 'none');
    let activeType = document.activeElement.id.replace(/\D*/, '');
    let activeSwatches = document.querySelector('#swatches'+activeType+'color1');
    if (activeSwatches) {
        activeSwatches.style.display = 'block';
        let currentImage = document.getElementById('itemType'+activeType).value;
        let colors = (avatarImages['type'+activeType]['image'+currentImage]['colors']);
        if (colors>=2) document.querySelector('#swatches'+activeType+'color2').style.display='block';
        if (colors>=3) document.querySelector('#swatches'+activeType+'color3').style.display='block';
        if (colors>=4) document.querySelector('#swatches'+activeType+'color4').style.display='block';
    }
}
</script>

<?php
/* FIXME: There are some issues with iOS Safari:
First, it already does left/right arrow handling for cycling through select items, so that's not necessary here.
Second, when I programmatically focus a select, it pops open the menu each time, which is quite annoying.
I could get around these issues by not using a select at all, and just making an element that looks and works
similarly to this, but I'm not sure if it's worth the trouble at this point.
*/

include 'includes/mw_footer.php';