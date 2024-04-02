<div>
    @push('styles')
        <style>
            /* Override the game CSS so we can scroll the map */
            nav {
                position:fixed;
                width:100%;
                z-index:1000;
            }

            .banner {
                position: relative;
                word-spacing: 4px;
                display: inline-block;
                margin: 0 auto 20px;
                padding: 0 12px;
                height: auto;
                min-width: 200px;
                font-family: 'Serif';
                font-weight: 400;
                text-align: center;
                color: rgba(0,0,0,0.8);
                background-color: #ffe50799;
                border-radius: 3px;
                box-shadow: 0 0 30px rgba(0 0 0 / 15%) inset, 0 6px 10px rgb(0 0 0 / 15%);
            }

            [data-tooltip]:before {
                top:35px;
            }

            /* List of tile types with their background-images */
            @foreach(MapTileType::all() as $tileType)
                .mtt{{$tileType->id}} { background-image:url({{$tileType->imageUrl}}); }
            @endforeach


        </style>
    @endpush

    <div id="gameUI" class="flex flex-col h-screen" style="height:calc(var(--vh, 1vh) * 100);" x-data="mapEditorData()">
        <div id="mapTitle" class="fixed w-full text-center top-14 z-10">
            <div class="banner">
                <div class="leading-tight">
                    <span class="text-xl">{{ $map->name }}</span>
                    <div clas="text-sm" style="word-spacing: initial;">
                        {{ $map->mapType->name }}
                        @if (MapTile::where('map_id', $map->id)->count() > 0)
                        <b>{{ MapTile::where('map_id', $map->id)->max('x')+1 }}</b><span>x</span><b>{{ MapTile::where('map_id', $map->id)->max('y')+1 }}</b>
                        @else
                            <b>No Map Tiles</b>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div id="zoomButtons" class="absolute z-20 right-0 top-36 z-30">
            <button id="zoomIn" type="button" class="zoomIn"><i class="fas fa-search-plus"></i></button>
            <button id="zoomOut" type="button" class="zoomOut"><i class="fas fa-search-minus"></i></button>
        </div>

        {{--            <div class="fixed flex justify-center">--}}
        {{--                <div class="text-xl shadow-lg mt-12 rounded bg-white dark:bg-black bg-opacity-50 dark:bg-opacity-50">{{ $map->name }}</div>--}}
        {{--            </div>--}}
        <div wire:ignore id="tileInfoSummary" class="fixed p-1 pb-2 ml-1 xs:ml-4 rounded-md shadow-lg bg-white/40 dark:bg-black/40" style="top:58px;min-width:180px;z-index:310;">
            <div id="currentCoords"></div>
            <div id="currentTileType" class="text-xs opacity-60 mb-1"></div>
            <ul id="npcList" class="text-sm space-y-2">
            </ul>

            <div class="infoLinks"><a id="showTileDetails" class="iButton" href="javascript:void(0);" style="display:none;">
                    Details<kbd class="tiny">Enter</kbd></a>
            </div>
            <div class="infoLinks" id=buildingLink>
            </div>

        </div>
        <div id="mapContainer" class="flex flex-1">
        <div id="map" class="relative z-0 flex-1 select-none overflow-hidden">
            <div class="fixed w-0 h-0">&nbsp;</div>
            <div wire:ignore>
                <div id="outlineCenter" class="absolute border-4 border-yellow rounded-1/4 border-opacity-60 filter drop-shadow-md"
                   style="
                    width:var(--tileHypSide,141.4px);
                    height:var(--tileHypSide);
                    transform:translateY(calc(var(--tileHypSide) * -0.155)) translateX(calc(var(--tileHypSide)*0.21)) rotateX(60deg) rotateZ(45deg) scale(0.97);
                "></div>
                <!-- Shows the selected map tile -->
                <div id="cursor" class="cursor filter drop-shadow-md"></div>
            </div>

            <!-- absolutely positioned divs for map tiles -->
            <div class="tileContainer">
                {!! $map->mapHTML !!}
            </div><!--tileContainer-->
        </div><!--map-->
        </div>

        <div id="controls" onmousedown="return false;"
            class="flex justify-between items-center flex-wrap fixed left-3 bottom-12 border-2 p-0.5 border-black border-opacity-20 rounded-full w-32 h-32 bg-white bg-opacity-30 select-none"
            style="transform: scaleX(1.1) scaleY(0.7) rotate(45deg);transition:all 0.3s ease-in-out;">
            <svg class="arrow upleft" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 74 64"><path d="M37 0L0 64h74z"/></svg>
            <svg class="arrow up" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 74 64"><path d="M37 0L0 64h74z"/><text x="37" y="54">N</text></svg>
            <svg class="arrow upright" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 74 64"><path d="M37 0L0 64h74z"/></svg>
            <svg class="arrow left" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 74 64"><path d="M37 0L0 64h74z"/><text x="37" y="54">W</text></svg>
            <svg class="center" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 74 64"><circle cx="37" cy="32" r="20"/></svg>
            <svg class="arrow right" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 74 64"><path d="M37 0L0 64h74z"/><text x="37" y="54">E</text></svg>
            <svg class="arrow downleft" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 74 64"><path d="M37 0L0 64h74z"/></svg>
            <svg class="arrow down" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 74 64"><path d="M37 0L0 64h74z"/><text x="37" y="54">S</text></svg>
            <svg class="arrow downright" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 74 64"><path d="M37 0L0 64h74z"/></svg>
        </div><!--controls-->


        {{-- In management, these panels are used for map tiles, buildings, and NPCs. --}}
        <div class="relative z-10">
            <div wire:ignore id="panelParent" class="relative flex bg-gray-200 dark:bg-gray-800 shadow-xl-dark max-h-64">
                <div id="panelTiles" class="panel collapsed shadow-inner-dark p-1 flex-1 flex flex-wrap justify-center justify-center p-1 pb-8 overflow-y-scroll text-2xs">
                    <div class="flex justify-center space-x-4 p-2 w-full">
                        <x-button wire:click="expandX">Expand X East</x-button>
                        <x-button wire:click="expandY">Expand Y North</x-button>
                        <x-button wire:click="reduceX">Reduce X East</x-button>
                        <x-button wire:click="reduceY">Reduce Y North</x-button>
                    </div>

                    @foreach(MapTileType::orderBy('id')->get() as $tile)
                        <button id="tile{{$tile->id}}"
                                @click="updateTile({{$tile->id}})"
                                class="relative px-1 text-center"
                                data-tooltip="{{ $tile->name }} move:{{$tile->movement_req}} @if($tile->skill_id_req!=0){{$tile->skill->name}}@endif ">
                            <img class="block mx-auto" width="52" src="{{$tile->imageUrl}}" />
                            <div class="-mt-1">{{ substr($tile->name, 0, 9) }}@if(strlen($tile->name)>9)&hellip;@endif</div>
                        </button>
                    @endforeach
                </div><!--panelTiles-->

                <div id="panelBuildings" class="panel collapsed shadow-inset-dark p-1 flex-1 flex flex-wrap justify-center overflow-y-scroll min-h-[12rem]">
                    <ul>
                    @foreach(Building::where('map_id', $this->map->id)->orderBy('x')->orderBy('y')->get() as $building)
                            <li class="flex items-center">
                                <div class="w-10">{{$building->x}}, {{$building->y}}</div>
                                <span class="text-gray-500 text-xs mr-1">{{ $building->id }}</span>
                                <a class="link" href="{{route('buildings')}}?filters%5Bid%5D={{$building->id}}">{{ strlen($building->name)?$building->name:'-unnamed-' }}</a>
                                @isset($building->dest_map_id)
                                    <i class="fas fa-arrow-right w-8 text-center"></i> <span class="text-gray-500 text-xs mr-1">{{ $building->dest_map_id }}</span>
                                    <a class="link" href="{{route('maps')}}?filters%5Bid%5D={{$building->dest_map_id}}">{{ $building->destMap->name }}</a>
                                    <span class="ml-2">{{$building->dest_x}}, {{$building->dest_y}}</span>
                                @endisset
                                @isset($building->external_link)
                                    <a class="link ml-2 text-xs" target="_blank" href="{{$building->external_link}}"><i class="fas fa-link"></i> ext.link</a>
                                @endisset
                            </li>
                    @endforeach
                    </ul>
                </div><!--panelBuildings-->

                <div id="panelNpcs" class="panel collapsed shadow-inset-dark p-1 flex-1 flex flex-wrap justify-center overflow-y-scroll min-h-[12rem]">
                    <div class="p-1 text-sm" style="max-height:240px;overflow:scroll;">
                        <ul>
                            @foreach(Npc::where('map_id', $this->map->id)->orderBy('x_left')->orderBy('y_top')->get() as $npc)
                                <li class="flex items-center">
                                    <div class="w-20">{{$npc->x_left}}, {{$npc->y_top}}@if($npc->x_right != $npc->x_left || $npc->y_top != $npc->y_bottom) - {{$npc->x_right}}, {{$npc->y_bottom}}@endif</div>
                                    <span class="text-gray-500 text-xs mr-1">{{ $npc->id }}</span>
                                    <a class="link" href="{{route('npcs')}}?filters%5Bid%5D={{$npc->id}}">{{ strlen($npc->name)?$npc->name:'-unnamed-' }}</a>

                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div><!--panelNpcs-->
                <div id="panelMonsters" class="panel collapsed shadow-inset-dark p-1 flex-1 flex flex-wrap justify-center overflow-y-scroll min-h-[12rem]">
                    <ul>
                    @foreach(\App\Models\Monster::orderBy('item_id')->get() as $monster)
    <li class="flex items-center"> 
        <a class="link" href="{{ route('monster', ['id' => $monster->id]) }}">{{ $monster->name ?: '-unnamed-' }}</a>
    </li>
