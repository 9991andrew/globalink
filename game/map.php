<?php
/**
 * This is the main game interface for playing MEGA World. This displays view of players'
 * current map showing their location, and has collapsible panels that show their
 * inventory, chat messages, current quests, or player stats.
 *
 * When a player encounters a building or NPC on a map tile, information about this will be
 * displayed in an information panel at the top left of the screen.
 *
 * This interface is designed to work well on mobile and expand to large screens.
 *
 * All game synchronization is performed through AJAX requests using the post() interface that
 * accepts a FormData object and callbacks for successful and failed transactions.
 *
 * When a transaction fails it will typically use the showToast() function to display
 * a message to the user.
 *
 * Extended information (such as NPC interactions) is shown in a modal dialog using
 * the showModal() function.
 */

// This is for debugging. Remove this in production.
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
include 'includes/mw_init.php';
$pageTitle = "MEGA World";
$removePadding = true;

// If no playerId is set, redirect user to player_select.php
if (!isset($_SESSION['playerId'])) {
    header('Location: player_select.php');
    exit();
}
try {
    $player = new Player((int)$_SESSION['playerId'], false, true);
    $guardian = new Guardian();
} catch (Exception $e) {
    // Clear the lastPlayerId because there's a chance this player doesn't exist or there's something wrong with it.
    if (get_class($e) == 'InvalidPlayerException') {
        unset($_SESSION['playerId']);
        $_SESSION['user']->setLastPlayerId();
    }

    include 'includes/mw_header.php';
    echo _("Something went wrong").'...<br>' . $e->getMessage();
    echo '<p class="text-center my-2"><a class="link" href="login.php">'._("Try logging in again").'</a>.</p>';
    exit();
}

include 'includes/mw_header.php';

$map = new Map($player->getMapId());
$_SESSION['mapId'] = $map->getId();
// Reset chatId so all messages from player's session will load
// Originally this was null but that seems to be treated the same as !isset
$_SESSION['lastChatId'] = 0;
$pX = $player->getX();
$pY = $player->getY();

// We use this in JS for information about other players on the same map.
$clientVisiblePlayersData = $player->getClientVisiblePlayersData();

// Specify in the session which players we know about so we don't get redundant data about them
$_SESSION['knownPlayerIds'] = array();
foreach ($clientVisiblePlayersData as $p) {
    array_push($_SESSION['knownPlayerIds'], $p['id']);
}


?>
<style>
/* Styles specific to map.php */
body {
    margin:0;
    padding:0;
    position:fixed;
    width:100%;
}
.encounter-box {
    display: none; 
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 90vw; 
    height: 80vh;
    background-color: #1a1a1a; 
    border-radius: 10px; 
    padding: 20px; 
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.5); 
    z-index: 1000; 
    overflow: auto; 
    font-family: 'Arial', sans-serif; 
    color: #f0f0f0; 
}


.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background-color: rgba(0, 0, 0, 0.75); 
    z-index: 999; 
    display: none; 
}


.player-info, .monster-info {
    text-align: center;
    color: #f0f0f0; 
}

.encounter-actions button {
    background-color: #4CAF50; 
    color: #ffffff; 
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    cursor: pointer;
    margin-top: 10px; 
}

.encounter-actions button:hover {
    background-color: #367c39; 
}

.monster-info h3, .monster-info p {
    color: #ff6347; 
}

.encounter-content {
    display: flex;
    flex-direction: row; 
    justify-content: space-between; 
    align-items: center; 
    height: 100%; 
    padding: 20px; 
}

.player-info, .monster-info {
    text-align: center;
    margin-bottom: 20px; 
}

.encounter-actions {
    flex-basis: 100px; 
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    gap: 10px; 
}
.player-info, .monster-info {
    flex: 1; 
    text-align: center; 
}
.encounter-title h2 {
    color: #E53E3E; 
    text-align: center; 
    margin-bottom: 30px; 
    font-size: 28px; 
    margin-top: 20px; 
}
.encounter-message {
    color: red;
    font-size: 1.2em;
    margin-bottom: 10px;
    text-align: center;
}
/* Style for gear labels */
.gear-label {
    font-size: 20px; /* Adjust the size as needed */
    font-weight: bold; /* Makes the font bold */
    margin-top: 20px; /* Adds some space above the label for better separation */
}

