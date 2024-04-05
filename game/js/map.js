/**
 * Variables that must be global to store game information.
 */

// If this is false, smaller 200x200 map tiles will be loaded to save bandwidth.
const useHQMap = true;

// Set this to true to output various extra messages to the browser console.
const debug = false;

// This is used as a semaphore to prevent moving until an update returns and is completed
let movementAllowed = true;

// Used as a semaphore to prevent certain operations (other player updates) while the map is currently switching
let mapUpdating = false;

// The name of the current tile type
let tileTypeName;

// Keeps track of NPCs that are in the current tile
let tileNPCs;

let unreadMsgCount = 0;

let tileW = window.getComputedStyle(document.body).getPropertyValue('--tileW').replace('px', '').trim();
let tileH = tileW / 2;

// Array of keypresses used for keystroke dynamics recording
let keys = [];

// Holds the timer for player animations so it can be cancelled if necessary.
let animationTimer = setTimeout(function () {}, 0);

// Holds the timer used to change the cursor color if the AJAX response is delayed after moving.
let colorChangeTimer = setTimeout(function () {}, 0);

// By default, updateData is a blank FormData object;
let updateData = new FormData();

// Keep track of the last time the player moved so slow server updates don't cause them to move back
let lastMoveTime = 0;
//The array of the monsters on the map.
let mapMonsters = [];
/**
 * Do fetch post to resource URL with provided data and returns a promise.
 * If the request fails, or 'error' is not false, promise is rejected and a toast is shown with error info.
 * This makes it a little easier to use the MW APIs and automates showing errors in the game interface.
 */
function fetchPost(resource, body) {
    // Allows caller of fetchPost to add a .catch to do specific error handling.
    function handleError(err, reject) {
        console.error(err);

        // Show a toast with the error message if there is one.
        if (typeof showToast === 'function') showToast(err, 'error');

        reject(err);
    }

    return new Promise((resolve, reject) => {
        fetch(resource, {method: 'POST', body})
            .then(response => response.json())
            .then(data => {
                if (data?.error !== false) {
                    let err = 'An unspecified error has occurred.';
                    // If the data contained an error message, throw it as an error.
                    if (data?.message?.length) err = data.message;
                    handleError(err, reject);
                }

                resolve(data);
            })
            .catch(err => {
                handleError(err, reject);
            });
    });
}


/**
 * Global functions for map interface
 */

function showMenu() {
    const menuContent = `
    <div class="text-center">
		<ul class="space-y-2 text-lg mb-4">
			<li><button class="link" onclick="toggleDarkMode()">${TXT.toggle_dark_theme}</button></li>
			<li><button class="link" onclick="document.getElementById('controls').classList.toggle('seeThrough')">${TXT.toggle_touch_controls}</button></li>
			<li><a class="link" href="player_select.php">${TXT.change_create_player}</a></li>
			<li><a class="link" href="password_change.php">${TXT.change_password}</a></li>
			<!-- <li><a class="link" href="todo.php">${TXT.rankings}</a></li> -->
			<li><a class="link" href="guide.php">${TXT.how_to_play}</a></li> 
			<li><a class="link" href="logout.php">${TXT.log_out}</a></li>
		</ul>

        <form class="mb-8" action="language_update.php" method="post">
            <label class="block text-lg" for="localeId">${TXT.language}</label>
            <select id="localeId" name="localeId" class="pl-3 pr-6" onchange="submit()">${languageOptions}</select>
        </form>

		<div>MEGA World v3.0 &nbsp; &copy;2021 <a href="https://smart-informatics.com/" target="_blank">Smart Informatics Ltd.</div>
    </div>
	`;
    showModal(TXT.main_menu, menuContent);
}

function getOtherPlayer(playerId) {
    return mapOtherPlayers.filter(op => op.id === playerId)[0];
}

// Accepts an "otherPlayer" object with basic data about the player and their avatar
function drawOtherPlayer(op) {
    // Don't draw the player if they already exist
    if (document.getElementById('cursor' + op.id)) return;
    // I'm not sure if the avatars have to be in a cursor element, hmm
    let el = document.createElement("DIV");
    el.classList.add('cursor');
    el.id = 'cursor' + op.id;
    // Unfortunately the clicks only register on the top part of the player because the rest is "behind" the tiles below
    // even though they are almost all visible.
    let html = '<div class="relative text-white hover:text-cyan-200" id="avatar' + op.id + '" onclick="otherPlayerClick(' + op.id + ');">';
    html += '<span class="text-center absolute w-full inline-block text-xs -top-1 over" style="text-shadow: 0 0 4px rgba(0,0,0,0.8);">' + op.name + '</span>';
    html += op.avatar;
    html += '</div>';
    el.innerHTML = html;

    // Need to get the player moved into the correct place.
    document.getElementById('playerContainer').appendChild(el);
    // This should maybe come before I put them on the map
    updateOtherPlayerCursor(op.id, op.x, op.y, true);
}

// Ensure that we are making the page contents the exact height of the page
function setVH() {
    let vh = window.innerHeight * 0.01;
    document.documentElement.style.setProperty('--vh', `${vh}px`);
}

// Adds a scale to the outline of the cursor so it sits comfortably inside a tile at all zoom levels
function setSide() {
    const tileW = parseInt(
        window
            .getComputedStyle(document.body)
            .getPropertyValue('--tileW')
            .replace('px', '')
            .trim()
    );

    // An adjustment factor so the square sits comfortably inside a tile
    const side = Math.sqrt(Math.pow(tileW, 2) / 2);
    document.documentElement.style.setProperty('--tileHypSide', `${side}px`);
}

// Change the tile size to zoom the map view in
function zoom(factor) {
    // Disable cursor animation for all players
    const cursors = document.querySelectorAll('.cursor');
    cursors.forEach(c => c.style.transition = 'none');
    // cursor.style.transition='none';
    const outline = document.getElementById('outlineCenter');
    outline.style.transition = 'none';

    let tileW = window.getComputedStyle(document.body).getPropertyValue('--tileW').replace('px', '').trim();
    if (typeof factor === "undefined") {
        tileW = 100;
    } else {
        tileW *= factor;
    }
    // Round to nearest 10
    tileW = Math.round(tileW / 10) * 10;
    // Set some min/max zoom levels
    if (tileW < 50) tileW = 50;
    if (tileW > 400) tileW = 400;
    //This is the percentage that shows to the user
    const zoomPercent = Math.round(tileW / 200 * 100);

    // This is to fix an artefact that happens at 50px
    if (tileW === 50) {
        tileW = 52;
        document.documentElement.style.setProperty('--tileW', tileW + 'px');
    }

    document.documentElement.style.setProperty('--tileW', `${tileW}px`);
    document.documentElement.style.setProperty('--tileH', `${tileW / 2}px`);

    if (tileW === 100) {
        let tileH = 48;
        document.documentElement.style.setProperty('--tileH', tileH + 'px');
    }
    setSide();

    updateCursor(player.x, player.y, false);
    setCookie('tileW', tileW);
    // These might be annoying if you change zoom levels a lot.
    showToast(TXT.zoom+' ' + zoomPercent + '%', 'success', 0.4);
}// end zoom()

function chatScroll() {
    const panelChat = document.querySelector("#panelChat");
    panelChat.scrollTop = panelChat.scrollHeight;
}

function left() {
    move(-1, 0);
}

function right() {
    move(+1, 0);
}

function up() {
    move(0, +1);
}

function down() {
    move(0, -1);
}

// Diagonal movement
function upleft() {
    move(-1, 1);
}

function upright() {
    move(+1, 1);
}

function downleft() {
    move(-1, -1);
}

function downright() {
    move(+1, -1);
}