@endforeach
                    </ul>
                
                        </div><!--panelMonsters-->
            </div><!--panelParent-->

            <div wire:ignore id="panelButtons" class="flex bg-gray-300 dark:bg-gray-700 space-x-0.5 select-none z-10" style="margin-bottom: env(safe-area-inset-bottom)">
                <span id="buttonTiles" class="panelButton tiles"><i class="fas fa-square"></i>Tiles</span>
                <span id="buttonBuildings" class="panelButton buildings"><i class="fas fa-building"></i>Buildings</span>
                <span id="buttonNpcs" class="panelButton npcs"><i class="fas fa-user-cog"></i>NPCs</span>
                <span id="buttonMonsters" class="panelButton monsters"> <i class="fas fa-user-cog"> </i>Monsters</span>
            </div>
        </div>

    </div><!--gameUI-->

    <script>
        // Keep the cursor x and y position in sync with the backend using alpine/livewire
        function mapEditorData() {
            return {
                currentX: @entangle('currentX').defer,
                currentY: @entangle('currentY').defer,
                mapTiles: @entangle('mapTilesJSON'),

                // Update the background image of a map tile when a button is clicked to change it.
                updateTile(typeId) {
                    let formData = new FormData();
                    formData.append('mapTileTypeId', typeId);
                    formData.append('x', this.currentX);
                    formData.append('y', this.currentY);
                    // Send a post request to the /maps/{map}/tile endpoint
                    fetch('/maps/{{$map->id}}/tile', {
                        method: 'POST',
                        headers:{"X-CSRF-Token": "{{ csrf_token() }}" },
                        body: formData
                    });
                    const mapTile = document.getElementById('tilex'+player.x+'y'+player.y);
                    mapTile.classList='tile x'+this.currentX+' y'+this.currentY+' mtt'+typeId;
                }
            }
        }

        // Reimplement game interface functionality. Hmm... not very DRY.

        /*************************************************************************
         Utility functions for Cookies

         Parameters - name=key and value=value
         Coookie expires in 10 years
         Applies to the root of apps (apply to every page.)
         *************************************************************************/
        function setCookie(key, value) {
            var expires = new Date();
            expires.setTime(expires.getTime() + (10 * 365 * 24 * 60 * 60 * 1000));
            document.cookie = key + '=' + value + ';path=/;expires=' + expires.toUTCString();
        }

        // getCookie function return the value of the Cookie named 'key'
        function getCookie(key) {
            var keyValue = document.cookie.match('(^|;) ?' + key + '=([^;]*)(;|$)');
            return keyValue ? keyValue[2] : null;
        }
        // Expires/clears the cookie
        function deleteCookie(key) {
            if(getCookie(key)) document.cookie=key + "=;path=/;expires=Thu, 01 Jan 1970 00:00:01 GMT";
        }

        var player = {
            map:{{$map->id}},
            x:{{$currentX}},
            y:{{$currentY}}
        };
        var mapTiles = {!! json_encode($map->tiles2DJS) !!};
        var tileTypes = {!! json_encode(MapTileType::all()->toArray()) !!};

        var tileTypeName; // The name of the current tile type
        var tileNPCs; // Keeps track of NPCs that are in the current tile
        var tileW = window.getComputedStyle(document.body).getPropertyValue('--tileW').replace('px', '').trim();
        var tileH = tileW / 2;
        var animationTimer = setTimeout(function () {}, 0);

        // Ensure that we are making the page contents the exact height of the page
        function setVH() {
            let vh = window.innerHeight * 0.01;
            document.documentElement.style.setProperty('--vh', `${vh}px`);
        }

        // Adds a scale to the outline of the cursor so it sits comfortably inside a tile at all zoom levels
        function setSide() {
            let tileW = window.getComputedStyle(document.body).getPropertyValue('--tileW').replace('px', '').trim();
            // An adjustment factor so the square sits comfortably inside a tile
            let side = Math.sqrt(Math.pow(tileW, 2) / 2);
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

            // This is to fix an artefact that happens at 50px for some reason
            if (tileW == 50) {
                tileW = 52;
                document.documentElement.style.setProperty('--tileW', tileW + 'px');
            }

            document.documentElement.style.setProperty('--tileW', `${tileW}px`);
            document.documentElement.style.setProperty('--tileH', `${tileW / 2}px`);

            if (tileW == 100) {
                let tileH = 48;
                document.documentElement.style.setProperty('--tileH', tileH + 'px');
            }
            setSide();

            updateCursor(player.x, player.y, false);

        }// end zoom()


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
            // If moving 0,0 to refresh player's position and info, we don't need all these checks
            let x = player.x + xInc;
            let y = player.y + yInc;
            if (xInc != 0 || yInc != 0) {
                // If we are off the map, go to the far edge
                if (x<0) x=0;
                if (y<0) y=0;
                if ( typeof mapTiles[x] === "undefined") x = mapTiles.length-1;
                if ( typeof mapTiles[0][y] === "undefined") y = mapTiles[0].length-1;

                // Update the cursor to this location
                player.x = x;
                player.y = y;
                // Also update the alpine values. Seems like a clunky way to do this
                document.getElementById('gameUI').__x.$data.currentX = x;
                document.getElementById('gameUI').__x.$data.currentY = y;
            }

            tileTypeName = tileTypes.filter(tile => tile.id == mapTiles[x][y][0])[0].name;
            document.getElementById('currentTileType').innerHTML = tileTypeName;

            // Update NPCs and buildings in the info area
            let possibleNpcs = mapTiles[x][y][3];

            // TODO: Show NPCs (and probabilities) for this tile
            // Make an array of the NPCs on this particular tile, factoring in the probability 0-99 vs npc.probability.
            // tileNPCs = mapNPCs.filter(npc => possibleNpcs.filter(
            //     pn => pn == npc.id).length > 0 && npc.probability > Math.floor(Math.random() * 100)
            // );
            //
            // updateNPCList(tileNPCs);

            const buildingLink = document.getElementById('buildingLink');
            buildingLink.innerHTML = '';

            let building = mapTiles[x][y][4];
            if (building != null) {
                if (building.name.trim().length == 0) building.name = "Portal";
                buildingLink.innerHTML = '<div class="buildingName">' + building.name + '</div>';
                if (Number.isInteger(building.dMapId) && Number.isInteger(building.dX) && Number.isInteger(building.dY)) {
                    buildingLink.innerHTML += '<a id="buildingTeleport" class="iButton" href="javascript:void(0);" data-pid="' + building.bid + '">Travel<kbd class="tiny">Space</kbd></a>';
                    // Bind buildingTeleport button to the usePortal() function
                    document.getElementById('buildingTeleport').addEventListener('click', function () {
                        usePortal(this.getAttribute('data-pid'));
                    });

                }

                if (building.link && building.link.trim().length > 0) {
                    buildingLink.innerHTML += '<a id="buildingTeleport" onclick="logVisitLink(\'' + building.link + '\')" class="iButton" target="_blank" href="' + building.link + '">Open Link</a>';
                }
            }

            //Do the animation and update the player position on the map
            updateCursor(x, y, smooth);
        }// end move()

        // Update tile info summary data
        function updateTileInfo(x, y) {
            let formData = new FormData();
            formData.append('x', x);
            formData.append('y', y);
            // Send a get request to the /maps/{map}/tile endpoint
            fetch('/maps/{{$map->id}}/tile?x='+x+'&y='+y, {
                method: 'GET',
                headers:{"X-CSRF-Token": "{{ csrf_token() }}" },
            })
                .then((resp) => resp.json())
                .then(function(data) {
                    document.getElementById('currentTileType').innerText = data.tileTypeName;
                    let npcList = "";
                    data.npcs.forEach(function(npc) {
                        npcList += '<li>';
                        npcList += '<span class="text-xs text-gray-500 mr-1">'+npc.id+'</span>';
                        npcList += npc.name;
                        npcList += '<span class="text-xxs text-gray-500 ml-1">'+npc.probability+'%'+'</span>';
                        npcList += '</li>';
                    })
                    document.getElementById('npcList').innerHTML = npcList;

                    let buildingList = "";
                    data.buildings.forEach(function(building) {
                        buildingList += '<div class="text-xs">';
                        buildingList += '<span class="text-gray-500 mr-1">'+building.id+'</span>';
                        buildingList += building.name;
                        if (building.dest_map_id != null) {
                            buildingList += '<i class="fas fa-arrow-right text-center w-8"></i><a class="link" href="{{ route('maps') }}/' + building.dest_map_id + '">Map ' + building.dest_map_id + '</a> <span class="text-gray-500 ml-1">' + building.dest_x + ',' + building.dest_y + '</span>';
                        }
                        if (building.external_link != null && building.external_link.length) {
                            buildingList += '<a class="link" target="_blank" href="' + building.external_link + '"><i class="fas fa-link ml-2"></i> link</a>';
                        }
                        buildingList += '</div>';
                    })
                    document.getElementById('buildingLink').innerHTML = buildingList;


                })
                .catch(function(error){
                    console.log(error);
                });

        }

        function updateCursor(x, y, smooth = false) {
            document.getElementById('currentCoords').innerHTML = 'At ' + x + ',' + y;

            // get information about this tile and put it in #tileInfoSummary
            updateTileInfo(x, y);

            const map = document.querySelector('#map');
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
            // Position z-index so that the cursor appears behind tall objects
            let lenX = mapTiles.length - 1;
            let lenY = mapTiles[0].length - 1;
            outlineC.style.zIndex = (1 + 1 + (x - y) + Math.max(lenY, lenX)) * 2;
            // Horrible hack to restart animation so it doesn't flash while moving
            clearTimeout(animationTimer);
            animationTimer = setTimeout(function () {
                outlineC.style.animation = 'flash 2s infinite ease-in-out';
                outlineC.style.transition = 'none';
                cursor.style.transition = 'none';
            }, 500);

            scrollToEl(map, dest, smooth);
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

            if (typeof smooth === "boolean" && smooth === false) {
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
            if (document.activeElement.matches('input[type="text"],input[type="password"],textarea')) {
                logKey(e)
            } else if (e.type === "keydown") {
                gameHotKey(e);
                // prevent tiles panel from scrolling when using up/down arrow keys.
                // This may be bad for accessibility - could also force user to use WASD keys
                if (e.keyCode === 40 || e.keyCode === 38) return false;
            }
        }


        function gameHotKey(e) {
            e = e || window.event;

            if (e.keyCode === 81) {
                // q
                document.querySelector('#controls .upleft').classList.add('active');
                setTimeout(function () {
                    document.querySelector('#controls .upleft').classList.remove('active');
                }, 150);
                upleft();
            } else if (e.keyCode === 38 || e.keyCode === 87) {
                // up arrow, w
                document.querySelector('#controls .up').classList.add('active');
                setTimeout(function () {
                    document.querySelector('#controls .up').classList.remove('active');
                }, 150);
                up();
            } else if (e.keyCode === 69) {
                // e
                document.querySelector('#controls .upright').classList.add('active');
                setTimeout(function () {
                    document.querySelector('#controls .upright').classList.remove('active');
                }, 150);
                upright();
            } else if (e.keyCode === 90) {
                // z
                document.querySelector('#controls .downleft').classList.add('active');
                setTimeout(function () {
                    document.querySelector('#controls .downleft').classList.remove('active');
                }, 150);
                downleft();
            } else if (e.keyCode === 40 || e.keyCode === 83 || e.keyCode === 88) {
                // down arrow, s, x. Yes, that's a lot of keys
                document.querySelector('#controls .down').classList.add('active');
                setTimeout(function () {
                    document.querySelector('#controls .down').classList.remove('active');
                }, 150);
                down();
            } else if (e.keyCode === 67) {
                // c
                document.querySelector('#controls .downright').classList.add('active');
                setTimeout(function () {
                    document.querySelector('#controls .downright').classList.remove('active');
                }, 150);
                downright();
            } else if (e.keyCode === 37 || e.keyCode === 65) {
                // left arrow
                document.querySelector('#controls .left').classList.add('active');
                setTimeout(function () {
                    document.querySelector('#controls .left').classList.remove('active');
                }, 150);
                left();
            } else if (e.keyCode === 39 || e.keyCode === 68) {
                // right arrow
                document.querySelector('#controls .right').classList.add('active');
                setTimeout(function () {
                    document.querySelector('#controls .right').classList.remove('active');
                }, 150);
                right();
            } else if (e.keyCode === 13) {
                // enter
                document.getElementById('showTileDetails').dispatchEvent((new Event('click')));
            } else if (e.keyCode === 32) {
                // space
                if (document.getElementById('buildingTeleport'))
                    document.getElementById('buildingTeleport').dispatchEvent((new Event('click')));
            } else if (e.keyCode === 27) {
                // esc
                closeModal();
            } else if (e.keyCode === 187 || e.keyCode === 107) {
                // =/+ or keypad +
                document.querySelector('#zoomIn').classList.add('active');
                document.querySelector('#zoomIn').dispatchEvent((new Event('click')));
                setTimeout(function () {
                    document.querySelector('#zoomIn').classList.remove('active');
                }, 150);
            } else if (e.keyCode === 189 || e.keyCode === 109) {
                // =/+ or keypad +
                document.querySelector('#zoomOut').classList.add('active');
                document.querySelector('#zoomOut').dispatchEvent((new Event('click')));
                setTimeout(function () {
                    document.querySelector('#zoomOut').classList.remove('active');
                }, 150);
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

        /**
         * Initialization tasks to be performed when the game first loads, should be called called on DOMContentLoaded.
         * This involves adding event handlers, initializing data, loading the map, etc.
         */
        document.addEventListener("DOMContentLoaded",function() {

            // Ensure updates are performed after Livewire updates
            Livewire.hook('element.updated', (el, component) => {
                mapTiles = JSON.parse(document.getElementById('gameUI').__x.$data.mapTiles);
            });

            // Handle arrow keypresses.
            document.onkeydown = handleKey;
            document.onkeyup = handleKey;

            // Set the size of the player's blinking cursor
            setSide();

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

            setVH();

            // reset vh on window resize event
            window.addEventListener('resize', handleResizing);
            window.addEventListener('orientationchange', handleResizing);


            // Handle clicks on panelButtons
            const panelButtons = document.querySelectorAll('.panelButton');

            panelButtons.forEach(function (pb) {
                pb.addEventListener('click', function () {
                    panelParent = document.querySelector('#panelParent');
                    const panelName = this.id.replace('button', '');
                    const panelNameL = panelName.toLowerCase();
                    const panel = document.getElementById('panel' + panelName);

                    // Clicking the active button - should collapse that panel
                    if (this.classList.contains('active')) {
                        this.classList.remove('active');
                        panel.classList.add('collapsed', 'collapsedM', 'collapsedS');
                        for (let i = 1; i <= 3; i++) {
                            if (getCookie('panel' + i) == (panelName || 'null')) deleteCookie('panel' + i);
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
                            if (pnl != panel && !pnl.classList.contains('collapsed')) {
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
                        if (getCookie('panel3') != (null || 'null' || panelName)) setCookie('panel4', getCookie('panel3'));
                        if (getCookie('panel2') != (null || 'null' || panelName)) setCookie('panel3', getCookie('panel2'));
                        if (getCookie('panel1') != (null || 'null' || panelName)) setCookie('panel2', getCookie('panel1'));
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



            // Get the panel cookie. If one is set, open the relevant item
            let pc4 = getCookie('panel4');
            let pc3 = getCookie('panel3');
            let pc2 = getCookie('panel2');
            let pc1 = getCookie('panel1');
            if (pc4 != null && pc4 != 'null') {
                const pnl = document.getElementById('panel' + pc4);
                pnl.classList.remove('collapsed');
                pnl.classList.add('collapsedM');
            }
            if (pc3 != null && pc3 != 'null') {
                const pnl = document.getElementById('panel' + pc3);
                pnl.classList.remove('collapsed');
                pnl.classList.add('collapsedM');
            }
            if (pc2 != null && pc2 != 'null') {
                const pnl = document.getElementById('panel' + pc2);
                pnl.classList.remove('collapsed', 'collapsedM');
                pnl.classList.add('collapsedS');
            }
            if (pc1 != null && pc1 != 'null') {
                const pnl = document.getElementById('panel' + pc1);
                pnl.classList.remove('collapsed', 'collapsedM', 'collapsedS');
            }
            updatePanelButtons();

            // If the user has adjusted zoom before, set the tileW from the cookie and zoom to set this.
            // TODO: Update this to use localstorage for management.
            if (typeof getCookie('tileW') === "string") {
                let tileW = getCookie('tileW');
                document.documentElement.style.setProperty('--tileW', `${tileW}px`);
                document.documentElement.style.setProperty('--tileH', `${tileW / 2}px`);
                // Sigh, there's a weird glitch that needs a pixel offset. Maybe there's a way to fix this.
                if (tileW == 50) {
                    tileW = 51;
                    document.documentElement.style.setProperty('--tileW', tileW + 'px');
                }
                if (tileW == 100) {
                    let tileH = 48;
                    document.documentElement.style.setProperty('--tileH', tileH + 'px');
                }

                setSide();
            }

            // Update the cursor on page load
            updateCursor(player.x,player.y);

        });


    </script>

</div>