/* Style for the list to add some space below */
ul {
    margin-bottom: 20px; /* Adds some space below the list for better separation */
}

/* Background images for each type of map tile */
<?=MapTile::getMapTileStyles()?>
</style>

<?php // I think that height: calc is to allow dynamic adjustment of the screen height for phone keyboards ?>
<div id="modalBackdrop" class="modal-backdrop"></div>

<!-- Encounter Box (Modal) -->
<div id="encounterForm" class="encounter-box">
    <div class="encounter-title">
        <h2>You have encountered a monster...</h2>
    </div>

    <div class="encounter-content">
        <div id="encounterMessage" class="encounter-message"></div>
        <div class="player-section">
            <div class="player-info">
                <h3 id="playerName">Player Name</h3>
                <p id="playerHP">HP: -1</p>
                <!-- Player's Weapons -->
                <h3>Weapons</h3>
                <ul id="weaponList"></ul>
                <!-- Player's Potions -->
                <h3>Potions</h3>
                <ul id="potionList"></ul>
                <!-- Player's Armor -->
                <h3>Armor</h3>
                <ul id="armorList"></ul>
            </div>
        </div>
        
    <!-- Encounter Actions (Buttons) -->
    <div class="encounter-actions">
        <button onclick="handleAttack('small')">Small Attack</button>
        <button onclick="handleAttack('medium')">Medium Attack</button>
        <button onclick="handleAttack('large')">Large Attack</button>
    </div>

        <!-- Monster Info on the Right -->
        <div class="monster-info">
            <h3 id="monsterName">Monster Name</h3>
            <p id="monsterHP">HP: 50</p>
            <p id="monsterDrops">Drops: Gold Coin</p>
        </div>
    </div>
</div>