function move(xInc, yInc, smooth = true) {
    // Don't allow player to move with a modal open.
    closeModal();
    // If moving 0,0 to refresh player's position and info, we don't need all these checks
    let x = player.x + xInc;
    let y = player.y + yInc;
    // We sometimes move nowhere to update game state. We do not want to evaluate this block when we do that.
    if (xInc !== 0 || yInc !== 0) {
        // Only move if movement is allowed. It will be allowed again after the pending gameUpdate is finished.
        if (!movementAllowed) return;
        // Abort if we have no record of the destination tile (ie, it's off the map)
        if (typeof mapTiles[x] === "undefined" || typeof mapTiles[x][y] === "undefined") return;
        // Check that the player has the required skill to move to this tile
        let skillReq = mapTiles[x][y][1];
        if (skillReq != null && player.skl.filter(skillid => skillid === skillReq).length === 0) {
            if (debug) console.log('Player does not have required skill.');
            movementAllowed = false;
            // We're not returning because we need the ajax request to be called for logging purposes.
        }

        // We have to know what is at this location. This should be in the mapTiles array
        let energyReq = mapTiles[x][y][2];
        // console.log(player.mv);
        if (player.mv < energyReq) {
            if (debug) console.log('Player does not have the required energy.');
            showToast(TXT.not_enough_energy, 'error', '2');
            movementAllowed = false;
            // We're not returning because we need the ajax request to be called for logging purposes.
        }


        // Actually record the new player location
        // Update player's cursor to show movement isn't allowed.
        // Originally an AJAX request didn't get sent if the movement would fail but
        // now we do send it so that the player's failed attempt at moving can be logged.
        updateData.append("mvX", xInc);
        updateData.append("mvY", yInc);

        // Update the last movement time so we can ignore the location from server updates requested before this.
        lastMoveTime = Date.now();

        // Perform AJAX request to update player position and energy and such
        autoUpdate();

        // If the movement failed we go no further.
        if (!movementAllowed) {
            // Let the player move again, otherwise they'll be stuck if they run into a mountain or water.
            // We don't use the setter so we leave the cursor as it was
            // so it stays red if they are out of energy.
            movementAllowed = true;
            return;
        }

        player.mv -= energyReq;
        document.querySelector('#energy strong').innerText = player.mv;

        player.x = x;
        player.y = y;

        setMovementAllowed(false);
    } // end xInc != 0 || yInc != 0
    // The following is evaluated even if the player "moves" nowhere

    tileTypeName = tileTypes.filter(tile => tile.id === mapTiles[x][y][0])[0].name;
    document.getElementById('currentTileType').innerHTML = tileTypeName;
    // Update NPCs and buildings in the info area
    let possibleNpcs = mapTiles[x][y][3];

    // Make an array of the NPCs on this particular tile, factoring in the probability 0-99 vs npc.probability.
    tileNPCs = mapNPCs.filter(npc => possibleNpcs.filter(
        pn => pn === npc.id).length > 0 && npc.probability > Math.floor(Math.random() * 100)
    );

    updateNPCList(tileNPCs);

    updateTilePlayers();

    const buildingLink = document.getElementById('buildingLink');

    let building = mapTiles[x][y][4];
    if (building != null) {
        if (building.name.trim().length === 0) building.name = "Portal";
        let html = '<div class="mt-3 text-sm">' + building.name + '</div>';

        // Check that the player has one of the required professions
        let portalAllowed = player.pr.some(p=>building.profs.indexOf(p.pid) > -1);
        if (building.profs.length === 0) portalAllowed = true;

        if (portalAllowed) {
            if (Number.isInteger(building.dMapId) && Number.isInteger(building.dX) && Number.isInteger(building.dY)) {
                html += '<button type="button" id="buildingTeleport" class="btn relative m-auto mt-1.5 flex w-36 text-sm justify-center items-center group select-none"';
                html += 'data-pid="' + building.bid + '">';
                html += TXT.travel+'<kbd class="absolute right-1 top-1.5 hidden xs:group-hover:inline border-2 rounded shadow px-0.5 bg-gray-500/30 leading-3 text-2xs tracking-tight">'+TXT.space_key+'</kbd>';
                html += '</button>';
            }

            if (building.link && building.link.trim().length > 0) {
                html += '<a onclick="logVisitLink(\'' + building.link + '\')" class="link text-xs" target="_blank" href="' + building.link + '">'+TXT.open_link+'</a>';
            }
            buildingLink.innerHTML = html;
            buildingLink.style.display='block';

            // Bind buildingTeleport button to the usePortal() function
            let buildingTeleport = document.getElementById('buildingTeleport');
            if (typeof(buildingTeleport) != 'undefined' && buildingTeleport != null) {
                document.getElementById('buildingTeleport').addEventListener('click', function () {
                    usePortal(this.getAttribute('data-pid'));
                });
            }
        } else {
            // Show the list of allowed professions
            html += '<ul class="error text-xs"><li><strong class="opacity-80">'+TXT.allowed_professions+'</strong></li>';
            building.profs.forEach(function(p) {
                let prof = professions.find(obj => {return obj.id === p});
                html += '<li>'+prof.name+'</li>';
            });
            html += '</ul>';
            buildingLink.innerHTML = html;
            buildingLink.style.display='block';

        }// end portal not allowed
    } else {
        buildingLink.style.display='none';
        buildingLink.innerHTML = '';
    }

    //Do the animation and update the player position on the map
    updateCursor(x, y, smooth);


    checkForEncounter(x,y);

}// end move()



function getMonsterData() {
    const formData = new FormData();
    formData.append('mapId', mapId);
    fetchPost("monsterEncounter.php", formData).then(data => {
        if (data.monsters) {
            mapMonsters = data.monsters;
        } else {
            console.log(data.message);
        }
        console.log(data);
        console.log(player.stats[1]);
    });

}
function showEncounter(playerData, monsterData) {
    console.log('Showing encounter with:', playerData, monsterData);
    // Encounter modal
    const encounterBox = document.getElementById('encounterForm');
    // Modal backdrop
    const backdrop = document.getElementById('modalBackdrop');

    
    // Updating text content based on provided data
    document.getElementById('playerName').textContent = playerName || 'Player Name';
    document.getElementById('playerHP').textContent = `HP: ${playerData.stats[9] || 'N/A'}`;
    document.getElementById('monsterName').textContent = monsterData.name || 'Monster Name';
    document.getElementById('monsterHP').textContent = `HP: ${monsterData.hp || 'N/A'}`;
        // Display the modal and backdrop
    encounterBox.style.display = 'block';
    backdrop.style.display = 'block';
    // Assuming monsterData includes item details
    const itemDetails = monsterData.item.id ? `Item: ${monsterData.item.name} (Drop Rate: ${monsterData.drop_rate}%)` : 'None';
    document.getElementById('monsterDrops').textContent = `Drops: ${itemDetails}`;


}


function checkForEncounter(x, y) {
    const encounteredMonster = mapMonsters.find(monster => monster.x === x && monster.y === y);
    if (encounteredMonster) {
        showEncounter(player, encounteredMonster);
    }
}

function updateCursor(x, y, smooth = false) {
    document.getElementById('currentCoords').innerHTML = TXT.location_label+ '<strong class="ml-2">'+ x + '</strong>, <strong>' + y + '</strong>';
    const map = document.getElementById('map');
    const dest = document.getElementById('tilex' + x + 'y' + y);
    // overlay a little dude on the destination square
    const cursor = document.getElementById('cursor');
    const outlineC = document.getElementById('outlineCenter');

    if (smooth) {
        cursor.style.transition = 'all .4s ease-in-out 0s';
        outlineC.style.transition = 'all .05s ease-in-out 0s';
    }
    // Have to make an adjustment to top because of the extra tile height.
    cursor.style.top = dest.style.top.replace(/(\d)\)/, '$1 + var(--tileW)*.75 - var(--tileH))');
    // I can't figure the math out for this. Dividing by 8 works at 96%.
    outlineC.style.top = dest.style.top.replace(/(\d)\)/, '$1 + var(--tileW)*.25)');
    outlineC.style.left = cursor.style.left = dest.style.left;
    outlineC.style.animation = 'none';
    // Position z-index so that the cursor and avatar appear behind tall objects
    let lenX = mapTiles.length - 1;
    let lenY = mapTiles[0].length - 1;
    let zIndex = (1 + 1 + (x - y) + Math.max(lenY, lenX)) * 2;
    // Increasing this by one fixes a safari glitch with tiles overlapping incorrectly
    // zIndex++;
    outlineC.style.zIndex = zIndex;
    document.getElementById('cursor').style.zIndex = zIndex + 1;
    // Horrible hack to restart animation so it doesn't flash while moving
    clearTimeout(animationTimer);
    animationTimer = setTimeout(function () {
        outlineC.style.animation = 'flash 2s infinite ease-in-out';
        outlineC.style.transition = 'none';
        cursor.style.transition = 'none';
    }, 500);

    scrollToEl(map, dest, smooth);
}

// Moves the other players on the map to the appropriate location
function updateOtherPlayerCursor(opId, x, y, smooth = false) {
    const opCursor = document.getElementById('cursor' + opId);
    if (!opCursor) return;
    const dest = document.getElementById('tilex' + x + 'y' + y);
    // Prevent throwing an error if the tiles aren't rendered yet.
    if (!dest) {
        opCursor.remove();
        return;
    }
    if (smooth) opCursor.style.transition = 'all .4s ease-in-out 0s';
    // Have to make an adjustment to top because of the extra tile height.
    opCursor.style.top = dest.style.top.replace(/(\d)\)/, '$1 + var(--tileW)*.75 - var(--tileH))');
    opCursor.style.left = dest.style.left;
    // Set other player z-index so he will show behind current player
    let lenX = mapTiles.length - 1;
    let lenY = mapTiles[0].length - 1;
    opCursor.style.zIndex = (1 + 1 + (x - y) + Math.max(lenY, lenX)) * 2;
}

