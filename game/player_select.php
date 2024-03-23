<?php
/**
 * Character selection view
 */
$pageTitle = _("Select A Player");

// Need this here because I have to use it before the header loads on this page
include 'includes/mw_init.php';

// Prefetch most common tiles so they are ready to go when the player is selected
$tilePath = MapTile::getTilePath();
$prefetch = "";
foreach(MapTile::getTopTileIDs() as $tile) {
	$tileImage = $tilePath;
	// We assume user will have enabled high resolution tiles. Maybe later we can check a setting to use thumbnails
	// Use the filename in the DB without any file extension on the end.
	// (This assumes they all have one!)
	//if ($tile['map_tile_type_id_image']!=null) $tileImage.=preg_replace('/\.\w{3,4}$/', '', $tile['graphic_filename']);
	$tileImage.='tile'.sprintf('%04d', ((int)$tile['map_tile_type_id_image']??(int)$tile['map_tile_type_id']));

    if ( strpos( $_SERVER['HTTP_ACCEPT'], 'image/webp' ) !== false || !empty($_COOKIE['useWebP']) )
    {
        $tileImage.='.webp';
    } else {
        $tileImage.='.png';
    }

	// Could add a media query to this if I only use large images on large-screen devices...
	$prefetch.= '<link rel="prefetch" href="'.$tileImage.'" as="image">'."\n";
} //end foreach

include 'includes/mw_header.php';

echo '<h2 class="mb-2 mt-8 text-xl font-ocr text-center">'.$pageTitle.'</h2>';

// If the player previously was playing, we make the prior player leave the game
if (isset($_SESSION['playerId'])) {
    // Handle making the player leave the game
    $player = new Player($_SESSION['playerId']);
    $player->leave();
}

$playerIDs = $_SESSION['user']->getPlayerIds();
// We get the last player the user was using to highlight it.
$lastPlayerID = $_SESSION['user']->getLastPlayerId();

$players = array();
foreach($playerIDs as $playerID) {
	array_push($players, new Player((int)$playerID));
}

echo '<div id="playerList" class="flex justify-center space-x-6 flex-wrap">';
foreach ($players as $player) {
    // If this player has no avatar, generate a random one and take the player to the editor.
    if (sizeof($player->getPlayerAvatarImages()) < 2) {
        Player::generateRandomAvatar($player);
        // Kick the player to the avatar editor
        ?>
            <script>
                window.location.replace("avatar_edit.php?id=<?=$player->getId()?>&updated");
            </script>
        <?php
    }
	$hl = "";
	if ($lastPlayerID==$player->getId()) $hl= ' highlight';
	echo '<div class="flex flex-col items-center">';
	echo '<button class="btn'.$hl.' w-40 p-0 rounded-lg ">';
	echo '<a id="player'.$player->getId().'" tabindex="-1" href="player_select_action.php?player='.$player->getId().'" class="flex justify-center flex-wrap shadow-lg">';
	echo $player->showAvatar();
	echo '<div class="w-full bg-gray-100 dark:bg-gray-900 rounded-b-lg shadow-lg">'.$player->getName().'</div>';
	echo '</a>';
	echo '</button>';
    echo '<a class="link text-xs mt-2 mb-4 inline-block" href="avatar_edit.php?id='.$player->getId().'">'._('Edit Avatar').'</a>';
    echo '</div>';
}
echo '</div><!--playerList-->';

?>

<form action="player_create.php" class="text-center mt-8">
    <button class="btn w-40"><i class="fas fa-user-plus text-xl mt-1"></i><br><?=_('New Player')?></button>
</form>

<div class="text-center mt-10">
    <a href="logout.php"class="link"><?=_('Log Out')?></a>
</div>

<?php

include 'includes/mw_footer.php';