<div id="gameUI" class="flex flex-col h-screen" style="height:calc(var(--vh, 1vh) * 100);">
    <div id="statusBar" class="font-ocr flex items-center justify-between z-30 shadow-md-dark text-sm text-white bg-black dark:text-black dark:bg-white">
        <button type="button" id="menu"
                class="text-black bg-gray-200 px-1 dark:text-white dark:bg-gray-800 hover:bg-white dark:hover:bg-gray-700 focus:outline-none focus:ring focus:ring-cyan-200/50">
            <span class="text-xl mx-2">&#9776;</span><span class="hidden xs:inline-block mr-2"><?=_('Menu')?></span>
        </button>
        <div class="max-w-lg m-auto flex flex-1 justify-around p-0.25 pt-0.5">
            <span id="health" class="sm:relative cursor-default group">
                <i class="fas fa-heartbeat mr-1.5"></i><strong><?=$player->getHealth()?></strong>
                <div class="tooltip group-hover:block">
                    <h4 class="font-bold text-base"><?=_('Health')?></h4>
                    <?=_('Damage that can be taken.')?><br><?=_('Your Max Health')?>: <strong id="maxHealth"><?=$player->getHealthMax()?></strong>
                </div>
            </span>
            <span id="mana" class="sm:relative cursor-default group">
                <i class="fas fa-magic mr-1.5"></i><strong><?=$player->getMana()?></strong>
                <div class="tooltip group-hover:block">
                    <h4 class="font-bold text-base"><?=_('Mana')?></h4>
                    <?=_('Needed for casting spells.')?>
                </div>
            </span>
            <span id="energy" class="sm:relative cursor-default group">
                <i class="fas fa-shoe-prints mr-1.5"></i><strong><?=$player->getMovement()?></strong>
                <div class="tooltip group-hover:block">
                    <h4 class="font-bold text-base"><?=_('Energy')?></h4>
                    <?=_('Tiles take different amounts of energy to walk to.')?><br /><?=_('Energy comes from food.')?>
                </div>
            </span>
            <span id="money" class="sm:relative cursor-default group">
                <i class="fas fa-dollar-sign mr-1.5"></i><strong><?=$player->getMoney()?></strong>
                <div class="tooltip group-hover:block">
                    <h4 class="font-bold text-base"><?=_('Money')?></h4>
                    <?=_('Used to buy items and food.')?>
                </div>
            </span>
        </div><!--.statusInfo-->
    </div><!--statusBar-->

    <div id="mapTitle" class="fixed w-full text-center top-9 z-10">
        <div class="banner"><div class="px-2"><?=$map->getName()?></div></div>
    </div>

    <div id="zoomButtons" class="absolute z-20 right-0 top-8 z-30" style="margin-right:env(safe-area-inset-right)">
        <button id="zoomIn" type="button" class="zoomIn"><i class="fas fa-search-plus"></i></button>
        <button id="zoomOut" type="button" class="zoomOut"><i class="fas fa-search-minus"></i></button>
    </div>

    <!-- area that shows summary of what is on the current tile -->
    <div id="tileInfoSummary"
         class="fixed left-2 xs:left-5 top-16 z-20 p-2 rounded-md border border-gray-500/10
            border dark:border-gray-800 bg-white dark:bg-black/70 shadow-lg dark:shadow-lg-dark"
         style="min-width:180px;">
        <div id="currentCoords" class="text-lg"><?=_('Location')?><strong class="ml-2"><?=$pX?></strong>, <strong><?=$pY?></strong></div>
        <div id="currentTileType" class="text-xs opacity-60 mb-1"></div>
        <ul id="npcList" class="text-sm space-y-2"></ul>
        <ul id="playerList" class="mt-3 text-sm space-y-2 hidden"></ul>

        <button type="button" id="showTileDetails" class="btn relative m-auto mt-3 flex w-36 text-sm justify-center items-center group select-none">
            <?=_('Details')?><kbd class="absolute right-1 top-1.5 hidden xs:group-hover:inline border-2 rounded shadow px-2 bg-gray-500/30 leading-3 text-xs tracking-tight">&#8629;</kbd>
        </button>

        <div id=buildingLink class="mt-2 text-center hidden leading-3"></div>
    </div>

    <?php // The outer mapContainer allows opacity without incurring this bug in Safari  ?>
    <div id="mapContainer" class="flex flex-1 opacity-0 transition duration-1000 ease-in-out">
    <div id="map" class="relative z-0 w-full select-none overflow-hidden" onmousedown="return false;">
        <?php // This useless-looking div fixes a Safari issue where the map tiles won't overlap correctly when player is above them. ?>
        <div class="fixed w-0 h-0">&nbsp;</div>
        <!-- All player avatars are inserted in playerContainer --->
        <div id="playerContainer">
            <div id="outlineCenter" class="absolute border-4 border-yellow/60 rounded-1/4 drop-shadow-md-dark"
                style="
                    width:var(--tileHypSide,141.4px);
                    height:var(--tileHypSide);
                    transform:translateY(calc(var(--tileHypSide) * -0.155)) translateX(calc(var(--tileHypSide)*0.21)) rotateX(60deg) rotateZ(45deg) scale(0.97);
            "></div>
            <div id="cursor" class="cursor drop-shadow-md-dark"><?=$player->showAvatar()?></div>
        </div>
        <!-- Absolutely positioned divs for the map tiles-->
        <div id="tileContainer"></div>
    </div><!--map-->
    </div>

    <div id="controls" onmousedown="return false;"
         class="fixed flex justify-between items-center flex-wrap w-32 h-32 bottom-12 border-2 p-0.5
         border-black/20 rounded-full bg-white/30 select-none transition-all duration-300 ease-in-out"
         style="left:calc(0.75rem + env(safe-area-inset-left)); transform: scaleX(1.1) scaleY(0.7) rotate(45deg);">
        <svg class="arrow upleft" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 74 64"><path d="M37 0L0 64h74z"/></svg>
        <svg class="arrow up" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 74 64"><path d="M37 0L0 64h74z"/><text x="37" y="54">N</text></svg>
        <svg class="arrow upright" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 74 64"><path d="M37 0L0 64h74z"/></svg>
        <svg class="arrow left" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 74 64"><path d="M37 0L0 64h74z"/><text x="37" y="54">W</text></svg>
        <svg class="center" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 74 64"><circle cx="37" cy="32" r="28"/></svg>
        <svg class="arrow right" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 74 64"><path d="M37 0L0 64h74z"/><text x="37" y="54">E</text></svg>
        <svg class="arrow downleft" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 74 64"><path d="M37 0L0 64h74z"/></svg>
        <svg class="arrow down" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 74 64"><path d="M37 0L0 64h74z"/><text x="37" y="54">S</text></svg>
        <svg class="arrow downright" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 74 64"><path d="M37 0L0 64h74z"/></svg>
    </div><!--controls-->

    <!-- Create a stacking context that includes the panels and buttons so tooltips can show over the buttons -->
    <div class="relative z-10">
        <!-- This panel is a multi-purpose area that can show various bits of information as required. -->
        <div id="panelParent" class="relative flex bg-gray-200 dark:bg-gray-800 shadow-xl-dark max-h-60">
            <div id="panelBag" class="panel collapsed shadow-inner-dark p-1 flex-1 grid grid-cols-5 grid-rows-4 gap-1 h-60"></div>

            <div id="panelPlayer" class="panel collapsed shadow-inner-dark p-1 flex-1 flex flex-wrap justify-center overflow-y-scroll">
            <!-- Player stats and avatar can go here -->
            <?php
                // Show player's name, profession, level, and XP
                echo '<table id="playerProfessions" class="self-start"></table>' .
                     '<div class="drop-shadow-md-dark dark:drop-shadow-xl-dark w-44">' .
                       $player->showAvatar() .
                     ' <div class="text-center w-full absolute bottom-2"><a class="link text-xs opacity-80 hover:opacity-100" href="avatar_edit.php?id='.$player->getId().'">'._('Edit Avatar').'</a></div>' .
                     '</div>' .

                     '<div id="playerStats">';
                foreach (Data::getStats() as $stat) {
                    echo '<div class="w-full flex justify-between items-center" id="statRow'.$stat['id'].'">' .
                         '<div class="text-sm pr-3">'._($stat['name']).'</div>' .
                         '<div class="text-right leading-snug" id="value'.$stat['id'].'"></div>' .
                         '</div>';
                }
                echo '</div>';
            ?>
            </div><!--panelPlayer-->

            <div id="panelQuests" class="panel collapsed flex flex-wrap justify-center shadow-inner-dark p-1 flex-1 overflow-y-scroll" style="min-height:12rem;"></div><!-- panelQuests -->

            <div id="panelChat" class="panel collapsed relative shadow-inner-dark p-1 pb-0 flex-1 overflow-y-scroll" style="min-height:15rem;">
                <div id="messageList" class="font-sans text-sm leading-tight" style="min-height:12rem;">
                    <!-- messages load here -->
                    <div id="chatFormSpacer"></div>
                </div>
                <form id="chatForm" class="w-full space-x-2 sticky bottom-0 pt-3 px-1 pb-1 flex overflow-y-scroll" method="post" action="game_update.php">
                    <select id="chatTo" name="chatTo" class="sm:w-36 w-20">
                        <option value=""><?=('Public')?></option>
                    </select>
                    <input type="text" id="newMessage" name="newMessage" class="flex-1 min-w-0" />
                    <button class="btn highlight"><i class="fas fa-paper-plane xs:mr-2"></i><span class="hidden xs:inline-block"><?=_('Send')?></span></button>
                </form>
            </div><!--panelChat-->

            <div id="panelGuardian" class="panel collapsed flex flex-wrap justify-center shadow-inner-dark p-1 flex-1 overflow-y-scroll" style="min-height:12rem;">
                <table id="playerGuardian" class="self-start">
                    <tbody>
                        <tr>
                            <th class="text-xl font-swashed text-center font-normal"><?=_('Guardian')?></th>
                            
                        </tr>
                        <tr>
                            <?php
                            echo  ' <td class="text-center p-0"><a class="link text-xs opacity-80 hover:opacity-100" href="guardian_edit.php?id='.$player->getId().'">'._('Edit Guardian').'</a></td>'
                            ?>
                        </tr>
                        <tr>
                            <?php
                            echo  ' <td class="text-center p-0"><a class="link text-xs opacity-80 hover:opacity-100" href="guardian_create.php?id='.$player->getId().'">'._('Create New Guardian').'</a></td>'
                            ?>
                        </tr>
                        <tr>
                            <?php
                            echo  ' <td class="text-center p-0">'.$guardian->getAvailableCoursesHTML($player->getMapId()).'</td>'
                            ?>
                        </tr>
                    </tbody>
                </table>
                <?php
                    // Show player's Guardian's avatar
                    echo '<div id="guardianAvatar" class="drop-shadow-md-dark dark:drop-shadow-xl-dark w-44">' .
                         $guardian->showGuardianAvatar($player->getId()) .
                         ' <div class="text-center w-full absolute bottom-2"></div>' .
                         '</div>'
                ?>
            </div><!-- panelGuardian -->

        </div><!--panelParent-->

        <div id="panelButtons" class="flex bg-gray-300 dark:bg-gray-700 space-x-0.5 select-none z-10" style="margin-bottom: env(safe-area-inset-bottom)">
            <span id="buttonBag" class="panelButton bag"><i class="fas fa-shopping-bag"></i><?=_('Bag')?></span>
            <span id="buttonPlayer" class="panelButton player"><i class="fas fa-user-shield"></i><?=_('Player')?></span>
            <span id="buttonQuests" class="panelButton quests"><i class="fas fa-map-signs"></i><?=_('Quests')?></span>
            <span id="buttonChat" class="panelButton chat"><i class="fas fa-comment"></i><?=_('Chat')?> <span class="hidden shadow-dark flex justify-center items-center text-lg bg-red-500 dark:bg-red-600 absolute z-30 top-0 right-1 -mt-2 rounded-full w-7 h-7 transition-all duration-100 ease-out" id="chatBadge"></span></span>
            <span id="buttonGuardian" class="panelButton guardian"><i class="fas fa-child"></i><?=_('Guardian')?></span>
        </div><!-- panelButtons -->
    </div>