// Moves the map view to center on the player's location
function scrollToEl(container, el, smooth = true) {
    // parent position
    const pPos = el.parentNode.getBoundingClientRect();
    // target position
    const cPos = el.getBoundingClientRect();
    let pos = {};

    pos.top = cPos.top - pPos.top + el.parentNode.scrollTop - (container.clientHeight / 2) + tileH / 2;
    pos.left = cPos.left - pPos.left + el.parentNode.scrollLeft - container.clientWidth / 2 + tileW / 2;

    const startTop = container.scrollTop;
    const startLeft = container.scrollLeft;
    const changeTop = pos.top - startTop;
    const changeLeft = pos.left - startLeft;
    const startTime = performance.now();
    let now, elapsed, t;

    const duration = 0.4;

    function animateScroll() {
        now = performance.now();
        elapsed = (now - startTime) / 1000;
        t = (elapsed / duration);

        container.scrollTop = startTop + changeTop * easeInOutQuad(t);
        container.scrollLeft = startLeft + changeLeft * easeInOutQuad(t);

        if (t < 1)
            window.requestAnimationFrame(animateScroll);

    }

    if (smooth === false) {
        container.scrollTop = startTop + changeTop;
        container.scrollLeft = startLeft + changeLeft;
    } else {
        animateScroll();
    }
}// end scrollToEl

function easeInOutQuad(t) {
    return t < .5 ? 2 * t * t : -1 + (4 - 2 * t) * t;
}

// This function is useful to figure out what element to add the keyup time to
function findLastNullIndex(array, keyCode) {
    const index = array.slice().reverse().findIndex(x => x[0] === keyCode && x[2] == null);
    const count = array.length - 1
    return index >= 0 ? count - index : index;
}

// Determine what to do with the key press or release
function handleKey(e) {
    // We ignore hotkeys if the modal is open, so get the display style of it
    let modalDisplay = document.getElementById('modalContainer').style.display;

    if (document.activeElement.matches('input[type="text"],input[type="password"],textarea')) {
        logKey(e)
    } // If the modal is closed, we allow movement/map hotkeys
    else if (e.type === 'keydown' && modalDisplay === 'none') {
        gameHotKey(e);
    } // If a modal is open allow escape to close it
    else if (e.type === 'keydown' && e.keyCode === 27) {
        // esc
        closeModal();
    }
}

// Log keypresses to the keys array
function logKey(e) {
    let toPlayerId = null;
    let questId = null;
    // Will contain the item_id
    let itemId = null;
    // quest_answer_id
    let answerId = null;
    // Quest ID if the player is answering a quest
    if (document.querySelector('[name="questId"]')) {
        questId = document.querySelector('[name="questId"]').value;
        questId = parseInt(questId);
    }

    if (document.activeElement.matches('#newMessage')) {
        toPlayer = document.getElementById('chatTo').value;
        if (toPlayer.trim().length === 0) toPlayer = null;
        // This will probably break if for some reason this isn't an int, but it should be.
        else toPlayer = parseInt(toPlayer);
    }

    // Get the itemId if player is typing into a quest item's text field
    if ((/^item/).test(document.activeElement.id)) {
        itemId = document.activeElement.id.replace(/.*?(\d+)$/, '$1');
        itemId = parseInt(itemId);
    }
    //get answerid
    if ((/^answer/).test(document.activeElement.id)) {
        answerId = document.activeElement.id.replace(/.*?(\d+)$/, '$1');
        answerId = parseInt(answerId);
    }

    if (e.type === 'keydown') {
        // We don't want repeating keypresses here, so if we detect the same keypress
        // that isn't finished, just return
        if (keys.length) {
            const lastKey = keys[keys.length - 1];
            if (lastKey[0] === e.keyCode && lastKey[2] == null) return;
        }
        keys.push([e.keyCode, Date.now(), null, player.x, player.y, player.map, questId, itemId, answerId, toPlayerId]);
        // Logging
        //console.table(keys);
    } else if (e.type === 'keyup') {
        // Find the last element in the array with a matching keycode and null 3rd element and update the date
        const idx = findLastNullIndex(keys, e.keyCode);
        if (idx >= 0) {keys[idx][2] = Date.now();}
    }
}

/**
 * gameHotKey contains the behaviour for certain keypresses in the map view.
 * @param e
 */
function gameHotKey(e) {
    e = e || window.event;
    switch (e.keyCode) {
        case 81: // q
            document.querySelector('#controls .upleft').classList.add('active');
            setTimeout(function () {
                document.querySelector('#controls .upleft').classList.remove('active');
            }, 150);
            upleft();
            break;

        case 38: // up arrow
        case 87: // w
            document.querySelector('#controls .up').classList.add('active');
            setTimeout(function () {
                document.querySelector('#controls .up').classList.remove('active');
            }, 150);
            up();
            break;

        case 69: // e
            document.querySelector('#controls .upright').classList.add('active');
            setTimeout(function () {
                document.querySelector('#controls .upright').classList.remove('active');
            }, 150);
            upright();
            break;

        case 90: // z
            document.querySelector('#controls .downleft').classList.add('active');
            setTimeout(function () {
                document.querySelector('#controls .downleft').classList.remove('active');
            }, 150);
            downleft();
            break;

        case 40: // down arrow
        case 83: // s
        case 88: // x
            document.querySelector('#controls .down').classList.add('active');
            setTimeout(function () {
                document.querySelector('#controls .down').classList.remove('active');
            }, 150);
            down();
            break;

        case 67: // c
            document.querySelector('#controls .downright').classList.add('active');
            setTimeout(function () {
                document.querySelector('#controls .downright').classList.remove('active');
            }, 150);
            downright();
            break;

        case 37: // left arrow
        case 65: // a
            document.querySelector('#controls .left').classList.add('active');
            setTimeout(function () {
                document.querySelector('#controls .left').classList.remove('active');
            }, 150);
            left();
            break;

        case 39: // right arrow
        case 68: // d
            document.querySelector('#controls .right').classList.add('active');
            setTimeout(function () {
                document.querySelector('#controls .right').classList.remove('active');
            }, 150);
            right();
            break;

        case 13: // enter
            document.getElementById('showTileDetails').dispatchEvent((new Event('click')));
            break;

        case 32: // space
            if (document.getElementById('buildingTeleport')) {
                document.getElementById('buildingTeleport').dispatchEvent((new Event('click')));
            }
            break;

        case 187: // =/+
        case 107: // numpad +
            document.querySelector('#zoomIn').classList.add('active');
            document.querySelector('#zoomIn').dispatchEvent((new Event('click')));
            setTimeout(function () {
                document.querySelector('#zoomIn').classList.remove('active');
            }, 150);
            break;

        case 189: // -
        case 109: // numpad -
            document.querySelector('#zoomOut').classList.add('active');
            document.querySelector('#zoomOut').dispatchEvent((new Event('click')));
            setTimeout(function () {
                document.querySelector('#zoomOut').classList.remove('active');
            }, 150);
            break;

        case 49: // 1
            document.querySelector('#buttonBag').dispatchEvent((new Event('click')));
            break;

        case 50: // 2
            document.querySelector('#buttonPlayer').dispatchEvent((new Event('click')));
            break;

        case 51: // 3
            document.querySelector('#buttonQuests').dispatchEvent((new Event('click')));
            break;

        case 52: // 4
            document.querySelector('#buttonChat').dispatchEvent((new Event('click')));
            break;
        
        case 53: // 5
            document.querySelector('#buttonGuardian').dispatchEvent((new Event('click')));
            break;
    }
}

// If there is a portal at the player's current position, it will be activated.
function usePortal(pid) {
    // fade out the map even though there's a chance it won't work...
    if (debug) console.log("Attempting to use portal");
    updateData.append("usePortal", pid);
    setMovementAllowed(false);
    autoUpdate();
}

// Sets a variable that prevents player from moving.
// Also changes the player's outline cursor to reflect this state
function setMovementAllowed(isAllowed) {
    clearTimeout(colorChangeTimer);
    if (!isAllowed) {
        document.getElementById('outlineCenter').style.borderColor = 'rgba(255,161,0,0.7)';
        // If movement isn't allowed in 200ms, change the border to red.
        colorChangeTimer = setTimeout(function () {
            document.getElementById('outlineCenter').style.borderColor = 'red';
        }, 200);
        movementAllowed = false;
    } else {
        document.getElementById('outlineCenter').style.borderColor = 'rgba(255, 255, 0, 0.7)';
        movementAllowed = true;
    }
}


// Ensures that the button styles match the visibility of panels
function updatePanelButtons() {
    // Remove the active button style if the associated panel is now hidden
    // Unfortunately I think this has to be a second loop :-/
    document.querySelectorAll('.panel').forEach(pnl => {
        const isVisible = pnl.offsetWidth > 0 || pnl.offsetHeight > 0;
        const btn = document.getElementById('button' + pnl.id.replace('panel', ''));
        if (isVisible) btn.classList.add('active');
        else btn.classList.remove('active');
    });
}

// Could use this to modify styles or something
function handleDragStart(e) {
    e.dataTransfer.setData('slotId', e.target.id.replace('bagItem', ''));
    // Removing or hiding tooltips would probably be good here...
    // It looks terrible draging something with a tooltip
    e.target.removeAttribute('data-tooltip');
}

