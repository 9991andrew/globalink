<x-app-layout>
    <x-header>
        <x-title>
            MEGA World Management Guide
        </x-title>
    </x-header>

    @push('styles')
        <style>
            .documentation h2 {
                margin-top: 2rem;
                margin-bottom: 0.25rem;
                font-size:1.5rem;
                font-weight:bold;
            }

            .documentation h3 {
                font-size:1.125rem;
                font-weight:bold;
            }

            .documentation h3+p {
                margin-top:0rem;
            }


            .documentation p {
                margin-top:.5rem;
                margin-bottom:1rem;
            }

            .documentation  ul {
                list-style-type: disc;
                margin-left:2rem;
            }

            .documentation li {
                margin-bottom:1rem;
            }

            .documentation .toc li {
                margin-bottom:0.25rem;
            }

            .documentation img, .documentation picture {
                display:block;
                text-align: center;
                margin: auto;
            }
            .documentation picture source, .documentation picture img {
                max-width:900px;
                text-align:center;
                margin: auto;
            }

        </style>
    @endpush
    <div id="guide-top" class="documentation max-w-3xl m-auto my-4 bg-white dark:bg-gray-800 shadow p-4 rounded-lg">

        <h2 style="margin-top:0;" id="Tutorial">Tutorial Video</h2>
        <!-- padding hack to maintain the aspect ratio of the video -->
        <div class="max-w-2xl m-auto">
            <div class="relative" style="padding-bottom:calc((9 / 16) * 100%);">
                <iframe class="absolute top-0 left-0 w-full h-full" src="https://www.youtube.com/embed/gt3o8NJlq_0" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            </div>
        </div>

        <h2>Table of Contents</h2>
        <ul class="toc pl-8">
            <li><a class="link" href="#Introduction">Introduction</a></li>
            <li><a class="link" href="#Basics">Basics of Management</a></li>
            <li><a class="link" href="#TableViews">Using Table Views</a></li>
            <li><a class="link" href="#BulkOperations">Using Bulk Operations</a></li>
            <li><a class="link" href="#Buildings">Buildings</a></li>
            <li><a class="link" href="#Birthplaces">Birthplaces</a></li>
            <li><a class="link" href="#Professions">Professions</a></li>
            <li><a class="link" href="#NPCs">NPCs</a></li>
            <li><a class="link" href="#Items">Items</a></li>
            <li><a class="link" href="#Quests">Quests</a></li>
            <li><a class="link" href="#Advanced">Advanced Quests</a></li>
            <li><a class="link" href="#QuestTools">Quest Tools</a></li>
            <li><a class="link" href="#PlayTesting">Play Testing</a></li>
            <li><a class="link" href="#Conclusion">Conclusion</a></li>


        </ul>


        <h2 id="Introduction">Introduction</h2>
        <p>MEGA World is an educational game that allows players to complete quests that support educational goals. If you’re a teacher, you can use the MEGA World Management website to create content for <a href="https://megaworld.game-server.ca/">MEGA World</a> that your students can register to play.</p>
        <p>This guide will give an overview of how to use the Management app and how MEGA World is structured so that you can offer students a fun and engaging series of quests to complete.</p>

        <h2 id="Basics">Basics of Management</h2>
        <p>To use Management, you’ll need an account. Self registration isn’t supported, so you’ll have to ask an administrator to create an account for you. Once you have a username and password, you can log in at <a class="link" href="https://management.megaworld.game-server.ca/">https://management.megaworld.game-server.ca/</a></p>
        <img src="images/guide/dashboard.webp" width="1800" height="1530" />
        <p>On logging in, you’ll be presented with the Dashboard home page which shows all the types of data that can be edited, such as <a class="link" href="maps">Maps</a>, <a class="link" href="items">Items</a>, and <a class="link" href="quests">Quests</a>. Click on any of the links to get to a table view showing all of that type of object in MEGA World. You may also navigate to the most-used views using the navigation at the top of the page, or in the hamburger menu on mobile devices.<p>
        <p>There is also  a user menu at the top right which allows you to log out and toggle between dark and light themes. By default, your theme will be based on your operating system preference.</p>

        <h2 id="TableViews">Using Table Views</h2>
        <img src="images/guide/players-table.webp" width="1800" height="1530" />
        <p>There are a number of features that are common to all of the table views. First, at the top you’ll see the title with the name of the type of data you’re viewing, along with the number of records in the database for that type.</p>

        <p>These views are split up into pages to make loading and working with them faster, but you can change how many items are on a page using the “per page” dropdown near the top, selecting as few as 10 records for fast loading or up to 5,000 records to show everything on one page at the same time.</p>
        <p>To the right of the title you will see the Bulk Actions menu which is mostly used for deleting many items at once. This is grayed out until you have selected some items with the checkboxes on the left of each row.</p>
        <p>Table views have a search filter under the title. This lets you type in a name or ID, but some views use this field to search across additional fields of the database. There may be additional filters that allow you to view only specific types so that you can focus on the particular items you’re interested in. As soon as you type or change an option the table results will immediately update. If you want to view all the rows again, click the “Show all” link under the filter area.</p>
        <p>The table heading area shows what that column contains, and you can click a heading to sort the table by that column. Clicking it again reverses the sort. A highlighted arrow indicates the direction of the sort, pointing up for ascending, down for descending. You can even click other headings and the previously clicked heading will become a secondary sort on the table, indicated by a dimmed arrow. For instance you could sort players, by Energy, and then by Money if the Energy matches for several players.</p>
        <p>If you’d like to see the full details for an object, click the green ID number on the left, or the green pencil the right. To permanently delete an object, click the red X on the right edge of the table and click “Delete” to confirm. Note that not all objects can be deleted because other objects in the game may depend on them. In order to delete such items, their dependencies will have to be deleted first. For instance, a map cannot be deleted if players are on the map - the players will have to be moved off the map first.</p>

        <h2 id="BulkOperations">Using Bulk Operations</h2>
        <p>Management allows you to perform operations (typically deletion) on many items at once using the Bulk Actions menu to the right of the title. To do this, click the checkboxes for objects on the left side of table rows. You may select all rows on a page using the checkmark in the table header at the top of a table. Clicking it again unchecks all the rows. Note that your selection will be preserved even if you change pages.</p>
        <p>Additionally, you can even check every row in the database by clicking the “Select All” button that appears after selecting an object. Obviously you will want to be careful with this ability!</p>
        <p>Once the desired items are selected, click the Bulk Actions menu at the top right and select an option. If you select Delete, you will be required to confirm it before the records are deleted.</p>
        <p>Note that Bulk deletes may fail on objects that have other objects that depend on them, so if bulk deletes fail, ensure you have only have objects selected that are no longer in use in the game.</p>


        <h2 id="Creating">Creating Maps</h2>
        <p>If you want to create your own independent “world”, you can get started by making a new Map Type that will be used exclusively for your content.</p>

        <ul>
            <li>From the Dashboard, click “Map Types”.</li>
            <li>On Map Types click the “+ New” button.</li>
            <li>Use a short name for your world - note that players won’t typically see this so it can just be a name used to differentiate it from other map types in the game.</li>
            <li>Add a description explaining what this map type is for and what sort of contents it will be used for.</li>
            <li>Click Save.</li>
        </ul>

        <p>Now maps can be added using your new type.</p>
        <ul>
            <li>Click “Maps” at the top of the page (or on the Dashboard)</li>
            <li>Click “+ New” near the top right of the Maps page.</li>
            <li>Choose a creative name for your Map.</li>
            <li>Select your map type that you just created.</li>
            <li>Write a description of the map that will help you remember what this map will have on it.</li>
            <li>Click Save.</li>
        </ul>

        <p>Your new map is now ready for editing. You may have to search for your new map’s name or sort the maps by ID in reverse order to show it at the top.</p>
        <p>The Maps view will show a small preview of what the maps look like, but you’ll notice that your map doesn’t show anything. This is because you haven’t yet added any tiles, which is covered in the next section.</p>


        <h2 id="Editing">Editing Maps</h2>
        <p>You can access the editor for a map from the Map icon link at the right of the Maps table, or from the “Open Map Tile Editor” link at the top of a Map edit dialog.</p>
        <img src="images/guide/map-editor.webp" width="1800" height="1530" />
        <p>The map editor works much like the game interface for MEGA World, although there are some key differences.  At the top, the name of the map will be shown, along with the map type, and the length and width. You will notice at the bottom there are panels for Tiles, Buildings, and NPCs.</p>
        <p>The Tiles panel shows a list of all the map tiles available in MEGA World, and by clicking on a tile you can add it to the currently selected location. If this is a new map, you will have to start by using the “Expand X” and “Expand Y” buttons to create some area to work with. You may expand the map to be as large as you want, within reason, although keep in mind that making extremely large maps may begin to affect the performance of the game.</p>
        <p>If you make a map too large, you can reduce the size with the “Reduce X” and “Reduce Y” buttons, but keep in mind that the configuration of the tiles on the deleted parts of the map will be permanently lost. Things that reside on the map like Buildings and NPCs will not be deleted on the map but players will have no way to access them until their location is moved.</p>
        <p>You need to move the yellow cursor to select a tile in order to edit it. You can use the arrow keys or WASD keys on your keyboard to move it around the map, or use the on-screen controls if you are using a touchscreen. Click a tile icon in the Tiles panel to immediately change the selected tile. Continue working your way through until you have an interesting looking map with features for the places you will want to create.</p>
        <p>The Buildings and NPCs panel shows the location of any Buildings or NPCs on this map, and you may click the links to open the editors for those objects.</p>


        <h2 id="Buildings">Buildings</h2>
        <p>In MEGA World, a “Building” can be added to each map tile, and a building can have a portal that allows the player to be transported to another location (even on any other map). Portals also optionally may have links with useful information for players.</p>
        <p>Buildings can be limited only to certain professions so that you can prevent players from using certain buildings (and thus, perhaps prevent access to certain maps) until the player has acquired a certain profession. In this way, you can create maps that player will only be able access after some work has been put in.</p>
        <p>The building title can always be seen by players on the same tile, even if they have not achieved any requisite professions.</p>


        <h2 id="Birthplaces">Birthplaces</h2>
        <p>When a user creates a new player, they will be required to pick a birthplace which determines which map and location they will start on. Typically a game with a series of quests will have a unique birthplace to put a player in the right spot to start their game. The name and description (as well as the name of the map) can be seen by users when they create players.</p>

        <h2 id="Professions">Professions</h2>
        <p>In MEGA World many limitations can be imposed on Players based on their professions. Professions can be hierarchical, requiring one or more prerequisite professions with a certain amount of experience points (xp). Typically, a series of quests will be written that allows a player to work up to more advanced professions by gaining enough experience in one profession to then take on subsequent professions that then can unlock more building portals and quests that are only available to players with a certain profession and level.</p>

        <p>Players achieve levels in their profession by completing quest and earning enough XP to get to the next level. The XP required to get to the player’s next level is 90% of the amount of XP awarded by the quests available at the player’s current level level.</p>


        <h2 id="NPCs">NPCs</h2>
        <p>Non player characters offer a number of interactions to Players. First, they can offer quests to Players that can be completed for XP and money, they can give players professions once a player meets the prerequisites, and they can sell items to players.</p>
        <p>NPCs each live on one specific map, but can exist within a rectangle on a map. Note that the larger the area the NPC “lives” in, the lower the probability of the player finding the NPC is. The probability is the inverse of the number of tiles they can appear on. So if an NPC lives on two tiles, the odds of the player finding the NPC is 100% — but if the NPC lives on eight tiles, the odds are only 25%. Be careful about allowing an NPC to exist anywhere on the map as a player could be very frustrated by trying to find the NPC that has such low odds of appearing each time the player moves.</p>
        <p>NPCs can be assigned icons, and MEGA World comes with a number of icons in different styles that you can give an NPC. If desired, you can also upload a custom NPC icon using the NPC Icon editor. Type in some keywords to the icon search field in the NPC editor to search from the various icons included with MEGA World.</p>
        <p>The NPC’s level currently has no impact on the game, so you can assign it whatever makes sense to you. A child may have a much lower level than a grizzled veteran of many battles, for instance.</p>
        <p>NPCs may offer to train players in professions, and can also offer to take the player to other locations. This functionality works much like building portals, except that NPC portals can be limited to players with a certain profession level and can also cost money.</p>
        <p>Players may purchase (or receive for free) items from a player. This might be items required for another quest, food, which players require to replenish movement points, or just random things of interest. If NPCs are going to give Players items related to quests, those items will be assigned in the quests editor, but you may view and delete all items, from the NPC editor, even items given for quests.</p>
        <p>Finally, NPCs can give quests to players, and serve as the destination for most quests, although these are specified in the Quest editor, not the NPC editor.</p>


        <h2 id="Items">Items</h2>
        <p>Items serve three main purposes in the game. The first is to serve as a requirement for quests, and in many cases each item can serve as a question or an option for quests. In such quests a player will have to obtain all the required items and bring them to a target NPC, and the NPC may ask the player to identify which if the items is correct, or provide an appropriate answer for each item.</p>
        <p>The second purpose items serve is as food to replenish a player’s movement points. Players have a finite number of movement points that are consumed as they move, and they will need to eat in order to keep playing the game. This function can be performed by choosing “eat” as an Item Effect, and and specifying how many movement points the player gets (in hundreds) under “effect parameters”.</p>
        <p>Finally, items can “dig” which is relevant for quests that use “Quest Tools”. When this is enabled, the player may try to use the item on a map tile, and if that tile has been configured as a quest tool location, the player may receive an item or be able to complete a quest.</p>
        <p>Items can be configured with a price, for which the player must pay to obtain them from an NPC. Initial amount is the quantity of the item a player receives when they get the item. Max Stack amount is the quantity of an item the player can have in one bag slot.</p>
        <p>Finally, items may have variables are a range of numbers from which a player will randomly be assigned a value when they receive the item. These variables may be used with quest tools to allow a tool to work at a different location each time the player receives the item.</p>
        <p>The item description may use HTML and LaTeX for formatting, which means that you can create math equations that the player can solve. If you’d like to use variables in this field, use two curly brackets around the variable name and the variable will appear in its place: eg @{{a}}, @{{b}}.</p>
        <p>Like NPCs, Items have icons and there are a number of images to choose from in MEGA World. If you’d like to upload a custom image, use the Item Icons view in Management.</p>


        <h2 id="Quests">Quests</h2>
        <p>We have come to one of the more complex parts of MEGA World, but possibly the most important. Players may take on quests from NPCs in the game and complete them to obtain money and XP and advance in level to move on to other parts of the game.</p>
        <p>To create a quest, start by clicking “+ New” in the quest editor, and give the quest a name. The player will see this, so be thoughtful. The level specifies what level the player needs to have in a required profession in order to accept the quest.</p>
        <p>If you’d like a player to be able to perform the quest multiple times, a quest can be Repeatable, and you may specify an amount of “cooldown” time the player must wait before doing so.</p>
        <p>Quests are all received from a “Giver NPC”, so specify an NPC you have created that the player can find on a map. If you’d like the player to immediately receive items from the NPC to use in the quest, check the “NPC Gives Quest Items” checkbox.</p>
        <p>There is a lot more to do before the quest is done, but you must save the quest to continue editing further settings. Once the quest is saved, you can edit the remaining options.</p>
        <p>There is a lot that can be done to configure quests. Here is a list of the key properties.</p>

        <ul>
            <li>Required Professions can be specified that the player has to have at the indicated level before taking the quest.</li>
            <li>Required Quests can be specified so that a sequential series of quests can be performed in a certain order.</li>
            <li>A list of NPC items can be specified. The items added here will be available to the Player from the specified NPC but only when the player is working on this quest. If the “NPC Gives Quest Items” checkbox is checked, the giver will automatically give the player any of the items they have from this list.</li>
            <li>Quest tools can be specified which allow the player to “dig” at specified locations to find items. These items must be obtained just like any other items so quest tools (and quest item) may also need to be specified under NPC items so the player has some means of obtaining them, unless they are already available from elsewhere in the game.</li>
            <li>Target NPC is an optional NPC that the player must visit in order to attempt to complete a quest. If one is not specified, the Giver NPC automatically becomes the target.</li>
            <li>Prologue is only displayed when a player first “inquires” about a quest.</li>
            <li>Content is displayed in the player’s quest information, and is also displayed when the player attempts to complete a quest with the target NPC.</li>
            <li>Target NPC Prologue is displayed when a player first inquires about a quest in progress with the target NPC.</li>
            <li>Success response is what the NPC says when the player successfully completes a quest.</li>
            <li>Failure response is what the NPC says when the player gets wrong answers when attempting to complete a quest.</li>
            <li>Retry Cooldown adds a delay before a player is able to complete the quest another time after failing.</li>
            <li>You may specify reward items that are given to a player when they complete a quest. In some cases you may want to also add quest items here if you want the player to keep them, because all quest items are removed from the player’s inventory when they successfully complete a quest.</li>
            <li>Base Reward Percentage is used for quests with long text answers and allows you to specify how similar the player’s answer needs to be to the specified “correct” answer in order to count as a successful quest completion.</li>
        </ul>

        <h2 id="Advanced">Advanced Quests</h2>
        <p>There are many types of quests in MEGA World, and this section will detail how they work.</p>
        <ul>
            <li>
            <h3>Check In (Items Optional)</h3>
            <p>This is the simplest quest that involve a player meeting a Target NPC or bringing items to the Target NPC, after which the quest will be automatically completed.</p>
            </li>

            <li>
            <h3>Items True/False</h3>
            <p>The player acquires all of the quest items and must specify which of them are true and false. The description of the item will typically be the statement being evaluated as true or false.</p>
            </li>

            <li>
            <h3>Items Choose One</h3>
            <p>The player specifies which one of the quest items are correct.</p>
            </li>

            <li>
            <h3>Items Choose Multiple</h3>
            <p>As above, but more than one can be chosen.</p>
            </li>

            <li>
            <h3>Items Order</h3>
            <p>The player must drag items into the right order in a sequence. This could be steps in a process or lines of code in a simple program.</p>
            </li>

            <li>
            <h3>Items Text Answers</h3>
            <p>Each item requires a text answer that must be filled in.</p>
            </li>

            <li>
            <h3>Cloze (Fill in Blanks)</h3>
            <p>In this case, the a number of “Answers” will be created and be inserted into the quest content using @{{A}}, @{{B}}, @{{C}}, etc. placeholders. The player will see these as blanks and be required to fill the correct answer into all the blanks.</p>
            </li>

            <li>
            <h3>True/False</h3>
            <p>A number of questions are posed and the player must evaluate them as True or False. For this type, the answer field is not used.</p>
            </li>

            <li>
            <h3>Choose One</h3>
            <p>The player is presented with a list of “answers” and must specify which one is true.</p>
            </li>

            <li>
            <h3>Choose Multiple</h3>
            <p>As above but more than one (or none) may be chosen.</p>
            </li>

            <li>
            <h3>Text Long Answers</h3>
            <p>A question is posed and a player must enter a text response. This response may contain multiple lines.</p>
            </li>

            <li>
            <h3>Calculation</h3>
            <p>A player must calculate an answer. The player can be randomly assigned variables from a specified range of integers, and those integers may be used in the Quest Content using the @{{a}}, @{{b}} placeholders. Further, the quest content may contain mathematical notation in LaTeX or MathML format to present in the format a question may appear in a math textbook.</p>
            <p>For calculation quests, the answer may be specified as an equation that will be evaluated when the player attempts to answer. Variables may just be added into this equation as themselves. For instance, you may specify an equation like “sqrt((a+b)/2)”  and the a and b will be evaluated with whatever variables the player received.</p>
            </li>

            <li>
            <h3>Coordinates</h3>
            <p>The player must find a location on the specified map and will be given some kind of clues as to how to find it.</p>
            <p>As described for Calculation quests, the X and Y coordinates may be equations that use variables so that the locations are different each time. Just be careful with this type of quest - you could easily give the players coordinates that land the player in the middle of an ocean or at a non-existent map tile. But if this happens, the player will be informed by the NPC that they can drop the quest and take the quest again to get a different location.</p>
            </li>

            <li>
            <h3>Speaking (Conversation)</h3>
            <p>This is currently a variation of the Text Long Answer quest that uses speech recognition. Only Safari and Chrome support this feature. A future version of MEGA World may use this for more elaborate conversation trees to help language learners practice speaking.</p>
            </li>

            <li>
            <h3>Items Calculation</h3>
            <p>Similar to Calculation above, although each item may have an equation that the player must solve. The equation should usually be part of the item description. Quest variables can be used for the answers, but these must be shown to the player in the Quest Content.</p>
            </li>
        </ul>


        <h2 id="QuestTools">Quest Tools</h2>
        <p>Quests may be assigned quest tools that the player can use to find items in the game. Each tool has one or more locations at which a player will find a specified item. These are configured in the Quest Tools view.</p>
        <p>Like Calculation quests and Coordinates quests, the X and Y coordinates may be equations that use variables. In this case the variables are the item variables of the quest tool, so edit the tool item to configure these variables.</p>
        <p>In some cases you may want finding the item to cause the quest to end, so you can check “Finding Completes Quest” to cause the player to receive the quest rewards as soon as they find the location and use the quest tool.</p>

        <h2 id="PlayTesting">Play Testing</h2>
        <p>This nearly wraps up the guide however there is one important thing to do before having people try to play your quests. They must be tested in the actual game!</p>
        <p>A player 0 is set aside solely for testing so that it is relatively easy to test advanced quests without having to play the whole game or configure a player with the prerequisites. In the Players view, you may edit Player 0 (Test Player) so that they have a user ID that you have access to.</p>
        <p>When you are satisfied that you have completed a quest, you can click the “Ready Player 0 for Test” button, and Player 0 will be moved to the location of the Giver NPC and given the prerequisite quests and professions in order to be able to accept the quest. Then you can immediately meet the NPC, take the quest, and try completing it to determine if everything is working correctly.</p>


        <h2 id="Conclusion">Conclusion</h2>
        <p>That was a lot of material to cover, but if you made it this far, you should have a fairly thorough understanding of how to use MEGA World Management and how the different parts of MEGA World work together so that you can create an interesting campaign or series of quests for people to play. If you have any questions feel free to get in touch with us.</p>

    </div>

    <div class="text-center p-4 leading-tight"><a href="#guide-top" class="link"><i class="fas fa-angle-double-up"></i><br>Jump to Top</a></div>

</x-app-layout>