</div><!--gameUI-->

<div id="modalBackground" class="hidden absolute inset-0 z-40 bg-black/50 opacity-0 transition-all duration-200 ease-in"></div>
<div id="modalContainer"
     class="hidden fixed z-40 inset-x-2 2xs:inset-x-5 top-0 max-w-xl m-auto mt-8 overflow-auto rounded-2xl border border-gray-500/10
     bg-white dark:bg-gray-800 shadow-2xl-dark transition-transform duration-200 ease-out"
     style="max-height:calc(100% - 36px);transform:translate(0px, -1000px);"
>
	<kbd id="closeKbd" class="absolute right-10 top-1 text-2xs tracking-tight inline-block border-2 rounded shadow px-0.5 my-1 bg-gray-500/30 opacity-0 transition-opacity duration-100 ease-in-out hover:opacity-100 select-none">Esc</kbd>
	<span id="modalClose" class="absolute right-0 rounded-full w-7 h-7 font-2xl m-1 flex justify-center items-center
	border-2 border-black bg-white text-black hover:text-red-600 hover:shadow-dark hover:border-red-600
	active:scale-95 active:shadow-none transition-transform duration-200 ease-out"><i class="fas fa-times"></i></span>
	<h2 class="modalTitle mt-1 mb-3 text-xl font-ocr text-center" id="modalTitle"></h2>
	<div id="modalContent" class="p-4 pt-0">
	</div>