function handleDragOver(e) {
    e.preventDefault();
    // e.target.style.border="1px solid yellow";
}

// Get the ID of the item and do the requisite AJAX query
function handleDrop(e) {
    e.preventDefault();
    let itemEl = e.target;
    // If you drag it onto an existing item, the target ends up being the image or label
    // This should find the actual bagItem element
    if (!itemEl.classList.contains('bagItem')) itemEl = itemEl.parentElement;


    // Here's the code that actually does the work when you land on an item slot
    if (itemEl.classList.contains('bagItem')) {
        let srcSlot = e.dataTransfer.getData("slotId");
        let destSlot = itemEl.id.replace('bagItem', '');
        // Add the dest and to slot data and send for an update
        updateData.append("srcSlot", srcSlot);
        updateData.append("destSlot", destSlot);
        autoUpdate();
    }

    updatePlayerBag();
}

// This button now shows up at all times so the user can complete location-based quests when the location is found.
function showTileDetails() {
    // Post the list of npcIds to tile_details;
    let npcIds = tileNPCs.map(npc => npc.id).join(',');

    let postData = new FormData();
    postData.append("npcs", npcIds);
    // Animate Show Details button while loading
    let std;
    std = document.getElementById('showTileDetails');
    std.classList.add('pulsate');

    fetchPost('tile_details.php', postData).then(data => {
        // Add all the html into the modal
        let html = '';
        data.detailsHtml.forEach(nh => html += nh);

        // We have to show something or the modal won't close.
        if (html.length === 0) {
            html = `<div class="p-10 opacity-70 text-center">${TXT.nothing_here}</div>`;
        }

        showModal(TXT.at_this_location, html);
        std.classList.remove('pulsate');

        // Add event handler for all the npcObject links
        document.querySelectorAll('.npcObject').forEach(no => no.addEventListener('click', loadNPCObject));
    });
}

function loadNPCObject() {
    let formData = new FormData();

    formData.append("npc", this.dataset.npc);
    formData.append("objectType", this.dataset.objecttype);
    formData.append("objectId", this.dataset.objectid);
    if (this.dataset.qty) formData.append("qty", this.dataset.qty);

    fetchPost('npc_object.php', formData).then(data => {
        // Append a "back" link that just runs showTileDetails.
        data.html += `<div class="mt-6 text-center"><button type="button" class="btn modalBack" onclick="showTileDetails();"><i class="fas fa-arrow-left mr-2"></i>${TXT.back_label}</button></div>`;
        showModal(data.title, data.html);

        // I have to bind any buttons to this function again
        document.querySelectorAll('.npcObject').forEach(no => no.addEventListener('click', loadNPCObject));

        // If there was sortable quiz list, let's make that a Sortable object.
        let sortableEl = document.getElementById('sortableQuizList');
        if (typeof(sortableEl) != 'undefined' && sortableEl != null) {
            const sortable = new Sortable(sortableEl, {
                animation:120,
                easing:"cubic-bezier(0.65, 0, 0.35, 1)",
                direction:"vertical",
                onEnd: function(e) {
                  document.getElementById('itemSortOrder').value=this.toArray();
                },
            });
            //console.log(sortable);
        }

        // It's likely that this changed something in the game, so ensure the game is updated
        autoUpdate();
    });
}

// Increments/Decrements the qty value of the Buy button
function incrBuy(amt) {
    let buyButton = document.getElementById('buyButton');
    let qty = buyButton.getAttribute('data-qty');
    let price = buyButton.getAttribute('data-price');
    qty = parseInt(qty) + amt;
    if (qty < 1) qty = 1;
    buyButton.setAttribute('data-qty', qty);
    buyButton.innerHTML = TXT.buy + ' ' + qty + ' &nbsp;($' + (price * qty)+')';
}

// Increments/Decrements the qty value of the split button
function incrSplit(amt) {
    let splitButton = document.getElementById('splitButton');
    let qty = splitButton.getAttribute('data-qty');
    let maxQty = splitButton.getAttribute('data-max');
    maxQty--;
    qty = parseInt(qty) + amt;
    if (qty < 1) qty = 1;
    if (qty > maxQty) return;
    splitButton.setAttribute('data-qty', qty);
    splitButton.innerHTML = '<i class="text-lg fas fa-sign-out-alt"></i><br>'+TXT.split_off_label+' ' + qty;
}

// Logs a click on a link
function logVisitLink(link) {
    let formData = new FormData();
    formData.append('type', 'link');
    // I could just get this url server-side to prevent tampering on the client
    formData.append("note", link);
    fetch('log.php', { method: 'POST', body: formData })
        .catch(err => {
            showToast(err, 'error');
        });
}

// load saved guardian messages on first render
function loadGuardianMessages() {
    let userId = localStorage.getItem(`currentUserId`);
    console.log("load: ",userId)
    let chatLog = document.getElementById('guardian-chat-box');
    let chatHistory = localStorage.getItem(`guardianHistroy_${userId}`);
    if (chatHistory) {
        chatLog.innerHTML = chatHistory;
        chatLog.scrollTop = chatLog.scrollHeight;
    }
}

// Updates Guardian chat log with latest messages
function updateGuardianMessages(data) {
    let chatLog = document.getElementById('guardian-chat-box');
    let userId = localStorage.getItem(`currentUserId`);
    console.log("update: ",userId)
    let html = '';
    data.forEach(msg => {
        if(msg.direction == 1){
            let msgTime = new Date(msg.message_time.replace(/-/g, '/') + ' UTC');
            let dtf = new Intl.DateTimeFormat(navigator.language, {hour: "numeric", minute: "2-digit", second: "2-digit"});
            let msgTimeFmt = dtf.format(msgTime);
            msgTimeFmt = msgTimeFmt.replace(/(.*:.*)(:\d\d)(.*)/, '$1<span class="seconds">$2</span>$3');
            html += `<div class="chatMessage"><span class="msgTime">${msgTimeFmt} </span>: <b>Guardian</b> ${msg.message}</div>`;
        }
        if(msg.direction == 0) {
            let msgTime = new Date(msg.message_time.replace(/-/g, '/') + ' UTC');
            let dtf = new Intl.DateTimeFormat(navigator.language, {hour: "numeric", minute: "2-digit", second: "2-digit"});
            let msgTimeFmt = dtf.format(msgTime);
            msgTimeFmt = msgTimeFmt.replace(/(.*:.*)(:\d\d)(.*)/, '$1<span class="seconds">$2</span>$3');
            html += `<div class="chatMessage"><span class="msgTime">${msgTimeFmt} </span>: <b>You</b> ${msg.message}</div>`;
        }
    });
    localStorage.setItem(`guardianHistroy_${userId}`, html);
    chatLog.innerHTML = html;
    chatLog.scrollTop = chatLog.scrollHeight;
}

// This function is responsible for updating the game UI as new data comes in.
// Typically an update will be received each second, and the data from it will be passed to this function.
// If some aspect of the game has new data, it will be updated in here.
function gameUpdate(data) {

    // storing current user's userID
    localStorage.setItem('currentUserId', data.userid);

    // Update chat messages
    if (typeof data.chatMessages === "object" && data.chatMessages.length) {
        updateChatMessages(data.chatMessages);
    }

    // Update the player's guardian chat messages
    if (data.guardianMessages.length !== 0) {
        updateGuardianMessages(data.guardianMessages);
    }

    // Player data updating;
    if (typeof data.player === "object" && data.requestTime >= lastMoveTime) {
        // We only update certain things if the update is newer than the last player interaction
        // If player's location isn't what we got back from the server, move them there
        // but only if they are on the same map -- that is handled elsewhere.
        if ( data.player.map === player.map && (data.player.x !== player.x || data.player.y !== player.y) ) {
            // Location mismatch. Relocating cursor.
            updateCursor(data.player.x, data.player.y, true);
        }

        player = data.player;
        if (typeof data.playerHash === "string") playerHash = data.playerHash;

        // Allow the player to move again as their state is in sync
        if (!movementAllowed) setMovementAllowed(true);

        // Update UI, status bar, player info panel with new player data
        updatePlayerStatus();
    }

    // PlayerBag updating;
    if (typeof data.playerBag === 'object') {
        playerBag = data.playerBag;
        if (typeof data.playerBagHash === 'string') playerBagHash = data.playerBagHash;

        // Update Contents of player's bag with new data
        updatePlayerBag();
    }

    // Update map (this happens when a player teleports to a new map)
    if (typeof data.mapName === 'string') mapName = data.mapName;
    if (typeof data.mapId === 'number') mapId = data.mapId;
    if (typeof data.mapNPCs === 'object') mapNPCs = data.mapNPCs;
    if (typeof data.mapTiles === 'object') {
        mapTiles = data.mapTiles;
        mapUpdating = true;
        drawMap();
    } else {
        // If we didn't load a new map, the player might still have used a portal
        // to another place on the same map
        if (data.usedPortal === true) move(0, 0, false);
    }

    // Ensure the player is in the correct spot
    // updateCursor(player.x, player.y, false);
    // move(0,0,false);

    // Add any new players to the array of other players
    if (typeof data.newP === 'object' && data.newP.length > 0) {
        mapOtherPlayers = mapOtherPlayers.concat(data.newP);
    }
    // Update the visibility and position data for any existing players
    // We don't want to do this if the map is switching to avoid glitches,
    // if this is the case we let drawMap handle it instead
    if (typeof data.others === 'object') {
        mapOtherPlayers.forEach((p) => {
            let pCursor = document.getElementById('cursor' + p.id);
            // Find the other player in the list of others' positions
            let op = data.others.find(other => other[0] === p.id);
            if (op) {
                p.vis = true;
                p.x = op[1];
                p.y = op[2];
                // draw the player if they aren't already in the DOM, otherwise update their position
                if (!pCursor && !mapUpdating) drawOtherPlayer(p);
                else updateOtherPlayerCursor(p.id, p.x, p.y, true);

            } else {
                // If we didn't find this player in the others array, we say they are no longer visible on this map
                p.vis = false;
                // Remove the other player from the map
                if (pCursor && !mapUpdating) pCursor.remove();
            }
            // console.log(p);
        });
    }

    updateOtherPlayersList();
    updateTilePlayers();

}//end gameUpdate


function updateChatMessages(chatMessages) {
    // Add each element to the message
    const panelChat = document.querySelector("#panelChat");
    let chatMessageCount = 0;
    chatMessages.forEach(function (msg) {
        const messageEl = document.createElement('div');
        messageEl.classList = 'message';
        let systemMessage = false;
        if (msg.src_name === 'SYSTEM_MESSAGE') {
            messageEl.classList.add('systemMessage');
            msg.src_name = '';
            systemMessage = true;
        } else {
            // we only increment chat messages for actual messages from players,
            // not system messages
            chatMessageCount++;
        }

        if (msg.player_id_src === playerId) messageEl.classList.add('you');
        const playerTo = parseInt(msg.player_id_tgt);
        let toStr = "";
        if (Number.isInteger(playerTo) && playerTo > 0) {
            messageEl.classList.add('private');
            if (playerTo === playerId) toStr = " to you";
            else toStr = " to " + msg.tgt_name;
        }
        messageEl.id = 'message' + msg.id;
        // This content will be shown as a toast
        let messageContent = `
            <span class="msgFromPlayer">${msg.src_name + '<span>' + toStr + '</span>'}</span>
            <span class="msg">${msg.message}</span>`;
        // Message content has time added on it for the chat window
        let msgTime = new Date(msg.message_time.replace(/-/g, '/') + ' UTC');
        let dtf = new Intl.DateTimeFormat(navigator.language, {hour: "numeric", minute: "2-digit", second: "2-digit"});
        let msgTimeFmt = dtf.format(msgTime);
        // put span tags around the seconds. Keep in mind that formatted time could vary wildly depending on
        // the user's locale
        msgTimeFmt = msgTimeFmt.replace(/(.*:.*)(:\d\d)(.*)/, '$1<span class="seconds">$2</span>$3');
        messageEl.innerHTML = `<span class="msgTime">${msgTimeFmt} </span>` + messageContent;

        // We show a toast if the message panel is closed
        if (panelChat.classList.contains('collapsed') && !systemMessage) {
            showToast(messageContent, '', 4);
        }
        document.getElementById('messageList').insertBefore(messageEl, document.getElementById('chatFormSpacer'));
    });
    // Only scroll if the window was scrolled all the way down before new messages came in.
    let isScrolledToBottom = false;
    if (panelChat.scrollHeight - panelChat.scrollTop - panelChat.offsetHeight <= 5) isScrolledToBottom = true;
    if (!isScrolledToBottom) chatScroll();

    // If the chat panel is not open, update the new message counter
    updateChatBadge(chatMessageCount);

    // Save the chat history to localStorage so it can be loaded later if necessary.
    localStorage.setItem('chatHistory', document.getElementById('messageList').innerHTML);

} //end updateChatMessages handling


function updateOtherPlayersList(forceUpdate = false) {
    // Update chatTo options with currently visible players
    let chatPlayers = mapOtherPlayers.filter(op => op.vis === true).sort(function (a, b) {
        return a.name.toLowerCase().localeCompare(b.name.toLowerCase());
    });

    const chatSelect = document.getElementById('chatTo');
    const messageField = document.getElementById('newMessage');
    let curValue = chatSelect.value;

    let selectedFound = false;
    let html = '<option value="">'+TXT.public_label+'</option>';
    chatPlayers.forEach(function (p) {
        html += '<option value="' + p.id + '"';
        if (curValue === p.id) {
            html += ' selected="selected"';
            selectedFound = true;
        }
        html += '>' + p.name + '</option>';
    });

    // If the player is typing a message and the recipient player moved away,
    // add back the player so this player can finish sending the message
    let currentOption = null;
    if (messageField.value.length && curValue.length && !selectedFound) {
        // get the currently selected option element
        currentOption = chatSelect.options[chatSelect.selectedIndex];
    }


    // Update dropdown if the options have changed
    if (chatSelect.innerHTML !== html || forceUpdate) {
        chatSelect.innerHTML = html;
    }
    if (currentOption) chatSelect.appendChild(currentOption);
}

// Function invoked by clicking on other player avatars.
// Using onclick in the element because otherwise I seem to register event handlers multiple times
function otherPlayerClick(pid) {
    // May need to check that pid is clean, it should be okay
    if (document.getElementById('panelChat').classList.contains('collapsed')) {
        document.querySelector('.panelButton.chat').dispatchEvent(new Event('click'));
    }
    document.getElementById('chatTo').value = pid;
}

// Makes a new tileContainer element with the current map data and returns it
function getMap() {
    // Drawing this takes a second... maybe I could fade in slowly or something to make it look nicer
    // Make new tileContainer
    const tileContainer = document.createElement('DIV');
    let tileString = "";
    // This will erase tileInfoSummary, outline, cursor, etc.
    // I either need to take those out of map or put the tiles into another container
    let lenX = mapTiles.length - 1;
    let lenY = mapTiles[0].length - 1;
    for (let x = 0; x < mapTiles.length; x++) {
        for (let y = 0; y < mapTiles[0].length; y++) {
            let left = 'calc(var(--tileW)/2 * ' + (x + y) + ')';
            let topFactor = Math.max(lenX, lenY) + x - y;
            let top = 'calc(var(--tileH)/2 * ' + topFactor + ')';
            let tile = mapTiles[x][y];
            let mttId=tile[0];
            // As the tiles go "higher" vertically on the screen, z-index goes lower.
            let zIndex = (1 + (x - y) + Math.max(lenY, lenX)) * 2;

            tileString += '<div id="tilex' + x + 'y' + y + '" class="tile mtt'+mttId+' x' + x + ' ' + 'y' + y + '" style="left:' + left + ';top:' + top + ';z-index:' + zIndex + '">';
            // tileString+=zIndex; // Show coordinates on the tile for debugging
            tileString += '</div>';
        }
    }

    // Place axis labels on each side
    // Axis Label X
    for (let x = 0; x < lenX + 1; x++) {
        const top = 'calc(var(--tileH)/2 * ' + (0.5 + x + Math.max(lenX, lenY)) + ')';
        const left = 'calc(var(--tileW)/2 * ' + (x - 1) + ')';
        tileString += '<div class="axisLabel opacity-50" style="padding-top:calc(var(--tileW)/2);top:' + top + ';left:' + left + ';">' + x + '</div>';
    }

    // Axis Label Y
    for (let y = 0; y < lenY + 1; y++) {
        const top = 'calc(var(--tileH)/2 * ' + (Math.max(lenX, lenY) - y - 0.5) + ')';
        const left = 'calc(var(--tileW)/2 * ' + (y - 1) + ')';
        tileString += '<div class="axisLabel opacity-50" style="top:' + top + ';left:' + left + ';">' + y + '</div>';
    }
    tileContainer.innerHTML = tileString;
    return tileContainer;

}

// uses getMap to update the map, replaces it with a fade effect
function drawMap() {
    // Disable player animation so they don't fly across the screen
    document.getElementById('cursor').style.transition = 'none';
    document.getElementById('mapContainer').style.opacity = '0';

    const tileContainer = getMap();
    setTimeout(function () {
        // Remove other players from the DOM so they don't end up as ghosts in the corner
        mapOtherPlayers.forEach(op => {
            const opCursor = document.getElementById('cursor' + op.id);
            if (opCursor) opCursor.remove();
        })
        // Update title banner
        document.querySelector('#mapTitle .banner div').innerText = mapName;
        // Replace old tiles with new one
        document.getElementById('tileContainer').remove();
        tileContainer.id = "tileContainer";
        document.getElementById('map').appendChild(tileContainer);
        // Update the player's position (this is already done in gameUpdate())
        // updateCursor(player.x,player.y,false);
        move(0, 0, false);

        // After a short delay to avoid seeing a glitch while the maps switch,
        // fade the map back in
        let mapFader = setTimeout(function () {
            document.getElementById('mapContainer').style.opacity = '1';
            // Update position of other players
            // Draw all the other players on the map
            mapOtherPlayers.forEach(op => drawOtherPlayer(op));
            // Map update is complete. Release semaphore
            mapUpdating = false;

        }, 10);
    }, 1000);
} // end drawMap()