</div><!--modalContainer-->
</main>

<!-- Library for handling drag and drop, including on mobile -->
<script src="js/Sortable.min.js" defer></script>

<script>
    // Localization strings used in map.js
    const TXT = {
        main_menu: `<?=_('Main Menu')?>`,
        language: `<?=_('Language')?>`,
        toggle_dark_theme: `<?=_('Toggle Dark Theme')?>`,
        toggle_touch_controls: `<?=_('Toggle Touch Controls')?>`,
        how_to_play: `<?=_('How to Play')?>`,
        change_create_player: `<?=_('Change or Create Player')?>`,
        change_password: `<?=_('Change Password')?>`,
        rankings: `<?=_('Rankings')?>`,
        log_out: `<?=_('Log Out')?>`,
        zoom: `<?=_('Zoom')?>`,
        not_enough_energy: `<?=_('You do not have enough energy to move there.')?>`,
        travel: `<?=_('Travel')?>`,
        space_key: `<?=_('Space')?>`,
        open_link: `<?=_('Open Link')?>`,
        allowed_professions: `<?=_('Allowed Professions')?>`,
        location_label: `<?=_('Location')?>`,
        at_this_location: `<?=_('At this location')?>`,
        nothing_here: `<?=_('There is nothing here.')?>`,
        back_label: `<?=_('Back')?>`,
        split_off_label: `<?=_('Split Off')?>`,
        scroll_down_for_more: `<?=_('Scroll down for more info')?>`,
        no_active_quests: `<?=_('You have no active quests.')?>`,
        drop_quest_confirmation: `<?=_('Are you sure you want to drop this quest?')?>`,
        item_interaction_error: `<?=_('There was a problem interacting with the item.')?>`,
        drop_item_confirmation: `<?=_('Are you sure you want to drop this item? It will be gone permanently.')?>`,
        click_name_to_chat: `<?=_('click name to chat')?>`,
        speech_not_detected: `<?=_('No speech was detected.')?>`,
        microphone_not_found: `<?=_('No microphone was found.')?>`,
        microphone_permission_denied: `<?=_('Permission to use microphone was denied.')?>`,
        microphone_permission_blocked: `<?=_('Permission to use microphone is blocked.')?>`,
        browser_no_speech: `<?=_('Your browser does not support speech recognition. For best results use Chrome or Safari.')?>`,
        buy: `<?=_('Buy')?>`,
        public_label: `<?=_('Public')?>`,
        my_guardian: `<?=_('My Guardian')?>`,
        guardian_panel: `<?=_('Talking to Guardian')?>`,
    };