// Updates the status bar and the player info
function updatePlayerStatus() {
    document.querySelector('#health strong').innerText = player.h;
    document.querySelector('#mana strong').innerText = player.stats[8];
    document.querySelector('#energy strong').innerText = player.mv;
    document.querySelector('strong#maxHealth').innerText = player.hmx;
    document.querySelector('#money strong').innerText = player.mny;
    // Next put the list of professions here in the playerProfessions table
    const pp = document.getElementById('playerProfessions');

    statTypes.forEach(stat => {
        const statId = stat.id;
        let statVal = player.stats[statId];
        if (statId === 10) statVal = statVal / 1000;
        document.getElementById('value' + statId).innerText = Math.round(statVal).toString();
    });

    let html = '<tr><th class="text-xl font-swashed text-center font-normal">' + playerName + '</th></tr>';
    html += '<tr><td class="text-center p-0 pb-2">' + player.xp + '&nbsp;xp</td></tr>';

    player.pr.forEach(prof => html += `<tr class="playerStatsProfessionName"><th class="pb-0 leading-4">${prof.pname}</th></tr>
		<tr class="playerStatsProfessionInfo"><td class="text-center text-sm opacity-80 pb-2">Level <strong>${prof.plvl}</strong><span class="ml-2 text-xs">${prof.pxp}/${Math.round(prof.nlxp)}xp</span></td></tr>`);
    html += '<tr class="scrollNotice text-2xs text-center text-gray-500/80 xs:hidden"><td>'+TXT.scroll_down_for_more+'</td></tr>';
    pp.innerHTML = html;

    // Update the list of quests in the Quests panel
    updatePlayerQuests();
}

// Updates the bag panel with all the items the player has
function updatePlayerBag() {
    let c = 0;
    let html = '';

    playerBag.forEach(function (slot) {
        html += '<div class="bagItem rounded-xl bg-white relative dark:bg-black hover:shadow-dark border border-gray-500 border-opacity-0 dark:hover:border-opacity-80" id="bagItem' + slot.id + '" ';
        // html+='ondrop="handleDrop(event);"';
        // html+='ondragover="handleDragOver(event);"';
        if (slot.pitm !== null) {
            // Show tooltip if the item name is over 17 characters
            if (slot.pitm.name.length > 17) html += 'data-tooltip="' + slot.pitm.name;
            html += '" data-itemguid="' + slot.pitm.itemGUID + '" draggable="true" ondragstart="handleDragStart(event);">';
            html += '<div class="overflow-hidden items-end h-full bg-contain bg-center bg-no-repeat" id="bagItemImg' + slot.id + '" style="background-image:url(' + slot.pitm.img + ');">';
            if (slot.pitm.qty > 1) html += '<div class="bagItemQty">' + slot.pitm.qty + '</div>'
            html += '</div><div id="bagItemName'+slot.id+'" class="bagItemName absolute left-0 right-0 bottom-0 text-xs px-0.5 text-center ' +
                'overflow-hidden overflow-ellipsis text-black dark:text-white whitespace-nowrap select-none rounded-b-xl">' +
                slot.pitm.name +
                '</div>';
        } else html += '>';
        html += '</div>';
        c++;

    });
    document.getElementById('panelBag').innerHTML = html;

    // Allow handling for drag and drop
    document.querySelectorAll('.bagItem').forEach(e => {
        // Need this to allow drop
        e.addEventListener('dragover', handleDragOver);
        e.addEventListener('drop', handleDrop);
    });


    // How attach event handler for all items with a guid in them
    document.querySelectorAll('.bagItem[data-itemguid]').forEach(function (slot) {
        slot.addEventListener('click', function () {
            const itemGUID = slot.getAttribute('data-itemguid');
            formData = new FormData();
            formData.append("itemGUID", itemGUID);

            fetchPost('playeritem_details.php', formData).then(data => {
                showModal(data.title, data.html);
                // I have to bind any buttons to this function again
                document.querySelectorAll('.itemInteraction').forEach(drop => drop.addEventListener('click', interactWithItem));
            });
        });
    });
}// end updatePlayerBag()


// Updates the Quests panel with the list of player quests
function updatePlayerQuests() {
    const panelQuests = document.getElementById('panelQuests');
    let html = '';
    if (player.q.length === 0) html += '<h4 class="flex-1 m-auto text-center p-10 opacity-70">'+TXT.no_active_quests+'</h4>';
    // IMPORTANT: This comment ensures purgeCSS will keep these classes in the CSS
    // Either grid-cols-1 grid-cols-2 grid-cols-3
    html += '<div class="questList mx-auto text-center sm:grid grid-cols-'+(player.q.length<3?player.q.length:3)+'">';
    let c = 0;
    player.q.forEach(function (quest) {
        c++;
        // I could show the pu time here, I suppose
        let pickupTime = new Date(quest.pu.replace(/-/g, '/') + ' UTC');
        // Use short localized time
        let dtf = new Intl.DateTimeFormat(navigator.language, {
            year: "numeric",
            month: "short",
            day: "numeric",
            hour: "numeric",
            minute: "numeric"
        });
        let formattedDate = dtf.format(pickupTime);
        html += '<div class="p-1.5" id="quest' + quest.qid + '"><button type="button" class="questDetails link py-0 leading-4" id="questDetails' + quest.qid + '">' + quest.name + '</button><span class="block text-xs opacity-60">' + formattedDate + '</span></div>';
    });
    html += '</div>';
    panelQuests.innerHTML = html;
    document.querySelectorAll('.questDetails').forEach(q => q.addEventListener('click', handleQuestClick));
}

function handleQuestClick() {
    let questId = this.id.replace('questDetails', '');
    let formData = new FormData();
    formData.append('questId', questId);
    fetchPost('quest_details.php', formData).then(data => {
        showModal(data.title, data.html);
        // add the event listener for the Drop Quest button
        document.getElementById('questDrop' + questId).addEventListener('click', function () {
            if (confirm(TXT.drop_quest_confirmation)) {
                formData = new FormData();
                formData.append("questId", questId);
                formData.append("action", 'drop');

                // Actually submit the request to delete the item
                fetchPost('quest_details.php', formData).then(data => {
                    closeModal();
                    autoUpdate();
                    showToast(data.message, 'success', 3);
                });
            }
        });
    });
}

// To be triggered from a delete button in item details
function interactWithItem() {
    if (this.dataset.action === 'drop' && !confirm(TXT.drop_item_confirmation)) return;
    const formData = new FormData();
    formData.append("itemGUID", this.dataset.itemguid);
    formData.append("action", this.dataset.action);
    formData.append("qty", this.dataset.qty);

    fetchPost('playeritem_details.php', formData).then(data => {
            // Just close the modal and show a toast success message.
            autoUpdate();
            if (typeof data.html === "string" && data.html.length > 0) {
                showModal(data.title, data.html);
            } else {
                closeModal();
                showToast(data.message, 'success', '3');
            }
        })
        .catch(err => {
            showToast(err.length === 0 ? TXT.item_interaction_error : err, 'error');
        });
}

// Shows the current number of messages that are unread if the chat panel is closed,
// otherwise hides the chatBadge if the panel is open
function updateChatBadge(newMsgCount = 0) {
    const panelChat = document.getElementById('panelChat');
    const chatBadge = document.getElementById('chatBadge');
    // If panel is collapsed, show a badge
    if (panelChat.classList.contains('collapsed')) {
        unreadMsgCount += newMsgCount;
        chatBadge.innerHTML = unreadMsgCount;
        if (unreadMsgCount > 0) {
            chatBadge.style.display = 'flex';
            chatBadge.style.transform = 'scale(1.6)';
            setTimeout(function () {
                chatBadge.style.transform = 'none';
            }, 150);
        }
    } else {
        chatBadge.style.display = 'none';
    }
}