</script>
<!-- JS functions for controls and keeping game state up-to-date -->
<script src="js/map.js"></script>

<script>
/**
 * Global JS variables for the game map interface that are set with PHP as they can change each time the game loads.
 */

<?php if (isset($_SESSION['playerChanged']) && $_SESSION['playerChanged'] == true) {
    $_SESSION['playerChanged'] = false;
?>
    // If the player is changed, we remove the chat history.
    localStorage.removeItem('chatHistory');
<?php } ?>

const tilePath = '<?=MapTile::getTilePath()?>';

// Game state data is initialized here
// map tile metadata
let mapName = "<?=$map->getName()?>";
let mapTiles = <?=json_encode($map->getClientTilesData())?>;
let mapNPCs = <?=json_encode($map->getClientMapNpcsData())?>;

// Information about other players who are currently visible on the map.
// If a new player enters the map, their information will be added to this array.
let mapOtherPlayers = <?=json_encode($clientVisiblePlayersData)?>;

// Array of all tiletypes with their names. Used to show the name in the tile info area.
const tileTypes = <?=json_encode(MapTile::getMapTileTypes())?>;

const playerId = <?=$_SESSION['playerId']?>;
const playerName = `<?=htmlspecialchars($player->getName(), ENT_QUOTES)?>`;
let mapId=<?=$map->getId()?>;
let maxX=<?=$map->getMaxX()?>, maxY=<?=$map->getMaxY()?>;

// Stats and information for the current player
let player = <?=json_encode($player->getClientPlayerData())?>;

// This hash is sent to game_update.php to determine whether the player object needs to be updated.
let playerHash = '<?=md5(json_encode($player->getClientPlayerData()))?>';

// Player's stored items
let playerBag = <?=json_encode($player->getPlayerBag()->getClientSlotItemsData())?>;

// This hash is sent to game_update.php to determine whether the PlayerBag object needs to be updated.
let playerBagHash = '<?=md5(json_encode($player->getPlayerBag()->getClientSlotItemsData()))?>';

// Used to update the player object with data returned from game_update.php
const statTypes = <?=json_encode(Data::getStats())?>;

const professions = <?=json_encode(Data::getProfessions())?>;

// Set the time between requesting updates to game state. This can be changed in Config.class.php.
const autoUpdateIntervalTime = <?=Config::GAME_UPDATE_INTERVAL_SECONDS?> * 1000;

// Get an object containing menu options for languages
const languageOptions = `
    <?php
    foreach(Data::getLanguages() as $language) {
        echo '<option value="'.$language['locale_id'].'.utf8" '.($language['locale_id'].".utf8"==$_SESSION['locale']?'selected':'').'>'.
        $language['name'].($language['name']!=$language['native_name']?' / '.$language['native_name']:'').'</option>';
    }
    ?>
`;

</script>
</body>
</html>