function renderGuardianChat() {
    htmlContent =`
        <div class="guardian-chat-panel bg-gray dark:bg-inner-dark">
            <div id="guardian-chat-box" class="guardian-chat-box">
            <!-- all the chat messages will be here -->
            </div>
            <div class="guardian-chat-input flex">
                <form id="guardianChatModule" class="w-full flex" method="post" action="game_update.php">
                    <input type="text"  id="guardianMessage" name="guardianMessage" placeholder="Ask a question.." class="flex-auto mr-2 w-96" />
                    <button class="flex-auto btn highlight"><i class="fas fa-paper-plane xs:mr-2"></i><span class="hidden xs:inline-block">Send</span></button>
                </form>
            </div>
        </div>
        `;
    showModal(TXT.guardian_panel, htmlContent);
    document.getElementById('guardianChatModule').addEventListener('submit', function (e) {
        // prevent form from submitting
        e.preventDefault();

        // Only bother doing anything if there's a message to send
        if (document.getElementById('guardianMessage').value.length > 0) {
            let formData = new FormData(this);
            fetchPost(this.action, formData).then(data => {
                document.getElementById('guardianMessage').value = '';
                // Do stuff with the data and check for errors
                gameUpdate(data);
            });
        }
    })
    // This is responsible for loading any previous conversation with guardian
    loadGuardianMessages()
}

// Accepts an array of data about the NPCs on the current tile and shows that info in the NPCList
function updateNPCList(npcs) {
    let listItems = '';
    npcs.forEach(npc => listItems += '<li class="flex items-center overflow-hidden"><img class="w-6 h-auto mr-2" src="' + npc.image + '" alt="" />' + npc.name + '</li>');
    document.querySelector('#npcList').innerHTML = listItems;
    const detailsLink = document.getElementById('showTileDetails');
    // Now the detailsLink will show for more reasons than just the NPCs, so we show it even if there are no NPCs.
    // if (npcs.length) detailsLink.style.display = 'block';
    // else detailsLink.style.display = 'none';
}

// Uses mapOtherPlayers to show any Players who are currently on the same tile (below NPCs)
function updateTilePlayers() {
    let listItems = '';
    mapOtherPlayers.forEach(function (p) {
        if (p.vis && p.x === player.x && p.y === player.y) {
            listItems += '<li><i class="fa fa-user mr-1"></i><button type="button" class="link" onclick="otherPlayerClick(' + p.id + ');">' + p.name + '</button></li>'
        }
    });
    if (listItems.length > 2) {
        listItems += '<li class="text-xs text-gray-500" style="margin-top:4px">'+TXT.click_name_to_chat+'</li>'
    }
    let playerList = document.querySelector('#playerList');
    playerList.innerHTML = listItems;
    if (listItems.length) playerList.style.display='block';
    else playerList.style.display='none';


}

// Perform a gameUpdate every second or two
function autoUpdate() {
    // We always want to get updates on the player's state so we send the hashes back so game_update.php
    // can return new data if anything has changed with the player or playerBag objects.
    updateData.append("plH", playerHash);
    updateData.append("pbH", playerBagHash);

    // Add a timestamp to the request so we can potentially ignore data that we know is stale
    updateData.append("requestTime", Date.now());

    // Add the array of typed keys to the submission, but only if the last key is not null
    // If someone holds a key down for an extremely long time it gets weird. How about holding a shift key while you type?
    // I only want to send and clear the keys that aren't null
    if (keys.length) {
        const lastKey = keys[keys.length - 1];
        if (lastKey[2] != null) {
            // Only send the completed keystrokes
            updateData.append("keys", JSON.stringify(keys.filter(k => k[2] != null)));
            // remove any completed keystrokes from this array.
            keys = keys.filter(k => k[2] == null);
        }
    }
    fetchPost('game_update.php', updateData)
        .then(data => { gameUpdate(data) })
        .catch(err => {
            // Stop page from automatically updating since something is broken.
            clearInterval(autoUpdateInterval);
        });
    // reset updateData
    updateData = new FormData();
}

// Stuff that we need to do when the page is resized or rotated
function handleResizing() {
    setVH();
    updatePanelButtons();
}

// Remove focus from anything else when clicking on the map to ensure keyboard shortcuts
// work again, ie after using chat
function blurActive() {
    document.activeElement.blur();
}

// Speech recognition setup
var finalTranscript = '';
var recognizing = false;
var ignoreOnend;
var startTimestamp;
var transcriptionEl;
var recognition = getRecognition();

//if ('webkitSpeechRecognition' in window) {
function getRecognition() {

    if (!('webkitSpeechRecognition' in window)) {
        return null;
    }

    const recognition = new webkitSpeechRecognition();
    recognition.continuous = true; // Set to true to end after user stops speaking
    recognition.interimResults = true;

    recognition.onstart = function() {
        document.getElementById('speechError').innerText = '';
        recognizing = true;
        // Show some animation or prompt here
    };

    recognition.onerror = function(event) {
        let error = '';
        // Here are different errors. we can show the relevant messages here.
        if (event.error === 'no-speech') {
            error = TXT.speech_not_detected;
        }
        if (event.error === 'audio-capture') {
            error = TXT.microphone_not_found;
        }
        if (event.error === 'not-allowed') {
            if (event.timeStamp - startTimestamp < 100) {
                error = TXT.microphone_permission_denied;
            } else {
                error = TXT.microphone_permission_blocked;
            }
        }
        if (event.error === 'network') {
            error = 'There was a network error. Please try again.';
        }
        console.log(event.error);
        document.getElementById('speechError').innerText = error;
    };

    recognition.onend = function() {
        recognizing = false;
        // Capitalize the first character of the transcript
        finalTranscript = finalTranscript.replace(/\S/, function(m) { return m.toUpperCase(); });
        transcriptionEl.innerHTML = finalTranscript;

        // Remove the "recording" style
        new Audio('audio/stop.mp3').play();
        document.getElementById('listenbtn').src='images/speech.png';
    };

    recognition.onresult = function(event) {
        let interim_transcript = '';
        for (let i = event.resultIndex; i < event.results.length; ++i) {
            if (event.results[i].isFinal) {
                finalTranscript += event.results[i][0].transcript;
            } else {
                interim_transcript += event.results[i][0].transcript;
            }
        }
        transcriptionEl.innerText = interim_transcript;
    };

    return recognition;
} // end if 'webkitSpeechRecognition'. Could add else to show error for unsupported browsers.

function startListening(event, id, button) {

    // Show error for browsers that don't support speech recognition
    if (!('webkitSpeechRecognition' in window)) {
        // Show error message saying browser doesn't support speech recognition
        document.getElementById('speechError').innerText = TXT.browser_no_speech;
        return;
    }

    if (recognizing) {
        recognition.stop();
        recognizing = false;

    } else {
        recognition.stop();
        setTimeout(function(){
            document.getElementById('listenbtn').src='images/speeching.png';
            transcriptionEl = document.getElementById(id);
            finalTranscript = '';
            recognition.lang = 'en-CA';
            recognition.start();
            ignoreOnend = false;
            recognizing = true;
            new Audio('audio/speeching.mp3').play();
            startTimestamp = event.timeStamp;
        },300);
    }
}

/**
 * Post the data from the current quest quiz form and submit it to quest_check_answers.php.
 * Insert the results into the current modal dialog.
 * @param event
 * @param form
 * @return string
 */
function checkAnswers(event, form) {
    event.preventDefault();

    // Apply some styles and add a loading spinner to the button
    let btn = document.querySelector('button[type="submit"]');
    let btnText = btn.innerText;
    btn.classList.add('relative', 'm-auto', 'flex', 'justify-center', 'items-center');
    // I don't love the way the button looks and works when disabled, but preventing clicks seems prudent.
    // btn.disabled = true;
    btn.innerHTML = '<span class="opacity-30">'+btnText+'</span><svg class="animate-spin absolute h-5 w-5 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">\
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>\
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>\
    </svg>';

    let formData = new FormData(form);
    // Do we need to append any additional data to the form? PlayerID, questID, etc?
    fetchPost( 'quest_check_answers.php', formData).then(data => {
        // Add back button
        data.html += '<div class="mt-6 text-center"><button type="button" class="btn modalBack" onclick="showTileDetails();"><i class="fas fa-arrow-left mr-2"></i>'+TXT.back_label+'</button></div>';
        showModal(data.title, data.html);
    });
}


/**
 * Initialization tasks to be performed when the game first loads, should be called called on DOMContentLoaded.
 * This involves adding event handlers, initializing data, loading the map, etc.
 */
document.addEventListener('DOMContentLoaded',function() {
    // Check that all required variables are defined before this runs
    if (typeof TXT === 'undefined'
        || typeof tilePath === 'undefined'
        || typeof mapName === 'undefined'
        || typeof mapTiles === 'undefined'
        || typeof mapNPCs === 'undefined'
        || typeof mapOtherPlayers === 'undefined'
        || typeof tileTypes === 'undefined'
        || typeof playerId === 'undefined'
        || typeof playerName === 'undefined'
        || typeof mapId === 'undefined'
        || typeof maxX === 'undefined'
        || typeof player === 'undefined'
        || typeof playerHash === 'undefined'
        || typeof playerBag === 'undefined'
        || typeof playerBagHash === 'undefined'
        || typeof statTypes === 'undefined') {
        console.log(`One or more required variables is undefined.
        For MEGA World to function correctly, map.js requires the following variables to be defined before loading:
            TXT
            tilePath
            mapName
            mapTiles
            mapNPCs
            mapOtherPlayers
            tileTypes
            playerId
            playerName
            mapId
            maxX
            player
            playerHash
            playerBag
            playerBagHash
            statTypes
            `);
        showToast('Required variables are undefined. See the console for more information.', 'error', 0);
    }

    // Handle arrow keypresses.
    document.onkeydown = handleKey;
    document.onkeyup = handleKey;

    // Set the size of the player's blinking cursor
    setSide();

    // Draw in the map when the page first loads
    drawMap();

    // Bind the d-pad buttons to the movement functions
    document.querySelector('#controls .upleft').addEventListener('click', function () {
        upleft();
    });
    document.querySelector('#controls .up').addEventListener('click', function () {
        up();
    });
    document.querySelector('#controls .upright').addEventListener('click', function () {
        upright();
    });
    document.querySelector('#controls .right').addEventListener('click', function () {
        right();
    });
    // Center button is like pressing enter. Perhaps entering portals would make more sense?
    document.querySelector('#controls .center').addEventListener('click', function () {
        // Originally this clicked the details button. Now it toggles visibility of the controls
        document.getElementById('showTileDetails').dispatchEvent((new Event('click')));
        // document.getElementById('controls').classList.toggle('seeThrough');
    });
    document.querySelector('#controls .left').addEventListener('click', function () {
        left();
    });
    document.querySelector('#controls .downleft').addEventListener('click', function () {
        downleft();
    });
    document.querySelector('#controls .down').addEventListener('click', function () {
        down();
    });
    document.querySelector('#controls .downright').addEventListener('click', function () {
        downright();
    });

    // Zoom buttons
    document.querySelector('#zoomIn').addEventListener('click', function () {
        zoom(2);
    });
    document.querySelector('#zoomOut').addEventListener('click', function () {
        zoom(0.5);
    });

    // add 'click' event listener for Guardian
    document.getElementById('guardianAvatar').addEventListener('click', function () {
        renderGuardianChat();
    });

    setVH();

    // reset vh on window resize event
    window.addEventListener('resize', handleResizing);
    window.addEventListener('orientationchange', handleResizing);

    // Handle clicks on panelButtonss
    const panelButtons = document.querySelectorAll('.panelButton');

    panelButtons.forEach(function (pb) {
        pb.addEventListener('click', function () {
            panelParent = document.querySelector('#panelParent');
            const panelName = this.id.replace('button', '');
            const panel = document.getElementById('panel' + panelName);

            // Clicking the active button - should collapse that panel
            if (this.classList.contains('active')) {
                this.classList.remove('active');
                panel.classList.add('collapsed', 'collapsedM', 'collapsedS');
                // panelParent.style.maxHeight='0';
                for (let i = 1; i <= 5; i++) {
                    if (getCookie('panel' + i) === (panelName || 'null')) deleteCookie('panel' + i);
                }
            } else { // Else an inactive button was clicked. We need to show that panel
                this.classList.add('active');
                // The panel for the clicked button is uncollapsed
                panel.classList.remove('collapsed', 'collapsedM', 'collapsedS');
                // Push the collapsedness level up for each panel.
                // collapsed is always collapsed
                // collapsedM is collapsed on medium sizes or less
                // collapsedS is collapsed for smallest sizes only
                document.querySelectorAll('.panel').forEach(pnl => {
                    // If a panel was not collapsed, collapse it at the smallest size
                    if (pnl !== panel && !pnl.classList.contains('collapsed')) {
                        // If panel was collapsed at small sizes before, and there's no M, collapse it at M now
                        if (pnl.classList.contains('collapsedS') && document.querySelectorAll('.collapsedM').length <= 1) {
                            pnl.classList.add('collapsedM');
                        }
                        // Everything other than the clicked panel should be collapsed at small sizes
                        pnl.classList.add('collapsedS');
                        // at small sizes, at least three panels must be collapsed
                        // at medium sizes, two panels must be collapsed
                    }
                });

                updatePanelButtons();
                if (getCookie('panel4') !== ('null' || panelName)) setCookie('panel5', getCookie('panel4'));
                if (getCookie('panel3') !== ('null' || panelName)) setCookie('panel4', getCookie('panel3'));
                if (getCookie('panel2') !== ('null' || panelName)) setCookie('panel3', getCookie('panel2'));
                if (getCookie('panel1') !== ('null' || panelName)) setCookie('panel2', getCookie('panel1'));
                setCookie('panel1', panelName);

                // Handle scrolling and clearing message badge
                if (this.classList.contains('chat')) {
                    chatScroll();
                    unreadMsgCount = 0;
                    updateChatBadge();
                }
            }
        }); // end pb addEventListener
    }); // end panelButtons forEach

    document.getElementById('map').addEventListener('click', blurActive);
    document.getElementById('controls').addEventListener('click', blurActive);
    document.getElementById('zoomButtons').addEventListener('click', blurActive);

    updatePlayerStatus();
    updatePlayerBag();
    mapMonsters = getMonsterData();
    // Handle submitting the chatForm
    document.getElementById('chatForm').addEventListener('submit', function (e) {
        // prevent form from submitting
        e.preventDefault();

        // Only bother doing anything if there's a message to send
        if (document.getElementById('newMessage').value.length > 0) {
            let formData = new FormData(this);
            formData.append("x", player.x);
            formData.append("y", player.y);
            fetchPost(this.action, formData).then(data => {
                document.getElementById('newMessage').value = '';
                // Do stuff with the data and check for errors
                gameUpdate(data);
            });
        }
    });

    

    document.getElementById('showTileDetails').addEventListener('click', showTileDetails);

    // Recalculate VH when mobile keyboard is shown to account for layout changes when keyboard appears.

    // Get the panel cookie. If one is set, open the relevant item
    let pc5 = getCookie('panel5');
    let pc4 = getCookie('panel4');
    let pc3 = getCookie('panel3');
    let pc2 = getCookie('panel2');
    let pc1 = getCookie('panel1');
    if (pc5 !== 'null') {
        const pnl = document.getElementById('panel' + pc5);
        if(pnl !== null){
            pnl.classList.remove('collapsed');
            pnl.classList.add('collapsedM');
        }
    }
    if (pc4 !== 'null') {
        const pnl = document.getElementById('panel' + pc4);
        if(pnl !== null){
            pnl.classList.remove('collapsed');  
            pnl.classList.add('collapsedM');
        }
    }
    if (pc3 !== 'null') {
        const pnl = document.getElementById('panel' + pc3);
        if(pnl !== null){
            pnl.classList.remove('collapsed');
            pnl.classList.add('collapsedM');
        }
    }
    if (pc2 !== 'null') {
        const pnl = document.getElementById('panel' + pc2);
        if(pnl !== null){
            pnl.classList.remove('collapsed', 'collapsedM');
            pnl.classList.add('collapsedS');
        }
    }
    if (pc1 !== 'null') {
        const pnl = document.getElementById('panel' + pc1);
        if(pnl != null) {
            pnl.classList.remove('collapsed', 'collapsedM', 'collapsedS');
        }
    }
    updatePanelButtons();

    // responsible for checking if the player has a guardian avatar and if not creating one
    let guardianAvatarStatus = true;
    document.getElementById('buttonGuardian').addEventListener('click', function () {
        if(guardianAvatarStatus) {
            guardianAvatarStatus = false;
        }
    });

    // If the user has adjusted zoom before, set the tileW from the cookie and zoom to set this.
    if (typeof getCookie('tileW') === "string") {
        let tileW = getCookie('tileW');
        document.documentElement.style.setProperty('--tileW', `${tileW}px`);
        document.documentElement.style.setProperty('--tileH', `${tileW / 2}px`);
        // Sigh, there's a weird glitch that needs a pixel offset. Maybe there's a way to fix this.
        if (tileW === 50) {
            tileW = 51;
            document.documentElement.style.setProperty('--tileW', tileW + 'px');
        }
        if (tileW === 100) {
            let tileH = 48;
            document.documentElement.style.setProperty('--tileH', tileH + 'px');
        }

        setSide();
    }

    // If there is previous chat history saved, restore it to the messageList
    let chatHistory = localStorage.getItem('chatHistory');
    if (chatHistory != null && chatHistory.length > 1) {
        document.getElementById('messageList').innerHTML = chatHistory;
        chatScroll();
    }

    // Do initial update immediately in case this does further initialization or gets required data.
    autoUpdate();

    // Run an update periodically as per the specified interval time.
    // We declare it as a global so we can stop it later if something goes wrong.
    // Provide a default of 1500ms if autoUpdateIntervalTime is undefined in map.php
    window.autoUpdateInterval = setInterval(autoUpdate, typeof autoUpdateIntervalTime === 'undefined' ? 1500 : autoUpdateIntervalTime);
});

function startConversation(questID, playerID, url) {

    let postData = new FormData();
    postData.append("quest_id", questID);
    postData.append("player_id", playerID);

    fetchPost('conversation_save.php', postData).then(data => {
        window.location = url;
    });
}
