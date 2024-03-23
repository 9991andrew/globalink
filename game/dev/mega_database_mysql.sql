/**
 * MEGA World MySQL Database Schema by JD Lien August 2, 2020 - July 2021
 * 
 * These conventions must be followed when changing or adding tables and fields:
 * - Table names and fields are all lowercase with English words separated by underscores
 * - Tables are pluralized when they contain a list of something like players, items, quests
 * - Virtually all tables should have an "id" primary key, even if they use composite foreign keys
 *     This is because Eloquent models are much easier to work with when there is an single id PK 
 * - Most tables need a "name" for a human readable label of something
 * - Foreign keys start with the singular name of the table they refer to, like
 *     FOREIGN KEY (user_id) REFERENCES users(id)
 * - Virtually all tables should have created_at and updated_at datetime NULL fields
 *     so Laravel/Eloquent can automatically insert auditing timestamps.
 * - Views must end with _view so that it is very clear in code that a view is being used
 * - Add a comment explaining the purpose of the table.
 * - Add a comment explaining the purpose of any non-obvious fields.
 *
 * An interactive diagram of this database can be found at https://dbdiagram.io/d/6094524bb29a09603d13be5d
 */

-- Uncomment these two lines if you need this script to create a database
-- CREATE DATABASE IF NOT EXISTS mega CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
-- USE mega;

-- Uncomment the following three lines and set a password if you don't already have a database user.
-- CREATE USER IF NOT EXISTS 'mega'@'localhost' IDENTIFIED BY 'CHANGE_ME!!';
-- GRANT SELECT,INSERT,UPDATE,ALTER,DELETE,CREATE,DROP ON mega.* TO 'mega'@'localhost';
-- FLUSH PRIVILEGES;


-- Drop existing views
DROP VIEW IF EXISTS map_tiles_buildings_npcs_view;
DROP VIEW IF EXISTS player_stats_initial_view;
DROP VIEW IF EXISTS player_professions_details_view;
DROP VIEW IF EXISTS profession_leveling_view;
DROP VIEW IF EXISTS quest_items_tools_view;
DROP VIEW IF EXISTS player_bag_items_view;

-- Drop existing tables in the reverse order of creation (because of foreign key constraints) so they can be built fresh.
DROP TABLE IF EXISTS languages;
DROP TABLE IF EXISTS protect_detail;
DROP TABLE IF EXISTS quest_chain_type;
DROP TABLE IF EXISTS money_flow_log;
DROP TABLE IF EXISTS player_actions_log;
DROP TABLE IF EXISTS player_action_types;
DROP TABLE IF EXISTS player_quests_log;
DROP TABLE IF EXISTS chat_log;
DROP TABLE IF EXISTS guardian_log;
DROP TABLE IF EXISTS key_log;
DROP TABLE IF EXISTS quest_events;
DROP TABLE IF EXISTS player_quest_variables;
DROP TABLE IF EXISTS player_quests;
DROP TABLE IF EXISTS npc_items;
DROP TABLE IF EXISTS player_avatar_images;
DROP TABLE IF EXISTS guardian_avatar_images;
DROP TABLE IF EXISTS guardian_player_relations;
DROP TABLE IF EXISTS avatar_image_type_colors;
DROP TABLE IF EXISTS avatar_images;
DROP TABLE IF EXISTS avatar_image_types;
DROP TABLE IF EXISTS player_item_variables;
DROP TABLE IF EXISTS player_items;
DROP TABLE IF EXISTS quest_answers;
DROP TABLE IF EXISTS quest_variables;
DROP TABLE IF EXISTS quest_tool_locations;
DROP TABLE IF EXISTS quest_tools;
DROP TABLE IF EXISTS quest_reward_items;
DROP TABLE IF EXISTS quest_items;
DROP TABLE IF EXISTS quest_prerequisite_quests;
DROP TABLE IF EXISTS quest_prerequisite_professions;
DROP TABLE IF EXISTS quests;
DROP TABLE IF EXISTS quest_result_types;
DROP TABLE IF EXISTS item_variables;
DROP TABLE IF EXISTS items;
DROP TABLE IF EXISTS item_effects;
DROP TABLE IF EXISTS item_categories;
DROP TABLE IF EXISTS npc_portals;
DROP TABLE IF EXISTS npc_professions;
DROP TABLE IF EXISTS npcs;
DROP TABLE IF EXISTS bag_slots;
DROP TABLE IF EXISTS bags;
DROP TABLE IF EXISTS player_professions;
DROP TABLE IF EXISTS player_skills;
DROP TABLE IF EXISTS player_stats;
DROP TABLE IF EXISTS player_base_attributes;
DROP TABLE IF EXISTS players;
DROP TABLE IF EXISTS guardians;
DROP TABLE IF EXISTS birthplaces;
DROP TABLE IF EXISTS genders;
DROP TABLE IF EXISTS race_attributes;
DROP TABLE IF EXISTS races;
DROP TABLE IF EXISTS stat_calculation_rules;
DROP TABLE IF EXISTS stats;
DROP TABLE IF EXISTS building_professions;
DROP TABLE IF EXISTS buildings;
DROP TABLE IF EXISTS profession_prerequisites;
DROP TABLE IF EXISTS professions;
DROP TABLE IF EXISTS map_tiles;
DROP TABLE IF EXISTS map_tile_types;
DROP TABLE IF EXISTS maps;
DROP TABLE IF EXISTS map_types;
DROP TABLE IF EXISTS skills;
DROP TABLE IF EXISTS item_icons;
DROP TABLE IF EXISTS npc_icons;
DROP TABLE IF EXISTS management_users;
DROP TABLE IF EXISTS users;

SET default_storage_engine=INNODB;

-- This is required to set PKs of 0;
SET sql_mode='NO_AUTO_VALUE_ON_ZERO';

-- List of users who have registered and may log into the system.
-- Contains no player information as each user may now have multiple player characters.
CREATE TABLE users (
	id int PRIMARY KEY AUTO_INCREMENT,
	username varchar(30), -- Can't have two usernames that are alike
	password varchar(40), -- SHA1 hash of password
	email varchar(255) NULL, -- May use this for password resets in the future
	locale_id varchar(20) NULL DEFAULT 'en_CA', -- Used for localization
	last_login_at datetime NULL, 
	last_player_id int NULL,
	-- Do not enforce this foreign key because it makes creation of the DB a bit more challenging.
	-- Have to add this constraint after the creation of the players table.
	-- FOREIGN KEY (last_player_id) REFERENCES players(player_id)
	created_at datetime NULL,
	updated_at datetime NULL,	
	-- Previously, users could have the same username if the case was different.
	-- This is no longer allowed.
	CONSTRAINT uq_users_user_name UNIQUE (username)
);

-- Users who may administer and edit MEGA World data. Separate from users for security and detachability.
CREATE TABLE management_users (
	id int PRIMARY KEY AUTO_INCREMENT,
	name varchar(30),
	email varchar(255),
	time_zone varchar(255) NOT NULL, -- Used for display time for the user
	password varchar(255), -- Passwords will be upgraded to a new format on first login
	password_old_sha1 varchar(40), -- Original password hash is stored here until first login
	remember_token varchar(100) NULL,
	created_by varchar(30) NULL,
	created_at datetime NULL,
	updated_at datetime NULL,
	last_login_at datetime NULL
);


-- List of images that may be used for in-game items.
CREATE TABLE item_icons (
	id int PRIMARY KEY AUTO_INCREMENT,
	name varchar(200), -- brief description of image, could be used in alt text
	author varchar(200) NULL,
	created_at datetime NULL,
	updated_at datetime NULL
);

-- List of images that may be used for non-player characters.
CREATE TABLE npc_icons (
	id int PRIMARY KEY AUTO_INCREMENT,
	name varchar(200), -- brief description of image, could be used in alt text
	author varchar(200) NULL,
	created_at datetime NULL,
	updated_at datetime NULL
);

-- Used as requirements for moving through certain map_tile_types.
-- Prevents the player from going through certain tiles
CREATE TABLE skills (
	id int PRIMARY KEY AUTO_INCREMENT,
	name varchar(200) NOT NULL,
	created_at datetime NULL,
	updated_at datetime NULL
);

INSERT INTO skills (id, name) VALUES
(1, 'Swimming'),
(2, 'Mountaineering'),
(3, 'Wall Climbing');


-- MEGA World can have multiple worlds that are independent (eg for separate schools or languages).
CREATE TABLE map_types (
	id int PRIMARY KEY AUTO_INCREMENT,
	name varchar(50) NOT NULL UNIQUE,
	description varchar(10000),
	created_at datetime NULL,
	updated_at datetime NULL
);

-- Each map players can move through (comprised of a 2D array of tiles)
CREATE TABLE maps (
	id int PRIMARY KEY AUTO_INCREMENT,
	map_type_id int NOT NULL,
	FOREIGN KEY (map_type_id) REFERENCES map_types(id),
	name varchar(30) NULL,
	description varchar(2000) NULL,
	old_map_id int NULL,
	-- old_map_id relates a map to map entities in the old DB.
	-- After a successful upgrade from MWv2.2, this field will serve no purpose.
	created_at datetime,
	updated_at datetime
);

-- Maps can use approximately 80 types of tiles, each with a different appearance and movement requirements.
CREATE TABLE map_tile_types (
	id int PRIMARY KEY AUTO_INCREMENT,
	name varchar(200),
	movement_req int NOT NULL, -- Number of movement points it takes to move to this tile
	skill_id_req int NULL, -- Tiles like mountains and water require a skill to enter
	FOREIGN KEY (skill_id_req) REFERENCES skills(id),
	map_tile_type_id_image int NULL, -- Only used to create a variant of an existing tile
	-- that uses the same image as another one. For example, create an impassable tile.
	-- Note that we use cascade delete, so deleting a tile with an image shared by others deletes those too
	FOREIGN KEY (map_tile_type_id_image) REFERENCES map_tile_types(id) ON DELETE CASCADE,
	-- Graphic filename is specified when a type uses the same image as another.
	author varchar(200) NULL,
	created_at datetime,
	updated_at datetime
);

-- Specifies tiles for all maps, along with the type of each tile
CREATE TABLE map_tiles (
	id int PRIMARY KEY AUTO_INCREMENT, -- Only needed for Eloquent; the real PK is a composite
	map_id int NOT NULL,
	FOREIGN KEY (map_id) REFERENCES maps(id) ON DELETE CASCADE,
	x int NOT NULL,
	y int NOT NULL,
	map_tile_type_id int NOT NULL DEFAULT 1,
	FOREIGN KEY (map_tile_type_id) REFERENCES map_tile_types(id),
	UNIQUE (map_id, x, y)
	-- To keep this table lightweight, we won't store the times
	-- ,created_at datetime,
	-- updated_at datetime
);

-- Professions that a player may adopt. Players may have more than one at a time.
CREATE TABLE professions (
	id int PRIMARY KEY AUTO_INCREMENT,
	name varchar(170) UNIQUE NULL,
	require_all_prerequisites bit DEFAULT 0,
	-- if 1, all prerequisites in the profession_prerequisites table are required to adopt.
	-- if above is 0, any of the prerequisites will fulfill the requirements.
	created_at datetime,
	updated_at datetime
);

-- This table allows for a profession to require one or more prerequisites
-- This structure allows for one of multiple options or all of a list.
CREATE TABLE profession_prerequisites (
	id int PRIMARY KEY AUTO_INCREMENT, -- Needed for the eloquent model, real PK is profession_id, profession_id_req
	profession_id int NOT NULL,
	FOREIGN KEY (profession_id) REFERENCES professions(id) ON DELETE CASCADE,
	profession_id_req int NOT NULL,
	FOREIGN KEY (profession_id_req) REFERENCES professions(id),
	profession_xp_req int NOT NULL DEFAULT 0,
	created_at datetime,
	updated_at datetime,	
	-- specifies required xp of the prereq profession
	UNIQUE (profession_id, profession_id_req)
);

-- A map can have buildings on it which may act as portals to other maps and/or have a web link.
-- If dest_map_id, dest_x, and dest_y are set to an integer, we transport the user.
CREATE TABLE buildings (
	id int PRIMARY KEY AUTO_INCREMENT,
	name varchar(100) NULL,
	level int NULL, -- Level required to access building. Sounds like an ACL. Not used yet.
	map_id int NULL,
	FOREIGN KEY (map_id) REFERENCES maps(id) ON DELETE CASCADE,
	x int NULL,
	y int NULL,
	dest_map_id INT NULL, -- destination map
	FOREIGN KEY (dest_map_id) REFERENCES maps(id) ON DELETE CASCADE,
	dest_x int NULL, -- destination coordinate on destination map
	dest_y int NULL,
	external_link varchar(2000) NULL, -- Can load the webpage into the interface.
	created_at datetime,
	updated_at datetime,	
	UNIQUE (map_id, x, y) -- a tile cannot contain more than one building
);

-- Specifies what professions are required to use a building.
-- If none are listed, all professions are allowed.
CREATE TABLE building_professions (
	id int PRIMARY KEY AUTO_INCREMENT,
	building_id int NOT NULL,
	FOREIGN KEY (building_id) REFERENCES buildings(id),
	profession_id int NOT NULL,
	created_at datetime,
	updated_at datetime,
	FOREIGN KEY (profession_id) REFERENCES professions(id) ON DELETE CASCADE,
	UNIQUE (building_id, profession_id)
);

-- Players, npcs, and items can have stats, some of which are attributes a player is 'born' with.
CREATE TABLE stats (
	id int PRIMARY KEY AUTO_INCREMENT,
	name varchar(30) NOT NULL,
	description varchar(2000), -- detailed description of what this statistic does
	is_attribute bit NOT NULL DEFAULT 0
);

INSERT INTO stats (id, name, description, is_attribute) VALUES
(1, 'Strength', 'Determines physical attack power and carrying ability.', 1),
(2, 'Constitution', 'Increases health points, damage received when attacked, and reduces damage over time attacks (eg poisoning).', 1),
(3, 'Dexterity', 'Improves movement, attack speed (including casting), and chance of dodging attacks. Some spells and skills may have dexterity requirements for learning and casting.', 1),
(4, 'Intelligence', 'Affects mana points for casting spells. Some spells and skills have intelligence requirements for learning and casting.', 1),
(5, 'Wisdom', 'Allows more efficient attacking, blocking, casting, attack reaction, and dodging of attacks.', 1),
(6, 'Charisma', 'Determines opportunity to get help and hints from NPCs, including trading with them and getting additional quests.', 1),
(7, 'Attack', NULL, 0),
(8, 'Mana', NULL, 0),
(9, 'Health', NULL, 0),
(10, 'Carry Power', NULL, 0);

-- Relates stats to each other - stats that are attributes can affect other stats
CREATE TABLE stat_calculation_rules (
	id int PRIMARY KEY AUTO_INCREMENT,
	stat_id int NOT NULL,
	FOREIGN KEY (stat_id) REFERENCES stats(id),
	attribute_id int NOT NULL,
	FOREIGN KEY (attribute_id) REFERENCES stats(id),
	attribute_coefficient double NOT NULL
);

INSERT INTO stat_calculation_rules (id, stat_id, attribute_id, attribute_coefficient) VALUES
(1, 7, 1, 0.2),
(2, 7, 5, 0.125),
(3, 8, 4, 15),
(4, 9, 2, 10),
(5, 9, 1, 0.125),
(6, 10, 1, 2500),
(7, 1, 1, 1),
(8, 2, 2, 1),
(9, 3, 3, 1),
(10, 4, 4, 1),
(11, 5, 5, 1),
(12, 6, 6, 1);

-- Players are of a race, each with modifiers to certain stats
CREATE TABLE races (
	id int PRIMARY KEY AUTO_INCREMENT,
	name varchar(20) NOT NULL,
	description varchar(2000), -- A backstory or description for each race could be added here
	created_at datetime,
	updated_at datetime
);

-- Insert races
INSERT INTO races (id, name) VALUES
(1, 'Human'),
(2, 'Dwarf'),
(3, 'Elf'),
(7, 'Orc');

-- Races may have adjustments to their attributes.
CREATE TABLE race_attributes (
	race_id int NOT NULL,
	FOREIGN KEY (race_id) REFERENCES races(id),
	attribute_id int NOT NULL,
	FOREIGN KEY (attribute_id) REFERENCES stats(id),
	modifier int NOT NULL, -- ± adjustment to stats
	PRIMARY KEY (race_id, attribute_id), -- each race/stat combo only gets only one entry
	created_at datetime,
	updated_at datetime	
);

-- Players have a gender that affects how their avatar looks
CREATE TABLE genders (
	id int PRIMARY KEY AUTO_INCREMENT,
	name varchar(30),
	available bit default 1 -- use this field to prevent users from selecting a gender
);

INSERT INTO genders (name, available) VALUES
('Male', 1), ('Female', 1), ('Other', 0);

-- The starting location that players choose when creating avatars.
CREATE TABLE birthplaces (
	id int PRIMARY KEY AUTO_INCREMENT,
	map_id int NULL,
	FOREIGN KEY (map_id) REFERENCES maps(id) ON DELETE CASCADE,
	x int NULL,
	y int NULL,
	name varchar(120),
	description varchar(2000),
	created_at datetime,
	updated_at datetime	
);

-- Player location, status, etc. Each user can have multiple players.
CREATE TABLE players (
	id int PRIMARY KEY AUTO_INCREMENT,
	user_id int NOT NULL,
	FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
	name varchar(128) NOT NULL UNIQUE, -- Player name must be unique throughought the game
	birthplace_id INT NOT NULL,
	FOREIGN KEY (birthplace_id) REFERENCES birthplaces(id),
	visible bit NOT NULL DEFAULT 0, -- Determines if they are actively playing and can be seen by others
	map_id int NOT NULL, -- player's current map
	FOREIGN KEY (map_id) REFERENCES maps(id),
	x int NOT NULL,
	y int NOT NULL,
	last_action_time datetime NOT NULL, -- Time player last moved. Used to determine visibility
	map_enter_time datetime NULL, -- When the player teleported to their current map. Used for chat messaging.
	health int NOT NULL DEFAULT 100,
	health_max int NOT NULL DEFAULT 100,
	money int NULL DEFAULT 0, -- Players usually start out with 100 MEGAbucks
	experience int NOT NULL DEFAULT 0,
	repute_radius int NOT NULL DEFAULT 0, -- unused
	movement int NOT NULL DEFAULT 99, -- limits how much a player can move
	travel int NULL, -- unused
	attack int NOT NULL DEFAULT 10, -- unused
	defence int NOT NULL DEFAULT 10, -- unused
	last_retrieval_time int NULL, -- unused
	bonus_point int NULL default 0, -- Players are given a bonus point each time they level up in a profession
	race_id int NOT NULL,
	FOREIGN KEY (race_id) REFERENCES races(id),
	gender_id int NOT NULL,
	FOREIGN KEY (gender_id) REFERENCES genders(id),
	is_super bit DEFAULT 0, -- Unused, could give special abilities for administrators.
	created_at datetime,
	updated_at datetime	
);

-- Guardian id, created at, edited at, gender, race, colour 
CREATE TABLE guardians (
	id int PRIMARY KEY AUTO_INCREMENT,
	race_id int NOT NULL,
	FOREIGN KEY (race_id) REFERENCES races(id),
	gender_id int NOT NULL,
	FOREIGN KEY (gender_id) REFERENCES genders(id),
	color varchar(200) NOT NULL
);

-- Player's starting attributes
CREATE TABLE player_base_attributes (
	id int PRIMARY KEY AUTO_INCREMENT,
	player_id INT NOT NULL,
	FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
	attribute_id int NOT NULL,
	FOREIGN KEY (attribute_id) REFERENCES stats(id),
	attribute_value double NOT NULL,
	UNIQUE (player_id, attribute_id)
);

-- Current stats for players. Computed initially with stat_calculation_rules via player_stats_initial_view
CREATE TABLE player_stats (
	id int PRIMARY KEY AUTO_INCREMENT,
	player_id INT NOT NULL,
	FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
	stat_id int NOT NULL,
	FOREIGN KEY (stat_id) REFERENCES stats(id),
	stat_value double NOT NULL,
	UNIQUE (player_id, stat_id)
);

-- Skills a player has learned (eg swimming) allowing traversal of some tile types.
CREATE TABLE player_skills (
	id int PRIMARY KEY AUTO_INCREMENT,
	player_id int NOT NULL,
	FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
	skill_id int NOT NULL,
	FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE,
	UNIQUE (player_id, skill_id),
	created_at datetime,
	updated_at datetime
);

-- Keeps track of the professions a player has and the experience and level for each.
CREATE TABLE player_professions (
	id int PRIMARY KEY AUTO_INCREMENT,
	player_id int NOT NULL,
	FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
	profession_id int NOT NULL,
	FOREIGN KEY (profession_id) REFERENCES professions(id) ON DELETE CASCADE,
	profession_xp int NOT NULL,
	profession_level int NOT NULL,
	UNIQUE (player_id, profession_id),
	created_at datetime,
	updated_at datetime	
);

-- There can be multiple bags, but the first, default bag is the one each player uses primarily.
-- This is a table to support a future feature where a player may have multiple bags.
CREATE TABLE bags (
	id int PRIMARY KEY AUTO_INCREMENT,
	name varchar(50) NOT NULL,
	description varchar(2000) NULL, -- Description of purpose of bag could go here
	created_at datetime,
	updated_at datetime	
);

INSERT INTO bags (name, description) VALUES
('Main Bag', 'The default bag each player uses.');

-- bag_slots(slot_id) is a primary key that other tables refer to without needing bag_id
CREATE TABLE bag_slots (
	id int PRIMARY KEY AUTO_INCREMENT,
	bag_id INT NOT NULL,
	FOREIGN KEY (bag_id) REFERENCES bags(id),
	slot_seq int NOT NULL, -- User-friendly id, can be used to resequence bag slots within a bag.
	UNIQUE (bag_id, slot_seq)
);

-- There are 20 bag slots, all assigned to bag 1. These are the player's slots
INSERT INTO bag_slots (id, bag_id, slot_seq) VALUES
(1, 1, 1), (2, 1, 2), (3, 1, 3), (4, 1, 4), (5, 1, 5),
(6, 1, 6), (7, 1, 7), (8, 1, 8), (9, 1, 9), (10, 1, 10),
(11, 1, 11), (12, 1, 12), (13, 1, 13), (14, 1, 14), (15, 1, 15),
(16, 1, 16), (17, 1, 17), (18, 1, 18), (19, 1, 19), (20, 1, 20);

-- Non player character information. NPCs gives quests and sell items.
CREATE TABLE npcs (
	id int PRIMARY KEY AUTO_INCREMENT,
	name varchar(200) NOT NULL,
	map_id int NULL,
	FOREIGN KEY (map_id) REFERENCES maps(id),	
	-- An NPC lives within a rectangular region. The bigger the square, the harder it is to find the NPC.
	y_top int NOT NULL,
	x_right int NOT NULL,
	y_bottom int NOT NULL,
	x_left int NOT NULL,
	level int NOT NULL,
	-- Acts as a toggle to disable all portals assigned to the NPC.
	-- portal_keeper bit NOT NULL,
	-- Acts as a toggle to disable any professions assigned to the NPC.
	-- trainer bit NOT NULL,
	-- The above two bits are now determined programmatically and are unused.
	npc_icon_id int NULL, -- NPC image
	FOREIGN KEY (npc_icon_id) REFERENCES npc_icons(id),
	created_at datetime NULL,
	updated_at datetime NULL
);

-- NPCs may train a player in one or more professions. Those are listed here.
CREATE TABLE npc_professions (
	id int PRIMARY KEY AUTO_INCREMENT, -- needed for Eloquent, real key is composite npc_id/profession_id
	npc_id int NOT NULL,
	FOREIGN KEY (npc_id) REFERENCES npcs(id) ON DELETE CASCADE,
	profession_id int NOT NULL,
	FOREIGN KEY (profession_id) REFERENCES professions(id) ON DELETE CASCADE,
	created_at datetime NULL,
	updated_at datetime NULL,
	UNIQUE (npc_id, profession_id)
);

-- Some NPCs have the ability to transport players. 
CREATE TABLE npc_portals (
	id int PRIMARY KEY AUTO_INCREMENT,
	npc_id int NOT NULL,
	FOREIGN KEY (npc_id) REFERENCES npcs(id) ON DELETE CASCADE,
	dest_map_id int NOT NULL,
	FOREIGN KEY (dest_map_id) REFERENCES maps(id) ON DELETE CASCADE,
	dest_x int NOT NULL,
	dest_y int NOT NULL,
	price int NOT NULL,
	name varchar(2000),
	level int NOT NULL,
	created_at datetime NULL,
	updated_at datetime NULL
);

/**
 * Avatar Tables
 */

-- The different categories of avatar parts that users may select for their players.
CREATE TABLE avatar_image_types (
	id int PRIMARY KEY AUTO_INCREMENT,
	name varchar(200) NOT NULL,
	layer_index int NULL, -- determines the stacking order in HTML rendering or SVG compositing
	display_seq int NULL, -- the order in which items are displayed in the avatar editor
	disabled bit NOT NULL DEFAULT 0, -- Could be used to disable types of parts we no longer want to use
	created_at datetime NULL DEFAULT now(),
	updated_at datetime NULL
);

/**
 * List of all unique avatar part shapes, each refers to an SVG file.
 * Avatar parts are (so far) specific to a race and gender.
 * Each part may have zero, one, two, or three colors that the player can configure.
 */ 
CREATE TABLE avatar_images (
	id int PRIMARY KEY AUTO_INCREMENT,
	name varchar(255) NOT NULL,
	svg_code text NULL, -- if there is no filename, SVG partials can be added right in here
	-- and they will be composited in the HTML as a single SVG	
	filename varchar(200) NULL, -- References an SVG file. Use lowercase with underscores and extension.
	avatar_image_type_id INT NOT NULL,
	FOREIGN KEY (avatar_image_type_id) REFERENCES avatar_image_types(id),
	gender_id int NULL, -- for avatar components that apply to specific genders
	FOREIGN KEY (gender_id) REFERENCES genders(id),
	race_id int NULL,
	FOREIGN KEY (race_id) REFERENCES races(id),
	color_qty INT DEFAULT 0, -- Some avatar types may have secondary or tertiary accent colors.
	disabled bit DEFAULT 0,
	-- The UI will typically check for the existence of "primary", "secondary" and "tertiary" classes
	-- To render this, but this can be used as a check or fallback
	created_at datetime NULL,
	updated_at datetime NULL
);

/* Avatar colors to be shown in the UI for each type of avatar part.
 * This is used to give the player a reasonable palette of colors to use for each part
 */
CREATE TABLE avatar_image_type_colors (
	id int PRIMARY KEY AUTO_INCREMENT,
	avatar_image_type_id INT,
	FOREIGN KEY (avatar_image_type_id) REFERENCES avatar_image_types(id) ON DELETE CASCADE,
	css_color varchar(200) NOT NULL, -- The CSS value used for the color.
	-- Could be transparent, typically will use #112233 style colors.
	-- 	race_id INT NULL, -- Use if a color only applies to a specific race. Probably won't use this.	
	display_seq INT NULL, -- Used to order similar colors together in the UI.
	created_at datetime NULL,
	updated_at datetime NULL
);

/**
 * Track which parts each player is using and what colors they have selected for each part
 */
CREATE TABLE player_avatar_images (
	player_id INT NOT NULL,
	FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
	avatar_image_id INT NOT NULL,
	FOREIGN KEY (avatar_image_id) REFERENCES avatar_images(id) ON DELETE CASCADE,
	-- These won't refer to the avatar_image_type_colors but will actually contain the CSS value
	-- In theory you could give the player infinite customization.
	color1 varchar(200) NULL,
	color2 varchar(200) NULL,
	color3 varchar(200) NULL, -- only used in human male shirts
	color4 varchar(200) NULL, -- reserved for future use
	created_at datetime NULL,
	updated_at datetime NULL,	
	PRIMARY KEY (player_id, avatar_image_id)
);

/**
 * Table for linking players with their guardians.
 */

CREATE TABLE guardian_player_relations (
	player_id INT NOT NULL,
	guardian_id INT NOT NULL,
	created_at datetime NULL,
	updated_at datetime NULL,
	PRIMARY KEY (player_id, guardian_id)
);

/**
 * Track which parts each player is using and what colors they have selected for each part
 */
CREATE TABLE guardian_avatar_images (
	guardian_id INT NOT NULL,
	avatar_image_id INT NOT NULL,
	FOREIGN KEY (avatar_image_id) REFERENCES avatar_images(id) ON DELETE CASCADE,
	-- These won't refer to the avatar_image_type_colors but will actually contain the CSS value
	-- In theory you could give the player infinite customization.
	color1 varchar(200) NULL,
	color2 varchar(200) NULL,
	color3 varchar(200) NULL, -- only used in human male shirts
	color4 varchar(200) NULL, -- reserved for future use
	created_at datetime NULL,
	updated_at datetime NULL,	
	PRIMARY KEY (guardian_id,avatar_image_id)
);

/**
 * Item Tables
 * These are for items that players can obtain and carry, they may be related to quests
 */

-- Used primarily for types of avatar parts. This table will be obsolete with the new avatar
-- system, but could be used for categorizing items, even if just for management.
-- The existing entries are probably all useless.
CREATE TABLE item_categories (
	id int PRIMARY KEY AUTO_INCREMENT,
	name varchar(180) NOT NULL UNIQUE,
	disabled bit NOT NULL DEFAULT 0, -- arms, hands, ears, etc get 1. Disabled from interface while being worked on
	-- layer_index int NULL, -- a third of categories have a layer index -- for avatar items
	display_seq int NULL, -- the order in which items are displayed
	created_at datetime NULL,
	updated_at datetime NULL
);

INSERT INTO item_categories (name, display_seq) VALUES
('Food', 10),
('Quest', 20),
('Tool', 30);

-- Insert a list of item categories. Some of these can be determined through other metadata.
/*
- quest items
- food
- tools (basically all have dig as the effect)
- None of the above (rewards for quests and jewelry?)
*/

-- Some items have actions able to be performed on them which produce an effect. This lists those effects.
CREATE TABLE item_effects (
	id int PRIMARY KEY,
	name varchar(100) UNIQUE,
	description varchar(2000),
	effect_parameters_description varchar(2000),
	-- Documents how parameters are used and how they must be stored in the items(item_parameters) field
	created_at datetime NULL,
	updated_at datetime NULL
);

INSERT INTO item_effects (id, name, description, effect_parameters_description) VALUES
(1, 'eat', 'Consumes food items converting into energy/movement units.', 'Integer. Each unit is worth 100 movement points.'),
(2, 'quest_activate', 'Gives the player a new quest', 'Integer. Foreign key references quests(quest_id)'),
(3, 'dig', 'Can reveal something hidden at a particular location.', 'Unused.');

-- Catalogs all items used in megaworld.
-- The item IDs are weird-looking because the digits previously had significance like gender, race, category, etc.
-- Negative numbers meant disabled. Now there are *far* more intelligible fields for these properties
-- so the items could be renumbered to an orderly sequence.
CREATE TABLE items (
	id int PRIMARY KEY AUTO_INCREMENT, -- The item_id *was* a meaningful number derived from the properties of the item.
	name varchar(200) NULL,
	item_category_id int NULL,
	FOREIGN KEY (item_category_id) REFERENCES item_categories(id),
	item_icon_id int NULL,
	FOREIGN KEY (item_icon_id) REFERENCES item_icons(id),	
	amount int NULL, -- Unused. Almost always 1, a handful of items set it to 0.
	description varchar(10000) NULL, -- Formerly used NONE when there was no description
	item_effect_id int NULL,
	FOREIGN KEY (item_effect_id) REFERENCES item_effects(id),
	effect_parameters varchar(2000) NULL, -- multiplier of effect, quest id to start, etc. Multiple parameters could be here.
	weight int NULL, -- Weight in grams
	author varchar(200) NULL,
	parameters varchar(2000), -- Intended for visual effect parameters.
	-- for instance, the parameters of "gear-on.jsp?visual_effect=fire&activate=hit" is "visual_effect=fire&activate=hit"):
	required_level int NULL, -- Player must have this level to use the item
	level int, -- This is an indicator of the quality of the item, not necessarily related to item_required_level
	hand varchar(100), -- Unused - left or right, typically
	bound varchar(200) NULL, -- Only ever None, No Bind, NULL. Show up in player_item_buy, player_item_get.
	price int NOT NULL, -- The price the item may be purchased for from an NPC
	max_amount int NOT NULL, -- The maximum of an item that can be stacked in an inventory slot
	disabled bit, -- formerly nouse, seems to be for legacy avatar parts
	old_id INT UNIQUE, -- The previous ID that had many attributes encoded into it. Kept here for migration.
	created_at datetime NULL,
	updated_at datetime NULL
);


-- Used for item calculations and for question tool location coordinates
-- selected from the specified range each time the item is received by a player.
CREATE TABLE item_variables (
	id INT PRIMARY KEY AUTO_INCREMENT,
	item_id int NOT NULL,
	FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
	var_name varchar(100) NOT NULL, -- typically a, b, c, or x, y, z. Allow for longer names, though
	min_value decimal(10,2) NOT NULL,
	max_value decimal(10,2) NOT NULL,
	allow_decimal bit DEFAULT 0, -- We may set this to 1 if we want to use non-integer numbers in the future.
	created_at datetime NULL,
	updated_at datetime NULL,
	UNIQUE (item_id, var_name)
);


-- Items could have an effect on player stats, but this is not used yet
/*
CREATE TABLE item_stats (
	item_id int NOT NULL,
	FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
	stat_id int NOT NULL,
	FOREIGN KEY (stat_id) REFERENCES stats(id),
	stat_value double NOT NULL
);
*/

/**
 * Quest tables
 */

-- Speficies the type quiz or answer process that applies to quests.
-- The bit fields are used for the quest editor
CREATE TABLE quest_result_types (
	id int PRIMARY KEY AUTO_INCREMENT,
	name varchar(200) NOT NULL,
	-- These bits control visibility of fields in Management.
	uses_items 		bit DEFAULT 0 NOT NULL,
	uses_bool 		bit DEFAULT 0 NOT NULL,
	uses_string 	bit DEFAULT 0 NOT NULL,
	uses_answers 	bit DEFAULT 0 NOT NULL,
	uses_variables  bit DEFAULT 0 NOT NULL,
	uses_questions  bit DEFAULT 0 NOT NULL,
	uses_automark 	bit DEFAULT 0 NOT NULL,
	-- Could disable types that are redundant or unused.
	disabled bit DEFAULT 0 NOT NULL
);

-- Information about all the quests, including NPC dialog
CREATE TABLE quests (
	id int PRIMARY KEY AUTO_INCREMENT,
	name varchar(200),
	-- is_quest_chain bit NOT NULL, -- Seems fairly useless to me, not sure how this would be used.
	repeatable bit NOT NULL, -- Quest can be completed more than once
	repeatable_cd_in_minute int NOT NULL, -- Cooldown time before which a completed quest cannot be repeated
	-- opportunity int DEFAULT 100, -- Unused, could specify probability of quest being available.
	level int NOT NULL,
	-- pickup_limitation bit NOT NULL, -- This flags that the quest should check that a player has specified professions
	-- could be removed and determined by checking quest_prerequisite_professions DB relationship
	-- general_quest bit NOT NULL, -- No idea what this is for. Marked for removal
	giver_npc_id int NULL, -- NPC who gives the quest to the player
	FOREIGN KEY (giver_npc_id) REFERENCES npcs(id),
	target_npc_id int NULL, -- NPC with whom the player can complete the quest
	FOREIGN KEY (target_npc_id) REFERENCES npcs(id),
	prologue text, -- Conversation with player
	content text, -- Information about and extended description of the quest
	target_npc_prologue text,
	npc_has_quest_item bit NOT NULL,
	-- amounts are now moved to the quest_items table
	-- min_quest_item_amount int NOT NULL,
	-- max_quest_item_amount int NOT NULL,
	quest_result_type_id int NOT NULL,
	old_result_type_id int NULL, -- Used to keep track of what it was before I changed it. Can be deleted after cleanup.
	FOREIGN KEY (quest_result_type_id) REFERENCES quest_result_types(id),
	success_words text NULL,
	failure_words text NULL,
	cd_in_minute int NOT NULL, -- Cooldown minutes before trying again after failing
	result varchar(2000) NULL, -- This field will only be used during migration and is not used by MWv3
	randomize_options bit NULL DEFAULT 0, -- If true, options for a result quiz will be displayed in a random order
	-- rather than the order specified by the creator of the quest.
	result_map_id int NULL,
	FOREIGN KEY (result_map_id) REFERENCES maps(id),
	-- Quest result type 13 uses coordinates, but the coordinates may vary by player, or use a formula.
	-- Therefore, result_x and resuly_y are varchar so they can be more complex than just integers.
	result_x varchar(200) NULL,
	result_y varchar(200) NULL,
	base_reward_automark_percentage int NOT NULL DEFAULT 0, -- Used for text-based short answer questions.
	-- The minimum "score" required from the automark API
	-- to be assigned by the automarking API to consider a correct answer.
	-- If the user is below this threshold, they get a partial reward.
	-- eg if base_reward_percentage is 60 and player gets 50% they get 50%
	-- of the reward money, xp, profession_xp, etc.
	-- If player gets 60% or more, they complete the quest and get the full reward.
	reward_profession_xp int NULL,
	reward_xp int NOT NULL, -- there's only one null - quest 547
	reward_money int NOT NULL, -- there's only one null - quest 547
	created_at datetime NULL,
	updated_at datetime NULL
);

-- Some quests require a player to have one or more professions
CREATE TABLE quest_prerequisite_professions (
	id int PRIMARY KEY AUTO_INCREMENT,
	quest_id INT NOT NULL,
	FOREIGN KEY (quest_id) REFERENCES quests(id) ON DELETE CASCADE,
	profession_id INT NOT NULL,
	FOREIGN KEY (profession_id) REFERENCES professions(id) ON DELETE CASCADE,
	-- The below is more flexible but is a lot harder to write a query for, so I'll keep it the old way
	-- min_profession_level INT NOT NULL,  -- The player has to have the profession at this level to qualify for the quest
	created_at datetime NULL,
	updated_at datetime NULL,	
	UNIQUE (quest_id, profession_id)
);

-- Specifies quests that must be completed before the player is offered a quest
CREATE TABLE quest_prerequisite_quests (
	id int PRIMARY KEY AUTO_INCREMENT,
	quest_id int NOT NULL,
	FOREIGN KEY (quest_id) REFERENCES quests(id) ON DELETE CASCADE,
	prerequisite_quest_id int NOT NULL, -- Long name, maybe this is a bit much. Formerly prequest_id
	FOREIGN KEY (prerequisite_quest_id) REFERENCES quests(id) ON DELETE CASCADE,
	quest_count int DEFAULT 1,-- renamed from count, this is only ever 1, could probably be dropped
	created_at datetime NULL,
	updated_at datetime NULL,	
	UNIQUE (quest_id, prerequisite_quest_id)
);

-- Used for quest result types that have variable numeric answers. A value will be
-- selected from the specified range each time the quest is accepted by a player. 
CREATE TABLE quest_variables (
	id INT PRIMARY KEY AUTO_INCREMENT,
	quest_id int NOT NULL,
	FOREIGN KEY (quest_id) REFERENCES quests(id) ON DELETE CASCADE,
	var_name varchar(100) NOT NULL, -- typically a, b, c, or x, y, z. Allow for longer names, though
	min_value decimal(10,2) NOT NULL,
	max_value decimal(10,2) NOT NULL,
	allow_decimal bit DEFAULT 0, -- We may set this to 1 if we want to use non-integer numbers in the future.
	created_at datetime NULL,
	updated_at datetime NULL,
	UNIQUE (quest_id, var_name)
);

-- Values selected from the range specified in quest_variables will be inserted here when a player accepts a quest.
-- These values are used in determining the correct response for a particular quest_answer.
-- This way answers are different each time a player picks them.
-- Originally these ranges were specified in in quest content like ^##a===5,9##^)
CREATE TABLE player_quest_variables (
	player_id INT NOT NULL,
	FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
	quest_id INT NOT NULL,
	FOREIGN KEY (quest_id) REFERENCES quests(id) ON DELETE CASCADE,
	-- Originally varname, varvalue
	var_name varchar(200) NOT NULL, -- Could possibly use the quest_variables(id), but this may be less user-friendly to specify.
	var_value double NOT NULL, -- originally this was varchar but seems to only store decimal numbers
	created_at datetime NULL,
	updated_at datetime NULL
);

-- Used for quest result types that have blanks needing to be filled with values, eg Cloze.
CREATE TABLE quest_answers (
	id INT PRIMARY KEY AUTO_INCREMENT,
	quest_id int NOT NULL,
	FOREIGN KEY (quest_id) REFERENCES quests(id) ON DELETE CASCADE,
	name varchar(100), -- typically A, B, C, or X, Y, Z. Allow for longer names, though
	question text NULL, -- Optional field where a question may be shown if one is filled in.
	answer_string varchar(1000), -- The correct answer. Is "value" okay in MySQL? seems to be a reserved word.
	correct_bool bit NOT NULL DEFAULT 1, -- Correct answer used when quest result type is 15 (True/False)
	created_at datetime NULL,
	updated_at datetime NULL,
	UNIQUE (quest_id, name)
);

-- Items that are used in the quests (eg answers to multiple choice questions).
-- Each record may contain a value used to as a correct answer for a question.
-- Which value is used will depend on the quest_result_type
CREATE TABLE quest_items (
	id int PRIMARY KEY AUTO_INCREMENT,
	quest_id int NOT NULL,
	FOREIGN KEY (quest_id) REFERENCES quests(id) ON DELETE CASCADE,
	item_id int NOT NULL,
	FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
	item_amount int NOT NULL DEFAULT 1, -- The minimum quantity required. Rarely, if ever, used.
	-- max_item_amount int NOT NULL, -- This is not likely to be very useful, imo.
	answer_bool bit NOT NULL DEFAULT 1, -- Used for select, T/F, MC, (boolean results)
	-- I'm forcing this to have a value because it becomes problematic when this is null and you're switching result_types in the editor
	answer_seq int NOT NULL DEFAULT 1, -- Used for qrt 5: Order items
	answer_string varchar(1000) NULL, -- Used for calculation, text answer
	-- Maybe calculation could be int or decimal... but what if decimals are used and such?
	-- I think it should just be treated like a short text answer, but it could be parsed in a way to allow some flexibility (like rounding the value)
	created_at datetime NULL,
	updated_at datetime NULL,	
	UNIQUE (quest_id, item_id)
);

-- Items rewarded for the successful completion of a quest
CREATE TABLE quest_reward_items (
	id int PRIMARY KEY AUTO_INCREMENT,
	quest_id int NOT NULL,
	FOREIGN KEY (quest_id) REFERENCES quests(id) ON DELETE CASCADE,
	item_id int NOT NULL,
	FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
	item_amount int NOT NULL,
	created_at datetime NULL,
	updated_at datetime NULL,	
	UNIQUE (quest_id, item_id)
);

-- A quest can have more than one 'tool' item. These have other items (typically quest_items) associaited with them
-- that can be found using these tools at particular coordinates.
CREATE TABLE quest_tools (
	id int PRIMARY KEY AUTO_INCREMENT,
	item_id int NOT NULL,
	FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
	quest_id int NOT NULL,
	FOREIGN KEY (quest_id) REFERENCES quests(id) ON DELETE CASCADE,
	item_amount int NOT NULL DEFAULT 1,
	created_at datetime NULL,
	updated_at datetime NULL,	
	UNIQUE (item_id, quest_id)
);

-- This shows the locations quest tools can be successfully used at and specifies the results of using the tools
-- These values are used primarily by quest_result_type 14
CREATE TABLE quest_tool_locations (
	id int PRIMARY KEY AUTO_INCREMENT,
	quest_tool_id int NOT NULL,
	FOREIGN KEY (quest_tool_id) REFERENCES quest_tools(id) ON DELETE CASCADE,
	item_id int NULL, -- a quest tool can optionally find an item
	FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
	-- Each of these has an assocaited location
	map_id int NULL,
	FOREIGN KEY (map_id) REFERENCES maps(id) ON DELETE CASCADE,
	x varchar(200), -- x and y could be equations with ranges, not necessarily a specific int
	y varchar(200),
	item_amount int NULL, -- quantity can be more than one
	success_message varchar(1000), -- Message to be displayed if the tool was used at the this location. Not yet sure if I will use this.
	-- I was thinking that a message could be the reward instead of an item.
	-- Am considering a "quest_complete" bit that would trigger quest completion when found.
	quest_complete bit NOT NULL DEFAULT 0, -- could trigger quest completion when found.
	created_at datetime NULL,
	updated_at datetime NULL	
);

-- Items an NPC has that a player can buy or that are given for quest purposes.
CREATE TABLE npc_items (
	id int PRIMARY KEY AUTO_INCREMENT,
	npc_id int NOT NULL,
	FOREIGN KEY (npc_id) REFERENCES npcs(id) ON DELETE CASCADE,	
	quest_id int NULL, -- null if the item is available for sale outside of quests
	FOREIGN KEY (quest_id) REFERENCES quests(id) ON DELETE CASCADE,
	item_id int NOT NULL,
	FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
	created_at datetime NULL,
	updated_at datetime NULL,	
	UNIQUE (quest_id, npc_id, item_id)
	-- can't be primary key as some fields can be NULL
);

-- Keeps track of active and completed quests. If a player restarts a quest, as some can,
-- the old record is deleted and a new one is created (or an update could happen, I suppose)
CREATE TABLE player_quests (
	id int PRIMARY KEY AUTO_INCREMENT,
	player_id int NOT NULL,
	FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
	quest_id int NOT NULL,
	FOREIGN KEY (quest_id) REFERENCES quests(id) ON DELETE CASCADE,
	pickup_time datetime NOT NULL,
	success_time datetime NULL, -- This records the time a player SUCCESSFULLY completed the quest
	-- A player only has one record for each quest, even if they completed it multiple times.
	-- The full history is in the player_quests_log table.
	created_at datetime NULL,
	updated_at datetime NULL,	
	UNIQUE (player_id, quest_id)
);

-- Events that are logged for each quest - player picking up, dropping, completing, or failing quests
CREATE TABLE quest_events (
	id int PRIMARY KEY AUTO_INCREMENT,
	name varchar(50) NOT NULL
);

INSERT INTO quest_events (name) VALUES
('Success'), ('Failed'), ('Drop'), ('Pick up');

-- Foreign key constraints are not used on this as we want to keep the log for
-- historical record keeping reasons, even if a player, quest, or map is deleted.
CREATE TABLE player_quests_log (
	player_id int NOT NULL,
	-- FOREIGN KEY (player_id) REFERENCES players(player_id),
	quest_id int NOT NULL,
	-- FOREIGN KEY (quest_id) REFERENCES quests(quest_id),
	event_datetime DATETIME NOT NULL,
	quest_event_id int NOT NULL,
	FOREIGN KEY (quest_event_id) REFERENCES quest_events(id),
	npc_id int NULL,
	-- I don't understand the point of recording this, when would it not be the giver_npc?
	-- FOREIGN KEY (npc_id) REFERENCES npcs(npc_id),
	map_id int NOT NULL,
	-- FOREIGN KEY (map_id) REFERENCES maps(map_id),
	x int NOT NULL,
	y int NOT NULL,
	ratio double NULL, -- ratio of right to wrong answers?
	-- Seems like an imprecise way to store this info, but it's probably close enough.
	quest_result varchar(2000) NULL -- this is from the quest table
	-- I suspect we store the full answer here in case the original table changes
	-- (perhaps there was a typo/wrong answer and we want to record that for posterity)
);


-- The items each player has in inventory (if there's a slot_id), or potentially equipped (not implemented yet)
-- This table must go later on as it references players, items, quests, and bag_slots
CREATE TABLE player_items (
	id INT PRIMARY KEY AUTO_INCREMENT, -- This is not used right now, but may be faster than using GUID as PK
	guid varchar(32) NOT NULL UNIQUE, -- some obfuscation for security.
	-- item_guid is generated from a hash of timestamp and other things to make an "unguessable" id for each item instance.
	-- I believe that this approach is unnecessary with v3 as there's no way a user can, for instance, just pass
	-- something in a URL parameter to get free items or steal another player's inventory or such
	-- since there is strict checking on all item operations on the server-side.
	-- Nonetheless, this is used as the key by which PlayerItem objects are instantiated in v3.
	player_id int NOT NULL,
	FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
	item_id int NOT NULL,
	FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
	quest_id int NULL, -- This makes it easy to determine which quest this item is for, helps with cleanup
	-- If quest_id is defined, the item will be deleted as soon as the quest is complete.
	FOREIGN KEY (quest_id) REFERENCES quests(id) ON DELETE CASCADE,	
	is_equipped bit NOT NULL, -- Denotes this item is an avatar part
	bag_slot_id INT NULL, -- For inventory items in a player's bag.
	FOREIGN KEY (bag_slot_id) REFERENCES bag_slots(id),
	-- item_type INT NULL, -- Can indicate item is for a quest
	-- Would a nullable quest_id field make sense here to see that items are specifically acquired due to a quest?
	-- Not sure it matters much which quest it's for
	bag_slot_amount INT NOT NULL DEFAULT 1,
	-- A player can only have one item in a slot
	-- but the quantity of that item might be up to items(max_amount)
	created_at datetime NULL,
	updated_at datetime NULL,
	UNIQUE(player_id, bag_slot_id)
);

-- Values selected from the range specified in quest_variables will be inserted here when a player accepts a quest.
-- These values are used in determining the correct response for a particular quest_answer.
-- This way answers are different each time a player picks them.
-- Originally these ranges were specified in in item descriptions like ^##a===5,9##^
-- These reference player_item_ids because each instance of an item could have separate variables.
CREATE TABLE player_item_variables (
	player_id INT NOT NULL,
	FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
	player_item_id INT NOT NULL,
	FOREIGN KEY (player_item_id) REFERENCES player_items(id) ON DELETE CASCADE,
	var_name varchar(40),
	var_value double NOT NULL,
	created_at datetime NULL,
	updated_at datetime NULL,
	UNIQUE (player_id, player_item_id, var_name)
);


/** 
 * Logging and analytics
 */

-- Messages sent to other players through the chat interface. These may have limited range
-- so public messages can only be seen by nearby players.
-- Note that we aren't using foreign key constraints here so we can keep a history of messages
-- even after the player is gone.
CREATE TABLE chat_log (
	id int PRIMARY KEY AUTO_INCREMENT,
	player_id_src int NULL, -- The player a message comes from
	-- FOREIGN KEY (player_id_src) REFERENCES players(player_id),
	player_id_tgt int NULL, -- player to whom the message is directed. NULL if broadcast to the rest of the map
	-- FOREIGN KEY (player_id_tgt) REFERENCES players(player_id),
	message_time datetime NOT NULL,
	message varchar(10000) NOT NULL,
	-- Location is important because messages are only seen by those within a certain range
	map_id int NULL,
	-- FOREIGN KEY (map_id) REFERENCES maps(map_id),
	x int NULL,
	y int NULL
);

-- Messages sent by the player to guardian.
CREATE TABLE guardian_log (
	id int PRIMARY KEY AUTO_INCREMENT,
	player_id int NULL, -- The player id
	guardian_id int NULL, -- the guardian id
	-- FOREIGN KEY (player_id_src) REFERENCES players(player_id)
	message_time datetime NOT NULL,
	direction int NULL, -- direction of message
	confirmation varchar(16) NOT NULL, -- confirmation of message
	query_status int NULL, -- status of message
	message varchar(10000) NOT NULL
);

-- log the status check for answer summary
CREATE TABLE guardian_log_answer (
	player_id int NULL, -- The player id
	query_time datetime NOT NULL,
	confirmation varchar(16) NOT NULL -- confirmation of message
)

-- Types of actions that are logged in player_actions_log
CREATE TABLE player_action_types (
	id int PRIMARY KEY AUTO_INCREMENT,
	name varchar(200) NOT NULL,
	description varchar(2000) NULL
);

-- This table tracks every action for every player and can quickly grow large.
-- Formerly wandering_behaviour
-- We don't use a FK constraints so we can keep the records for players after they are deleted.
CREATE TABLE player_actions_log (
	id int PRIMARY KEY AUTO_INCREMENT,
	user_id int NOT NULL, -- Now that users and players are separate, we store these separately
	player_id int NULL,
	-- FOREIGN KEY (player_id) REFERENCES players(player_id),
	map_id int NULL,
	x int NULL,
	y int NULL,
	event_time datetime(3) NOT NULL, -- More precision
	player_action_type_id INT NULL, -- An unknown action could fall back to NULL.
	FOREIGN KEY (player_action_type_id) REFERENCES player_action_types(id),
	note varchar(200) NULL -- Could be used to add notes like npc_id, building_id, etc.
);

-- Keeps track of keypresses in chat and answering some quest_result_types
CREATE TABLE key_log (
	id int PRIMARY KEY AUTO_INCREMENT,
	player_id int NOT NULL,
	-- FOREIGN KEY (player_id) REFERENCES players(id),
	quest_id int NULL,
	-- FOREIGN KEY (quest_id) REFERENCES quests(id),
	item_id int NULL, -- for quests that involve items and accept long answers
	-- FOREIGN KEY (item_id) REFERENCES items(id),
	quest_answer_id int NULL, -- for quests that require text input
	-- FOREIGN KEY (quest_answer_id) REFERENCES quest_answers(id),	
	to_player_id int NULL, -- Can refer to the ID of another player
	-- FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
	map_id int,
	-- FOREIGN KEY (map_id) REFERENCES maps(id),
	x int,
	y int,
	keycode int,
	down_time datetime(3),
	up_time datetime(3)
	-- duration int -- This can be calculated and presented in a view
);

CREATE TABLE money_flow_log (
	player_id int NOT NULL,
	-- FOREIGN KEY (player_id) REFERENCES players(id),
	npc_id int NULL,
	-- FOREIGN KEY (npc_id) REFERENCES npcs(id),
	payout int NOT NULL, -- Can be negative, sometimes extremely so (to take *all* a player's money?)
	reserve int NOT NULL,
	payout_datetime datetime NOT NULL,
	quest_id int NULL -- not typically relevant to a quest if a player buys an item
	-- FOREIGN KEY (quest_id) REFERENCES quests(id)
);


-- Used for language selection
CREATE TABLE languages(
	id int PRIMARY KEY AUTO_INCREMENT,
	name varchar(255) NOT NULL,
	native_name varchar(255),
	country varchar(255),
	locale_id varchar(20) NOT NULL UNIQUE,
	supported bit NOT NULL DEFAULT 0
);



/**
 * Unused tables. Could leave them out to keep the DB a bit simpler, they can be added when needed.

-- Unused, only had four entries in it
CREATE TABLE quest_chain_type (
	id int,
	sequence_number int,
	quest_type_id int,
	FOREIGN KEY (quest_type_id) REFERENCES quest_types(id)
);


-- This table is unused, it was intended for a certain type of quest where an area has to be protected
CREATE TABLE protect_detail (
	quest_id int NOT NULL,
	FOREIGN KEY (quest_id) REFERENCES quests(id),
	map_id int NOT NULL,
	FOREIGN KEY (map_id) REFERENCES maps(id),
	building_id int NULL, -- seems this is usually 0
	FOREIGN KEY (building_id) REFERENCES buildings(id),
	-- CSS order
	y_top int,
	x_right int,
	y_bottom int,
	x_left int,
	arrival_time int, -- originally int, looks like this is supposed to store a time? It's only 0 here, though
	protect_time int, -- is this actually a timestamp or an interval of time in ms or something?
	punish_xp int,
	punish_currency int
	-- This had a map_name and map_name_id... isn't this already conveyed via the map_id?
);
*/


/**
 * Views
 * The mega database had 10 views. If they were nontrivial and are needed, they are created here.
 */


-- Used to determine when a player achieves the next profession level
CREATE VIEW profession_leveling_view AS (
	SELECT sum(q.reward_profession_xp) * 0.9 AS divide_xp,
	    sum(q.reward_profession_xp) AS reward_xp,
	    q.level, pl.profession_id, p.name
	   FROM quests q
	   JOIN quest_prerequisite_professions pl ON pl.quest_id=q.id
	   JOIN professions p ON p.id=pl.profession_id
	  GROUP BY q.level, pl.profession_id, p.name
	  ORDER BY q.level
);

-- Compute initial stats from player_base_attributes.
CREATE VIEW player_stats_initial_view AS (
	SELECT pba.player_id, scr.stat_id, ROUND(EXP(SUM(LOG(pba.attribute_value*attribute_coefficient))),2) AS stat_value
	FROM player_base_attributes pba
	JOIN stat_calculation_rules scr ON scr.attribute_id=pba.attribute_id
	GROUP BY scr.stat_id, pba.player_id
	ORDER BY scr.stat_id
);

-- Get a list of map tiles with a list of NPC IDs and info about a building, if there is one there
CREATE VIEW map_tiles_buildings_npcs_view AS (
	SELECT mt.map_id, mt.x, mt.y, mt.map_tile_type_id, mtt.name AS map_tile_type_name,
	movement_req, skill_id_req, map_tile_type_id_image,
	b.id AS building_id, b.name AS building_name, b.level AS building_level, b.dest_map_id, b.dest_x, b.dest_y, b.external_link,
	(
	    SELECT GROUP_CONCAT(id) FROM npcs WHERE map_id=mt.map_id
	    AND mt.y BETWEEN LEAST(y_bottom,y_top) AND GREATEST(y_top,y_bottom)
	    AND mt.x BETWEEN LEAST(x_left,x_right) AND GREATEST(x_right,x_left)
	) AS npc_ids FROM map_tiles mt
	JOIN map_tile_types mtt ON mt.map_tile_type_id=mtt.id
	LEFT JOIN buildings b ON b.map_id=mt.map_id AND b.x=mt.x AND b.y=mt.y
);


/* Unused views that were from MWv2

-- formerly player_bag
CREATE VIEW player_bag_items_view AS (
	SELECT pi.player_id, pi.bag_slot_id, pi.bag_slot_amount, pi.guid, pi.item_id,
	i.name, i.item_effect_id, i.required_level, i.level, i.bound, i.max_amount,
	ico.id, ico.author
	FROM player_items pi
	JOIN items i ON i.id=pi.item_id
	LEFT OUTER JOIN item_icons ico ON ico.id=i.item_icon_id
	WHERE pi.bag_slot_id IS NOT NULL
	ORDER BY player_id
);

-- Shows info about each player and profession.
-- Players with multiple professions will show multiple times in here.
CREATE VIEW player_professions_details_view AS (
	SELECT p.id, p.user_id, u.username, r.name AS race_name, p.gender_id, p.experience, p.money, p.movement,
	pp.profession_id, pp.profession_level, pp.profession_xp, pr.name AS profession_name, p.birthplace_id
	FROM players p
	JOIN users u ON u.id=p.user_id
	JOIN races r ON r.id=p.race_id
	LEFT OUTER JOIN player_professions pp ON pp.player_id=p.id
	LEFT OUTER JOIN professions pr ON pr.id=pp.profession_id
);

-- This makes me wonder if quest_item and quest_tool should just be one table with a bit for "tool".
-- It's just the two tables unioned
-- Weirdly, this doesn't seem to work in MariaDB unless I remove the brackets around the query.
CREATE VIEW quest_items_tools_view AS
	SELECT qi.quest_id, qi.item_id, i.item_effect_id, 0 AS is_tool FROM quest_items qi JOIN items i ON i.id=qi.item_id
	UNION 
	SELECT qt.quest_id, qt.item_id, i.item_effect_id, 1 AS is_tool FROM quest_tools qt JOIN items i ON i.id=qt.item_id
	ORDER BY quest_id;

*/





/**
 * Bulk Data Insertion
 * This area is to add data that any new installation of MEGA World gets by default.
 */

-- Map Tile Types with names and appropriate movement requirements 
INSERT INTO map_tile_types (id, name, movement_req, skill_id_req, map_tile_type_id_image) VALUES
(1, 'Plains', 			5, NULL, NULL),
(2, 'Village', 			1, NULL, NULL),
(3, 'City Street', 		0, NULL, NULL),
(4, 'Village', 			0, NULL, 2),
(5, 'Armour Store', 	1, NULL, NULL),
(6, 'Bulletin Board',	1, NULL, NULL),
(7, 'Plaza', 			2, NULL, NULL),
(8, 'Camp', 			3, NULL, NULL),
(9, 'Town Centre', 		1, NULL, NULL),
(10, 'Grocery', 		1, NULL, NULL),
(11, 'Harbour', 		1, NULL, NULL),
(12, 'House', 			1, NULL, NULL),
(13, 'Lake', 			10, 1, NULL),
(14, 'Library', 		1, NULL, NULL),
(15, 'Meeting', 		1, NULL, NULL),
(16, 'Mountains', 		10, 2, NULL),
(17, 'Ocean', 			10, 1, NULL),
(18, 'Doorway', 		1, NULL, NULL),
(19, 'Restaurant', 		1, NULL, NULL),
(20, 'River', 			10, 1, NULL),
(21, 'School', 			1, NULL, NULL),
(22, 'Weapon Store',	1, NULL, NULL),
(23, 'Workshop', 		1, NULL, NULL),
(24, 'Harbour', 		1, NULL, NULL),
(25, 'Harbour', 		1, NULL, NULL),
(26, 'Lake', 			10, 1, NULL),
(27, 'Lake', 			10, 1, NULL),
(28, 'Lake', 			10, 1, NULL),
(29, 'Path', 			2, NULL, NULL),
(30, 'Path', 			2, NULL, NULL),
(31, 'Path Corner', 	2, NULL, NULL),
(32, 'Path Corner', 	2, NULL, NULL),
(33, 'Path Corner', 	2, NULL, NULL),
(34, 'Path Corner', 	2, NULL, NULL),
(35, 'Crossroads', 		2, NULL, NULL),
(36, 'River', 			10, 1, NULL),
(37, 'River', 			10, 1, NULL),
(38, 'Riverbend', 		10, 1, NULL),
(39, 'River End', 		10, 1, NULL),
(40, 'River End', 		10, 1, NULL),
(41, 'River End', 		10, 1, NULL),
(42, 'River End', 		10, 1, NULL),
(43, 'Riverbend', 		10, 1, NULL),
(44, 'Riverbend', 		10, 1, NULL),
(45, 'Riverbend', 		10, 1, NULL),
(46, 'City Street', 	0, NULL, NULL),
(48, 'Village Houses',	0, NULL, NULL),
(49, 'Construction Site',5, NULL, NULL),
(50, 'Brick House', 	5, NULL, NULL),
(51, 'Cottage', 		2, NULL, NULL),
(52, 'Castle', 			2, NULL, NULL),
(53, 'Castle Wall', 	2, 3, NULL),
(54, 'Castle Wall', 	2, 3, NULL),
(55, 'Castle Wall', 	2, 3, NULL),
(56, 'Castle Wall', 	2, 3, NULL),
(57, 'Castle Wall', 	2, 3, NULL),
(58, 'Castle Wall', 	2, 3, NULL),
(59, 'Castle Gate', 	2, NULL, NULL),
(60, 'Castle Gate', 	2, NULL, NULL),
(61, 'Castle Tower',	2, 3, NULL),
(62, 'Castle Tower',	2, 3, NULL),
(63, 'Dungeon', 		2, NULL, NULL),
(64, 'Dungeon', 		2, NULL, NULL),
(66, 'Garden', 			2, NULL, NULL),
(67, 'Garden', 			2, NULL, NULL),
(68, 'Garden', 			2, NULL, NULL),
(69, 'Garden', 			2, NULL, NULL),
(70, 'House', 			2, NULL, NULL),
(71, 'House', 			2, NULL, NULL),
(72, 'Stable', 			2, NULL, NULL),
(73, 'Stable', 			2, NULL, NULL),
(74, 'Stone Pillar',	2, NULL, NULL),
(75, 'Stone Pillar',	2, NULL, NULL),
(76, 'Stone Pillar',	2, NULL, NULL),
(77, 'Stone Pillar',	2, NULL, NULL),
(78, 'Stone Wall', 		2, NULL, NULL),
(79, 'Stone Wall', 		2, NULL, NULL),
(80, 'Stone Wall', 		2, NULL, NULL),
(81, 'Stone Wall', 		2, NULL, NULL),
(82, 'Fountain', 		2, NULL, NULL),
(83, 'Ruins', 			2, NULL, NULL);


-- NPC Icons, this excludes the low quality icons that some MEGA World quests originally used
INSERT INTO npc_icons (id, name, author) VALUES
(235, 'Default NPC', 'YZU'),
(252, 'policeman', 'amber'),
(253, 'postman', 'amber'),
(254, 'doctor', 'amber'),
(255, 'man with star cap', 'amber'),
(256, 'fisherman', 'amber'),
(257, 'man2', 'amber'),
(258, 'man3', 'amber'),
(259, 'worker', 'amber'),
(260, 'worker', 'amber'),
(261, 'worker', 'amber'),
(263, 'worker', 'amber'),
(264, 'man', 'amber'),
(266, 'girl smiling brown hair', ''),
(267, 'guard policeman', ''),
(281, 'king 米多拉城領主', ''),
(282, 'man with hat moustache 雜貨店老闆', ''),
(284, 'man on horse 帝國將領-圖留斯', ''),
(288, 'bald man 隱月村長', ''),
(308, 'Mystery Man 神秘人', ''),
(315, 'Magician 魔法師', ''),
(316, 'Captain 機長', ''),
(322, 'doctor white coat old man 醫生', ''),
(325, 'Blacksmith 鐵匠', ''),
(327, 'Businessman 商人', ''),
(330, 'TANG-TANG', 'Yunchi'),
(331, 'teacher', 'Yunchi'),
(332, 'SIAO-SIAO', 'Yunchi'),
(333, 'YOU-RONG-from-Dream', 'Yunchi'),
(337, 'Mysterious man 树林神秘人', 'internet'),
(339, 'SIAO-MEI', 'Yunchi'),
(349, 'restaurant owner 餐厅老板 peter', 'internet'),
(350, 'General Mellon 将领梅隆', 'internet'),
(351, 'Chiku 兵器厂奇库', 'internet'),
(354, 'Xiaoqi', 'Yunchi'),
(361, 'Manager', 'Yunchi'),
(367, 'milkman', 'internet'),
(371, 'Evil dragon 恶龙', 'internet'),
(382, 'Diablo 迪亞布羅', ''),
(500, 'Old caucasian man with beard', 'YusufArtun'),
(501, 'Young caucasian woman with short black hair', 'YusufArtun'),
(502, 'Red haired woman', 'YusufArtun'),
(503, 'Blonde elf woman', 'YusufArtun'),
(504, 'Young Asian woman with green hair', 'YusufArtun'),
(505, 'Blonde woman with scar over eye', 'YusufArtun'),
(506, 'Woman with purple hood and veil', 'YusufArtun'),
(507, 'Blonde woman with big coat', 'YusufArtun'),
(508, 'Black-haired elf noblewoman', 'YusufArtun'),
(509, 'Black knight woman with green eyes', 'YusufArtun'),
(510, 'Auburn woman with red hood', 'YusufArtun'),
(511, 'Asian woman in armour', 'YusufArtun'),
(512, 'Blue-eyed woman in helmet', 'YusufArtun'),
(513, 'Dark-haired man with goatee', 'YusufArtun'),
(514, 'Long-bearded man with tousled hair and armour', 'YusufArtun'),
(515, 'Bald man with goatee in leather armour', 'YusufArtun'),
(516, 'Full-face armour suit with plume', 'YusufArtun'),
(517, 'Flat-top full-faced helmet', 'YusufArtun'),
(518, 'Old man with goatee in green hood', 'YusufArtun'),
(519, 'Blond elf man in armour', 'YusufArtun'),
(520, 'Black man with dreadlocks and painted face', 'YusufArtun'),
(521, 'Man in black and gold hood', 'YusufArtun'),
(522, 'Man with gray beard in helmet', 'YusufArtun'),
(523, 'Man with braided gray beard and eyepatch', 'YusufArtun'),
(524, 'Man with turban', 'YusufArtun'),
(525, 'Man brown beard in leather helmet', 'YusufArtun'),
(526, 'Blond, bearded man in armour', 'YusufArtun'),
(527, 'Man clean-shaven in green coat', 'YusufArtun'),
(528, 'Man in burgundy hood', 'YusufArtun'),
(529, 'Man with goatee in blue tunic', 'YusufArtun'),
(530, 'Young blonde girl in red scarf', 'JD Lien'),
(531, 'Young man in purple tunic', 'JD Lien'),
(532, 'Blond man in green tunic', 'JD Lien'),
(533, 'Old Asian man in blue and white shirt.', 'JD Lien');


-- Item icons, excluding some of the low quality ones from MEGA World
INSERT INTO item_icons (id, name, author) VALUES
(10, 'ANTIQUE pot vase', 'Alex Yao'),
(12, 'ANTIQUE', 'Alex Yao'),
(13, 'BADGE', 'Alex Yao'),
(14, 'BAG', 'Alex Yao'),
(15, 'BISCULT', 'Alex Yao'),
(16, 'CERTIFICATE', 'Alex Yao'),
(17, 'COIN', 'Alex Yao'),
(18, 'DIG ITEM', 'Alex Yao'),
(19, 'DONUTS', 'Alex Yao'),
(125, 'AMULET', 'Alex Yao'),
(126, 'AXE', 'Alex Yao'),
(127, 'AXE', 'Alex Yao'),
(129, 'BOX', 'Alex Yao'),
(130, 'BOX', 'Alex Yao'),
(131, 'BOX', 'Alex Yao'),
(132, 'BREAD', 'Alex Yao'),
(133, 'CALCULATION', 'Alex Yao'),
(134, 'CANNED FOOD', 'Alex Yao'),
(135, 'CAT', 'Alex Yao'),
(136, 'CERTIFICATE', 'Alex Yao'),
(137, 'CHICKEN LEG', 'Alex Yao'),
(138, 'CLUZE', 'Alex Yao'),
(139, 'CMD', 'Alex Yao'),
(140, 'COAL', 'Alex Yao'),
(142, 'COMPUTER', 'Alex Yao'),
(143, 'COPPER MINE', 'Alex Yao'),
(144, 'COPPER', 'Alex Yao'),
(145, 'DAILY NEWSPAPER', 'Alex Yao'),
(146, 'DIAMOND MINE', 'Alex Yao'),
(147, 'DIAMOND', 'Alex Yao'),
(148, 'DICTIONARY', 'Alex Yao'),
(149, 'DONAIR', 'Alex Yao'),
(150, 'FARM WORMS', 'Alex Yao'),
(151, 'FARM WORMS', 'Alex Yao'),
(152, 'FARM WORMS', 'Alex Yao'),
(153, 'FARM WORMS', 'Alex Yao'),
(154, 'FARM WORMS', 'Alex Yao'),
(155, 'FLOWERS', 'Alex Yao'),
(156, 'FLOWERS', 'Alex Yao'),
(157, 'FLOWERS', 'Alex Yao'),
(158, 'GENERIC ITEM', 'Alex Yao'),
(159, 'GIFT', 'Alex Yao'),
(160, 'GOLD MINE', 'Alex Yao'),
(161, 'GOLD', 'Alex Yao'),
(162, 'HAMBURGER', 'Alex Yao'),
(163, 'HAMMER', 'Alex Yao'),
(164, 'HAMMER', 'Alex Yao'),
(165, 'HAMMER', 'Alex Yao'),
(166, 'HERBS', 'Alex Yao'),
(167, 'HERBS', 'Alex Yao'),
(168, 'HERBS', 'Alex Yao'),
(169, 'HOT DOG', 'Alex Yao'),
(170, 'IRON MINE', 'Alex Yao'),
(171, 'IRON', 'Alex Yao'),
(172, 'JOURNAL', 'Alex Yao'),
(173, 'KEY', 'Alex Yao'),
(174, 'KEY', 'Alex Yao'),
(175, 'KEY', 'Alex Yao'),
(176, 'KEY', 'Alex Yao'),
(177, 'KEY', 'Alex Yao'),
(178, 'KEY', 'Alex Yao'),
(179, 'KEY', 'Alex Yao'),
(180, 'KEY', 'Alex Yao'),
(181, 'KEY', 'Alex Yao'),
(182, 'LETTER', 'Alex Yao'),
(183, 'LETTER', 'Alex Yao'),
(184, 'LOVE LETTER', 'Alex Yao'),
(185, 'MACHINE COMPONENTS', 'Alex Yao'),
(186, 'MEAL', 'Alex Yao'),
(187, 'MEAL', 'Alex Yao'),
(188, 'MEAL', 'Alex Yao'),
(189, 'MOON NEWSPAPER', 'Alex Yao'),
(190, 'NOTE', 'Alex Yao'),
(191, 'NOTE', 'Alex Yao'),
(192, 'ONIGIRI', 'Alex Yao'),
(193, 'PICKAXE', 'Alex Yao'),
(194, 'PICKAXE', 'Alex Yao'),
(195, 'PICKAXE', 'Alex Yao'),
(196, 'PLIERS', 'Alex Yao'),
(197, 'PLIERS', 'Alex Yao'),
(198, 'PLIERS', 'Alex Yao'),
(199, 'POTION', 'Alex Yao'),
(200, 'POTION', 'Alex Yao'),
(201, 'POTION', 'Alex Yao'),
(202, 'POTION', 'Alex Yao'),
(203, 'POTION', 'Alex Yao'),
(204, 'POTION', 'Alex Yao'),
(205, 'POTION', 'Alex Yao'),
(206, 'POTION', 'Alex Yao'),
(207, 'POTION', 'Alex Yao'),
(208, 'QUEST ITEM', 'Alex Yao'),
(209, 'RAIDER ATTACK', 'Alex Yao'),
(210, 'ROSE', 'Alex Yao'),
(211, 'SAW', 'Alex Yao'),
(212, 'SAW', 'Alex Yao'),
(213, 'SAW', 'Alex Yao'),
(214, 'SCISSORS', 'Alex Yao'),
(215, 'SCISSORS', 'Alex Yao'),
(216, 'SCISSORS', 'Alex Yao'),
(217, 'SHIELD', 'Alex Yao'),
(218, 'SHOVEL', 'Alex Yao'),
(219, 'SHOVEL', 'Alex Yao'),
(220, 'SHOVEL', 'Alex Yao'),
(221, 'SLIVER MINE', 'Alex Yao'),
(222, 'SLIVER', 'Alex Yao'),
(223, 'STEAK', 'Alex Yao'),
(224, 'SUN FLOWERS', 'Alex Yao'),
(225, 'SWORD', 'Alex Yao'),
(226, 'TIMBER', 'Alex Yao'),
(227, 'TREE BRANCHES', 'Alex Yao'),
(228, 'UNKNOWN BOX', 'Alex Yao'),
(229, 'UNKNOWN BOX', 'Alex Yao'),
(230, 'UNKNOWN BOX', 'Alex Yao'),
(231, 'WRENCH', 'Alex Yao'),
(232, 'WRENCH', 'Alex Yao'),
(233, 'WRENCH', 'Alex Yao'),
(265, 'COMPUTER', 'Alex Yao'),
(341, 'L1 Cloak', 'RoyLI'),
(342, 'L2 Cloak', 'RoyLI'),
(343, 'L3 Cloak', 'RoyLI'),
(344, 'L1 Stick', 'RoyLI'),
(345, 'L2 Stick', 'RoyLI'),
(346, 'L3 Stick', 'RoyLI'),
(347, 'EdUID', 'RoyLI'),
(355, 'Key of fate', 'RoyLI'),
(356, 'Master degree', 'RoyLI'),
(357, 'L1 armor', 'RoyLI'),
(358, 'L2 armor', 'RoyLI'),
(359, 'L3 armor', 'RoyLI'),
(362, 'Debris I', 'RoyLI'),
(363, 'Debris II', 'RoyLI'),
(364, 'Debris III', 'RoyLI'),
(365, 'YOU-RONG-SHORT-HAIR', 'Yunchi'),
(374, 'helmet 头盔', 'internet'),
(375, 'armor 盔甲', 'internet'),
(383, 'Compound Bow', 'Shuyu'),
(384, 'Elf Bow', 'Shuyu'),
(385, 'Griffon Force Bow', 'Shuyu'),
(386, 'Hurricane Bow ', 'Shuyu'),
(387, 'Wind Whisper Bow', 'Shuyu'),
(388, 'Black Knight Spear', 'Shuyu'),
(389, 'Master Spear', 'Shuyu'),
(390, 'Paladin Spear', 'Shuyu'),
(391, 'Platinum Spear', 'Shuyu'),
(392, 'Beast Bone Staff', 'Shuyu'),
(393, 'Moon Light Staff', 'Shuyu'),
(394, 'Priest Staff', 'Shuyu'),
(395, 'Treant Staff', 'Shuyu'),
(396, 'Goblin King Sword', 'Shuyu'),
(397, 'Paladin Sword', 'Shuyu'),
(398, 'White Knight Sword', 'Shuyu'),
(399, 'Wyvern Sword', 'Shuyu'),
(400, 'Army Bow', 'Vicky'),
(401, 'Powered Beast Bone Staff', 'Vicky'),
(402, 'Powered Compound Bow', 'Vicky'),
(403, 'Crystal Sword', 'Vicky'),
(404, 'Dragon King Bow', 'Vicky'),
(405, 'Dragon King Spear', 'Vicky'),
(406, 'Dragon King Staff', 'Vicky'),
(407, 'Dragon King Sword', 'Vicky'),
(408, 'Glass Staff', 'Vicky'),
(409, 'Hunter Bow', 'Vicky'),
(410, 'Iron Spear', 'Vicky'),
(411, 'Iron Sword', 'Vicky'),
(412, 'Knight Spear', 'Vicky'),
(413, 'Knight Sword', 'Vicky'),
(414, 'White Wolf Lance', 'Vicky'),
(415, 'Witcher Staff', 'Vicky'),
(416, 'Wood Bow', 'Vicky'),
(417, 'Wood Spear', 'Vicky'),
(418, 'Wood Staff', 'Vicky'),
(419, 'Wood Sword', 'Vicky'),
(420, 'Guardian Armor', 'Shuyu'),
(421, 'Guardian Boots', 'Shuyu'),
(422, 'Guardian helmet', 'Shuyu'),
(423, 'Knight Armor', 'Shuyu'),
(424, 'Knight Boots', 'Shuyu'),
(425, 'Knight Helmet', 'Shuyu'),
(426, 'Paladin Armor', 'Shuyu'),
(427, 'Paladin Boots', 'Shuyu'),
(428, 'Paladin Helmet', 'Shuyu'),
(429, 'Unicorn Armor ', 'Shuyu'),
(430, 'Unicorn Boots', 'Shuyu'),
(431, 'Unicorn Helmet', 'Shuyu'),
(432, 'White Knight Armor', 'Shuyu'),
(433, 'White Knight Boots', 'Shuyu'),
(434, 'White Knight Helmet', 'Shuyu'),
(435, 'Bahamut Armor', 'Vicky'),
(436, 'Dragon King Armor', 'Vicky'),
(437, 'Holy Unicorn Armor', 'Vicky'),
(438, 'Iron Armor', 'Vicky'),
(439, 'Leather Armor', 'Vicky'),
(440, 'Bahamut Boots', 'Vicky'),
(441, 'Dragon King Boots', 'Vicky'),
(442, 'Holy Unicorn Boots', 'Vicky'),
(443, 'Iron Boots', 'Vicky'),
(444, 'Leather Boots', 'Vicky'),
(445, 'Bahamut Helmet', 'Vicky'),
(446, 'Dragon King Helmet', 'Vicky'),
(447, 'Holy Unicorn Helmet', 'Vicky'),
(448, 'Iron Helmet', 'Vicky'),
(449, 'Leather Helmet', 'Vicky'),
(450, 'HP Potion', 'Vicky'),
(451, 'Super HP Potion', 'Vicky'),
(452, 'MP Potion', 'Vicky'),
(453, 'Super MP Potion', 'Vicky'),
(454, 'Agility Potion', 'Shuyu'),
(455, 'Super Agility Potion', 'Shuyu'),
(456, 'Strength Potion', 'Shuyu'),
(457, 'Super Strength Potion', 'Shuyu'),
(458, 'Defense Potion', 'Shuyu'),
(459, 'Super Defense Potion', 'Shuyu'),
(1000, 'Purple Orb', 'a-ravlik'),
(1001, 'Root', 'a-ravlik'),
(1002, 'Leaves', 'a-ravlik'),
(1003, 'Green Artefact', 'a-ravlik'),
(1004, 'Purple Roll', 'a-ravlik'),
(1005, 'Wooden Chest', 'a-ravlik'),
(1006, 'Sharp Tools', 'a-ravlik'),
(1007, 'Pentagram Book', 'a-ravlik'),
(1008, 'Purple Bookmark', 'a-ravlik'),
(1009, 'Egg', 'a-ravlik'),
(1010, 'Green Berries', 'a-ravlik'),
(1011, 'Vase', 'a-ravlik'),
(1012, 'Green Amulet', 'a-ravlik'),
(1013, 'Red Orb', 'a-ravlik'),
(1014, 'Red Thread', 'a-ravlik'),
(1015, 'Horn', 'a-ravlik'),
(1016, 'Spear', 'a-ravlik'),
(1017, 'Green Slab', 'a-ravlik'),
(1018, 'Broken Tablet', 'a-ravlik'),
(1019, 'Pink Claw', 'a-ravlik'),
(1020, 'Ruby Armband', 'a-ravlik'),
(1021, 'Sapphire', 'a-ravlik'),
(1022, 'Assorted Tools', 'a-ravlik'),
(1023, 'Corked Flask', 'a-ravlik'),
(1024, 'Chain with Hook', 'a-ravlik'),
(1025, 'Roll of Cloth', 'a-ravlik'),
(1026, 'Dragon Claws', 'a-ravlik'),
(1027, 'Gold Bar', 'a-ravlik'),
(1028, 'Herbs', 'a-ravlik'),
(1029, 'Fish Bone', 'a-ravlik'),
(1030, 'Dragon Wing Ornament', 'a-ravlik'),
(1031, 'Leather Satchel', 'a-ravlik'),
(1032, 'Horn', 'a-ravlik'),
(1033, 'Purple Minerals', 'a-ravlik'),
(1034, 'Green Orb', 'a-ravlik'),
(1035, 'Old Scroll', 'a-ravlik'),
(1036, 'Decorative Scroll', 'a-ravlik'),
(1037, 'Sealed Scroll', 'a-ravlik'),
(1038, 'Orc Skull', 'a-ravlik'),
(1039, 'Blue Ornament', 'a-ravlik'),
(1040, 'Green Book with Star', 'a-ravlik'),
(1041, 'Green & Leather Book', 'a-ravlik'),
(1042, 'Rocks', 'a-ravlik'),
(1043, 'Leather Book with Jewel', 'a-ravlik'),
(1044, 'Purple Portfolio', 'a-ravlik'),
(1045, 'Rune with Eye Symbol', 'a-ravlik'),
(1046, 'Purple Gem 1', 'a-ravlik'),
(1047, 'Gift Box Hexagonal', 'a-ravlik'),
(1048, 'Red Droplet	', 'a-ravlik'),
(1049, 'Dragon Teeth', 'a-ravlik'),
(1050, 'Purple Gem 2', 'a-ravlik'),
(1051, 'Blue Gift Box with Stars', 'a-ravlik'),
(1052, 'Bird Tablet', 'a-ravlik'),
(1053, 'Green Droplet', 'a-ravlik'),
(1054, 'Blue A Token', 'a-ravlik'),
(1055, 'Bones', 'a-ravlik'),
(1056, 'Horn with Strings', 'a-ravlik'),
(1057, 'Gift Box Turquoise Round', 'a-ravlik'),
(1058, 'Rock Angular', 'a-ravlik'),
(1059, 'Skull Green Jewel Necklace', 'a-ravlik'),
(1060, 'Iron Bars', 'a-ravlik'),
(1061, 'Gift Box Red Green Bow', 'a-ravlik'),
(1062, 'Rock Rounded', 'a-ravlik'),
(1063, 'Gift Box Pink with Flowers', 'a-ravlik'),
(1064, 'Gold Medalion', 'a-ravlik'),
(1065, 'Fabric Gold & Purple', 'a-ravlik'),
(1066, 'Teeth Necklace', 'a-ravlik'),
(1067, 'Token', 'a-ravlik'),
(1068, 'Symbol Book ', 'a-ravlik'),
(1069, 'Spine', 'a-ravlik'),
(1070, 'Green Gem', 'a-ravlik'),
(1071, 'Gift Box Green with Red Bow', 'a-ravlik'),
(1072, 'Sharp Rock', 'a-ravlik'),
(1073, 'Open Book', 'a-ravlik'),
(1074, 'Blue Rocks', 'a-ravlik'),
(1075, 'Gold Ore', 'a-ravlik'),
(1076, 'Sealed Letter', 'a-ravlik'),
(1077, 'Red Rocks', 'a-ravlik'),
(1078, 'Fish Catfish', 'a-ravlik'),
(1079, 'Fish 2', 'a-ravlik'),
(1080, 'Fish 3', 'a-ravlik'),
(1081, 'Fish Perch', 'a-ravlik'),
(1082, 'Fish 5', 'a-ravlik'),
(1083, 'Fish 6', 'a-ravlik'),
(1084, 'Fish 7', 'a-ravlik'),
(1085, 'Fish 8', 'a-ravlik'),
(1086, 'Fish 9', 'a-ravlik'),
(1087, 'Fish Pike', 'a-ravlik'),
(1088, 'Fish 10', 'a-ravlik'),
(1089, 'Fish 11', 'a-ravlik'),
(1090, 'Meat Ham', 'a-ravlik'),
(1091, 'Vegetables', 'a-ravlik'),
(1092, 'Meat Steak', 'a-ravlik'),
(1093, 'Fish 12', 'a-ravlik'),
(1094, 'Wooden Hatch', 'a-ravlik'),
(1095, 'Metal Hammer', 'a-ravlik'),
(1096, 'Pick Bone', 'a-ravlik'),
(1097, 'Pick Wood', 'a-ravlik'),
(1098, 'Wooden Hammer', 'a-ravlik'),
(1099, 'Metal Pick', 'a-ravlik'),
(1100, 'Lether Boots', 'Horski'),
(1101, 'Leather Boots Buckle', 'Horski'),
(1102, 'Armour Boots', 'Horski'),
(1103, 'Silver Armour Boots', 'Horski'),
(1104, 'Gold Armour Boots', 'Horski'),
(1105, 'Red Claw Boots', 'Horski'),
(1106, 'Turqoise Claw Boots', 'Horski'),
(1107, 'Blue Boots Spikes', 'Horski'),
(1108, 'Red Boots', 'Horski'),
(1109, 'Green Boots', 'Horski'),
(1110, 'Tall Green Boots', 'Horski'),
(1111, 'Leather Shoes', 'Horski'),
(1112, 'Red Armour Boots', 'Horski'),
(1113, 'Purple Armour Boots', 'Horski'),
(1114, 'Pointy Armoured Boots', 'Horski'),
(1115, 'Leather Gauntlet', 'Horski'),
(1116, 'Metal Bracer', 'Horski'),
(1117, 'Metal Gauntlet', 'Horski'),
(1118, 'Gold Bracer', 'Horski'),
(1119, 'Metal Bracer', 'Horski'),
(1120, 'Pointy Bracer', 'Horski'),
(1121, 'Tooth Bracer', 'Horski'),
(1122, 'Green Bracer', 'Horski'),
(1123, 'Red Bracer', 'Horski'),
(1124, 'Green Bracer', 'Horski'),
(1125, 'Purple Gauntlet', 'Horski'),
(1126, 'Red Gauntlet', 'Horski'),
(1127, 'Crimson Bracer', 'Horski'),
(1128, 'Red Decorative Bracer', 'Horski'),
(1129, 'Leather Vest', 'Horski'),
(1130, 'Green Chest Armour', 'Horski'),
(1131, 'Chest Plate Armor', 'Horski'),
(1132, 'Body Armour', 'Horski'),
(1133, 'Gold Body Armour', 'Horski'),
(1134, 'Red Body Armour', 'Horski'),
(1135, 'Blue Body Armor', 'Horski'),
(1136, 'Purple Robe', 'Horski'),
(1137, 'Red Robe', 'Horski'),
(1138, 'Spiked Blue Armor', 'Horski'),
(1139, 'Spiked Red Armor', 'Horski'),
(1140, 'Purple Robe 2', 'Horski'),
(1141, 'Red Robe 3', 'Horski'),
(1142, 'Leather Body Armour', 'Horski'),
(1143, 'Plated Mail Armour', 'Horski'),
(1144, 'Blue Ornamental Armour', 'Horski'),
(1145, 'Gold Body Armour', 'Horski'),
(1146, 'Green Body Armour', 'Horski'),
(1147, 'Gold Wing Helmet', 'Horski'),
(1148, 'Red Winged Helmet', 'Horski'),
(1149, 'Blue Helmet', 'Horski'),
(1150, 'Metal Nasal Helmet', 'Horski'),
(1151, 'Red Helmet', 'Horski'),
(1152, 'Turquoise Ornamental Helmet', 'Horski'),
(1153, 'Open Helmet', 'Horski'),
(1154, 'Blue Helmet with Plume', 'Horski'),
(1155, 'Red Helmet with Plume', 'Horski'),
(1156, 'Horned Nasal Helmet', 'Horski'),
(1157, 'Green Hood', 'Horski'),
(1158, 'Purple Hood', 'Horski'),
(1159, 'Blue Ornamental Helmet', 'Horski'),
(1160, 'Red Pointy Hat', 'Horski'),
(1161, 'Green Pointy Hat', 'Horski'),
(1162, 'Blue Sword', 'Horski'),
(1163, 'Bronze Sword', 'Horski'),
(1164, 'Steel Sword', 'Horski'),
(1165, 'Green Sword', 'Horski'),
(1166, 'Heavy Sword', 'Horski'),
(1167, 'Large Sword Blue Stripe', 'Horski'),
(1168, 'Large Red Sword ', 'Horski'),
(1169, 'Purple Sword', 'Horski'),
(1170, 'Golden Sword', 'Horski'),
(1171, 'Evil Dagger', 'Horski'),
(1172, 'Barbed Dagger', 'Horski'),
(1173, 'Golden Dagger', 'Horski'),
(1174, 'Steel Dagger', 'Horski'),
(1175, 'Blue Dagger', 'Horski'),
(1176, 'Green Ceremonial Knife', 'Horski'),
(1177, 'Pink Wand', 'Horski'),
(1178, 'Plant Wand', 'Horski'),
(1179, 'Green Tree Wand', 'Horski'),
(1180, 'Blue Wand', 'Horski'),
(1181, 'Fire Wand', 'Horski'),
(1182, 'Red Orb Wand', 'Horski'),
(1183, 'Ram Skull Wand', 'Horski'),
(1184, 'Green Wand', 'Horski'),
(1185, 'Green Bow', 'Horski'),
(1186, 'Blue Bow', 'Horski'),
(1187, 'Red Bow', 'Horski'),
(1188, 'Simple Bow', 'Horski'),
(1189, 'Dragon Eye Ring', 'Horski'),
(1190, 'Gold Ornamental Ring', 'Horski'),
(1191, 'Blue Eye Ring', 'Horski'),
(1192, 'Blue Gem Ring', 'Horski'),
(1193, 'Large Ring', 'Horski'),
(1194, 'Bronze Ring', 'Horski'),
(1195, 'Iron Ring', 'Horski'),
(1196, 'Ruby Iron Ring', 'Horski'),
(1197, 'Purple Gem Ring', 'Horski'),
(1198, 'Large Purple Gem Ring', 'Horski'),
(1199, 'Large Ruby Ring', 'Horski'),
(1200, 'Blue Ring', 'Horski'),
(1201, 'Pearl Ring', 'Horski'),
(1202, 'Flower Ring', 'Horski'),
(1203, 'Heart Ring', 'Horski'),
(1204, 'Fire Ring', 'Horski'),
(1205, 'Pink Gem Ring', 'Horski'),
(1206, 'Green Gem Amulet', 'Horski'),
(1207, 'Blue Teeth Amulet', 'Horski'),
(1208, 'Blue Cross Amulet', 'Horski'),
(1209, 'Devil Skull Amulet', 'Horski'),
(1210, 'Green Drop Amulet', 'Horski'),
(1211, 'Pink Gold Amulet', 'Horski'),
(1212, 'Red Drop Amulet', 'Horski'),
(1213, 'Green Pearls Amulet', 'Horski'),
(1214, 'Red Gem Amulet', 'Horski'),
(1215, 'Bread Loaf French', 'JD Lien');

/* Original quest result types. These have been simplified and reorganized -- see below.
INSERT INTO quest_result_types (name,
uses_items, uses_bool, uses_string, uses_answers, uses_variables, uses_questions, uses_automark) VALUES
                         -- Uses ItemBoolStrAnsVarQueAutomark
('True/False (items)', 				1, 1, 0, 0, 0, 0, 0), -- 1
('Single Choice (items)', 			1, 1, 0, 0, 0, 0, 0), -- 2
('Choose Multiple (items)',			1, 1, 0, 0, 0, 0, 0), -- 3
('Cloze',				 			0, 0, 0, 1, 0, 0, 0), -- 4
('Text Answers',					0, 0, 1, 1, 0, 1, 0), -- 5  Maybe this should use automark
('Order (items)', 					1, 0, 0, 0, 0, 0, 0), -- 6
('Quest Items', 					1, 0, 0, 0, 0, 0, 0), -- 7
('Check In (only)', 				0, 0, 0, 0, 0, 0, 0), -- 8
('Check In (with Item)',	 		1, 0, 0, 0, 0, 0, 0), -- 9
('Calculation', 					0, 0, 1, 1, 1, 1, 0), -- 10
('Multiple Calculation (items)',	1, 0, 1, 0, 0, 0, 0), -- 11
('Multiple text answers (items)',	1, 0, 1, 0, 0, 0, 0), -- 12
('Coordinates', 					0, 0, 0, 0, 1, 0, 0), -- 13  Uses quest map_id, result_x, result_y fields
('Multiple Coordinates (items)', 	1, 0, 0, 0, 1, 0, 0), -- 14  This one uses quest_tools
('True/False', 						0, 1, 1, 1, 0, 0, 0), -- 15  No questions, select answers as true/false
('Single Choice', 					0, 1, 0, 1, 0, 0, 0), -- 16  Similar to above but radio for one correct
('Multiple Calculation',			0, 0, 0, 1, 1, 1, 0), -- 17  REDUNDANT (Same as 10)
('Multiple text answers',			0, 0, 0, 1, 0, 1, 1), -- 18  REDUNDANT (Same as 5)
('Speaking (Conversation)', 		0, 0, 0, 0, 0, 0, 0); -- 19  This type used to kick users to a page that did speech recognition. Not planned for v3.0.
*/

-- New quest result types for v3.0. Conversion script maps the old quests to these ones
INSERT INTO quest_result_types (name,
uses_items, uses_bool, uses_string, uses_answers, uses_variables, uses_questions, uses_automark) VALUES
                     -- Uses ItemBoolStrAnsVarQueAutomark
('Check In (Items Optional)',   1, 0, 0, 0, 0, 0, 0), -- 1  Formerly 7, 9, 8 (check in only), 14 (multi coordinates)
('Items True/False',            1, 1, 0, 0, 0, 0, 0), -- 2  Formerly 1
('Items Choose One',            1, 1, 0, 0, 0, 0, 0), -- 3  Formerly 2
('Items Choose Multiple',       1, 1, 0, 0, 0, 0, 0), -- 4  Formerly 3
('Items Order',                 1, 0, 0, 0, 0, 0, 0), -- 5  Formerly 6
('Items Text Answers',          1, 0, 1, 0, 0, 0, 0), -- 6  Formerly 12 & 11 (Multiple calculation items)
('Cloze (Fill in Blanks)',      0, 0, 0, 1, 0, 0, 0), -- 7  Formerly 4
('True/False',                  0, 1, 1, 1, 0, 1, 0), -- 8  15  Questions optional, select answers as true/false
('Choose One',                  0, 1, 0, 1, 0, 0, 0), -- 9  16  Radio for one correct
('Choose Multiple',             0, 1, 0, 1, 0, 0, 0), -- 10  New. As above but checkbox for multiple options
('Text Long Answers',           0, 0, 1, 1, 0, 1, 1), -- 11 Formerly 5 & 18 - should use automark
('Calculation',                 0, 0, 1, 1, 1, 1, 0), -- 12 Formerly 10 and 17
('Coordinates',                 0, 0, 0, 0, 1, 0, 0), -- 13 Formerly 13  Uses quest map_id, result_x, result_y fields
('Speaking (Conversation)',     0, 0, 1, 1, 0, 1, 1), -- 14 Formerly 19 speech recognition
('Items Calculation',           1, 0, 1, 0, 1, 0, 0); -- 15 In this weird place in the list because it was added later.

 
INSERT INTO player_action_types (name, description) VALUES
('login', 'Logged in successfully'),
('logout', 'Logged out'),
('move_with_energy', 'Enough energy to move.'), -- Should this apply when the move didn't require energy?
('move_not_need_profession', 'Building does not require profession.'),
('move_with_profession', 'Has a required building profession.'),
('move_without_profession', 'Does not have required building profession.'),
('move', 'Any movement attempt.'), -- Redundant that this was recorded for every movement in addition to the move variations.
('move_without_energy_or_skill', 'Player was unable to move.'),
('move_portal', 'Used building portal at this source location.'),
('teleport', 'Teleported to new destination location (typically via building portal)'),
('teleport_offered_by_npc', 'Player paid NPC to teleport.'),
('move_external_link', 'Opened link in browser.'),
('ranking_list_check', NULL),
('ranking_list_check_for_talkative_hero', NULL),
('ranking_list_check_for_richest', NULL),
-- There was originally teleport_npc_without_money if player didn't have enough money and teleport_npc_illegally but these were never used.
-- Each check for loads of the ranking lists were logged. Several others were in the code that were never used.
('ranking_list_check_for_highest_level', NULL),
('ranking_list_check_for_travelers', NULL),
('ranking_list_check_for_treasure_hunter', NULL),
('ranking_list_check_for_experienced_hero', NULL),
('ranking_list_check_for_explorers', NULL),
('ranking_list_check_for_helpful_hero', NULL),
('ranking_list_check_for_frequent_login_hero', NULL),
('treasure_found_without_tool', 'Successfully completed a quest without an item.'),
('treasure_found_with_tool', 'Player used an item to complete a quest.'),
('treasure_found_item_without_tool', 'Player completed a quest without an item and got an item.'),
('create_player', 'Made a new player character.'),
('select_player', 'Selected a player character.');


INSERT INTO languages (name, native_name, country, locale_id, supported) VALUES
('Albanian',			'gjuha shqipe',		'Albania',			'sq_AL', 	0),
('Arabic', 				'العربية',			'Algeria',			'ar_DZ', 	0),
('Arabic', 				'العربية',			'Bahrain',			'ar_BH', 	0),
('Arabic', 				'العربية',			'Egypt',			'ar_EG', 	0),
('Arabic', 				'العربية',			'Iraq',				'ar_IQ', 	0),
('Arabic', 				'العربية',			'Jordan',			'ar_JO', 	0),
('Arabic', 				'العربية',			'Kuwait',			'ar_KW', 	0),
('Arabic', 				'العربية',			'Lebanon',			'ar_LB', 	0),
('Arabic', 				'العربية',			'Libya',			'ar_LY', 	0),
('Arabic', 				'العربية',			'Morocco',			'ar_MA', 	0),
('Arabic', 				'العربية',			'Oman',				'ar_OM', 	0),
('Arabic', 				'العربية',			'Qatar',			'ar_QA', 	0),
('Arabic', 				'العربية',			'Saudi Arabia',		'ar_SA', 	0),
('Arabic', 				'العربية',			'Sudan',			'ar_SD', 	0),
('Arabic', 				'العربية',			'Syria',			'ar_SY', 	0),
('Arabic', 				'العربية',			'Tunisia',			'ar_TN', 	0),
('Arabic', 				'العربية',			'United Arab Emirates','ar_AE', 0),
('Arabic', 				'العربية',			'Yemen',			'ar_YE', 	0),
('Belarusian',			'беларуская мова',	'Belarus',			'be_BY', 	0),
('Bulgarian',			'български език',	'Bulgaria',			'bg_BG', 	0),
('Catalan',				'català', 			'Spain',			'ca_ES', 	0),
('Chinese (Simplified)','中文（简体）',		'China',			'zh_CN', 	1),
('Chinese (Simplified)','中文（简体）',		'Singapore',		'zh_SG(*)', 0),
('Chinese (Traditional)','中文（繁体）',		'Hong Kong',		'zh_HK', 	0),
('Chinese (Traditional)','中文（繁体）',		'Taiwan',			'zh_TW', 	0),
('Croatian',			'hrvatski jezik',	'Croatia',			'hr_HR', 	0),
('Czech',				'čeština',			'Czech Republic',	'cs_CZ', 	0),
('Danish',				'dansk', 			'Denmark',			'da_DK', 	0),
('Dutch',				'Nederlands', 		'Belgium',			'nl_BE', 	0),
('Dutch',				'Nederlands', 		'Netherlands',		'nl_NL', 	0),
('English',				'English',			'Australia',		'en_AU', 	0),
('English',				'English',			'Canada',			'en_CA', 	1),
('English',				'English',			'India',			'en_IN', 	0),
('English',				'English',			'Ireland',			'en_IE', 	0),
('English',				'English',			'Malta',			'en_MT(*)', 0),
('English',				'English',			'New Zealand',		'en_NZ', 	0),
('English',				'English',			'Philippines',		'en_PH(*)', 0),
('English',				'English',			'Singapore',		'en_SG(*)', 0),
('English',				'English',			'South Africa',		'en_ZA', 	0),
('English',				'English',			'United Kingdom',	'en_GB', 	0),
('English',				'English',			'United States',	'en_US', 	0),
('Estonian',			'eesti',			'Estonia',			'et_EE', 	0),
('Finnish',				'suomi',			'Finland',			'fi_FI', 	0),
('French',				'français', 		'Belgium',			'fr_BE', 	0),
('French',				'français', 		'Canada',			'fr_CA', 	0),
('French',				'français', 		'France',			'fr_FR', 	1),
('French',				'français', 		'Luxembourg',		'fr_LU', 	0),
('French',				'français', 		'Switzerland',		'fr_CH', 	0),
('German',				'Deutsch',			'Austria',			'de_AT', 	0),
('German',				'Deutsch',			'Germany',			'de_DE', 	0),
('German',				'Deutsch',			'Luxembourg',		'de_LU', 	0),
('German',				'Deutsch',			'Switzerland',		'de_CH', 	0),
('Greek',				'ελληνικά', 		'Cyprus',			'el_CY(*)', 0),
('Greek',				'ελληνικά',			'Greece',			'el_GR', 	0),
('Hebrew',				'עברית',			'Israel',			'iw_IL', 	0),
('Hindi',				'हिन्दी, हिंदी',			'India',			'hi_IN', 	0),
('Hungarian',			'magyar',			'Hungary',			'hu_HU', 	0),
('Icelandic',			'Íslenska',			'Iceland',			'is_IS', 	0),
('Indonesian',			'Bahasa Indonesia', 'Indonesia',		'in_ID(*)', 0),
('Irish',				'Gaeilge',			'Ireland',			'ga_IE(*)', 0),
('Italian',				'italiano',			'Italy',			'it_IT', 	0),
('Italian',				'italiano',			'Switzerland',		'it_CH', 	0),
('Japanese',			'日本語',				'Japan',			'ja_JP',	0),
('Japanese (Imperial calendar)', '日本語',	'Japan',			'ja_JP_JP', 0),
('Korean',				'한국어',				'South Korea',		'ko_KR', 	0),
('Latvian',				'latviešu valoda',	'Latvia',			'lv_LV', 	0),
('Lithuanian',			'lietuvių kalba',	'Lithuania',		'lt_LT', 	0),
('Macedonian',			'македонски јазик',	'Macedonia',		'mk_MK', 	0),
('Malay',				'bahasa Melayu',	'Malaysia',			'ms_MY(*)', 0),
('Maltese',				'Malti',			'Malta',			'mt_MT(*)', 0),
('Norwegian (Bokmål)',	'Norsk (Bokmål)',	'Norway',			'no_NO', 	0),
('Norwegian (Nynorsk)',	'Norsk (Nynorsk)',	'Norway',			'no_NO_NY', 0),
('Polish',				'język polski',		'Poland',			'pl_PL', 	0),
('Portuguese',			'português',		'Brazil',			'pt_BR(*)', 0),
('Portuguese',			'português',		'Portugal',			'pt_PT(*)', 0),
('Romanian',			'limba română',		'Romania',			'ro_RO', 	0),
('Russian',				'русский язык',		'Russia',			'ru_RU', 	0),
('Serbian (Cyrillic)',	'српски језик',		'Bosnia and Herzegovina','sr_BA(*)',0),
('Serbian (Cyrillic)',	'српски језик',		'Montenegro',		'sr_ME(*)', 0),
('Serbian (Cyrillic)',	'српски језик',		'Serbia',			'sr_RS(*)', 0),
('Serbian (Latin)',		'srpski jezik',		'Bosnia and Herzegovina','sr_Latn_BA(**)',0),
('Serbian (Latin)',		'srpski jezik',		'Montenegro',		'sr_Latn_ME(**)',0),
('Serbian (Latin)',		'srpski jezik', 	'Serbia',			'sr_Latn_RS(**)',0),
('Slovak',				'slovenčina',		'Slovakia',			'sk_SK', 	0),
('Slovenian',			'slovenščina',		'Slovenia',			'sl_SI', 	0),
('Spanish',				'español',			'Argentina',		'es_AR', 	0),
('Spanish',				'español',			'Bolivia',			'es_BO', 	0),
('Spanish',				'español',			'Chile',			'es_CL', 	0),
('Spanish',				'español',			'Colombia',			'es_CO', 	0),
('Spanish',				'español',			'Costa Rica',		'es_CR', 	0),
('Spanish',				'español',			'Dominican Republic','es_DO', 	0),
('Spanish',				'español',			'Ecuador',			'es_EC', 	0),
('Spanish',				'español',			'El Salvador',		'es_SV', 	0),
('Spanish',				'español',			'Guatemala',		'es_GT', 	0),
('Spanish',				'español',			'Honduras',			'es_HN', 	0),
('Spanish',				'español',			'Mexico',			'es_MX', 	0),
('Spanish',				'español',			'Nicaragua',		'es_NI', 	0),
('Spanish',				'español',			'Panama',			'es_PA', 	0),
('Spanish',				'español',			'Paraguay',			'es_PY', 	0),
('Spanish',				'español',			'Peru',				'es_PE', 	0),
('Spanish',				'español',			'Puerto Rico',		'es_PR', 	0),
('Spanish',				'español',			'Spain',			'es_ES', 	0),
('Spanish',				'español',			'United States',	'es_US(*)', 0),
('Spanish',				'español',			'Uruguay',			'es_UY', 	0),
('Spanish',				'español',			'Venezuela',		'es_VE', 	0),
('Swedish',				'Svenska',			'Sweden',			'sv_SE', 	0),
('Thai', 				'ไทย',				'Thailand',			'th_TH', 	0),
('Thai (Thai digits)',	'ไทย',				'Thailand',			'th_TH_TH', 0),
('Turkish',				'Türkçe',			'Turkey',			'tr_TR', 	0),
('Ukrainian',			'українська мова',	'Ukraine',			'uk_UA', 	0),
('Vietnamese',			'Tiếng Việt',		'Vietnam',			'vi_VN', 	0);


INSERT INTO avatar_image_types (name, layer_index, display_seq) VALUES
('Skin', 1, 10),
('Shoes', 2, 100), -- seq is 100 because we want shoes at the bottom in the UI
('Bottom', 3, 90),
('Top', 4, 80),
('Mouth', 5, 70),
('Beard', 6, 60), -- formerly mustache
('Nose', 7, 50),
('Eyes', 8, 40),
('Eyebrows', 9, 30),
('Hair', 10, 20);

-- Insert of avatar parts with embedded SVG code.
INSERT INTO avatar_images (name, svg_code, avatar_image_type_id, gender_id, race_id, color_qty) VALUES
('Human Male',	 '<path class="color1" d="M65.3 242.4c-1.5-.7-3.1-1.4-4.4-2.3-.4-.2-.7-.6-.9-1-.7-1.1-.1-2.6 1.2-2.8h1.2c2.1.5 4 1.4 6.4 1.6.3 0 .4-.3.2-.5-1.1-.7-2.2-1.4-3-2.3l-.6-.8c-.7-1.4.4-3.1 1.9-2.9l.8.2c2.3 1.1 6 4.4 9.5 6.5 2.1 1 3.6 1 3.5.8-.5-1.1-1.4-6-1-7 .6-1.3 1.9-2.6 2.9-1.6.9.9 1.6 3.3 1.8 4.4.9 4.6 2.9 6.9 7.1 8.6 4.6 1.9 15.2 7.1 15.2 6.9l.3-24.6 14.1.1s.4 32.7.1 46.3c0 1.2-2.8 2.9-4.1 2.9-2.3.1-4.9-.7-7.1-1.8-10.8-5.2-21.3-10.8-32.2-15.8a65.2 65.2 0 00-14.6-4.4c-1.1-.2-1.5-1.4-.2-2.2l3.5-1.3c.2-.1.2-.4 0-.5-2.2-.7-3.8-1.1-5.3-1.8l-.3-.2c-1.7-.9-1.1-3.5.8-3.6h.1c.9 0 1.9.1 2.8.3 2.6.2 2.5-.1.3-1.2zm72.2 155.1c3.9-1.3 4.9-2.9 4.1-7.2-2.7-13.7-4.7-30.4-7-44.7H158v26.6c.1 10.3-.1 20.6.1 30.9.2 8.8-1.1 8.1-8.5 7.8-9.1-.4-18.3 0-27.4 0-1.5 0-3.2.4-4.4-.2l-1.6-.9a3.8 3.8 0 01-.3-6.1l21.6-6.2zm59.6-52.4c-1.3 8.2-2.6 18.5-3.8 25.7a706 706 0 01-3.6 20.1c-.6 3.3.2 5.3 3.6 6.4a270 270 0 0119.6 7.2c2.1.9 5.2 1.1 5.2 2.7.1 3.7-3.6 3.6-6.4 3.6h-32.9c-4.1 0-5.8-2-5.7-5.8.2-7.5.7-12.9.7-20.4-.1-10.5-2-39.7-2-39.7l25.3.2zm25.6-43c1.3 1.1 2.6 3.1 2.6 7.2 0 1.9-.5 7.1-2.4 6.8-1.2-.2-2 .4-2.8.2-.5.2-1.7-1.2-2.1-.8-.8.8-2.4.5-3.2-.1-.2-.2-.6 0-.7.1-.8.5-2.3.6-3.1 0-.6-.5 0-.5-1.2-1.5-.2-.2-.2.8-.2.8-.2 0-1.9 1.5-2.8.7-.7-.6-1.7-5.8-1.7-6.4l.4-4.1c.2-3.4 1.3-4.7 1.5-8.8.2-2.1-.6-70.7-.6-70.7l17-.1-.7 76.7zm-16.5-192.6V104a41.2 41.2 0 00-82.4 0v5.5c-1.2-.2-12.7-1.8-12.6 13 .1 15.6 12.5 12.9 12.6 12.9v1.3c0 15.8 8.9 29.5 21.9 36.4 1.3 6.5 9.4 12.5 19.3 12.5s18.1-5.9 19.4-12.4a41.3 41.3 0 0021.8-36.5l.1-1.3s12.5 2.7 12.6-12.9c.1-15.4-12.3-13.1-12.7-13z"/>\n<path d="M206.2 109.5s12.8-2.6 12.7 13-12.6 12.9-12.6 12.9l-.1-25.9zm-82.3 0s-12.8-2.6-12.7 13c.1 15.6 12.6 12.9 12.6 12.9l.1-25.9z" opacity=".2"/>\n<path opacity=".3" d="M165 177.9c-7 0-13.6-1.7-19.3-4.8 1.3 6.5 9.4 12.5 19.3 12.5s18.1-5.9 19.4-12.4a40.6 40.6 0 01-19.4 4.7zM123.8 112s-10.5-2.1-10.4 10.5 10.4 10.4 10.4 10.4V112zm82.5-.1s10.6-2 10.5 10.5-10.4 10.3-10.4 10.3l-.1-20.8z"/>', 1, 1, 1, 0),
('Men''s Shoes',	 '<g class="color2"><path d="M218.8,405.4c-1.5-4.6-10.2-3.3-28.2-11.9c-0.1,0,1.5-13.8,1.5-13.8h-18.9c0,0,0.1,16.6,0,16.6
c0,0-2.1,6.8-1.2,10.7c1,3.9,3,4.4,3,4.4h42C217.1,411.3,220.3,410,218.8,405.4z"/>
<path d="M112.8,405.4c1.5-4.6,10.2-3.3,28.2-11.9c0.1,0-1.5-13.8-1.5-13.8h18.9c0,0-0.1,16.6,0,16.6
c0,0,2.1,6.8,1.2,10.7c-1,3.9-3,4.4-3,4.4h-42C114.5,411.4,111.3,410,112.8,405.4z"/></g>
<g class="color1"><path d="M158.6 396.3c-7.9.4-12.6 1-17.6-2.8-18 8.6-26.8 7.3-28.3 11.9-1.5 4.6 1.9 6.1 1.9 6.1h42.1s2.1-.6 3.1-4.5c.9-3.9-1.2-10.7-1.2-10.7z"/><path d="M173.1 396.3c7.9.4 12.6 1 17.6-2.8 18 8.6 26.8 7.3 28.3 11.9 1.5 4.6-1.9 6.1-1.9 6.1H175s-2.1-.6-3.1-4.5c-.9-3.9 1.2-10.7 1.2-10.7z"/></g>', 2, 1, 1, 2),
('Shorts',			 '<path class="color1" d="M122.3 288.5l5 57.4h33.2v-26.8c0-2.5 2-4.5 4.5-4.5s4.5 2 4.5 4.5v26.8H203l3-57.4h-83.7z"/>', 3, 1, 1, 1),
('Pants',			 '<path class="color1" d="M122.3,288.5l11,104.2h29.2v-73.6c0-2.5,0-4.5,2.5-4.5s2.5,2,2.5,4.5v73.6H199l7-104.2H122.3z"/>', 3, 1, 1, 1),
('T-Shirt',			 '<path class="color1" d="M190.8 168.9a55 55 0 01-6.4 4.3c-1.3 6.5-9.4 11.5-19.3 11.5s-18-5-19.3-11.5a52.3 52.3 0 01-6.3-4.1c-19.7 5.8-34 24-34 45.6V227h16.4v67.5a47.4 47.4 0 0031.1 11.6h24.7c10.9 0 20.9-3.7 28.9-9.8v-68.7h18.7v-13a48 48 0 00-34.5-45.7z"/>\n<path class="color2" d="M121.9 217.5l21.3-45.9c-1.3-.8-2.6-1.7-3.8-2.7-19.7 5.8-34 24-34 45.6v12.3h16.5v-9.3zM190.8 168.9c-1.2 1-2.5 1.9-3.8 2.7l19.5 46v10h18.7v-13a47.8 47.8 0 00-34.4-45.7z"/>\n<path class="color3" d="M188.1 239.2h-4.2c-6.4 0-11.6-5.2-11.6-11.6v-18.4h27.5v18.4c0 6.4-5.2 11.6-11.7 11.6z"/>', 4, 1, 1, 3),
('Big Smile',		 '<path d="M153.2 150.9c.6 6.7 5.6 12 11.8 12s11.2-5.3 11.8-12h-23.6z" opacity=".3" fill="#ed1c24"/>\n<path fill="#fff" d="M157.9 151.1v.5c0 1.5 1.2 2.6 2.6 2.6h1.5c1.5 0 2.6-1.2 2.6-2.6v-.5h-6.7zM165.3 151.1v.5c0 1.5 1.2 2.6 2.6 2.6h1.5c1.5 0 2.6-1.2 2.6-2.6v-.5h-6.7z"/>', 5, 1, 1, 0),
('Teeth',			 '<path fill="#bf324c" d="M172.9 159h-15.7a4 4 0 01-4-4 4 4 0 014-4h15.7a4 4 0 014 4c.1 2.2-1.7 4-4 4z"/>\n<path fill="#fff" d="M160.1 150.9v.5c0 1.5.9 2.6 1.9 2.6h1.1c1.1 0 1.9-1.2 1.9-2.6v-.5h-4.9zM165.5 150.9v.5c0 1.5.9 2.6 1.9 2.6h1.1c1.1 0 1.9-1.2 1.9-2.6v-.5h-4.9zM173.1 150.9h-2.2v.5c0 1.5.9 2.6 1.9 2.6h1.1c.9 0 1.6-.8 1.9-1.9a4.5 4.5 0 00-2.7-1.2zM154.8 151.8c.1 1.3.9 2.3 1.9 2.3h1.1c1.1 0 1.9-1.2 1.9-2.6v-.5H157c-.8 0-1.6.3-2.2.8z"/>', 5, 1, 1, 0),
('Serious',			 '<path opacity=".8" fill="#b33223" d="M173.4 154.5h-16.6c-.8 0-1.4-.6-1.4-1.4 0-.8.6-1.4 1.4-1.4h16.6c.8 0 1.4.6 1.4 1.4 0 .7-.6 1.4-1.4 1.4z"/>', 5, 1, 1, 0),
('Grimace',			 '<path fill="#bf324c" d="M156.2 158.9c-.7 0-1.4-.2-2.1-.5-1.8-.9-2.3-2.8-1.1-4.1 2.1-2.6 6.1-4.5 11-5.2 4.6-.7 9-.2 11.9 1.3 1.8.9 2.3 2.8 1.1 4.1-1.1 1.4-3.5 1.8-5.3.9-1-.5-3.4-.9-6.4-.4-2.7.4-5 1.4-6 2.5-.6.9-1.9 1.4-3.1 1.4z"/>\n<path fill="#fff" d="M164 149.1l-.7.1v.3c.2 1.4 1.2 2.5 2.2 2.4l1.1-.1c1.1-.1 1.8-1.4 1.6-2.8v-.1a20 20 0 00-4.2.2zM153.5 153.7l.1.2c.8 1.2 2.2 1.7 3.1 1.1l.9-.6c.9-.6 1-2.1.1-3.3l-.1-.2c-1.6.8-3 1.7-4.1 2.8zM160.8 153l1.1-.3c1-.3 1.5-1.7 1.1-3.1l-.1-.2c-1.8.3-3.4.8-4.8 1.4l.1.3c.4 1.3 1.6 2.2 2.6 1.9zM176 150.8l.1-.3-.2-.1a13 13 0 00-4.6-1.3l-.1.2c-.5 1.4 0 2.8 1 3.1l1.1.4c1 .2 2.2-.6 2.7-2z"/>', 5, 1, 1, 0),
('Smile',			 '<path opacity=".8" fill="#b33223" d="M164.8 159.5c-5 0-9.6-1.9-13.1-5.4-1-1-1-2.6 0-3.5 1-1 2.6-1 3.5 0a13.5 13.5 0 0019.2 0c1-1 2.6-1 3.5 0 1 1 1 2.6 0 3.5a18.7 18.7 0 01-13.1 5.4z"/>', 5, 1, 1, 0),
('Clean Shaven', NULL, 6, 1, NULL, 0),
('Big Mustache',	 '<path class="color1" d="M178.6 139h-27.3c-7.4 0-13.4 6-13.4 13.4v1.5h19.6a17 17 0 006.8-7.4 16 16 0 006.8 7.4H192v-1.5c0-7.4-6-13.4-13.4-13.4z"/>', 6, 1, 1, 1),
('Mustache',		 '<path class="color1" d="M172.7 140.3h-15.4a7.5 7.5 0 00-7.5 7.5v.8h30.4v-.8c0-4.2-3.4-7.5-7.5-7.5z"/>', 6, 1, 1, 1),
('Pencil Mustache',	 '<path class="color1" d="M148.9 149.3a2 2 0 01-1-.3c-.6-.5-.7-1.5-.2-2.1 3.4-4.1 9.2-4.4 15.3-4.4.8 0 1.5.7 1.5 1.5s-.7 1.5-1.5 1.5c-5.3 0-10.4.3-12.9 3.4-.3.2-.8.4-1.2.4zM181.1 149.3c-.4 0-.9-.2-1.2-.5-2.6-3.1-7.6-3.4-12.9-3.4-.8 0-1.5-.7-1.5-1.5s.7-1.5 1.5-1.5c6.1 0 11.8.3 15.3 4.4.5.6.4 1.6-.2 2.1-.3.3-.7.4-1 .4z"/>', 6, 1, 1, 1),
('Goatee',			 '<path class="color1" d="M190 157.5c0-22.5-50-22.5-50 0 0 37.5 50 37.5 50 0zm-12.3-10c2.8 0 4.2 2.6 4.2 5.4 0 7.1-6.4 12.1-16.8 12.1s-16.8-5-16.8-12.1c0-2.8 1.3-5.4 4.2-5.4h25.2z"/>', 6, 1, 1, 1),
('Full Beard',		 '<path class="color1" d="M206.3 117.1c0 11.5 1.1 22.6-.4 33.3-2.9 21.4-22.6 35.5-45.1 33.8a41.6 41.6 0 01-37.3-40c-.2-8.9.3-17.8.3-27.4 5.7 26.5 7.7 37.6 14.1 26.8 3.6-5.1 8.2-5.1 12.9-5 10.5.1 20.9-.2 31.4.2 3.3.1 7.5.2 10.5 2.9 2.9 2.8 8.4 15.3 13.6-24.6zm-41.6 47.3c3.4-.2 6.2-.1 9-.6 6.2-1 10.6-6.2 10.5-12-.1-5.9-4.5-3.3-10.7-3.8-5.7-.5-11.6-.5-17.3.2-6.1.7-10-2-10 3.8a12 12 0 009.7 11.7c3 .6 6.2.5 8.8.7z"/>', 6, 1, 1, 1),
('Medium',		 '<path opacity=".6" d="M171.1 137.4h-1.4s-2.2 1.8-4.7 1.8-4.7-1.8-4.7-1.8h-1.4c-1.3 0-2.2-1.6-2.3-3.7-.1 2.6.7 4.7 2.3 4.7h1.4s2.2 1.8 4.7 1.8 4.7-1.8 4.7-1.8h1.4c1.6 0 2.4-2.1 2.3-4.7-.1 2.1-.9 3.7-2.3 3.7z"/>\n<path class="color1" d="M169.6 126.3h-.2l-1.3-6.4c-.2-1.8-1.6-3.1-3.1-3.1s-2.9 1.3-3.1 3.1l-1.3 6.4h-.2c-1.4 0-3 2-3.6 4.8-.7 3.3.2 6.3 2.1 6.3h1.4s2.2 1.8 4.7 1.8 4.7-1.8 4.7-1.8h1.4c1.9 0 2.8-3.1 2.1-6.3-.6-2.8-2.1-4.8-3.6-4.8z" opacity=".6" />\n<circle opacity=".2" fill="#fff" cx="165" cy="134.5" r="2.5"/>', 7, 1, 1, 1),
('Large',		 '<path class="color1" d="M171.8,128.1h-1.1v-10.6c0-3.1-2.5-5.5-5.7-5.5s-5.7,2.5-5.7,5.5v10.6h-1.1c-2.9,0-5.3,2.3-5.3,5.2 s2.4,5.2,5.3,5.2h13.5c2.9,0,5.3-2.3,5.3-5.2S174.7,128.1,171.8,128.1z" opacity="0.5" />\n<path d="M161.8,138.1c0-0.8-1.2-1.4-2.6-1.4s-2.6,0.6-2.6,1.4c0,0.1,0,0.3,0.1,0.4c0.2,0,0.4,0,0.5,0h4.4 C161.8,138.4,161.8,138.2,161.8,138.1z" opacity=".6"/>\n<path d="M173.3,138.1c0-0.8-1.2-1.4-2.6-1.4s-2.6,0.6-2.6,1.4c0,0.1,0,0.3,0.1,0.4c0.2,0,0.4,0,0.5,0h4.4 C173.3,138.4,173.3,138.2,173.3,138.1z" opacity=".6"/><ellipse fill="#fff" opacity=".15" cx="165" cy="130.5" rx="6.9" ry="2.5"/>', 7, 1, 1, 1),
('Round',		 '<path opacity=".5" class="color1" d="M165 137.2a6.5 6.5 0 01-6.5-6.5v-6.5a6.5 6.5 0 1113 0v6.5c0 3.5-2.9 6.5-6.5 6.5z"/>\n<path opacity=".3" d="M165 137.2a6.5 6.5 0 01-6.5-6.5v1a6.5 6.5 0 1013 0v-1c0 3.5-2.9 6.5-6.5 6.5z"/>\n<ellipse opacity=".15" fill="#fff" cx="165" cy="129.8" rx="3.2" ry="4"/>', 7, 1, 1, 1),
('Small Round', '<path opacity=".5" class="color1" d="M165 136a5.6 5.6 0 01-5.6-5.6v-1.6a5.6 5.6 0 1111.2 0v1.6c0 3.1-2.5 5.6-5.6 5.6z"/>\n<ellipse opacity=".15" fill="#fff" cx="165" cy="131.2" rx="2.8" ry="2.6"/>\n<path opacity="0.3" d="M165 136c-3.1 0-5.6-1.9-5.6-4.3v.7c0 2.4 2.5 4.3 5.6 4.3s5.6-1.9 5.6-4.3v-.7c0 2.4-2.5 4.3-5.6 4.3z"/>', 7, 1, 1, 1),
('Triangular',	 '<path class="color1" opacity=".5" d="M163.9 121.6l-4.4 13.9c-.3.8.4 1.7 1.2 1.7h8.8c.9 0 1.5-.8 1.2-1.7l-4.4-13.9c-.3-1.2-2-1.2-2.4 0z"/>\n<path d="M163.6 137c0-.4-.5-.7-1.1-.7s-1.1.3-1.1.7l.1.2h2.1v-.2zm5.1 0c0-.4-.5-.7-1.1-.7s-1.1.3-1.1.7l.1.2h2.1c-.1-.1 0-.2 0-.2z" opacity=".8"/>', 7, 1, 1, 1),
('Small Eyes',		 '<circle class="color1" cx="144.3" cy="111.1" r="4.6"/>\n<circle class="color1" cx="185.7" cy="111.1" r="4.6"/>', 8, 1, 1, 1),
('Sleepy Eyes',		 '<path class="color1" d="M151.1 107h-13.9a1.9 1.9 0 100 3.8h1.5v.5c0 3 2.4 5.3 5.3 5.3s5.3-2.4 5.3-5.3v-.5h1.7c1.1 0 1.9-.9 1.9-1.9.2-1-.7-1.9-1.8-1.9zM192.7 107h-13.9a1.9 1.9 0 100 3.8h1.5v.5c0 3 2.4 5.3 5.3 5.3s5.3-2.4 5.3-5.3v-.5h1.7c1.1 0 1.9-.9 1.9-1.9.2-1-.7-1.9-1.8-1.9z"/>', 8, 1, 1, 1),
('Medium Eyes',		 '<circle fill="#fff" cx="144.3" cy="111.1" r="6.6"/>\n<circle class="color1" cx="144.3" cy="111.1" r="4.6"/>\n<circle fill="#fff" cx="185.7" cy="111.1" r="6.6"/>\n<circle class="color1" cx="185.7" cy="111.1" r="4.6"/>', 8, 1, 1, 1),
('Wide Eyes',		 '<ellipse transform="rotate(-3.1 143.7 110.8)" fill="#fff" cx="144.2" cy="110.9" rx="9.6" ry="5.8"/>\n<circle class="color1" cx="144.2" cy="110.8" r="4.9"/>\n<ellipse transform="rotate(-86.8 185.9 110.9)" fill="#fff" cx="185.9" cy="110.9" rx="5.8" ry="9.6"/>\n<circle class="color1" cx="185.8" cy="110.8" r="4.9"/>', 8, 1, 1, 1),
('Narrow Eyes',		 '<path fill="#FFF" d="M179.3,109.6c0.1,3.7,3.1,6.7,6.8,6.7c3.7,0,6.7-3,6.8-6.7H179.3z"/>
<path class="color1" d="M181,109.6c0,2.7,2.3,5,5,5s5-2.2,5-5H181z"/>
<path fill="#FFF" d="M137.2,109.6c0.1,3.7,3.1,6.7,6.8,6.7c3.7,0,6.7-3,6.8-6.7H137.2z"/>
<path class="color1" d="M138.9,109.6c0,2.7,2.3,5,5,5c2.7,0,5-2.2,5-5H138.9z"/>', 8, 1, 1, 1),
('Straight','<path class="color1" d="M154.3 103.9h-18.4a3.7 3.7 0 110-7.4h18.4c2 0 3.7 1.6 3.7 3.7a3.8 3.8 0 01-3.7 3.7zM194.2 103.9h-18.4a3.7 3.7 0 110-7.4h18.4c2 0 3.7 1.6 3.7 3.7a3.8 3.8 0 01-3.7 3.7z"/>', 9, 1, 1, 1),
('Arched',	 '<path class="color1" d="M151.3 103.8c-.8 0-1.5-.3-2.1-.9a7.2 7.2 0 00-10.2 0 2.9 2.9 0 01-4.2 0 2.9 2.9 0 010-4.2 13.2 13.2 0 0118.6 0 2.9 2.9 0 010 4.2c-.6.6-1.3.9-2.1.9zM193.1 103.8c-.8 0-1.5-.3-2.1-.9a7.1 7.1 0 00-5.1-2.1c-1.9 0-3.7.8-5.1 2.1a2.9 2.9 0 01-4.2 0 2.9 2.9 0 010-4.2 13.2 13.2 0 0118.6 0 2.9 2.9 0 010 4.2c-.6.6-1.3.9-2.1.9z"/>', 9, 1, 1, 1),
('Angry',	 '<path class="color1" d="M156.1 103.1l-23.4-1.8a.5.5 0 01-.3-.9l6.9-6.8c.2-.2.4-.2.6-.1l16.5 8.6c.6.2.3 1-.3 1zM173.9 103.1l23.4-1.8c.5 0 .7-.6.3-.9l-6.9-6.8c-.2-.2-.4-.2-.6-.1l-16.5 8.6c-.6.2-.3 1 .3 1z"/>', 9, 1, 1, 1),
('Slanted', '<path class="color1" d="M156.3 100.4l-24.9 3.5c-1.5.2-2.8-.8-3-2.3-.2-1.5.8-2.8 2.3-3l24.9-3.5c1.5-.2 2.8.8 3 2.3.2 1.5-.8 2.8-2.3 3zM173.7 100.4l24.9 3.5c1.5.2 2.8-.8 3-2.3.2-1.5-.8-2.8-2.3-3l-24.9-3.5c-1.5-.2-2.8.8-3 2.3-.2 1.5.8 2.8 2.3 3z"/>', 9, 1, 1, 1),
('Round',	 '<path class="color1" d="M155.4 102c-2.3-3.2-6.5-5.3-11.2-5.3s-8.8 2.1-11.2 5.3h22.4zM196.9 102c-2.3-3.2-6.5-5.3-11.2-5.3s-8.8 2.1-11.2 5.3h22.4z"/>', 9, 1, 1, 1),
('Bald', NULL, 10, 1, NULL, 0),
('Right',		 '<path class="color1" d="M150.8 50.8h25.4c19.3 0 27.4 7.1 30.2 26.2 1.7 11.4 1.5 22.9 1.1 34.3-.1 2-.2 3.6-2.9 3.5-2.7-.1-3.9-1.1-3.9-3.9V90c-.1-5.2-2-6.4-6.4-3.9a27.8 27.8 0 01-14.3 3.7c-15.6-.1-31.2.7-46.8-.6-3.1-.3-2.8 1.3-2.8 3.1v18c0 2.8-.8 4.4-3.9 4.4-3.3.1-2.7-2.3-2.9-4.1-.5-7-.9-13.9-.1-20.9.3-2.6-.1-4.6-2.4-6.5a17.1 17.1 0 01-6.2-14.1c0-4.8.2-9.6-.1-14.5-.1-3.1.8-4 3.9-4 10.9.3 21.5.2 32.1.2z"/>', 10, 1, 1, 1),
('Left',		 '<path class="color1" d="M206.9 81.5c0 10.3.1 20.6-.1 30.9 0 1.5 1.2 4.2-1.9 4.1-2.7-.1-2-2.6-2-4.3l-.1-12.5c0-2.9 1.1-6.3-4.1-6.6-2.5-.1-1.8-3.3-1.6-5.1.6-5-1-7.3-6.4-6.6-3.6.5-7.3.1-10.9.1-3.3 0-6.6-.6-9.2 3-1.7 2.4-10.5 2.6-12.3.1-1.3-1.8-2.6-3.1-4.5-3.1-5.5.1-11 .2-16.4-.1-3.7-.2-4.5 1.2-4.3 4.6.1 3.3 1.6 7.8-4.5 7.7-1.7 0-1.2 2.1-1.2 3.3v15.5c0 1.8.3 4.1-2.4 4-2.3-.1-1.4-2.3-1.4-3.6.3-12.3-1.4-24.7.8-36.8a36.2 36.2 0 0136-29.9c14.1-.2 28.2.1 42.3-.1 3.5 0 4.5 1.1 4.4 4.5-.3 10.2-.2 20.6-.2 30.9z"/>', 10, 1, 1, 1),
('Done Up',			 '<path class="color1" d="M122.4 107.4c0-10.6 2.3-20.4 8.7-29.1 1.4-1.9 2-3.7.6-5.9-6.5-10.7 1-27.4 17.7-28.2 10.4-.5 20.9-.5 31.4 0 15 .7 23.4 14 17.8 27.9-1.1 2.7-.8 4.6.9 6.8 7.6 10 8.5 21.7 7.8 33.7-.1 1.6-.3 3.1-2.3 3.2-2.3.1-2.7-1.6-2.8-3.4l-.1-13.5c0-1.8.3-4-1.7-4.9-4.6-2.2-6.2-6.3-7.4-10.6-.6-2.2-1.8-3.1-4-2a26 26 0 01-13.2 1.9c-11.5-.7-23.2 1.8-34.6-1.8-2.1-.7-2.4 1.2-2.8 2.6-1.3 4.3-3 8.1-7.7 9.7-2.1.7-2.3 2.6-2.3 4.5l-.1 13.5c0 2 0 4.3-2.9 4.3s-2.9-2.2-3-4.2v-4.5z"/>', 10, 1, 1, 1),
('Mohawk',			 '<path class="color1" d="M165 82.7a6.2 6.2 0 01-6.2-6.2V38.3l2.9 7 3.3-7 3.2 7 3-7v38.2c0 3.4-2.8 6.2-6.2 6.2z"/>', 10, 1, 1, 1),
('Down',			 '<path class="color1" d="M206.4 112l-.3-5.3c-.1-5.3-.3-8.8-6.7-10.5-4.6-1.2-8.2-5.9-12.4-9.1l-.4 8.2c-8.7 1.5-14.5-3.1-20.1-9.7l-.6 9.8c-9.4.8-15.9-2.9-20.2-11.6-4.2 7.5-10.1 11.7-18.5 12.1-1 .1-2.6 2-2.8 3.2-.8 4-.1 8.2-.6 12.1-9.6-10.5-7.9-34.7 4.7-48.5a50.8 50.8 0 0166.7-5.9 41.7 41.7 0 0111.2 55.2z"/>', 10, 1, 1, 1),
('Bowl Cut',		 '<path class="color1" d="M132.4 87c-.6 6.5-7.6 8.3-7.6 8.3l-.6 3.4s-7.9-4.4-7.8-7.8a48.6 48.6 0 0197.1.2c.4 5.5-7.8 7.3-7.8 7.3s-2.8-2.9-4.6-2.9l-53.3.4c-6.1.1-11.6-1.1-15.4-8.9z"/>', 10, 1, 1, 1),
('Afro',			 '<path class="color1" d="M123 109.3l-5.9 1.5c-11.7-12-16.5-43.8 4.2-66a61.3 61.3 0 0182.7-4.3c21.3 18.6 25.6 51 9.2 70.4l-5.6-1.4-.6 1.1-1.8 5.4c-.7-1.9-1.7-3.7-2-5.6-.3-2.4-.1-5-.1-7.5.1-4.2.6-8.1-4.9-10.4-2.4-1-3.5-5.7-4.7-8.9-1-2.7-2.1-3.9-5.2-3.9-15.1.2-30.2.1-45.3 0-2.7 0-4.3.4-4.9 3.4-1.1 5.2-3 9.9-9 11.4-.8.2-1.6 2.3-1.7 3.5-.2 4.7.1 9.3-.2 14-.1 1.5-1.1 2.9-1.8 4.4-.7-1.4-1.5-2.7-2-4.2-.3-.7-.3-1.5-.4-2.9z"/>', 10, 1, 1, 1),
('Neat',			 '<path class="color1" d="M206.1 110.5c-4.2-2-5.6-5.1-5.3-9.1.3-3.3-.4-5.3-4.1-6.6-6.1-2.2-8.2-7.8-9.1-14.1a95.9 95.9 0 01-46.1-.1c-.5 7.7-4 12.9-11.3 15.5-.9.3-1.3 3.1-1.3 4.8-.1 4.3-1.1 7.8-5.5 9.5a40 40 0 018.8-42.4 49 49 0 0166.8 1.9 38 38 0 017.1 40.6z"/>', 10, 1, 1, 1),
('Serious',			 '<path class="color1" d="M206.5 109.7a36.7 36.7 0 01-11.6-22.2c-3.8 5.6-8.8 6.1-14.3 6-14.3-.2-28.6 0-42.9-.1-2.9 0-4.3.9-5.5 3.6-2.2 4.5-5.2 8.6-8.1 13.4-8.1-13.7-4.7-35.6 9.7-48.6a47.1 47.1 0 0159.3-2.4 41.6 41.6 0 0113.4 50.3z"/>', 10, 1, 1, 1),
('Bed Head',		 '<path class="color1" d="M225.7 85.5l-6.9 12.9 4.8.7c.8 5.2-1.5 9.8-6.6 12-4.6 2-9.2-.5-11.8-3.6-3.7-4.4-6-10.1-8.8-15.3-.7-1.3-.8-2.8-1.3-5.1-5.5 7.8-13.1 5.7-20.3 5.8l-.7-5.8C170 93.9 163.9 93 158 93c-6.6 0-13.3.1-19.9-.1-2.9-.1-4.4.8-5.5 3.6-2.7 6.5-6.1 12.3-13.8 14.6-2.8.8-4.6.9-6.8-.7-3.6-2.7-6.1-6-5.9-11.2l5.6-.7-7.3-12.8h13.8c5.3-8.3 8.8-16.5 14.5-22.5a45.5 45.5 0 0176.1 18.7c.9 3.1 2.3 3.9 5.2 3.8 3.2-.4 6.5-.2 11.7-.2z"/>', 10, 1, 1, 1),
('Long Hair',		 '<path class="color1" d="M220.9 112c0-4.3-.4-8.6-1.1-12.9A56.3 56.3 0 00161 51.9a57 57 0 00-51.5 53.8c-.7 19.1-.3 38.2-.3 57.3 0 .9.3 1.9.4 2.9H136a41.2 41.2 0 01-12.2-29.2v-1.3c-.1 0-12.5 2.7-12.6-12.9-.1-10.7 5.9-12.8 9.7-13.1 1 .2 1.9.3 2.9.1 21.4-5 31.5-19.6 39.7-39.3.4-.9 1-1.7 1.6-2.6.5.4.7.5.7.7l1 2.3c8.1 19.5 18.3 34 39.5 39.1 1 .2 2.1.1 3.2-.1 3.8.4 9.5 2.6 9.5 13.1-.1 15.6-12.6 12.9-12.6 12.9l-.1 1.3c0 11.5-4.7 21.9-12.3 29.4h27l-.1-54.3z"/>', 10, 1, 1, 1),
('Human Female', '<path class="color1" d="M160.5 276.2l-35.4-12.5s11.8 72.5 14.8 81.2c0 12.5 7.4 50 7.4 50s-13.3 6.2-17.7 7.5c-4.4 1.2 0 3.8 0 3.8h32.5c1.5 0 0-10 0-10l-1.6-120zM169.5 276.2l35.4-12.5s-11.8 72.5-14.8 81.2c0 12.5-7.4 50-7.4 50s13.3 6.2 17.7 7.5 0 3.8 0 3.8h-32.5c-1.5 0 0-10 0-10l1.6-120zM140 154.8c20.4-4.5 34-4.4 48.4-.3 13 2.5 30.7 20.5 33.2 44.9-4.7 2.3-107.5.7-112.7-1.9 1-19.6 14.8-37.8 31.1-42.7z"/>\n<path opacity=".2" d="M140 154.8c20.4-4.5 34-4.4 48.4-.3 13 2.5 30.7 20.5 33.2 44.9-4.7 2.3-107.5.7-112.7-1.9 1-19.6 14.8-37.8 31.1-42.7z"/>\n<path class="color1" d="M205.8 93.4c-2.9-35.7-20.7-47.2-40.8-47.2s-37.9 11.5-40.8 47.2c-7.8 1.1-9.3 8.8-6.8 16.1 2.9 8.4 3.8 11.4 8.4 10.5 7 27.1 30.8 42.4 39.3 42.4s32.2-15.3 39.3-42.4c4.6.9 5.5-2.1 8.4-10.5 2.3-7.4.8-15.1-7-16.1zM108.7 197.4l15.7 7.6s.7 17.8.5 25.6c-.1 2.2-1.5 12.2-1.2 12 7.2-3.6 16.6-7.6 22.9-11.2 1.6-.9.5-5.5 1.6-7.4.4-.8.8-2.8 1.4-3.7 1.8-1.9 5.1-7 5.5-5.2.1.5.7 3.8-1.2 6.2-2.3 3-.6 2.5-.3 2.4a566 566 0 0110.1-6c3-1.7 5 .3 3.2 2.3-1.7 1.2-6.5 4.1-7.4 5.4-.2.3.2 1.3.6 1.2 2.1-.8 6.5-2.4 8.7-2.8 1-.2 2.9-.4 3.5 0 .6.3.7.7.5 1.4-.3 1.1-3.7 2-4.6 2.5-2.3.6-3.2 1.1-5.4 2-1.6.9-1.6 1.8.9 1.6l7.3-1.4c4.1-.9 4.4 1.6.7 3-1.9.9-5.4 1.6-7.9 2.2-.3.1-.7 1 0 .9.7.1 1.9.6 3 .5 3.3-.5 6.7 1.4 1.6 2.7-2.3.6-5.3.3-6.6.4-2.5.1-5.9-.2-9.7 1.5-9.4 4.2-18.3 12.8-27.6 17.2-10.4 4.9-13.6 4.1-14.8-7.3-2.7-24.3-1.7-28.5-1.7-28.5l.7-25.1zM219 247.4a250 250 0 001.9-49.7c-3.1 1.3-14.9 5.5-14.9 5.5 1.6.5-2.9 45.7-3.1 62 11.3-7.9 12.4-.9 16.1-17.8z"/>\n<path opacity=".3" d="M205.8 93.4l.3 4.6c4 1.2 4.6 5.7 3.2 10-1.6 4.6-2.1 8-4.2 8.2l-.9 3.9c4.6.9 5.5-2.1 8.4-10.5 2.5-7.5 1-15.2-6.8-16.2zM120.7 107.9c-1.5-4.3-.8-8.7 3.2-10l.3-4.6c-7.8 1.1-9.3 8.8-6.8 16.1 2.9 8.4 3.8 11.4 8.4 10.5l-.9-3.9c-2.1-.1-2.6-3.4-4.2-8.1z"/>\n<path opacity=".5" d="M205.1 116.1c2.1-.2 2.6-3.5 4.2-8.2 1.5-4.3.8-8.7-3.2-10l.1 6.3c0 4.2-.4 8.2-1.1 11.9zM120.7 107.9c1.6 4.6 2.1 8 4.2 8.2a65 65 0 01-1.1-11.9l.1-6.3c-4 1.3-4.6 5.8-3.2 10z"/>\n<g opacity=".2" fill="#FF2316">\n<circle cx="136.5" cy="118.9" r="7.2"/>\n<circle cx="193.5" cy="118.9" r="7.2"/>\n</g>', 1, 2, 1, 1),
('Shoes', '<g class="color2">\n<path d="M167.9 386.1c5.4.9 11.2 1.1 16.7.1l-1.7 8.7s13.3 6.2 17.7 7.5-.1 3.9-.1 3.9h-32.6c-1.4 0 0-10.1 0-10.1v-10.1zM162.1 386.1c-5.4.9-11.2 1.1-16.7.1l1.7 8.7s-13.3 6.2-17.7 7.5c-4.4 1.2.1 3.9.1 3.9h32.6c1.4 0 0-10.1 0-10.1v-10.1z"/>\n</g>\n<g class="color1">\n<path d="M167.5 396.2l.1-.5c3 1.4 6.6 2.8 17.3 0l1.9.9c-1 2.2-5.4 2.6-8.8 3.5 0 0 12.6 4.1 17.1 0l7.5 2.6c3.6 2-1.5 4.9-1.5 4.9h-33.6c-1.5 0 0-11.4 0-11.4zM162.5 396.2l-.1-.5c-3 1.4-6.6 2.8-17.3 0l-1.9.9c1 2.2 5.4 2.6 8.8 3.5 0 0-12.6 4.1-17.1 0l-7.5 2.6c-3.6 2 1.5 4.9 1.5 4.9h33.6c1.5 0 0-11.4 0-11.4z"/>\n</g>', 2, 2, 1, 2),
('T-Shirt', '<path class="color1" d="M188.4 154.5c-16.2 24-30.8 22.7-48.4.3-16.3 5-30.3 23.5-31.3 43.2l15.8 7.6.2 5.8c.2 5.7.4 14.3.3 19.1l-.3 11.6c7.1-3.5 16-7.3 22-10.7 1.6-.9.5-5.5 1.6-7.4.4-.8.8-2.8 1.4-3.7 1.8-1.9 5-6.9 5.4-5.1.1.5.7 3.7-1.2 6.1-2.3 3-.6 2.6-.3 2.5a566 566 0 0110.1-6c3-1.7 5 .1 3.1 2.1-1.7 1.2-6.5 4.1-7.4 5.4-.2.3.2 1.5.6 1.4 2.1-.8 6.5-2.4 8.7-2.8 1-.2 2.8-.5 3.4-.1.6.3.8.6.6 1.3-.3 1.1-3.7 2-4.6 2.5-2.3.6-3.4 1.2-5.6 2.1-1.6.9-1.4 2 1.1 1.8l7.3-1.5c4.1-.9 4.3 1.5.6 2.9-1.9.9-5.5 1.5-8 2.1-.3.1-.6 1.1.1 1.2.7.1 1.9.6 3.1.5 2.9-.4 5.8.8 2.9 2H207v-32.1l14.5-7.1c-2.4-24.4-20.1-42.5-33.1-45z"/>', 4, 2, 1, 1),
('Skirt', '<path class="color1" d="M217.8 308.6c-3.3-22-6.9-43.9-10.2-65.9-.4-2.9.2-4-2.6-4h-35.3l-1.4.4c-2.3.6-5.3.3-6.6.4-2.5.1-5.9-.2-9.7 1.5-9.4 4.2-18.3 12.8-27.6 17.2-1.5.7-2.8 1.3-4 1.7a99 99 0 00-2.1 11.7c-2.2 17.3-5.7 38.1-7.4 55.4-1 9.7 41.1 15.4 53.9 15.4 13.1 0 54.5-5.7 54.6-14.8-.1-5-.8-13.9-1.6-19z"/>\n  <path class="color2" d="M205 238.6h-35.3l-1.4.4c-2.3.6-5.3.3-6.6.4-2.5.1-5.9-.2-9.7 1.5-9.4 4.2-18.3 12.8-27.6 17.2-1.5.7-2.8 1.3-4 1.7l-.4 2c12.9 1.2 35.8 1.9 44.7 1.9 9.3 0 33-.7 45.9-1.9l-3-19.2c-.4-2.8.2-4-2.6-4z"/>', 3, 2, 1, 2),
('Pants', '<path class="color1" d="M207.2 242.6c.2-2.9 0-4.1-.2-4h-37.3l-1.4.4c-2.3.6-5.3.3-6.6.4-2.5.1-5.9-.2-9.7 1.5-9.4 4.2-18.3 12.8-27.6 17.2-1.4.7 11.1 79.6 13.5 86.8 0 12.5 8.4 49 8.4 49l16.3.2a555 555 0 00-1.3-50.3c.4-1.1 1.6-37.7 1.4-58 0-1.3 1.1-3.3 2.3-3.3h.1c1.3 0 2.4 2 2.3 3.3-.2 20.3.9 56.8 1.4 58-.5 1.8-1.6 31.9-1.3 50.3l16.3-.2s8.4-36.5 8.4-49c2.4-7 14.5-94.5 15-102.3z"/>\n  <path class="color2" d="M206.7 250.2l.9-7.6c.2-2.9 0-4.1-.2-4h-37.6l-1.4.4c-2.3.6-5.3.3-6.6.4-2.5.1-5.9-.2-9.7 1.5-5.1 2.3-10 5.8-14.9 9.3h69.5z"/>', 3, 2, 1, 2),
('Smiling Eyes', '<path fill="#fff" d="M140.5 109.8a7.5 7.5 0 1115 0M174.5 109.8a7.5 7.5 0 1115 0"/>\n  <g class="color1">\n    <path d="M142.1 109.8c0-3.3 2.7-5.9 5.9-5.9s5.9 2.7 5.9 5.9M176.1 109.8c0-3.3 2.7-5.9 5.9-5.9s5.9 2.7 5.9 5.9"/>\n  </g>', 8, 2, 1, 1),
('Elliptical Eyes', '<ellipse fill="#fff" transform="rotate(-83 148.5 107.1)" class="st15" cx="148.5" cy="107.1" rx="5.6" ry="8.1"/>\n  <ellipse fill="#fff" transform="rotate(-7 181.4 107)" class="st15" cx="181.5" cy="107.1" rx="8.1" ry="5.6"/>\n  <g class="color1">\n    <circle cx="148.5" cy="107.1" r="4.5"/>\n    <circle cx="181.5" cy="107.1" r="4.5"/>\n  </g>', 8, 2, 1, 1),
('Eyelash', '<path d="M144.5 102.5c-.3-.3-.6-.7-.7-1.2-.1-.5-.7-.9-1.2-.7-.5.1-.9.7-.7 1.2.3 1.1 1 2 1.9 2.6-.1 0 .3-1.3.7-1.9zM185.5 102.5c.3-.3.6-.7.7-1.2.1-.5.7-.9 1.2-.7.5.1.9.7.7 1.2-.3 1.1-1 2-1.9 2.6.1 0-.3-1.3-.7-1.9z"/>\n<g class="color1"><circle cx="148.2" cy="105" r="4.5"/><circle cx="181.8" cy="105" r="4.5"/></g>', 8, 2, 1, 1),
('Heavy Eyelids', '<g class="color1"><circle class="st17" cx="148.1" cy="104.9" r="4.6"/><circle class="st17" cx="181.9" cy="104.9" r="4.6"/></g>\n<path class="color2" d="M153.4 101.1c-2.5-2.4-5.2-3-8.8-1.5-.6.2-2.9 1.5-4.6-.7-.4-.5-1.4-1.5-1.6-1.4-.3.2-.1 1.5.1 1.8a5.4 5.4 0 002.5 2.1c-.8.5-1.8 1.3-2.5 2.2-.7.9-1.6 2.7.8 1.7l.5-.3c7.6-5.7 10.1-4.3 12-2.4.4.4 1.2.4 1.6 0a1 1 0 000-1.5zM176.6 101.1c2.5-2.4 5.2-3 8.8-1.5.6.2 2.9 1.5 4.6-.7.4-.5 1.4-1.5 1.6-1.4.3.2.1 1.5-.1 1.8a5.4 5.4 0 01-2.5 2.1c.8.5 1.8 1.3 2.5 2.2.7.9 1.6 2.7-.8 1.7l-.5-.3c-7.6-5.7-10.1-4.3-12-2.4-.4.4-1.2.4-1.6 0a1 1 0 010-1.5z"/>', 8, 2, 1, 2),
('Eyelids', '<path class="color2" d="M140.2 103.8h15.4l-.6-1.5a7.6 7.6 0 00-11.7-3.5l-1.6-1.6a.6.6 0 00-.8 0c-.2.2-.2.6 0 .8l1.5 1.6-.5.6-1.6-1.6a.6.6 0 00-.8 0c-.2.2-.2.6 0 .8l1.8 1.8-.5 1.1-.6 1.5zM189.8 103.8h-15.4l.6-1.5a7.6 7.6 0 0111.7-3.5l1.6-1.6c.2-.2.6-.2.8 0 .2.2.2.6 0 .8l-1.5 1.6.5.6 1.6-1.6c.2-.2.6-.2.8 0 .2.2.2.6 0 .8l-1.8 1.8.5 1.1.6 1.5z"/>\n<circle fill="#fff" cx="147.9" cy="104.9" r="6.5"/>\n<circle fill="#fff" cx="182.1" cy="104.9" r="6.5"/>\n<g class="color1"><circle cx="147.9" cy="104.9" r="4.6"/><circle cx="182.1" cy="104.9" r="4.6"/></g>', 8, 2, 1, 2),
('Medium', '<path opacity=".6" d="M169.2 124.5h-2.6c-.5.4-1 .6-1.6.6-.6 0-1.1-.2-1.6-.6h-2.6c-1.5 0-2.7-1.4-2.8-3.3v.3c0 2 1.3 3.6 2.9 3.6h2.6c.5.4 1 .6 1.6.6.6 0 1.1-.2 1.6-.6h2.6c1.6 0 2.9-1.6 2.9-3.6v-.3c-.3 1.8-1.5 3.3-3 3.3z"/>\n  <path opacity=".6" class="color1" d="M169.2 117.3H168l-1-8.1c0-2.1-1.4-2.8-2-2.8s-2 .7-2 2.8l-1 8.1h-1.2c-1.6 0-2.9 1.6-2.9 3.6s1.3 3.6 2.9 3.6h2.6c.5.4 1 .6 1.6.6.6 0 1.1-.2 1.6-.6h2.6c1.6 0 2.9-1.6 2.9-3.6s-1.4-3.6-2.9-3.6z"/>', 7, 2, 1, 1),
('Large', '<path class="color1" opacity=".6" d="M170.2,117.3h-1.5l-1-8.1c0-3.5-5.5-3.5-5.5,0l-1,8.1h-1.5c-2,0-3.6,1.6-3.6,3.6\n	s1.6,3.6,3.6,3.6h3.2c0.6,0.4,1.3,0.6,2,0.6s1.4-0.2,2-0.6h3.2c2,0,3.6-1.6,3.6-3.6S172.2,117.3,170.2,117.3z"/>\n<path opacity=".6" d="M170.2,124.5H167c-0.6,0.4-1.3,0.6-2,0.6s-1.4-0.2-2-0.6h-3.2c-1.9,0-3.4-1.4-3.6-3.3c0,0.1,0,0.2,0,0.3\n	c0,2,1.6,3.6,3.6,3.6h3.2c0.6,0.4,1.3,0.6,2,0.6s1.4-0.2,2-0.6h3.2c2,0,3.6-1.6,3.6-3.6c0-0.1,0-0.2,0-0.3\n	C173.6,123,172.1,124.5,170.2,124.5z"/>', 7, 2, 1, 1),
('Rounded', '<path opacity=".6" d="M165.9,123h-1.7c-1.7,0-3.1-1.2-3.5-2.9c0,0-0.1,0-0.1,0v0c0,2,1.6,3.6,3.6,3.6h1.6c2,0,3.6-1.6,3.6-3.6l0,0\nc0,0-0.1,0-0.1,0C169,121.8,167.6,123,165.9,123z"/>\n<path opacity=".6" class="color1" d="M165.8,123h-1.6c-2,0-3.6-1.6-3.6-3.6v-4.1c0-2,1.6-3.6,3.6-3.6h1.6\nc2,0,3.6,1.6,3.6,3.6v4.1C169.4,121.5,167.8,123,165.8,123z"/>', 7, 2, 1, 1),
('Triangular', '<path opacity=".6" d="M169.7,122.5c-0.1,0.4-0.5,0.7-0.9,0.7h-7.7c-0.5,0-0.8-0.3-0.9-0.7l0,0c-0.2,0.6,0.3,1.3,1,1.3\n	h7.7C169.4,123.8,169.9,123.2,169.7,122.5L169.7,122.5z"/>\n<path opacity=".6" class="color1" d="M164,110.1l-3.8,11.7c-0.2,0.6,0.3,1.3,1,1.3h7.7c0.7,0,1.2-0.7,1-1.3l-3.8-11.7\n	C165.6,109.2,164.3,109.2,164,110.1z"/>', 7, 2, 1, 1),
('Round', '<path opacity=".6" d="M165,122.6c-1.7,0-3.2-2-3.3-4.6c0,0.1,0,0.2,0,0.2c0,2.7,1.5,4.8,3.3,4.8s3.3-2.2,3.3-4.8c0-0.1,0-0.2,0-0.2\n	C168.2,120.6,166.7,122.6,165,122.6z"/>\n<ellipse opacity=".6" class="color1" cx="165" cy="117.8" rx="3.3" ry="4.8"/>', 7, 2, 1, 1),
('Teeth', '<path fill="#C86669" d="M173.5,142.7h-17c-3.1,0-5.7-2.5-5.7-5.7v0c0-3.1,28.3-3.1,28.3,0v0C179.2,140.2,176.6,142.7,173.5,142.7z"\n	/>\n<path fill="#FF978A" d="M165.1,139.8c-3.6,0-8.2,3-8.2,3h16.4C173.3,142.7,168.7,139.8,165.1,139.8z"/>\n<path fill="#fff" d="M160.6,134.8v2c0,0.4-0.3,0.7-0.7,0.7h-2.4c-0.4,0-0.7-0.3-0.7-0.7v-1.7C158,135,159.3,134.9,160.6,134.8z"\n	/>\n<path fill="#fff" d="M164.8,134.7v2.1c0,0.4-0.3,0.7-0.7,0.7h-2.4c-0.4,0-0.7-0.3-0.7-0.7v-2\n	C162.3,134.7,163.5,134.7,164.8,134.7z"/>\n<path fill="#fff" d="M169,134.8v2c0,0.4-0.3,0.7-0.7,0.7h-2.4c-0.4,0-0.7-0.3-0.7-0.7v-2.1C166.5,134.7,167.8,134.7,169,134.8z"\n	/>\n<path fill="#fff" d="M173.2,135.1v1.7c0,0.4-0.3,0.7-0.7,0.7h-2.4c-0.4,0-0.7-0.3-0.7-0.7v-2C170.8,134.9,172.1,135,173.2,135.1z\n	"/>', 5, 2, 1, 0),
('Lips', '<path fill="#9F1600" opacity="0.6" d="M174.2,138c-4.6-1.2-5.7-2-9.6-0.8c-3.8-1.2-5-0.4-9.6,0.8c1.1,3,4.8,4.4,9.4,4.5h0.3\n	C169.3,142.4,173.1,140.9,174.2,138z"/>\n<path opacity="0.7" d="M173.4,139.4c0.1-0.2,0.3-0.4,0.4-0.6c-4.7,0.2-13.8,0.2-18.4,0c0.1,0.2,0.2,0.4,0.4,0.6c0.1,0,0.3,0,0.5,0\n	c2.2,0.1,5.2,0.1,8.4,0.1C168.8,139.5,171.5,139.5,173.4,139.4z"/>', 5, 2, 1, 0),
('Open Smile', '<path opacity=".7" fill="#491000" d="M177.9,137.3h-26.5c0,0,5.7,7.1,12,7.2h2.5C172.2,144.4,177.9,137.3,177.9,137.3z"/>', 5, 2, 1, 0),
('Smirk', '<path fill="#553400" d="M177.7,136.7c-1.3-0.2-2.1-0.8-2.5-1.8c-0.1-0.3-0.4-0.4-0.7-0.3c-0.3,0.1-0.4,0.4-0.3,0.7\n		c0.2,0.5,0.5,1,0.8,1.4c-6.7,6.5-13.7,6.4-20.3-0.2c-0.2-0.2-0.6-0.2-0.8,0c-0.2,0.2-0.2,0.6,0,0.8c3.6,3.6,6.9,5.4,11.1,5.4\n		c4.2,0,7.4-1.8,10.9-5.3c0.5,0.3,1,0.4,1.7,0.5c0,0,0,0,0.1,0c0.3,0,0.5-0.2,0.6-0.5C178.3,137,178.1,136.7,177.7,136.7z"/>', 5, 2, 1, 0),
('Smile with Lips', '<path fill="#F17379" d="M165,144.9c-2.1,0-4.1-1.8-4.1-3.7c0-1.2-0.3-1.8,1-2.4c1-0.4,2.1-0.5,3.1-0.1c1-0.4,2.1-0.4,3.1,0.1\nc1.3,0.6,1,1.2,1,2.4C169.1,143.1,167.1,144.9,165,144.9z"/>\n<path fill="#6A0000" d="M165.2,142.7c-3.2,0-6.5-1.2-9.8-3.6c-0.5-0.4-0.6-1.1-0.3-1.6c0.4-0.5,1.1-0.6,1.6-0.3\nc5.8,4.2,11.2,4.2,16.5,0c0.5-0.4,1.2-0.3,1.6,0.2c0.4,0.5,0.3,1.2-0.2,1.6C171.5,141.5,168.4,142.7,165.2,142.7z"/>', 5, 2, 1, 0),
('Big Smile', '<path fill="#8C2B30" d="M176,138.4c0,4.5-4.9,8.1-11,8.1s-11-3.6-11-8.1c0-4.5,5.7-0.3,11-0.3S176,133.9,176,138.4z"/>\n<path fill="#E88282" d="M165,146.5c3.1,0,5.9-0.9,7.8-2.4c-1.5-1.7-4.4-2.8-7.8-2.8c-3.4,0-6.4,1.1-7.8,2.8\nC159.1,145.5,161.9,146.5,165,146.5z"/>', 5, 2, 1, 0),
('Straight', '<path class="color1" d="M154.2,95.9H141c-0.6,0-1.1-0.5-1.1-1.1s0.5-1.1,1.1-1.1h13.2c0.6,0,1.1,0.5,1.1,1.1S154.8,95.9,154.2,95.9z M175.8,95.9\nH189c0.6,0,1.1-0.5,1.1-1.1s-0.5-1.1-1.1-1.1h-13.2c-0.6,0-1.1,0.5-1.1,1.1S175.2,95.9,175.8,95.9z"/>', 9, 2, 1, 1),
('Thick', '<path class="color1" d="M152,96.4h-8.9c-0.9,0-1.7-0.7-1.7-1.7c0-0.9,0.7-1.7,1.7-1.7h8.9c0.9,0,1.7,0.7,1.7,1.7S153,96.4,152,96.4z M178,96.4h8.9\nc0.9,0,1.7-0.7,1.7-1.7c0-0.9-0.7-1.7-1.7-1.7H178c-0.9,0-1.7,0.7-1.7,1.7S177,96.4,178,96.4z M0,1.4"/>', 9, 2, 1, 1),
('Rounded', '<path class="color1" d="M153.6 91.9a9.8 9.8 0 00-7-1.5 9.8 9.8 0 00-6.2 3.6l13.2-2.1zM176.4 91.9a9.8 9.8 0 017-1.5c2.6.4 4.8 1.8 6.2 3.6l-13.2-2.1z"/>', 9, 2, 1, 1),
('Large Rounded', '<path class="color1" d="M155,94.4c0.1-0.2,2.3-3.6-8.1-2.5c-3.1,0.4-5.8,1.7-7.7,3.6c-0.2,0.2,0,0.5,0.2,0.5L155,94.4z M175,94.4\nc-0.1-0.2-2.3-3.6,8.1-2.5c3.1,0.4,5.8,1.7,7.7,3.6c0.2,0.2,0,0.5-0.2,0.5L175,94.4z"/>', 9, 2, 1, 1),
('Arched', '<path class="color1" d="M139.5 97.6c-.4 0-.7-.2-1-.5-.3-.5-.2-1.2.3-1.6 3.3-2.1 14.4-6.1 18.9.3.4.5.2 1.2-.3 1.6-.5.4-1.2.2-1.6-.3-3.6-5.1-13.5-1.2-15.9.3 0 .2-.2.2-.4.2zM190.5 97.6l-.6-.2c-2.4-1.5-12.3-5.4-15.9-.3-.4.5-1.1.6-1.6.3-.5-.4-.6-1.1-.3-1.6 4.5-6.4 15.6-2.4 18.9-.3.5.3.7 1 .3 1.6-.1.3-.4.5-.8.5z"/>', 9, 2, 1, 1),
('Long Straight Over Ear', '<path class="color1" d="M217.1 95.9c0-3.1-.2-6.3-.6-9.4a52.2 52.2 0 00-53.8-45.8 51.9 51.9 0 00-49.9 51.5v88.5c.2-.1.5-.9.7-1.3a45.5 45.5 0 0126.4-24.5l7.3-1.5a66 66 0 01-18.8-25.5l-2.6-7.8c-4.6.9-5.5-2.1-8.4-10.5-.9-2.7-1.3-5.5-1-8 1.2-3.3 4-5.2 7.9-6.4a20 20 0 0014.4-15.1l1.3-3.5c4.5 5.7 10.4 6.8 16.8 6.7h33.8c6.5.1 9.9 2.6 10.1 9.1.3 11.1-.2 22.2-.7 33.2-.2 4.2-2.3 9.5-4.8 14.2-3.7 5.5-8 10-12.2 13.5l5.3 1.3c8.6 1.7 19.2 10.1 26.2 22.6.9 1.4 1.8 3.7 2.6 5.3V95.9z"/>', 10, 2, 1, 1),
('Long Wavy', '<path class="color1" d="M236.3 169.2c-3.5-6.1-5-12.1-4.8-18.9a28 28 0 00-4.4-16.5c-2-3.1-3.2-7.4-3-11 .4-5.6 0-11.2-1.5-16.6-1.6-5.7-5-10.6-6.9-16.2-.7-2.1-1.4-4.3-1.9-6.5a72 72 0 00-4.4-13.6 50.1 50.1 0 00-27.8-25.7 49.5 49.5 0 00-65.7 36c-1.3 6-3.4 11.7-6 17.2a49.2 49.2 0 00-5.1 16.3 30 30 0 00.2 6.7c.6 4.4 1 9-2.4 12.8a22.3 22.3 0 00-5.2 16.2c.2 7.3-1 13.8-5.1 20.2s-5.8 14.1-1.2 21.2c4.4 6.7 9.9 7.6 17.6 8.8l.2-2.2c1.3-19.5 14.7-37.4 31-42.5l7.4-1.6a66.2 66.2 0 01-21.4-33.5v.1c-4.6.9-5.5-2.1-8.4-10.5-.8-2.3-1.1-4.7-1-6.9.1-2.2.7-4.1 1.8-5.8l8.4-.8c8.6-1.1 15-5.1 17.3-14 .8-3 1.6-6.1 2.9-9a18.5 18.5 0 0118-11.9c8.3 0 14.7 3.8 18.1 11.7 1.4 3.2 2.2 6.6 3.2 9.9 2 6.4 6.2 10.6 12.6 12.3 3 .8 13.1 1.8 13.1 1.8 1.1 1.6 1.9 3.6 1.9 5.9.1 2.2-.3 4.5-1.1 6.8-2.9 8.4-3.8 11.4-8.4 10.5v-.1a65.3 65.3 0 01-21.1 33.4c19.1 3.7 35.7 21.7 38.4 46l-.6.2v.8h.5c4.6-1.9 9.3-3.2 13.1-5.8 2.7-1.9 5.5-5.8 5.5-8.8a38.3 38.3 0 00-3.8-16.4z"/>', 10, 2, 1, 1),
('Pigtails', '<path class="color1" d="M208.2,134.3c5.2-6.4,8.7-13.1,8.6-21.1c-0.3-14.5-2.1-28.9-7.2-42.5c-8.1-21.4-25.9-30.3-45.3-30.5\nc-20.8-0.3-38.8,13.4-45,33.1c-2.2,7.1-3.6,14.5-4.8,21.8c-2.3,13.6-3.4,27.2,6.5,38.9c0.1,0.1-0.1,0.6-0.2,0.9\nc-11.1,3.9-13.5,7.8-12.7,19.6c0.1,1.8,0.8,4.1,0.8,4.1s2.5-0.2,4-0.4c8.3-1.3,12.7-6.5,12.9-15.1c0-1.4,0.2-2.8,0.3-4.5\nc2.7,1.3,7.4,2.4,10.8,3.8c-4.6-6.1-8.8-13.7-11.1-22.4c-4.6,0.7-5.3-2-8.2-10.4c-2.5-7.4-1-15,6.8-16.1\nc17-5.5,27.9-14.7,37.1-29.7c1.2-1.9,2.4-3.8,3.9-6.1c1,1.5,1.7,2.4,2.2,3.4c8.5,16.8,19.8,27.1,38.3,32.5\nc7.8,1.1,9.3,8.8,6.8,16.1c-2.9,8.4-3.9,11-8.3,10.4c-2.2,8.6-6.2,16.1-10.8,22.2c2.9-1.2,8-2.3,10.5-3.5\nc1.4,15.4,6.1,20.6,16.9,19.2C224.3,145.2,221.7,140.2,208.2,134.3z" />', 10, 2, 1, 1),
('Shoulder Length', '<path class="color1" d="M225 144.8c-2-24.3-8.4-47.7-15.8-70.8a44.9 44.9 0 00-42.3-30.8 45.4 45.4 0 00-45.1 29.7 398.4 398.4 0 00-16.7 71.4l-.1 1.1-.1 1.1v2.2l.1 1.7v.4l.2 1.8v.2l.3 1.9v.1a30 30 0 0011.7 18.1 50 50 0 0116.1-15.3c2.1-1.1 4.5-2.2 6.7-2.9l7.2-1.4a66.2 66.2 0 01-21.4-33.5c-4.6.9-5.4-2.3-8.3-10.7-.8-2.4-1.2-4.8-1.1-7 1.8-7 10.6-10.6 15.6-12.7 5.4-2.3 9.4-5.2 11.6-10.7.3-.7.9-1.2 2-2.6l-.6 7.8c8-.5 15.6-1.3 23.2-1.2 13.8.2 15.4-7.6 15.9-7.6l-1.1 9.2c6 1.7 5.5-5.2 9.2-6.5v6.7c.1 1 .4 2.5 1.1 2.9 4.3 2.9 8.2 7.6 13.3 8.3 3.5.5 5.7 2.2 6.9 8.3-.1 1.7-.5 3.4-1.1 5.2-2.9 8.4-3.8 11.4-8.4 10.5a44.2 44.2 0 01-2.6 7.6 65.6 65.6 0 01-18.2 25.8l4.8 1.2a42.1 42.1 0 0123.8 18.3l.3-.1a30.6 30.6 0 0012.9-27.7z"/>', 10, 2, 1, 1),
('Short', '<path class="color1" d="M219.9 110.6c-1-9.2-3-18.3-4.9-27.4A50.5 50.5 0 00172.3 44a50.2 50.2 0 00-52.6 28c-6.1 13.2-8 27.6-9 42.1-.3 4.3.7 9.6 4.8 11.8 2.5 1.4 5.9 2.6 8.5 3.9 1.9.9 5.8 1.7 6.1 1.8l-.1-.3a65.9 65.9 0 01-4.2-11.3c-4.6.9-5.5-2.1-8.4-10.5-2-5.9-1.3-12 3.1-14.8l3.7-1.3s5.8-4.3 8-7.6c3.1-4.7 4.7-10.4 7.1-15.9 1.1.9 2.2 1.8 3.2 2.9a64.4 64.4 0 0025.6 15.8c11.4 4 23.2 5.5 35.3 5 1.6-.1 3 .2 4.1.5 2.4.6 3.8 2.2 4.9 4.4 1.3 2.6.5 8.5-.8 14.3-2.1 6-3.4 8.1-7.3 7.3l-.1.1c-.9 3.4-2 6.6-3.4 9.6-.2.8-.8 1.4-1 2.4 3.5-1.5 6.3-2.3 8.9-3.4 9.5-4.3 12.3-8 11.2-18.2z"/>', 10, 2, 1, 1),
('Long over Shoulder', '<path class="color1" d="M204.3 120a64.8 64.8 0 01-21.2 33.2l5.3 1.3a38 38 0 0119.2 12.7c-.3-3-.4-6.8-1.2-9-1.7-4.4-1.4-8 1.6-12 1.6-2.1 2.2-5.5 2.1-8.3-.3-4.7-.2-13.9-.8-20-1.2 1.9-2.6 2.6-5 2.1zM210.3 88.8c-.8-31.7-18.6-40.8-43.7-45.8-20.4-4.1-40.3 13.9-46.2 30.2a524.7 524.7 0 00-14.4 50.6c-.9 4.1-1.3 8.5-1.1 12.7.3 6.8.8 13.6-2.7 19.9-3.9 7.1-3.3 14.2-.3 21.5 1 2.5 1.4 6.2.3 8.5a17.7 17.7 0 001.7 18.8c.9 1.4 3.1 2.2 4.5 2.9a31.9 31.9 0 0032.4 0c1.6 0 4.1-1.5 4.4-2.8 1.1-4.4 3.2-9.8 1.7-13.4a42.3 42.3 0 01-2.6-26.5c.9-3.9.1-7.6-1.3-11.2l4.2-.8c-2.9-2.4-6-5.5-8.8-8.9a28.8 28.8 0 01-2.1-19.2c1.1-5.4 1-11.2.8-16.8-.1-3.6-1.5-7.1-2.2-10.7-1.6-7.6.9-11 8.4-11.4 15.3-.8 29.4-4.1 37.7-19.8 10.4 22.8 12.3 22 29.2 28.3-.1-3.4.1-2.9.1-6.1zm-67.9-3.7c20.9-3.3 24.8-6.4 29.6-9.5-8.6 8-20 8.8-29.6 9.5zm42.9-15.2c6.1 12.3 4.8 11 12.3 19.4a29.7 29.7 0 01-12.3-19.4z"/>', 10, 2, 1, 1),
('Ear Length', '<path class="color1" d="M214 103.2c-.5-.1-.2-1.3-.8-1.7-4.4-2.9-12.7-1.8-12.7-1.8l.8-1.1c6.8-8.1-.9-15.6-12.1-16-7.8-.3-15.6-.1-23.4-.1-13.1 0-13.6-.2-24.3-7.7l-1.7 4.5c-2.4 6.8-6.7 12-13.6 14.3-4.8 1.6-8.3 3.9-9.9 8.9-5.8-6.4-1.8-32.2 9.5-45.1a52.6 52.6 0 0169.4-9.2c17.7 11.8 27.2 36.7 18.8 55z"/>', 10, 2, 1, 1),
('Long Straight', '<path class="color1" d="M213.6 172.9c1.3-6.8 2.7-13.3 2.5-19.8-.7-23.3-1.1-46.8-4.3-69.5-3-21.1-14.2-35.6-34.9-41.4A46.1 46.1 0 00126 58.4a46.7 46.7 0 00-10.4 33.2h-.2v84.1a44.2 44.2 0 0124.5-20.8l7.5-1.4a66.4 66.4 0 01-21.6-33.5c-4.6.9-5.5-2.1-8.4-10.5-1.6-4.9-1.6-9.7.9-12.9 2-1.2 4-2.8 5.9-3.1 5-.9 8.3-2 10.3-6.6 1.9-4.3 3.3-8.9 5.1-13.9.6 2.2 1 4.1 1.7 5.9 3.4 9 6.1 10.4 14.5 9.5 10.9-1.1 22.2-.7 33.1.7 6.5.8 12.9 4 19 7 5.9 3 5.2 9 4.9 14.6l.1 4.9.6 18.7.4 15.2c0 1.9-.1 8.8-.6 12.9l-.5-19.7-.1-32.1.1-1.5-.1.3c-2.9 8.4-3.8 11.4-8.4 10.5a65 65 0 01-21.1 33.3l5.2 1.3c8.2 1.6 18.3 9.5 25.3 21.1l-.1-2.7zm-83.2-83.7c2.8-5 6.4-12.6 7.9-17.5-.4 3.9-6.1 18.2-7.9 17.5z"/>', 10, 2, 1, 1),
('Side Swept', '<path class="color1" d="M204.3 120a61.9 61.9 0 01-10.7 22h1.1c3.5-.3 18-6.4 25.7-9.8-3.2-4.3-5.8-14.4-8.5-20.6-2.3 6.8-3.5 9.2-7.6 8.4zM136.4 142a64.3 64.3 0 01-10.6-22c-4.4.9-5.5-2-8.2-9.9-3 5.6-5.9 17.4-9.1 21.6 7 4.2 14.8 8.4 24.5 10.2-.1.1 1.2.1 3.4.1zM210.5 95.3c-12.6-4.1-16.9-12.7-18.1-24.4h-1.2c-8.2 9.2-18.6 12-30.6 11.2-6.9-.4-13.9.1-20.9-.1-4.1-.1-7 1.3-9 4.8-2.8 4.7-6.1 9-12.6 9.7a46.8 46.8 0 0116-42 46.5 46.5 0 0151.7-5.4 44.8 44.8 0 0124.7 46.2z"/>', 10, 2, 1, 1),
('Pixie', '<path class="color1" d="M178.5 75.5l4.4 8c-14.2-.5-24.7-6-32.1-18.8-5.2 14.5-8.9 23-23.6 28.4-.6.2-2 6.5-3.4 7.9-.1.1.1-6.1.3-7.5-1.1.8-4.4 1.6-4.7 2.1-.6-1.2-.7-2.8-.9-4.1-.3-1.9-.5-3.9-.5-5.8-.3-7.3.4-15.5 4-22.1 1.8-3.4 4.9-6.4 7.6-9.1 6.5-6.4 12.3-5.2 13.3-5.4 3.7-4.7 11.7-7.6 18.2-8.1 27.6-2.1 46.8 14.8 51.5 35.3 1.1 4.8.8 10.2.3 15.2-.2 1.8-1.8 4.4-1.8 4.4 0-.2-2.7-1-5.2-1.6l.4 7.8c0 .1-.1.1-.1 0-1.4-1.2-3.3-8.9-3.6-9.2-2.4-2.4-6-7.6-7.2-10.7 0 0-5.4-.5-6.9-1.3a73.6 73.6 0 01-10-5.4z"/>', 10, 2, 1, 1),
('Bob', '<path class="color1" d="M233.6 116.9c-6.2-12.8-10.9-26.3-15.7-39.7-7.7-21.4-22-34.3-45-36.6-21.7-2.2-44.9 4.1-55.3 26.6-8.6 18.8-16 38.2-23.2 57.6-4.3 11.6 1.9 22.2 13.7 25.7 8 2.4 37.5 1.5 37.5 1.5a66.8 66.8 0 01-11.7-13.7c0-1 0-1.9-.3-2.8-1.8-6-3.5-12.1-5.6-18-3.3-9.6-2.7-11.4 5.1-17.7 3-2.5 5.8-5.6 7.8-8.9 2.3-4 3.6-8.5 5.2-12.9 1.7-4.6 3.8-9.3 8.9-10.7 15.6-4.1 27.7-2.2 31.6 14.6a32 32 0 0010.8 17.5c7.1 6 7.4 7.5 4 16.3-1.9 4.8-4.1 9.5-5.2 14.5-.7 3-.7 6.1-.6 9.2a67.5 67.5 0 01-10.9 12.5h11.4c5.7 0 12.3.2 18.8 0 16.4-.5 29.2-13.3 18.7-35z"/>', 10, 2, 1, 1),
('Asymmetric', '<path class="color1" d="M213.2 81.3a49.3 49.3 0 00-51.2-42c-22.8.5-45.2 20.5-45.8 43.9-1.1 38.9-.3 77.8-.2 116.7 0 .9.9 1.2 1.8 1.8a2 2 0 002.1.1c1-.5 2.1-.8 2.4-1.8.5-2 .4-4.2.5-6.3 0-.3.5-.4.6-.1.8 2 .8 5.5 1 6.6.1.5 1.9 1.9 2.3.3.5-1.6.9-8.2.9-8.2s.2-2.5.7-.7c.7 2.6 1 9.8 1.3 10.7 1.2 3 3.8 1 3.8-.5 0 2.8 3-1.8 3-5.6v-33.4c0-2.1-.3-4.4-.2-6.4l.2-.3 3.5-1.3c2.3-.5 7.4-1.3 7.2-1.4-3.2-2.8-10.2-10.8-10.3-10.8-.3-.9-.4-1.3-.4-2.2-.5-16.1-.1-32.3-.3-48.4 0-3.3 1-4.5 4.2-5.3a32.6 32.6 0 0021.8-17.5l2.3-4.3c.1-.2.5-.2.6 0A35.4 35.4 0 00202.9 88l.3.2c1.4 2.4 1.3 4 2.6 5.3 1.9 1.9 7.1 5.6 7.1 5.6a16 16 0 01-.4 10.5c-2.8 8.2-3.8 11.3-8.1 10.6l-.4.2a64.6 64.6 0 01-20.7 32.5c-.2.2-.1.5.1.5l4.7 1.2c8.3 1.6 19 9.8 25.9 21.7.2.3.2 0 .2-.4 0-.1-.2-88.9-1-94.6z"/>', 10, 2, 1, 1),

('Elf Male', '<path class="color1" d="M223.8 207.7h-16.7v82l-.2.1-1.2.7c0 2.5-.2 9.3-.7 19 .4.3.8.5 1.4.5a2 2 0 002-1.3v1.1c0 1.2 1 2.2 2.2 2.2 1 0 1.8-.7 2.1-1.6h.1c.2 1 1.1 1.7 2.1 1.7 1.2 0 2.2-1 2.2-2.2v-.2c.4.4.9.7 1.5.7 1.2 0 2.2-.9 2.2-2.1v-1.4l.5.1c1.2.1 2.2-1 2.3-2.3l.3-5.4v-91.6zM191.3 374.1l-2.5 14.8 16.7 6.6s9.3 2.5 11.9 4.4c1.7 1.2.3 3.3-.9 4.7a4 4 0 01-3 1.4c-7.3.1-35.7.7-38.3-.1-3-.8-2.1-14.6-2.1-14.6l-.5-17.2-1-73 30.3-6-10.6 79zM138.7 374.1l2.5 14.8-16.7 6.6s-9.3 2.5-11.9 4.4c-1.7 1.2-.3 3.3.9 4.7a4 4 0 003 1.4c7.3.1 35.7.7 38.3-.1 3-.8 2.1-14.6 2.1-14.6l.5-17.2 1-73-30.3-6 10.6 79zM133.8 267.4l-.8-.2c1.3 0 2.5-.8 2.7-2 .3-1.4-.8-2.8-2.4-3.1l-10-2-.1-.1.2-5.9-3.7 1.3-14.2-18.9 24.9-42.8-17.7-10.8L85 230.4l-.5.8a9.7 9.7 0 00.8 10.3l5.8 7.8 19.8 26.3a9.7 9.7 0 005.7 3.6l.4.1 5.5 1.1 7.1 1.4c1.6.3 3.1-.5 3.4-1.9.2-1-.3-2-1.2-2.6h.1c1.6.3 3.1-.5 3.4-1.9.2-1.2-.5-2.4-1.8-2.9 1.3-.1 2.4-.8 2.6-2 .3-1.4-.7-2.8-2.3-3.1zM220.6 82.2l-13.7 18.6v-.9c0-15.8-10.1-41.9-41.5-41.9s-42.6 24.4-42.3 42v1l-13.9-18.8s-6.4 27.7 13.9 42.3v5.1c0 13.7 6.5 25.8 16.7 33.5l.1.1a85 85 0 003.6 2.5L165 186l21.9-20.7a93.8 93.8 0 003.3-2.3 42 42 0 0016.7-33.5v-5.2c20.2-14.6 13.7-42.1 13.7-42.1z"/>\n  <path opacity=".1" d="M217.4 92s1.9 19.5-10.7 28.7v3.8c20.4-14.6 13.9-42.3 13.9-42.3L206.8 101v4.9L217.4 92zM112.4 92l10.7 14v-5l-13.9-18.8s-6.5 27.7 13.9 42.3v-3.8c-12.6-9.3-10.7-28.7-10.7-28.7z"/>\n  <path opacity=".3" d="M217.4 92l-10.7 14v14.8c12.6-9.4 10.7-28.8 10.7-28.8zM123 120.7v-14.8l-10.7-14c.1.1-1.8 19.5 10.7 28.8z"/>\n  <path opacity=".2" d="M180.6 168.5a41.2 41.2 0 01-37.1-2.9L165 186l21.9-20.7c-2 1.2-4.1 2.3-6.3 3.2z"/>', 1, 1, 3, 1),
('Elf Shoes', '<path class="color1" d="M217 398.9c-3.3-1.8-7.7-2.4-11.1-3.7l-10.7-4.1-6.2-2.4-5.3-4.6 8.1-6.8.1-.1.1-.1c.2-.4.2-.8 0-1.2l-.4-.3-7.8-5.1 9.9-6.4c.5-.3.6-1 .4-1.5l-.2-.3-.1-.1-.2-.1h-.2-.2l-20.4 1.1-.7.1-.2.1-.1.1v.1l-.1.2v.4l.4.6 8.2 5.4-7.3 4.8c-.4.3-.6.9-.4 1.4v.1l.2.2 8.2 7.2-7.4 6.3-.2.3c-.6 1.5-1 4.1-1.1 4.7-.2 1.3-.3 2.7-.2 4 .2 1.8.3 4 1.4 5.6 1.1 1.6 3 1.9 4.8 1.8l5.7-.1 9.5.1 16 .1c3.4 0 9.3.3 9.9-4.2 0-1.6-1.1-2.9-2.4-3.6zm-41.9-34.1l14.5-.8-7.9 5.1-6.6-4.3zm-.4 11.4l7.1-4.6 7.6 5-7.2 6.1-7.5-6.5zm.5 15.1l7-5.9 5 4.4a16.6 16.6 0 01-12 1.5zM158.8 363.8l-.1-.2v-.1l-.1-.1-.2-.1c-.2-.1-.4-.2-.7-.1l-20.4-1.1h-.1H136.9l-.2.1-.1.1-.2.3c-.3.5-.1 1.1.4 1.5l9.9 6.4-7.8 5.1-.4.3c-.3.3-.3.8 0 1.2l.1.1.1.1 8.1 6.8-5.3 4.6-6.2 2.5-10.7 4.1c-3.5 1.2-7.7 1.9-11 3.6a3.6 3.6 0 00-1.9 3.7c.6 4.5 6.1 4.2 9.5 4.1l16-.1 9.5-.1 5.7.1c1.8.1 3.7-.2 4.8-1.8 1.1-1.5 1.3-3.7 1.4-5.6.1-1.3 0-2.7-.2-4-.1-.6-.4-2.8-.9-4.3 0-.2-.1-.5-.3-.6l-7.4-6.3 8.2-7.2.2-.2v-.1c.2-.5 0-1.1-.4-1.4l-7.3-4.8 8.2-5.4c.2-.1.3-.3.4-.6v-.2l-.3-.3zm-3.9 27.5c-5.3 1.4-9.7-.2-12-1.5l5-4.4 7 5.9zm.4-15.1l-7.4 6.5-7.2-6.1 7.6-5 7 4.6zM140.4 364l14.5.8-6.6 4.4-7.9-5.2z"/>', 2, 1, 3, 1),
('Elf Open Shoes', '<path class="color1" d="M158.6 363.7l-.1-.2-.1-.1-.1-.1-.2-.1-.3-.1h-.3l-20-1.1h-.4l-.2.1-.1.1-.2.3v.1c-.3.5-.1 1.1.4 1.5l10.1 6.3-8.3 5.1-.2.1c-.5.3-.6.9-.3 1.4l.1.1.1.1 8.6 6.8-5.6 4.9-4.6 1.8 4.8 4.7c-6.6 1.9-14.1 5.5-20.8 1.1-2.5.7-5 1.5-7.3 2.7-1.3.7-2.4 1.9-2.2 3.5.7 4.5 6 4.3 9.4 4.3l16.2-.1 9.6-.1 5.8.1c1.8.1 3.8-.2 4.9-1.8 1.1-1.5 1.3-3.7 1.4-5.6.1-1.3 0-2.7-.2-4-.1-.6-.4-2.8-.9-4.3 0-.2-.1-.5-.3-.6l-7.2-6.5 8.1-7 .2-.2v-.1l.1-.3a1 1 0 00-.5-1.1l-7.2-4.9 7.7-5.3c.2-.1.3-.3.4-.6v-.2l-.3-.7zm-3.4 27.5c-5.4 1.4-9.9-.2-12.2-1.5l5.4-4.6 6.8 6.1zm.5-15l-7.2 6.3-7.8-6.1 8.1-4.9 6.9 4.7zm-6.9-7.3l-8.2-4.9 14.3.7-6.1 4.2zM171.4 363.7l.1-.2.1-.1.1-.1.2-.1.3-.1h.3l20-1.1h.4l.2.1.1.1.2.3v.1c.3.5.1 1.1-.4 1.5l-10.1 6.3 8.3 5.1.2.1c.5.3.6.9.3 1.4l-.1.1-.1.1-8.6 6.8 6 4.9 4.6 1.8-5.2 4.8c6.6 1.9 14.5 5.4 21.2 1.1 2.5.7 5 1.5 7.3 2.7 1.3.7 2 1.9 1.8 3.5-.7 4.5-6 4.3-9.4 4.3L193 407l-9.6-.1-5.8.1c-1.8.1-3.8-.2-4.9-1.8-1.1-1.5-1.3-3.7-1.4-5.6-.1-1.3 0-2.7.2-4 .1-.6.4-2.8.9-4.3 0-.2.1-.5.3-.6l7.2-6.5-8.1-7-.2-.2v-.1l-.1-.3a1 1 0 01.5-1.1l7.2-4.9-7.7-5.3c-.2-.1-.3-.3-.4-.6v-.2l.3-.8zm3.4 27.5c5.4 1.4 9.9-.2 12.2-1.5l-5.4-4.6-6.8 6.1zm-.5-15l7.2 6.3 7.8-6.1-8.1-4.9-6.9 4.7zm6.9-7.3l8.2-4.9-14.3.7 6.1 4.2z"/>', 2, 1, 3, 1),
('Elf Shorts', '<path class="color1" d="M165 275.2c-10.1 0-32 5.4-32 5.4-1 2.3-4.6 1.2-6.3.6l-3.8.4s2.1 33.3 5.2 59.5h34l1.5-25.2c0-2.2 2-2.5 2-2.5s2 .3 2 2.5l.8 25.2h34.2c1.2-12.1 2-23.1 2.5-31.8.5-9.6 1.8-26.2 1.8-28.7-.5.4-26.5-5.4-41.9-5.4z"/>', 3, NULL, 3, 1),
('Elf Tunic', '<path class="color1" d="M225.7 221.7c0-21.2-6.3-34.4-6.3-34.4h-.1a44.5 44.5 0 00-12.3-15.8c-4.6-3.7-10.8-7-16.7-8.6 0 0-2.6 2.2-8.4 5.1L165 184.6 148.4 168a64.3 64.3 0 01-8.2-4.8l-3.8 1.1a45.6 45.6 0 00-11.3 6.1A90 90 0 0093.3 208l23 14 8.6-9.3-1.7 47.2.1.1 10 2c1.6.3 2.7 1.7 2.4 3.1-.2 1.2-1.4 2-2.7 2l.8.2c1.6.3 2.7 1.7 2.4 3.1-.2 1.1-1.3 1.9-2.6 2 1.2.5 2 1.7 1.8 2.9-.3 1.4-1.8 2.3-3.4 1.9h-.1c.9.6 1.4 1.6 1.2 2.6-.3 1.4-1.8 2.3-3.4 1.9l-7.1-1.4-.3 8.8.6.4.8.5a81.2 81.2 0 0041.3 9.6c15.4 0 29.2-3.1 38.5-8l2.4-1.4 1.2-.7.2-.1v-68h18.4z"/>\n<path class="color2" d="M165.7 190.8l2.4-2.4c.2-.2.2-.5 0-.7s-.5-.2-.7 0l-2.4 2.4-2.4-2.4c-.2-.2-.5-.2-.7 0s-.2.5 0 .7l2.4 2.4-2.4 2.4c-.2.2-.2.5 0 .7l.4.1.4-.1 2.4-2.4 2.4 2.4.4.1.4-.1c.2-.2.2-.5 0-.7l-2.6-2.4zM168.1 196.9a.5.5 0 00-.7 0l-2.4 2.4-2.4-2.4c-.2-.2-.5-.2-.7 0s-.2.5 0 .7l2.4 2.4-2.4 2.4c-.2.2-.2.5 0 .7l.4.1.4-.1 2.4-2.4 2.4 2.4.4.1.4-.1c.2-.2.2-.5 0-.7l-2.4-2.4 2.4-2.4c0-.2 0-.5-.2-.7zM168.1 206.1a.5.5 0 00-.7 0l-2.4 2.4-2.4-2.4c-.2-.2-.5-.2-.7 0s-.2.5 0 .7l2.4 2.4-2.4 2.4c-.2.2-.2.5 0 .7l.4.1.4-.1 2.4-2.4 2.4 2.4.4.1.4-.1c.2-.2.2-.5 0-.7l-2.4-2.4 2.4-2.4c0-.2 0-.5-.2-.7z"/>', 4, 1, 3, 2),
('Long Straight', '<path class="color1" d="M213.6 102c-.3-13.3-7.8-25.6-16.8-35.5a41.5 41.5 0 00-32.7-13.1 49 49 0 00-43.6 27.8 62.5 62.5 0 00-5.1 26l.2 70.7c3.3-2.9 6.5-5.3 9.5-7.2 3.4-2.6 7.2-4.6 11.3-6.1l3.8-1.1-.4-.3a42 42 0 01-16.7-33.5V101s37.8.4 42-39.5a41.9 41.9 0 0041.8 39.3v28.8a42 42 0 01-16.7 33.5 47.8 47.8 0 0116.7 8.4c2.5 2 4.8 4.3 6.8 6.9-.6-38.6-.1-76.3-.1-76.4z"/>', 10, 1, 3, 1),
('Styled', '<path class="color1" d="M202.8 64.3c-.4-1.1-.9-2.1-1.5-3.1-1.9-3.5-3.5-4.3-3.5-4.3v.1a26.3 26.3 0 00-18.2-6.8h-65.2v19.7c0 6.1 3.4 11.5 8.7 15.1-.6 7.6-.8 17.3 0 29.5h4.2c1.4 0 2.5-1.1 2.5-2.5V88.1c2.7.9 5.6 1.3 8.7 1.3h41.1c5.9 0 11.3-1.7 15.4-4.6l.1-.1.2-.1a3 3 0 011.6-.5c1.7 0 3.2 1.4 3.2 3.1v24.9c.1 1.2 1 2.1 2.2 2.2h4.5c2.1-27.9-.9-42.5-4-50z"/>', 10, 1, 3, 1),
('Long Wavy', '<path class="color1" d="M123 101a51 51 0 005.9-1.9c2.7-1 5.1-2.2 7.3-4.1 2.8-2.5 5.1-5.6 6.7-9 1.6-3.4 2.6-7.2 5.5-9.8 2.7-2.4 6.7-2.8 10.1-2.2l3.1.8 2 .7c.6.1 1.2.2 1.8.1.9-.1 1.8-.5 2.7-.8l2.5-.7.7-.2c3.5-.7 7.4-.2 10.1 2.2 2.9 2.5 4 6.4 5.5 9.8 1.6 3.4 3.9 6.5 6.7 9 2.1 1.9 4.6 3.1 7.3 4.1 1.6.6 4.2 1.5 6 1.9l4.7-6.4c-1.8-9.2-5.5-17-5.5-17-9.2-20.6-31.1-22.7-38.7-22.8H162.7h-.1c-7.7.1-29.5 2.3-38.7 22.8 0 0-3.7 7.9-5.5 17.2l4.6 6.3zM147.7 167.8a42 42 0 01-24.7-38.3v-5.1c-2.7-2-5-4.2-6.9-6.5l-.8 8.9c-.8 2.6-3.1 4.5-2.6 7.6.5 2.7 2.4 4.9.3 7.3a9.9 9.9 0 00-2.2 4.6c-.9 4.3 3.6 7.6.1 11.5-3 3.4-1.4 7.5.6 10.8l.2.3h38.6l-2.6-1.1zM219 146.4c-.4-1.8-1-3.2-2.2-4.6-2-2.4-.1-4.6.3-7.3.5-3.2-1.8-5.1-2.6-7.6l-.8-9.1a34.5 34.5 0 01-7 6.7v5.1a42 42 0 01-16.7 33.5l-2.8 1.8s-4.1 2.6-6.7 3.6l-1.3.5H218l.2-.3c2.1-3.4 3.6-7.5.6-10.8-3.3-4 1.2-7.2.2-11.5z"/>', 10, 1, 3, 1),
('Short Tousled', '<path class="color1" d="M210.6 81.8a41.9 41.9 0 00-4.5-10.3 49.7 49.7 0 00-37.4-24.2 38 38 0 00-17.9 3.4 24.3 24.3 0 00-6.1 4.4c-6.2.8-11.6 4.2-15.7 8.8-6.2 7-7.9 16.1-8.6 25.3-.3 4.1-.1 9 .6 13.1.4 2.5 1.3 4.5 3.9 4.1 4.6-.8 7.1-6.3 9.4-10 2.2-3.6 3.3-7.4 4.2-11.2-.2.8 4.2 3.9 4.9 4.4 1.7 1.2 3.5 2.3 5.4 3.2 3.7 1.7 7.6 2.9 11.5 4 3.6 1 7.3 2.5 11.1 2.3 1.2-.1 2.8-.5 3.4-1.7.8-1.6-.7-3.2-1.4-4.5 6.4 1.5 12 5.8 17.4 9.3 1.6 1 3.2-.6 2.9-2.3-.6-3.1-2.1-5.8-3.3-8.6a16 16 0 014.9 4.1c1.4 1.6 2.5 3.2 3.3 5.1.9 2.3 2.3 5 5.3 4.6 1.3-.2 2.5-1.9 3.2-2.8 2.1-2.7 3.7-5.9 4.2-9.4.7-3.7.3-7.5-.7-11.1z"/>', 10, 1, 3, 1),
('Skrill', '<path class="color1" opacity=".5" d="M126.8 101.1c.9-3 3.5-4.8 4.6-7.6 1.1-2.5 1.3-5 1.6-7.7.3-2.6 5.3-5.8 5.3-5.8l.9-13.6-1.8 1a42 42 0 00-14.2 32.7v7.8l2.2.4c-.3-.1 1.2-6.7 1.4-7.2z"/>\n  <path class="color1" d="M229.9 155.8c-1.9-3.7-5.8-5.8-8.6-8.6-3.9-3.8.6-9.6 1-14.1.4-4.9-1-8.6-4-12.5-3.5-4.5-3.9-9.6-4.3-15.2a96.8 96.8 0 00-2.5-16 60.2 60.2 0 00-11.2-23.7 32.6 32.6 0 00-19.8-14.1 45.3 45.3 0 00-27 1.2 42 42 0 00-13.9 10.4c-4.2 4.9-4.4 11.8-3.8 18.3.1.7 1.3 3.6 1.3 3.6l.6.8s-2.3-3 0 0 6 1.8 6 1.8l3.5-1.2c4.3-1.3 11-1.1 13.4 2.8 1.2 2 .8 5.2 1 7.4.1.5 2.2 6.5 23.4 4.8 3-.2 7.4 16.4 5.6 21.4-1.9 5.5-.4 9.9 3.2 14.5 4.1 5.3 3 9.8-1.5 18-3.3 6-4.8 5.2-3 12.5.7 2.8-8.3 9.2-6.8 11.7 0 0 43.1 17.3 49.2-10.4a19 19 0 00-1.8-13.4z"/>', 10, 1, 3, 1),
('Medium', '<path class="color1" opacity=".6" d="M169.4 127.5c0 2.2-2 4-4.4 4-2.4 0-4.4-1.8-4.4-4v-13c0-2.2 2-4 4.4-4 2.4 0 4.4 1.8 4.4 4v13z"/>\n  <path opacity=".6" d="M165 131.5c-2.4 0-4.4-1.8-4.4-4v.5c0 2.2 2 4 4.4 4s4.4-1.8 4.4-4v-.5c0 2.2-2 4-4.4 4z"/>', 7, 1, 3, 1),
('Large', '<path opacity=".6" d="M161.9 130.1c-.4-.4-.8-.6-1.3-.6h-.2a2 2 0 01-1.1 0c.7.3 1.6.4 2.6.6zM169.4 129.5a2 2 0 00-1.4.6c1.1-.1 2.1-.4 2.9-.7-.4.2-.9.2-1.5.1h0zM157.6 128.4c.1.3.4.5.7.7l-.7-.7zM171.6 129.1c.4-.2.7-.4.8-.7-.3.3-.5.6-.8.7z"/>\n  <path class="color1" opacity=".6" d="M172.5 122.3l-4.8-6.3-.3-.5c-.6-.8-1.5-1.3-2.3-1.2a3 3 0 00-2.3 1.2l-.3.5-4.8 6.3a5.1 5.1 0 000 6c.8 1 1.9 1.4 2.9 1.2h.2c.8 0 1.4.5 1.8 1.3.6 1 1.5 1.7 2.6 1.7s2.1-.7 2.7-1.9l-.1.2c.4-.8 1.1-1.3 1.9-1.3h.1c1 .3 2.1-.1 3-1.1 1-1.7 1-4.4-.3-6.1z"/>', 7, 1, 3, 1),
('Pointy', '<path class="color1" opacity=".6" d="M173 122.8a3.2 3.2 0 00-3.5-1.2v-7.2c0-2.2-2-4-4.4-4-2.4 0-4.4 1.8-4.4 4v7.2c-1.3-.4-2.7.1-3.5 1.2a3.2 3.2 0 00.7 4.5l4.5 3.3.1.1.3.2.2.1a4.6 4.6 0 004.2 0l.2-.1.3-.2.1-.1 4.5-3.3c1.4-1 1.7-3 .7-4.5z"/>\n  <path opacity=".6" d="M172.2 127.4l-4.5 3.3-.1.1-.3.2-.2.1a4.6 4.6 0 01-4.2 0l-.2-.1-.3-.2-.1-.1-4.5-3.3a3 3 0 01-1.3-2.4c-.1 1.1.4 2.2 1.3 2.9l4.5 3.3.1.1.3.2.2.1a4.6 4.6 0 004.2 0l.2-.1.3-.2.1-.1 4.5-3.3c1-.7 1.4-1.8 1.3-2.9a3 3 0 01-1.3 2.4z"/>', 7, 1, 3, 1),
('Small', '<path class="color1" opacity=".6" d="M171.1 124.3c0 2.5-2 4.4-4.4 4.4h-3.3c-2.5 0-4.4-2-4.4-4.4 0-2.5 2-4.4 4.4-4.4h3.3c2.4 0 4.4 2 4.4 4.4z"/>\n  <path opacity=".6" d="M166.6 128.7h-3.3a4.4 4.4 0 01-4.4-4.2v.2c0 2.5 2 4.4 4.4 4.4h3.3c2.5 0 4.4-2 4.4-4.4v-.2c-.1 2.3-2 4.2-4.4 4.2z"/>', 7, 1, 3, 1),
('Triangular', '<path class="color1" opacity=".6" d="M163.2 111.4l-4 17.9c-.3 1.1.6 2.2 1.8 2.2h8c1.2 0 2-1.1 1.8-2.2l-4-17.9a1.8 1.8 0 00-3.6 0z"/>\n  <path opacity=".6" d="M169 131.5h-8c-.9 0-1.7-.7-1.8-1.6-.1 1.1.7 2.1 1.8 2.1h8c1.1 0 1.9-1 1.8-2.1-.2.9-.9 1.6-1.8 1.6z"/>', 7, 1, 3, 1),
('Angry', '<path class="color1" d="M157 102.2a2.5 2.5 0 01-2.9 2l-10.9-2.1a2.5 2.5 0 01-2-2.9 2.5 2.5 0 012.9-2l10.9 2.1c1.4.2 2.2 1.5 2 2.9zM171.7 102.2a2.5 2.5 0 002.9 2l10.9-2.1a2.5 2.5 0 002-2.9 2.5 2.5 0 00-2.9-2l-10.9 2.1a2.4 2.4 0 00-2 2.9z"/>', 9, 1, 3, 1),
('Arched', '<path class="color1" d="M156 105.8c-.5 0-1-.2-1.3-.7-1.7-2.7-3.7-4.2-6-4.5-3.8-.6-7.3 2.4-7.3 2.4-.6.5-1.6.5-2.1-.2-.5-.6-.5-1.6.1-2.1a13 13 0 019.7-3.1c3.2.5 5.9 2.4 8.1 5.9.4.7.2 1.6-.5 2.1l-.7.2zM174.1 106.1l-.8-.2c-.7-.4-.9-1.4-.5-2.1 2-3.3 4.6-5.2 7.6-5.6 5.3-.8 9.9 3.2 10.1 3.4.6.5.7 1.5.1 2.1s-1.5.7-2.1.1c0 0-3.8-3.3-7.7-2.7-2.1.3-3.9 1.7-5.5 4.2-.2.5-.7.8-1.2.8z"/>', 9, 1, 3, 1),
('Sinister', '<path class="color1" d="M135.6 101.5l13.7-1 6.1 2.7c.7.3 2.4.8 1.7 1.6-.5.6-2 .4-2.8.3l-14.5-1.9c-1.7-.2-3.6-.3-5.3-.7l1.1-1zM194.4 101.5l-13.7-1-6.1 2.7c-.7.3-2.4.8-1.7 1.6.5.6 2 .4 2.8.3l14.5-1.9c1.7-.2 3.6-.3 5.3-.7l-1.1-1z"/>', 9, 1, 3, 1),
('Rounded', '<path class="color1" d="M141.4 102.5s10.4-8.9 16 2.5l-16-2.5zM188.6 102.5s-10.4-8.9-16 2.5l16-2.5z"/>', 9, 1, 3, 1),
('Bushy', '<ellipse class="color1" transform="matrix(.08438 -.9964 .9964 .08438 36.7 242.8)" cx="150.5" cy="101.4" rx="2.8" ry="5.4"/>\n  <ellipse class="color1" transform="matrix(.9964 -.08438 .08438 .9964 -8 15.5)" cx="179.5" cy="101.4" rx="5.4" ry="2.8"/>', 9, 1, 3, 1),
('Smirk', '<path fill="#8E2829" d="M176.4 143.3c-.1-.5-.7-.8-1.2-.7-15 4.1-23.5-4.7-24.1-5.5.7-1.1.8-2.1.8-2.1 0-.5-.4-1-.9-1.1-.5-.1-1 .3-1.1.9 0 .1-.2 1.8-2.7 3.2-.5.3-.7.9-.4 1.4.2.3.5.5.9.5l.5-.1a9 9 0 001.6-1.1c1.2 1.3 7.3 7 17.7 7a31 31 0 008.2-1.1c.5-.2.9-.8.7-1.3z"/>', 5, 1, 3, 0),
('Happy', '<path fill="#EBA17A" d="M158.4 143c-.5.2-.9.5-1.2.8 1.9 2.1 4.7 3.4 7.8 3.4 3.3 0 6.3-1.5 8.2-3.9-4-2.9-10.4-3.2-14.8-.3z"/>\n  <path fill="#D9765A" d="M154.4 136.6c0 2.8 1.1 5.3 2.8 7.2l1.2-.8a13.5 13.5 0 0114.8.4c1.5-1.8 2.4-4.2 2.4-6.8h-21.2z"/>', 5, 1, 3, 0),
('Solemn', '<path fill="#8E2829" d="M166.5 141.5c-7.4 0-10.9-.5-11.1-.5-.5-.1-.9-.6-.8-1.1.1-.5.6-.9 1.1-.8.1 0 6 .8 18.7.3.5 0 1 .4 1 1s-.4 1-1 1l-7.9.1z"/>', 5, 1, 3, 0),
('Agape', '<path fill="#D9765A" d="M172.5,143c0.5-0.5,0.8-1.2,0.8-2c0-1.6-1.3-2.9-2.9-2.9h-10.8c-1.6,0-2.9,1.3-2.9,2.9\n	c0,0.7,0.3,1.4,0.7,1.9C162.6,141.1,167.7,141.6,172.5,143z"/>\n<path fill="#EBA17A" d="M157.5,142.9c0.5,0.6,1.3,1,2.1,1h10.8c0.8,0,1.5-0.3,2.1-0.9C167.7,141.6,162.6,141.1,157.5,142.9z"/>', 5, 1, 3, 0),
('Smile', '<path fill="#8E2829" d="M165.1 140.7h-.2c-4.7-.1-8-4.9-8.1-5.1-.3-.5-.2-1.1.3-1.4.5-.3 1.1-.2 1.4.3 0 0 2.8 4.2 6.5 4.2 2.2 0 4.4-1.4 6.5-4.3.3-.4.9-.5 1.4-.2.4.3.5 1 .2 1.4-2.4 3.4-5.1 5.1-8 5.1z"/>', 5, 1, 3, 0),
('Wide', '<circle fill="#fff" cx="145.6" cy="114.9" r="6.6"/>\n  <path fill="#966A07" d="M154.5 116.5c-.4 0-.8-.2-.9-.6-1.8-4.3-4.1-6.7-6.8-7.2-4.3-.8-8.5 3.5-8.5 3.5a1 1 0 01-1.4 0 1 1 0 010-1.4c.2-.2 5-5.1 10.3-4.1 3.4.6 6.2 3.5 8.3 8.4.2.5 0 1.1-.5 1.3l-.5.1z"/>\n  <circle class="color1" cx="145.6" cy="114.9" r="4.7"/>\n  <circle fill="#fff" cx="184.4" cy="114.9" r="6.6"/>\n  <path fill="#966A07" d="M175.5 116.5c.4 0 .8-.2.9-.6 1.8-4.3 4.1-6.7 6.8-7.2 4.3-.8 8.5 3.5 8.5 3.5.4.4 1 .4 1.4 0 .4-.4.4-1 0-1.4-.2-.2-5-5.1-10.3-4.1-3.4.6-6.2 3.5-8.3 8.4-.2.5 0 1.1.5 1.3l.5.1z"/>\n  <circle class="color1" cx="184.4" cy="114.9" r="4.7"/>', 8, 1, 3, 1),
('Round', '<circle fill="#fff" cx="147.1" cy="114.8" r="5.8"/>\n<circle class="color1" cx="147.1" cy="114.8" r="3.9"/>\n<circle fill="#fff" cx="182.9" cy="114.8" r="5.8"/>\n<circle class="color1" cx="182.9" cy="114.8" r="3.9"/>', 8, 1, 3, 1),
('Angry', '<path fill="#fff" d="M150.5 114.2l.1 1.1c-.1 2.3-2 4.1-4.3 4s-4.1-2-4-4.3c0-.9.4-1.7.9-2.4l-2.8-.6c-.5.9-.7 1.9-.8 2.9a7 7 0 0013.8.4v-.6l-2.9-.5z"/>\n  <path class="color1" d="M142.3 115c-.1 2.3 1.7 4.2 4 4.3s4.2-1.7 4.3-4l-.1-1.1-7.3-1.6c-.6.7-.9 1.5-.9 2.4z"/>\n  <path fill="#A6670A" d="M143.2 123.4h-.1c-5.8-1.6-5.7-7.7-5.7-7.8 0-.3.2-.5.5-.5s.5.2.5.5c0 .2-.1 5.4 5 6.8.3.1.4.3.4.6-.2.3-.4.4-.6.4z"/>\n  <path fill="#fff" d="M179.5 114.2l-.1 1.1c.1 2.3 2 4.1 4.3 4s4.1-2 4-4.3c0-.9-.4-1.7-.9-2.4l2.8-.6c.5.9.7 1.9.8 2.9a7 7 0 01-13.8.4v-.6l2.9-.5z"/>\n  <path class="color1" d="M187.7 115c.1 2.3-1.7 4.2-4 4.3a4.1 4.1 0 01-4.2-5.1l7.3-1.6c.6.7.9 1.5.9 2.4z"/>\n  <path fill="#A6670A" d="M186.8 123.4h.1c5.8-1.6 5.7-7.7 5.7-7.8 0-.3-.2-.5-.5-.5s-.5.2-.5.5c0 .2.1 5.4-5 6.8-.3.1-.4.3-.4.6.2.3.4.4.6.4z"/>', 8, 1, 3, 1),
('Serious', '<ellipse class="color1" cx="148.2" cy="114.5" rx="3.8" ry="4.4"/>\n<ellipse class="color1" cx="181.8" cy="114.5" rx="3.8" ry="4.4"/>\n<path fill="#59413C" d="M154.4,117.8c-0.4,0-0.8-0.3-0.9-0.7c-1.1-3.1-2.6-4.9-4.6-5.5c-3.4-1-7,1.8-7.1,1.9\nc-0.4,0.3-1.1,0.3-1.4-0.2c-0.3-0.4-0.3-1.1,0.2-1.4c0.2-0.1,4.5-3.5,8.9-2.2c2.6,0.8,4.6,3.1,5.9,6.8c0.2,0.5-0.1,1.1-0.6,1.3\nC154.7,117.8,154.6,117.8,154.4,117.8z"/>\n<path fill="#59413C" d="M175.6,117.8c-0.1,0-0.2,0-0.3-0.1c-0.5-0.2-0.8-0.8-0.6-1.3c1.3-3.7,3.3-6,5.9-6.8\nc4.3-1.3,8.7,2.1,8.9,2.2c0.4,0.3,0.5,1,0.2,1.4c-0.3,0.4-1,0.5-1.4,0.2c0,0-3.7-2.8-7.1-1.9c-2,0.6-3.5,2.4-4.6,5.5\nC176.4,117.5,176,117.8,175.6,117.8z"/>', 8, 1, 3, 1),
('Small', '<ellipse class="color1" cx="148.8" cy="116.2" rx="3.5" ry="3.9"/>\n<ellipse class="color1" cx="181.2" cy="116.2" rx="3.5" ry="3.9"/>', 8, 1, 3, 1),
('Elf Female', '<path class="color1" d="M109.7 216.3c.1 4.1 7.6 57.9 13.9 70.4l81.8-.1c6.4-12.6 13.9-66.1 13.9-70.2l-109.6-.1zM218.1 88.3l-8 9.7-4.7 5.7c0-61-80.5-62.3-80.5 1.4l-4.7-5.7-7.9-9.6s-3.5 19.7 7.9 34a33 33 0 008 7.2 52.4 52.4 0 0016.6 29.5l.2.2 1.7 1.4.4.3 8.9 8.9 9 9 9-9 8.2-8.2 1.5-1.1 1.1-.9.1-.1a54 54 0 0017.5-31.7c3.1-2.1 5.6-4.4 7.7-7 11.5-14.2 8-34 8-34zM143.5 388.6l-.2.1-17 6.5c-1.1.4-8.2 2.4-10.4 3.6-1.3.7-2.4 1.9-2.1 3.5.6 4.5 5.8 4.1 9.2 4.1l16-.1 9.5-.1 5.7.1c1.8.1 3.7-.2 4.8-1.8 1.1-1.5 1.3-3.6 1.4-5.4.1-1.3 0-2.7-.2-4-.1-.6-.4-2.8-.9-4.3l-.1-.4.9-27.3 3.6-70-33.5-.2 13.3 95.7zM186.5 388.6l.2.1 17 6.5c1.1.4 8.2 2.4 10.4 3.6 1.3.7 2.4 1.9 2.1 3.5-.6 4.5-5.8 4.1-9.2 4.1l-16-.1-9.5-.1-5.7.1c-1.8.1-3.7-.2-4.8-1.8-1.1-1.5-1.3-3.6-1.4-5.4-.1-1.3 0-2.7.2-4 .1-.6.4-2.8.9-4.3l.1-.4-.9-27.3-3.6-70 33.5-.2-13.3 95.7z"/><path opacity=".2" d="M178.3 165.5a29 29 0 01-25.3.3c-2.9-1.3-5-2.7-6-3.3l17.9 18.4 17.2-17.7c-1.2.9-2.5 1.6-3.8 2.3z"/><path opacity=".3" d="M125 110.1l-5-5.8-4.2-4.9s-.4 10.1 4.2 18.9c1.6 3.1 3.9 6.1 7 8.4-1-5.4-1.6-11-2-16.6zm89.4-12.2l-4.2 4.9-5 5.8c-.3 5.5-.8 11-1.8 16.5a25 25 0 006.8-8.2c4.6-8.9 4.2-19 4.2-19z"/><path opacity=".1" d="M127.1 126.7a24.2 24.2 0 01-7-8.4c-4.6-8.8-4.2-18.9-4.2-18.9l4.2 4.9 5 5.8v-.4l-.2-4.6-4.7-5.7-7.9-9.6s-3.5 19.7 7.9 34a33 33 0 008 7.2 30 30 0 01-1.1-4.3zm91-38.4l-8 9.7-4.7 5.7-.2 4.9 5-5.8 4.2-4.9s.4 10.1-4.2 19a25 25 0 01-6.8 8.2l-.9 4.3c3.1-2.1 5.6-4.4 7.7-7 11.4-14.3 7.9-34.1 7.9-34.1z"/>', 1, 2, 3, 1),
('Blouse', '<path class="color1" d="M206.4 221.5h14.8a76.3 76.3 0 00-7.2-31.4 62.4 62.4 0 00-20.8-23.8l-8.3-5.2-.1.1-1.1.9-1.5 1.1-8.2 8.2-9 9-9-9-8.9-8.9-.4-.3-1.7-1.4-.2-.2-8.5 5.2a62.4 62.4 0 00-26.6 38.5c-1.2 5.2-2 10.9-2.1 17.2h14.8v51.8c.2.1 6 3 10 4.4a93.5 93.5 0 0071.9-3.2l2.2-1.3v-51.7z"/>\n  <g class="color2">\n    <circle class="st9" cx="165.3" cy="187.6" r="2.7"/>\n    <circle class="st9" cx="165.3" cy="195.8" r="2.7"/>\n    <circle class="st9" cx="165.3" cy="204.1" r="2.7"/>\n  </g>', 4, 2, 3, 2),
('Skirt', '<path class="color1" d="M204.9 273.8a93.6 93.6 0 01-72.7 3.6c-4-1.4-7-3-8.7-3.8l-1.6 13.1-4.3 35.1s7.2 4.1 18.7 7.5a103.9 103.9 0 0031.7 4.4 90.1 90.1 0 0044.3-12.7l-7.4-47.2z"/>', 3, 2, 3, 1),
('Open Shoes', '<path class="color1" d="M160.5 363.7a1 1 0 00-1.1-.7l-18.7-1.1h-.5c-1.1.4-.8 1.8.1 2.2l8.7 6c-11.2 8.1-9.6 4-.1 13.5l-5.6 4.7-4.5 2 4.7 4.7c-6.5 1.9-14 5.4-20.6 1-3.2 1.3-9.4 1.8-9.4 6.2.6 4.5 6 4.3 9.4 4.3 8.3-.1 23.7-.2 30.6-.1 8.8 1.1 7.6-10.3 5.8-16.1l-.3-.3-7.1-6.5c9.6-9.3 12-5.8.6-13.7l7.5-5.3c.4 0 .6-.4.5-.8zm-3.4 27.4c-5.3 1.4-9.7-.2-12-1.5l5.3-4.6 6.7 6.1zm.4-15l-7.1 6.3-6.7-6.1 7-4.9 6.8 4.7zm-6.7-7.3l-7.1-4.9 13.1.7-6 4.2zM169.5 363.7a1 1 0 011.1-.7l18.7-1.1h.5c1.1.4.8 1.8-.1 2.2l-8.7 6c11.2 8.1 9.6 4 .1 13.5l5.6 4.7 4.5 2-4.7 4.7c6.5 1.9 14 5.4 20.6 1 3.2 1.3 9.4 1.8 9.4 6.2-.6 4.5-6 4.3-9.4 4.3-8.3-.1-23.7-.2-30.6-.1-8.8 1.1-7.6-10.3-5.8-16.1l.3-.3 7.1-6.5c-9.6-9.3-12-5.8-.6-13.7l-7.5-5.3c-.4 0-.6-.4-.5-.8zm3.4 27.4c5.3 1.4 9.7-.2 12-1.5l-5.3-4.6-6.7 6.1zm-.4-15l7.1 6.3 6.7-6.1-7-4.9-6.8 4.7zm6.7-7.3l7.1-4.9-13.1.7 6 4.2z"/>', 2, 2, 3, 1),
('Side Part', '<path class="color1" d="M135 148.9a54 54 0 01-5.6-12.8l-1.3-5a35 35 0 01-8-7.2v42.3c0 2.9 2.3 5.2 5.2 5.2h3.7a83.2 83.2 0 0115.7-10.8 53 53 0 01-9.7-11.7zM202.5 129.4l-1.7 6.7a52.4 52.4 0 01-15.8 25l8.3 5.2c2.1 1.5 4.4 3.2 6.5 5.1h5.2c2.8 0 5.1-2.3 5.2-5.1v-44c-2.1 2.6-4.6 5-7.7 7.1zM210.1 98a45 45 0 00-90 1.4l4.7 5.7a56 56 0 0018.4-22.9c1.1 6.9 5.2 17.6 20.8 17.6h35.7c1.5 1.7 3.3 3.1 5.6 4l4.8-5.8z"/>', 10, 2, 3, 1),
('Wavy', '<path class="color1" d="M188.7 87.5s1.5 11.6 15.9 16.2l.4.7 7.7-9.7c0-.8-.3-1.6-.5-2.4a38.8 38.8 0 00-6.3-12.3c-2.9-4-6-7.9-9.6-11.3-2-1.9-4.2-3.6-6.5-5.1l-.5-.3-1.2-.7-.8-.5-1.1-.6-.8-.4-1.3-.6-.4-.2-3.5-1.5a45.6 45.6 0 00-33.7 1.5l-.4.2-1.3.6-.8.4-1.1.6-.8.5-1.1.7-.5.3a45.6 45.6 0 00-6.5 5.1 69.1 69.1 0 00-9.6 11.3 36 36 0 00-6.3 12.3 9.6 9.6 0 00-.2 4.1l6.8 8.7s18.1-5.9 24.3-25.4c2.3 6.7 8.8 19 24.5 19V83.4s1.6 15.5 15.5 15.5V87.5zM219 165.4c-1.8-2.4-5.4-4.8-4-8.4.7-1.9 2.1-3.5 3.2-5.3l.4-.9c1.8-3.9.7-7.9-1.6-11.2-1.7-2.5-4-5.5-3.1-8.8.6-2.3 1.8-4.4 2.4-6.8.9-3.8.2-6.6-1.3-9.5a35.3 35.3 0 01-12.7 14.8l-1.7 6.7a52.4 52.4 0 01-15.6 25.1l8.1 5.1c3.1 2.1 6.4 4.5 9.5 7.6l19.4.1c-.3-2.9-1.2-5.9-3-8.5zM135.1 148.9a54 54 0 01-5.6-12.8l-1.3-5a33.1 33.1 0 01-12.7-15.2c-1.2 2.5-1.7 5-1 8.3.5 2.3 1.8 4.5 2.4 6.8.9 3.3-1.4 6.2-3.1 8.8-2.3 3.4-3.4 7.3-1.6 11.2l.7 1.2c1 1.6 2.3 3.2 2.9 4.9 1.4 3.6-2.3 6-4 8.4a14.4 14.4 0 00-2.8 8.5h17.5c3.3-3.3 6.7-6 10-8.2l8.5-5.2a46.6 46.6 0 01-9.9-11.7z"/>', 10, 2, 3, 1),
('Middle Part', '<path class="color1" d="M165 52.1a45.2 45.2 0 00-45.2 45.2V99l.3.3 4.7 5.7v-1.1c30.4-7.9 38.6-30.3 39.5-33.2 2.2 7.3 10.5 25.3 40.9 31.9l.1 1.1L210 98l.1-.1v-.6A45.1 45.1 0 00165 52.1zM199.8 171.3l3.6 3.5h6.8v-52.4c-2 2.6-4.6 4.9-7.7 7l-1.7 6.7a52.4 52.4 0 01-15.8 25l8.3 5.2c2.1 1.4 4.3 3.1 6.5 5zM125.3 174.8l3.6-3.5a83.2 83.2 0 0115.7-10.8 53 53 0 01-15.3-24.5l-1.3-5a35 35 0 01-8-7.2l-.2-.3v51.3h5.5z"/>', 10, 2, 3, 1),
('Bob', '<path class="color1" d="M217.1 108a37.4 37.4 0 01-6.9 14.4c-2 2.6-4.6 4.9-7.7 7l-1.7 6.7a52.4 52.4 0 01-5.6 12.8l-.5.8c7.3-.1 14.2-2.8 20.7-6.3a9.8 9.8 0 005.2-7.4c1.4-9.3-1.2-19-3.5-28zM129.3 136l-1.3-5a35 35 0 01-15.2-22.8c-2.3 8.9-4.8 18.5-3.4 27.7a10 10 0 005.2 7.4 44.6 44.6 0 0020.8 6.3l-.5-.8a61.3 61.3 0 01-5.6-12.8zM205.4 102.8v.9l4.7-5.7 3.1-3.7-3-9.3-2-5.6c-9.8-31.8-46.9-26.1-46.9-26.1l.1.1c-7.2.2-31.3 2.6-39.9 27l-.3.9-1.3 3.7a198 198 0 00-3.3 10.1l3.6 4.3 4.7 5.7v-1s14.6-4.6 20.2-22.4c-.1 0 3.6 22.6 60.3 21.1z"/>', 10, 2, 3, 1),
('Straight Bangs', '<path class="color1" d="M129 171.3a83.2 83.2 0 0115.7-10.8 53 53 0 01-15.3-24.5l-1.3-5a33.5 33.5 0 01-7.4-6.5v55.8c2.6-3.4 5.4-6.4 8.3-9zM209.3 123.3a33.2 33.2 0 01-6.8 6l-1.7 6.7a52.4 52.4 0 01-15.8 25l8.3 5.2a72.3 72.3 0 0115.2 14.6h.9v-57.5zM124.8 105.1c0-63.7 80.5-62.4 80.5-1.4l4-4.8v-.8a44.3 44.3 0 00-88.6 0v2l4.1 5z"/>\n  <path class="color1" d="M204.7 89.4a38 38 0 00-1.8-7.4l-.4-1.2c-.6-1.5-1.3-2.9-2.1-4.2l-.1-.1-.5-.8-.8-1.3-1.4-1.9a40.7 40.7 0 00-32.5-15.9h-.5l-1.7.1a40.4 40.4 0 00-33 20.1c-.8 1.3-1.4 2.7-2 4.1l-.5 1.4a57.4 57.4 0 00-2.5 21.9c4.6-2.9 7.3-7.3 8.8-11.6a21 21 0 0014 5.4h34.9c5.4 0 10.2-2 13.9-5.3 1.6 4.2 4.2 8.6 8.8 11.5v-1.4-4.2a70 70 0 00-.6-9.2z"/>', 10, 2, 3, 1),
('Sleepy', '<circle class="color1" cx="182" cy="115.2" r="3.5"/>\n  <circle class="color1" cx="148.3" cy="115.2" r="3.5"/>\n  <path class="color2" d="M190 111.1c.1-.2.1-.5-.2-.7-.2-.2-.5-.1-.7.2 0 0-.8 1.2-2.1 1.8-1.6-1-3.7-2.1-5.8-1.9a6.2 6.2 0 00-4.6 2.9c-.3.4-.2.9.2 1.2.4.3.9.2 1.2-.2.9-1.3 2-2.1 3.3-2.2 3.3-.3 6.9 3.3 7 3.4.2.2.5.3.8.3l.5-.2c.3-.3.4-.9 0-1.2l-1.6-1.4c1.2-.8 1.9-1.9 2-2zM140.2 111.1a.5.5 0 01.2-.7c.2-.2.5-.1.7.2 0 0 .8 1.2 2.1 1.8 1.6-1 3.7-2.1 5.8-1.9 1.8.2 3.4 1.1 4.6 2.9.3.4.2.9-.2 1.2-.4.3-.9.2-1.2-.2-.9-1.3-2-2.1-3.3-2.2-3.3-.3-6.9 3.3-7 3.4-.2.2-.5.3-.8.3l-.5-.2c-.3-.3-.4-.9 0-1.2l1.6-1.4c-1.2-.8-1.9-1.9-2-2z"/>', 8, 2, 3, 2),
('Wide', '<circle fill="#fff" cx="147.7" cy="118.3" r="5.3"/>\n<circle class="color1" cx="147.7" cy="118.3" r="3.6"/>\n<circle fill="#fff" cx="182.3" cy="118.3" r="5.3"/>\n<circle class="color1" cx="182.3" cy="118.3" r="3.6"/>', 8, 2, 3, 1),
('Eyelashes', '<path d="M145.3 114c-2.1 0-3.6-1.8-3.7-1.9-.2-.2-.1-.5.1-.6.2-.2.5-.1.6.1 0 0 1.3 1.6 3 1.6.2 0 .4.2.4.4s-.1.4-.4.4zm1.3-1.2c-2.1 0-3.6-1.8-3.7-1.9-.2-.2-.1-.5.1-.6.2-.2.5-.1.6.1 0 0 1.3 1.6 3 1.6.2 0 .4.2.4.4s-.2.4-.4.4zM184.9 114c2.1 0 3.6-1.8 3.7-1.9.2-.2.1-.5-.1-.6-.2-.2-.5-.1-.6.1 0 0-1.3 1.6-3 1.6-.2 0-.4.2-.4.4s.2.4.4.4zm-1.2-1.2c2.1 0 3.6-1.8 3.7-1.9.2-.2.1-.5-.1-.6-.2-.2-.5-.1-.6.1 0 0-1.3 1.6-3 1.6-.2 0-.4.2-.4.4s.1.4.4.4z"/>\n  <path fill="#fff" d="M187.8 115.8a5 5 0 01-4.9 5.1 5 5 0 01-4.9-5.1 5 5 0 014.9-5.1 5 5 0 014.9 5.1zM142.5 115.8a5 5 0 004.9 5.1 5 5 0 004.9-5.1 5 5 0 00-4.9-5.1 5 5 0 00-4.9 5.1z"/>\n  <path class="color1" d="M186.3 115.8c0 2-1.6 3.6-3.5 3.6a3.6 3.6 0 01-3.5-3.6c0-2 1.6-3.6 3.5-3.6 2 0 3.5 1.6 3.5 3.6zM143.9 115.8c0 2 1.6 3.6 3.5 3.6s3.5-1.6 3.5-3.6-1.6-3.6-3.5-3.6a3.6 3.6 0 00-3.5 3.6z"/>\n  <path class="color2" d="M142.8 114.7h-.1c-.2-.1-.4-.3-.3-.5 0-.1.8-3.2 4.6-3.8.1 0 3.4-.4 5.2 2.8.1.2 0 .5-.2.6-.2.1-.5 0-.6-.2-1.5-2.7-4.3-2.4-4.3-2.4-3.1.4-3.8 3.1-3.8 3.1-.1.2-.3.4-.5.4zm44.6 0h.1c.2-.1.4-.3.3-.5 0-.1-.8-3.2-4.6-3.8-.1 0-3.4-.4-5.2 2.8-.1.2 0 .5.2.6.2.1.5 0 .6-.2 1.5-2.7 4.3-2.4 4.3-2.4 3.1.4 3.8 3.1 3.8 3.1.1.2.3.4.5.4z"/>', 8, 2, 3, 2),
('Eyeshadow', '<path opacity=".5" d="M154.6 117a.8.8 0 01-.7-.5c-1.4-3.4-3.3-5.3-5.4-5.7-3.4-.6-6.8 2.8-6.8 2.8-.3.3-.8.3-1.1 0a.8.8 0 010-1.1c.2-.2 3.9-4.1 8.2-3.3 2.7.5 4.9 2.7 6.6 6.7.2.4 0 .9-.4 1l-.4.1zM175.4 117c.3 0 .6-.2.7-.5 1.4-3.4 3.3-5.3 5.4-5.7 3.4-.6 6.8 2.8 6.8 2.8.3.3.8.3 1.1 0 .3-.3.3-.8 0-1.1-.2-.2-3.9-4.1-8.2-3.3-2.7.5-4.9 2.7-6.6 6.7-.2.4 0 .9.4 1l.4.1z"/>\n  <circle fill="#fff" cx="147.6" cy="115.8" r="5.2"/>\n  <circle class="color1" cx="147.6" cy="115.8" r="3.7"/>\n  <circle fill="#fff" cx="182.4" cy="115.8" r="5.2"/>\n  <circle class="color1" cx="182.4" cy="115.8" r="3.7"/>', 8, 2, 3, 1),
('Small', '<circle class="color1" cx="148.7" cy="116.9" r="3.7"/>\n	<circle class="color1" cx="181.3" cy="116.9" r="3.7"/>', 8, 2, 3, 1),
('Straight', '<path class="color1" d="M156.2 106.1c0 1-.8 1.8-1.8 1.8h-12.7c-1 0-1.8-.8-1.8-1.8s.8-1.8 1.8-1.8h12.7c1 0 1.8.8 1.8 1.8zM190.2 106.1c0 1-.8 1.8-1.8 1.8h-12.7c-1 0-1.8-.8-1.8-1.8s.8-1.8 1.8-1.8h12.7c1 0 1.8.8 1.8 1.8z"/>', 9, 2, 3, 1),
('Curved', '<path class="color1" d="M140 107.2s8-9.2 16.1-2.1l-16.1 2.1zM190 107.2s-8-9.2-16.1-2.1l16.1 2.1z"/>', 9, 2, 3, 1),
('Tapered', '<path class="color1" d="M154.4 102.2l.3 3.4-18.4 1 1.9-1.4c1.6-1.2 3-2.1 5-2.4a41.7 41.7 0 0111.2-.6zM175.6 102.2l-.3 3.4 18.4 1-1.9-1.4c-1.6-1.2-3-2.1-5-2.4a41.7 41.7 0 00-11.2-.6z"/>', 9, 2, 3, 1),
('Small', '<path class="color1" d="M151.1 100.5H148c-1.6 0-2.9 1.2-2.9 2.6 0 1.4 1.3 2.6 2.9 2.6h3.1c1.6 0 2.9-1.2 2.9-2.6 0-1.4-1.3-2.6-2.9-2.6zM182 100.5h-3.1c-1.6 0-2.9 1.2-2.9 2.6 0 1.4 1.3 2.6 2.9 2.6h3.1c1.6 0 2.9-1.2 2.9-2.6 0-1.4-1.3-2.6-2.9-2.6z"/>', 9, 2, 3, 1),
('Arched', '<path class="color1" d="M153.9 109.4a1 1 0 01-.8-.4c-1.3-1.7-2.7-2.5-4.2-2.6h-.1c-2.6 0-4.7 2.5-4.8 2.6-.4.4-1 .5-1.4.1-.4-.3-.5-1-.1-1.4.1-.1 2.7-3.3 6.3-3.3h.1c2.1 0 4 1.1 5.7 3.3.3.4.3 1.1-.2 1.4-.1.2-.3.3-.5.3zM176.1 109.4c.3 0 .6-.1.8-.4 1.3-1.7 2.7-2.5 4.2-2.6h.1c2.6 0 4.7 2.5 4.8 2.6.4.4 1 .5 1.4.1.4-.3.5-1 .1-1.4-.1-.1-2.7-3.3-6.3-3.3h-.1c-2.1 0-4 1.1-5.7 3.3-.3.4-.3 1.1.2 1.4.1.2.3.3.5.3z"/>', 9, 2, 3, 1),
('Happy', '<path fill="#EBA17A" d="M158.4 142.5c-.5.2-.9.5-1.2.8 1.9 2.1 4.7 3.4 7.8 3.4 3.3 0 6.3-1.5 8.2-3.9-4-3-10.4-3.2-14.8-.3z"/>\n  <path fill="#D9765A" d="M154.4 136.1c0 2.8 1.1 5.3 2.8 7.2l1.2-.8a13.5 13.5 0 0114.8.4c1.5-1.8 2.4-4.2 2.4-6.8h-21.2z"/>', 5, 2, 3, 0),
('Smile', '<path fill="#AD542C" d="M165.5 142.3h-.4c-6.2-.2-10.4-6-10.6-6.2a1 1 0 01.2-1.4 1 1 0 011.4.2c0 .1 3.8 5.2 9 5.4 3.2.1 6.4-1.7 9.5-5.5.3-.4 1-.5 1.4-.1.4.4.5 1 .1 1.4-3.3 4.1-6.9 6.2-10.6 6.2z"/>', 5, 2, 3, 0),
('Smirk', '<path fill="#AD542C" d="M185 132c-4.6.2-6-3.8-6.1-3.9-.2-.5-.8-.8-1.3-.7-.6.2-.9.8-.7 1.3.3 1 1.1 2.4 2.5 3.5a16 16 0 01-9.9 6.8c-5.9 1.1-11-2.3-11.1-2.3-.5-.3-1.1-.2-1.5.3-.3.5-.2 1.1.3 1.4.2.1 4.3 2.9 9.8 2.9l2.8-.3c4.3-.8 8.1-3.4 11.3-7.7a8 8 0 003.6.8h.3c.6 0 1-.5 1-1.1.1-.6-.4-1-1-1z"/>', 5, 2, 3, 0),
('Serious', '<path fill="#AD542C" d="M172.8 137.4h-15.7c-.6 0-1-.4-1-1s.4-1 1-1h15.7c.6 0 1 .4 1 1s-.4 1-1 1z"/>', 5, 2, 3, 0),
('Wow', '<path fill="#D9765A" d="M170.3 142.9c.2-.5.3-1 .3-1.6 0-2.6-2.1-4.8-4.8-4.8h-1.7a4.8 4.8 0 00-4.7 5.8c3.7-1.4 7.3-2 10.9.6z"/>\n  <path fill="#EBA17A" d="M159.5 142.2a4.7 4.7 0 004.7 3.8h1.7c2.1 0 3.8-1.3 4.5-3.1-3.7-2.6-7.3-2-10.9-.7z"/>', 5, 2, 3, 0),
('Large', '<path class="color1" opacity=".6" d="M172.6 123.3a3 3 0 00-4-1.1v-5.4c0-2-1.6-3.6-3.6-3.6s-3.6 1.6-3.6 3.6v5.4c-1.4-.7-3.2-.3-4 1.1a3 3 0 001 4.2l4.8 3 .3.2c.5.2 1 .4 1.6.4.7 0 1.3-.2 1.8-.5h.1l.1-.1 4.6-2.9c1.4-.9 1.8-2.8.9-4.3z"/>\n  <path opacity=".6" d="M171.7 127.6l-4.6 2.9-.1.1h-.1c-.5.3-1.2.5-1.8.5-.6 0-1.1-.2-1.6-.4l-.3-.2-4.8-3a3.1 3.1 0 01-1.4-2.3 3 3 0 001.4 2.8l4.8 3 .3.2c.5.2 1 .4 1.6.4.7 0 1.3-.2 1.8-.5h.1l.1-.1 4.6-2.9a3 3 0 001.4-2.8c-.1.9-.6 1.7-1.4 2.3z"/>', 7, 2, 3, 1),
('Narrow', '<path class="color1" opacity=".6" d="M169.1 124.4c0 2.3-1.9 4.1-4.1 4.1a4.1 4.1 0 01-4.1-4.1v-6.8c0-2.3 1.9-4.1 4.1-4.1 2.3 0 4.1 1.9 4.1 4.1v6.8z"/>\n  <path opacity=".6" d="M165 128.5a4.1 4.1 0 01-4.1-4.1v.5c0 2.3 1.9 4.1 4.1 4.1s4.1-1.9 4.1-4.1v-.5a4 4 0 01-4.1 4.1z"/>', 7, 2, 3, 1),
('Small', '<path class="color1" opacity=".6" d="M165 126.7a4.4 4.4 0 01-4.4-4.3v-2.9c0-2.4 2-4.3 4.4-4.3 2.4 0 4.4 1.9 4.4 4.3v2.9c0 2.4-2 4.3-4.4 4.3z"/>\n  <path opacity=".6" d="M165 126.7a4.4 4.4 0 01-4.4-4.3v.5c0 2.4 2 4.3 4.4 4.3s4.4-1.9 4.4-4.3v-.5c0 2.4-2 4.3-4.4 4.3z"/>', 7, 2, 3, 1),
('Triangular', '<path class="color1" opacity=".6" d="M169.8 125.8l-.1-.4-.6-2.5-.7-2.7-.6-2.6-.5-2.2c-.2-.9-.4-1.8-1.2-2.4-.5-.3-1.2-.5-1.8-.2-.4.2-.7.5-.9.8l-.4 1.2-.6 2.2-.7 2.8-.7 2.9-.6 2.4-.1.2-.1.3v.3a2 2 0 001.7 2h6.3a2 2 0 001.7-2c-.1.1-.1 0-.1-.1z"/>\n  <path opacity=".6" d="M168.1 128.1h-6.3a2 2 0 01-1.7-1.7v.2a2 2 0 001.7 2h6.3a2 2 0 001.7-2v-.2c-.1.8-.8 1.5-1.7 1.7z"/>', 7, 2, 3, 1),
('Medium', '<path class="color1" opacity=".6" d="M169.2 125.8h-.6v-8c0-2-1.6-3.6-3.6-3.6s-3.6 1.6-3.6 3.6v8h-.6c-1.2 0-2.1 1-2.1 2.1 0 1.2 1 2.1 2.1 2.1h1.2c.6 1 1.7 1.6 3 1.6 1.2 0 2.3-.6 3-1.6h1.2c1.2 0 2.1-1 2.1-2.1s-1-2.1-2.1-2.1z"/>\n  <path opacity=".6" d="M169.2 130.1H168c-.6 1-1.7 1.6-3 1.6-1.2 0-2.3-.6-3-1.6h-1.2a2 2 0 01-2.1-1.9v.3c0 1.2 1 2.1 2.1 2.1h1.2c.6 1 1.7 1.6 3 1.6 1.2 0 2.3-.6 3-1.6h1.2c1.2 0 2.1-1 2.1-2.1v-.3a2 2 0 01-2.1 1.9z"/>', 7, 2, 3, 1),

-- Dwarf Parts
('Dwarf Female', '<path class="color1" d="M247 254.2v-31.8l-163.9.1H83v97.9c-.1 3.1 1.4 9.2 7.7 10.3l.6.1c1 0 1.9-.4 2.6-1.1.9.5 1.9.9 3 1.1l.6.1c1 0 1.9-.4 2.6-1.1.9.5 1.9.9 3 1.1l.6.1c1 0 1.9-.4 2.6-1.1.7.4 1.4.7 2.3.9l.7.2.6.1c1.7 0 3.2-1.2 3.5-2.9.3-1.9-.9-3.7-2.8-4.1-1.9-.3-1.9-3-1.9-3.4v-10c-.1-.4.1-.6.3-.6s.3.1.4.3c.5 2 .8 4.6.8 8.2 0 1.9 1.4 3.5 3.2 3.5 1.8 0 3.2-1.6 3.2-3.5v-.4c0-4-.4-7.2-1.1-9.8-.8-3.2-1.9-5.4-3-6.9l-1.1-1.3a9 9 0 00-2.7-1.9v-68h-.2 112.8v67.9c-.7.3-1.7.8-2.7 1.9l-1.2 1.5a18.2 18.2 0 00-2.9 6.8c-.6 2.5-1 5.6-1.1 9.4v.8c0 1.9 1.4 3.5 3.2 3.5 1.8 0 3.2-1.6 3.2-3.5 0-3.6.3-6.2.8-8.2.1-.2.2-.3.4-.3s.3.1.4.3v10.1c0 .4 0 3-1.9 3.4a3.5 3.5 0 00-2.8 4.1 3.5 3.5 0 003.5 2.9l.6-.1.7-.2c.9-.2 1.6-.5 2.3-.9.7.7 1.6 1.1 2.6 1.1l.6-.1c1.2-.2 2.2-.6 3-1.1.7.7 1.6 1.1 2.6 1.1l.6-.1c1.2-.2 2.2-.6 3-1.1.7.7 1.6 1.1 2.6 1.1l.6-.1c6.3-1.1 7.8-7.3 7.7-10.3v-64.9l-.1-1.1zm-50.8-120.3a31.2 31.2 0 00-62.4 0 9.7 9.7 0 00-9.6 9.7v4.1c0 5.3 4.3 9.6 9.6 9.7v5.3c0 14.2 9.5 26.1 22.4 30h-.1l8.9 19.1 8.9-19.1h-.1a31.3 31.3 0 0022.4-29.9v-5.3a9.6 9.6 0 009.5-9.7v-4.1a9.5 9.5 0 00-9.5-9.8z"/>\n  <path opacity=".2" d="M156.1 192.7l8.9 19.1 8.9-19.1a29.7 29.7 0 01-17.8 0z"/>\n  <path opacity=".3" d="M202.7 147.1v-2.8c0-3.7-2.9-6.7-6.6-6.7v16.2c3.6 0 6.6-3 6.6-6.7zM127.1 144.2v2.8c0 3.6 2.9 6.6 6.6 6.7v-16.1c-3.7 0-6.6 3-6.6 6.6z"/>\n  <path opacity=".1" d="M196.1 133.9v3.7c3.7 0 6.6 3 6.6 6.7v2.8c0 3.7-3 6.7-6.6 6.7v3.7c5.4 0 9.7-4.3 9.7-9.7v-4.1c0-5.5-4.3-9.8-9.7-9.8zm-69 13.1v-2.8c0-3.6 2.9-6.6 6.6-6.6v-3.7a9.7 9.7 0 00-9.6 9.7v4.1c0 5.3 4.3 9.6 9.6 9.7v-3.7a6.7 6.7 0 01-6.6-6.7z"/>', 1, 2, 2, 1),
('Tunic', '<path class="color1" d="M217.6 301.6l-8.5-36.6 12.3-34.7h.1l25.6-7.8v-1.6a62.7 62.7 0 00-50.9-61.6v3.5c0 14.3-9.6 26.3-22.7 30l-8.5 18-8.5-18a31.3 31.3 0 01-22.7-30.1v-3.5c-29 5.5-50.9 31-50.9 61.6v1.7h.1l25.6 7.8v.2l12.3 34.5-8.4 36.4c1.1 1.5 2.3 3.8 3 6.9.6 2.6 1 5.8 1.1 9.8l4.9 1.8a140 140 0 0049.1 7.5 116 116 0 0040.6-8.7l2.5-1c0-3.8.5-6.9 1.1-9.4.6-3 1.7-5.2 2.8-6.7z"/>\n  <path class="color2" d="M212.6 264.7c0 3.2-2.3 5.8-5.2 5.8h-84.7c-2.9 0-5.2-2.6-5.2-5.8 0-3.2 2.3-5.8 5.2-5.8h84.7c2.9 0 5.2 2.6 5.2 5.8z"/>\n  <path fill="#888" d="M170.3 207h-10.6c-.6 0-1-.4-1-1s.4-1 1-1h10.6c.6 0 1 .4 1 1s-.5 1-1 1zM172.7 202.3h-15.6c-.6 0-1-.4-1-1s.4-1 1-1h15.6c.6 0 1 .4 1 1s-.4 1-1 1z"/>', 4, 2, 2, 2),
('Pants', '<path class="color1" d="M120.9 356.6c0 6.7 2.3 12.9 6.1 17.3 4.7-.8 17-2.2 27.6.5a27.6 27.6 0 006.9-17.6v-47.9c-17.2-.5-31.6-4.4-40.6-7.5v55.2zM209.1 356.6c0 6.7-2.3 12.9-6.1 17.3-4.7-.8-17-2.2-27.6.5a27.6 27.6 0 01-6.9-17.6v-47.9c17.2-.5 31.6-4.4 40.6-7.5v55.2z"/>', 3, 2, 2, 1),
('Boots', '<path class="color1" d="M127 373.9c-1.6.5-2.3 1-3 1.4-3.1 1.5 8.2 17.5 5.6 18.6-28.1.3-24.4 12.9-24.4 12.6h45.2c5.7-.3 2.3-9.3 2.2-12.6.2-.9 5.4-15.9 5.3-16.7-2.4-6.9-29.4-3.9-30.9-3.3zM203 373.9c1.6.5 2.3 1 3 1.4 3.1 1.5-8.2 17.5-5.6 18.6 28.1.3 24.4 12.9 24.4 12.6h-45.2c-5.7-.3-2.3-9.3-2.2-12.6-.2-.9-5.4-15.9-5.3-16.7 2.4-6.9 29.4-3.9 30.9-3.3z"/>', 2, 2, 2, 1),
('Dwarf Male', '<path class="color1" d="M102.5 300.1a9.7 9.7 0 00-3-1.9v-76.3L71.9 219l-.5 35-.1 1.4v64.9a10 10 0 008.5 10.3l.7.1a4 4 0 002.8-1.1c.9.5 2 .9 3.3 1.1l.7.1a4 4 0 002.8-1.1c.9.5 2 .9 3.3 1.1l.7.1a4 4 0 002.8-1.1c.7.4 1.6.7 2.5.9l.8.2.7.1c1.8 0 3.5-1.2 3.8-2.9.4-1.9-1-3.7-3.1-4.1-2.1-.3-2.1-3-2.1-3.4v-10c0-.2.2-.4.4-.4s.3.1.4.3c.5 2 .9 4.6.9 8.2 0 1.9 1.6 3.5 3.5 3.5s3.5-1.6 3.5-3.5c0-4.2-.5-7.5-1.2-10.2a17.5 17.5 0 00-4.5-8.4zM259.1 254.1l-.2-35L231 222v76.3c-.7.3-1.6.7-2.6 1.5a15.9 15.9 0 00-4.7 7.7c-.9 2.8-1.4 6.4-1.4 11.1 0 1.9 1.6 3.5 3.5 3.5s3.5-1.6 3.5-3.5c0-3.6.4-6.2.9-8.2.1-.2.2-.3.4-.3s.4.2.4.4v10c0 .4 0 3-2.1 3.4-2.1.3-3.5 2.2-3.1 4.1.3 1.7 2 2.9 3.8 2.9l.7-.1.8-.2c.9-.2 1.8-.5 2.5-.9a4 4 0 002.8 1.1l.7-.1c1.3-.2 2.4-.6 3.3-1.1a4 4 0 002.8 1.1l.7-.1c1.3-.2 2.4-.6 3.3-1.1a4 4 0 002.8 1.1l.7-.1c6.9-1.1 8.6-7.3 8.5-10.3v-14.3-50.6l-.1-1.2zM199.5 133.8a34.7 34.7 0 00-69 0v.1a12 12 0 00-10 11.7v6c0 5.9 4.4 10.8 10 11.6a34.6 34.6 0 0025.7 29.4l8.8 20.2 8.8-20.2a34.5 34.5 0 0025.6-29.5v-.1c5.7-.8 10.1-5.7 10.1-11.6v-6c0-5.8-4.3-10.6-10-11.6zm6.8 16.7v.3-4.5c0-4.1-2.9-7.5-6.7-8.4v-.1c3.8.9 6.7 4.3 6.7 8.4v4.3z"/>\n  <path opacity=".1" d="M199.4 133.8c.2 1.3.2 2.7.2 4 3.8.9 6.7 4.3 6.7 8.4v4.6c-.1 4-2.9 7.3-6.7 8.2a51 51 0 01-.2 4c5.7-.8 10.1-5.7 10.1-11.6v-6c.1-5.8-4.3-10.7-10.1-11.6z"/>\n  <path opacity=".3" d="M199.7 137.9V159.1a8.9 8.9 0 006.7-8.2v-4.6c0-4.1-2.8-7.5-6.7-8.4z"/>\n  <path opacity=".1" d="M130.6 133.9c-.2 1.3-.2 2.7-.2 4a8.7 8.7 0 00-6.7 8.4v4.6c.1 4 2.9 7.3 6.7 8.2l.2 4a11.8 11.8 0 01-10.1-11.7v-6c0-5.7 4.3-10.6 10.1-11.5z"/>\n  <path opacity=".3" d="M130.3 138v21.4a8.9 8.9 0 01-6.7-8.2v-4.6c0-4.3 2.9-7.7 6.7-8.6z"/>\n  <path opacity=".2" d="M156.1 192.5l8.9 19.3 8.9-19.2a29 29 0 01-17.8-.1z"/>', 1, 1, 2, 1),
('Boots', '<path class="color1" d="M120.5 373.6L117 375a2 2 0 00-1.2 1.8l.2.7v.1l8.6 16.1c-33.6 0-29.7 12.6-29.7 12.6H148.3a4 4 0 004-4l-.1-.6-.1-.3-1.3-7.7 6-15.9.2-.6v-.4a2 2 0 00-1-1.7l-.2-.1-2.3-1a46.3 46.3 0 00-33-.4zM178.2 373.6l-3.3 1.4-.2.1c-.6.3-1 1-1 1.7v.4l.2.6 6 15.9-1.3 7.7-.1.3-.1.6a4 4 0 004 4H235.8s3.9-12.6-29.7-12.6l8.6-16.1v-.1l.2-.7a2 2 0 00-1.2-1.8l-2.4-1a46 46 0 00-33.1-.4z"/>', 2, 1, 2, 1),
('Tunic', '<path class="color1" d="M209.4 151.4c0 5.9-4.4 10.8-10.1 11.6a34.7 34.7 0 01-25.9 29.7l-8.5 17.9-8.5-18a34.7 34.7 0 01-25.9-29.6 11.7 11.7 0 01-10.1-11.6 60 60 0 00-49.2 57.8v11.3l28.1 3 6.2 38.2.2 8.1-3.5 30.4c1.6 1.5 3.4 4 4.6 8.2l6 1.8c10.5 2.9 27.4 6.6 47.4 7.3l.4-.4v.4l9.9.1 4.1-.1a156.3 156.3 0 0042.6-7.7l1.1-.4 5-1.8c1.2-4.1 3-6.4 4.7-7.8l-3-29.8c-.4.3.3-8.6.3-8.6l5.4-37.9 28.1-2.9v-11.3a60.3 60.3 0 00-49.4-57.9z"/>\n  <path class="color2" d="M225 261.5a13 13 0 00-8-2.5H113.7c-3.4 0-6.4 1.1-8.3 2.8-1.2 1.1-1.9 2.4-1.9 3.9s.8 2.9 2.1 4.1c1.9 1.6 4.8 2.6 8.1 2.6h103.4c3.1 0 5.9-.9 7.8-2.4 1.5-1.2 2.4-2.7 2.4-4.3a5.8 5.8 0 00-2.3-4.2z"/>\n  <path fill="#888" d="M170.3 207h-10.6c-.6 0-1-.4-1-1s.4-1 1-1h10.6c.6 0 1 .4 1 1s-.5 1-1 1zm2.4-4.7h-15.6c-.6 0-1-.4-1-1s.4-1 1-1h15.6c.6 0 1 .4 1 1s-.4 1-1 1z"/>', 4, 1, 2, 2),
('Pants', '<path class="color1" d="M215.7 304.6a156.3 156.3 0 01-41.4 7.3l-4.1.1c-3.4.1-6.7.1-9.9-.1h-.4c-20-.7-37-4.4-47.4-7.3v51.9a24 24 0 007.2 17.1 46.3 46.3 0 0133 .4 24 24 0 007.7-17.2v-26.3a5 5 0 0110-.2V357.5c.3 6.3 3 11.9 7.2 16.1a46.2 46.2 0 0133 .5 24.2 24.2 0 007.6-17.6v-52.7l-1.1.4-1.4.4z"/>', 3, 1, 2, 1),
('Medium', '<path opacity=".6" d="M170.7,162.9h-0.4c0.2-0.2,0.3-0.4,0.3-0.7c0-0.7-0.6-1-1.4-1h-1.1c-0.8,0-1.4,0.3-1.4,1\n  c0,0.3,0.1,0.5,0.3,0.7h-4c0.1-0.1,0.3-0.6,0.3-0.7c0-0.7-0.6-1-1.4-1h-0.7h-0.3c-0.8,0-1.4,0.3-1.4,1c0,0.3,0.1,0.5,0.3,0.7h-0.5\n  c-2.2,0-3.9-1.6-4.1-3.6c0,0.1,0,0.2,0,0.2c0,2.1,1.8,3.8,4.1,3.8h0.5h10.9c2.3,0,4.1-1.7,4.1-3.8c0-0.1,0-0.3,0-0.4\n  C174.7,161.2,173,162.9,170.7,162.9z"/>\n<path class="color1" opacity=".8" d="M170.9,155.3h-1.3l-1-7.3c0-2.2-1-4.1-3.5-4.1s-3.5,1.8-3.5,4.1l-1,7.3h-1.3\n  c-2.3,0-4.1,1.7-4.1,3.8s1.8,3.8,4.1,3.8h0.5c-0.2-0.2-0.3-0.4-0.3-0.7c0-0.7,0.6-1.1,1.4-1.1h0.3h0.7c0.8,0,1.4,0.4,1.4,1.1\n  c0,0.1-0.2,0.6-0.3,0.7h4c-0.2-0.2-0.3-0.4-0.3-0.7c0-0.7,0.6-1.1,1.4-1.1h1.1c0.8,0,1.4,0.4,1.4,1.1c0,0.3-0.1,0.5-0.3,0.7h0.4\n  c2.3,0,4.1-1.7,4.1-3.8C175,157,173.2,155.3,170.9,155.3z"/>', 7, NULL, 2, 1),
('Small', '<path opacity=".6" d="M168.6 160.1c.2-.2.4-.5.4-.8 0-.6-.5-1.1-1.1-1.1h-1.6c-.6 0-1.1.5-1.1 1.1 0 .4.2.7.5.9H164c.3-.2.5-.5.5-.9 0-.6-.5-1.1-1.1-1.1h-1.6c-.6 0-1.1.5-1.1 1.1 0 .3.2.6.4.8a4.1 4.1 0 01-3.5-3.8v.2a4 4 0 003.5 4h7.5c2.2-.3 3.8-2 3.8-4v-.2c-.1 1.9-1.7 3.5-3.8 3.8z"/>\n  <path class="color1" opacity=".8" d="M172.4 156.1a4 4 0 00-4-4h-6.8a4 4 0 00-.5 8c-.2-.2-.4-.5-.4-.8 0-.6.5-1.1 1.1-1.1h1.6c.6 0 1.1.5 1.1 1.1 0 .4-.2.7-.5.9h1.7a1 1 0 01-.5-.9c0-.6.5-1.1 1.1-1.1h1.6c.6 0 1.1.5 1.1 1.1 0 .3-.2.6-.4.8 2.2-.3 3.8-2 3.8-4z"/>', 7, NULL, 2, 1),
('Narrow', '<path opacity=".6" d="M169.7 160.4h-1.1c-.8 1.4-2.1 2.3-3.6 2.3s-2.8-.9-3.6-2.3h-1.1c-1.3 0-2.5-1.3-2.6-2.9v.2c0 1.7 1.2 3.1 2.6 3.1h1.1c.7 1.4 2.1 2.3 3.6 2.3s2.7-.9 3.6-2.3h1.1c1.4 0 2.6-1.4 2.6-3.1v-.1c-.2 1.6-1.3 2.8-2.6 2.8z"/>\n  <path opacity=".8" class="color1" d="M169.5 154.2h-.2l-1.7-11.1c0-3-5.3-3-5.3 0l-1.7 11.1h-.2c-1.4 0-2.6 1.4-2.6 3.1 0 1.7 1.2 3.1 2.6 3.1h1.1c.7 1.4 2.1 2.3 3.6 2.3s2.7-.9 3.6-2.3h1.1c1.4 0 2.6-1.4 2.6-3.1-.3-1.7-1.5-3.1-2.9-3.1z"/>', 7, NULL, 2, 1),
('Rounded', '<path opacity=".6" d="M169.1 161.2c0-.4-.3-.8-.6-.8h-1.6c-.4 0-.6.4-.6.8v.2h-2.6v-.2c0-.4-.3-.8-.6-.8h-1.6c-.4 0-.6.4-.6.8-1.8-.6-3.2-2.7-3.2-5.1v.3c0 2.5 1.4 4.7 3.3 5.3 0-.4 2.8.1 2.8.2h2.6c0-.1 2.8-.6 2.8-.2 1.9-.6 3.3-2.8 3.3-5.3v-.3c-.2 2.5-1.6 4.5-3.4 5.1z"/>\n  <path class="color1" opacity=".8" d="M167.1 144.4h-4.2c-2.4 0-5.3 8.5-5.3 11.5 0 2.5 1.4 4.7 3.3 5.3 0-.4.3-.8.6-.8h1.6c.4 0 .6.4.6.8v.2h2.6v-.2c0-.4.3-.8.6-.8h1.6c.4 0 .6.4.6.8 1.9-.6 3.3-2.8 3.3-5.3 0-3-2.8-11.5-5.3-11.5z"/>', 7, NULL, 2, 1),
('Eyelashes', '<circle fill="#fff" cx="149.8" cy="147" r="4.2"/>\n  <circle fill="#fff" cx="180" cy="147" r="4.2"/>\n  <circle class="color1" cx="149.8" cy="147" r="3"/>\n  <circle class="color1" cx="180" cy="147" r="3"/>\n    <path opacity=".5" d="M155.5,148c-0.2,0-0.5-0.1-0.6-0.4c-1.2-2.7-2.6-4.3-4.4-4.6c-2.8-0.5-5.4,2.2-5.5,2.3\n      c-0.2,0.3-0.6,0.3-0.9,0c-0.3-0.2-0.3-0.6,0-0.9c0.1-0.1,3.2-3.3,6.6-2.6c2.2,0.4,4,2.2,5.3,5.4c0.1,0.3,0,0.7-0.3,0.8\n      C155.6,148,155.5,148,155.5,148z"/>\n    <path opacity=".5" d="M174.3,148c0.2,0,0.5-0.1,0.6-0.4c1.2-2.7,2.6-4.3,4.4-4.6c2.8-0.5,5.4,2.2,5.5,2.3c0.2,0.3,0.6,0.3,0.9,0\n      c0.3-0.2,0.3-0.6,0-0.9c-0.1-0.1-3.2-3.3-6.6-2.6c-2.2,0.4-4,2.2-5.3,5.4c-0.1,0.3,0,0.7,0.3,0.8C174.2,148,174.3,148,174.3,148z"\n      />', 8, NULL, 2, 1),
('Small', '<circle class="color1" cx="149.5" cy="147.7" r="2.7"/>\n  <circle class="color1" cx="180.5" cy="147.7" r="2.7"/>', 8, NULL, 2, 1),
('Round', '<path fill="#fff" d="M153.2 147.5a4.7 4.7 0 11-9.4 0c0-2.6 2.1-4.7 4.7-4.7 2.5 0 4.7 2.1 4.7 4.7zM186.2 147.5a4.7 4.7 0 11-9.4 0c0-2.6 2.1-4.7 4.7-4.7 2.5 0 4.7 2.1 4.7 4.7z"/>\n  <path class="color1" d="M151.8 147.5c0 1.8-1.5 3.3-3.3 3.3a3.3 3.3 0 01-3.3-3.3c0-1.8 1.5-3.3 3.3-3.3 1.7 0 3.3 1.5 3.3 3.3zM184.9 147.5c0 1.8-1.5 3.3-3.3 3.3a3.3 3.3 0 01-3.3-3.3c0-1.8 1.5-3.3 3.3-3.3 1.8 0 3.3 1.5 3.3 3.3z"/>', 8, NULL, 2, 1),
('Sideeye', '<path fill="#fff" d="M152.6 145.8H142.7a5.8 5.8 0 0011.6 0h-1.7zM185.6 145.8H175.7a5.8 5.8 0 0011.6 0h-1.7z"/>\n  <path class="color1" d="M150.1 150c1.9 0 3.5-1.5 3.5-3.5h-6.9c0 1.9 1.5 3.5 3.4 3.5zM183.1 150c1.9 0 3.5-1.5 3.5-3.5h-6.9a3.3 3.3 0 003.4 3.5z"/>', 8, NULL, 2, 1),
('Surprised', '<path fill="#fff" d="M154.3 147.7a4.7 4.7 0 11-9.4 0 4.7 4.7 0 119.4 0zM185.4 147.7a4.7 4.7 0 11-9.4 0c0-2.6 2.1-4.7 4.7-4.7 2.5 0 4.7 2.1 4.7 4.7z"/>\n  <path class="color1" d="M152.9 147.7c0 1.8-1.5 3.3-3.3 3.3a3.3 3.3 0 01-3.3-3.3c0-1.8 1.5-3.3 3.3-3.3a3.2 3.2 0 013.3 3.3zM184 147.7c0 1.8-1.5 3.3-3.3 3.3a3.3 3.3 0 01-3.3-3.3c0-1.8 1.5-3.3 3.3-3.3a3.2 3.2 0 013.3 3.3z"/>\n  <path fill="#BD681C" d="M146 153.6l-.3-.1c-4.3-2.5-2.9-7.6-2.9-7.6.1-.3.4-.4.6-.3.3.1.4.4.3.6-.1.2-1.2 4.4 2.4 6.5.2.1.3.4.2.7 0 .1-.1.2-.3.2zM183.6 153.5c-.2 0-.4-.1-.4-.3-.1-.2 0-.5.2-.7 3.8-1.9 2.9-6.1 2.9-6.3-.1-.3.1-.5.4-.6s.5.1.6.4c0 .1 1.1 5.2-3.4 7.4l-.3.1z"/>', 8, NULL, 2, 1),
('Straight', '<path class="color1" d="M159.4 135.8c0 .8-.7 1.5-1.5 1.5h-12.5c-.8 0-1.5-.7-1.5-1.5s.7-1.5 1.5-1.5h12.5c.8 0 1.5.7 1.5 1.5zM185.8 135.8c0 .8-.7 1.5-1.5 1.5h-12.4c-.8 0-1.5-.7-1.5-1.5s.7-1.5 1.5-1.5h12.5c.8 0 1.4.7 1.4 1.5z"/>', 9, NULL, 2, 1),
('Angry', '<path class="color1" d="M158.2 137.2l-8.5-6.9-.2-.2-.8-.2-.3.1-.2.1-7.2 3.1h-.1c-.4.2-.6.5-.5.8.1.4.5.7 1 .8l16.4 3.1.3.1c.3 0 .5-.2.5-.5l-.1-.2-.3-.1zM189.5 134.1c.1-.4-.1-.7-.5-.8h-.1l-7.2-3.1-.2-.1-.3-.1-.8.2-.2.2-8.5 6.9-.1.1-.1.2c0 .3.2.5.5.5l.3-.1 16.4-3.1c.3-.1.7-.4.8-.8z"/>', 9, NULL, 2, 1),
('Tapered', '<path class="color1" d="M141.2 134.9s-.6.6-.4.9c.2.4.6.6 1.1.6l16.7-.3s1.1-4.3-.4-4.3c-8.8 0-17 3.1-17 3.1zM188.8 134.9s.6.6.4.9c-.2.4-.6.6-1.1.6l-16.7-.3s-1.1-4.3.4-4.3c8.8 0 17 3.1 17 3.1z"/>', 9, NULL, 2, 1),
('Thick', '<path class="color1" d="M159.1 139c0 1.3-1 2.3-2.3 2.3h-12.1c-1.3 0-2.3-1-2.3-2.3 0-1.3 1-2.3 2.3-2.3h12.1c1.3 0 2.3 1 2.3 2.3zM187.5 139c0 1.3-1 2.3-2.3 2.3h-12.1c-1.3 0-2.3-1-2.3-2.3 0-1.3 1-2.3 2.3-2.3h12.1c1.2 0 2.3 1 2.3 2.3z"/>', 9, NULL, 2, 1),
('Arched', '<path class="color1" d="M154.7 140.1a2 2 0 01-1.6-.9c-1.3-1.9-2.6-2.8-4-2.8-2.1 0-4.2 2.1-4.7 2.9-.7.9-1.9 1.1-2.8.4-.9-.7-1.1-1.9-.4-2.8.3-.5 3.5-4.5 7.9-4.5h.1c2.7 0 5.1 1.6 7.2 4.5a2 2 0 01-1.7 3.2zM187.1 140.1a2 2 0 01-1.6-.9c-1.3-1.9-2.6-2.8-4-2.8-2.1 0-4.2 2.1-4.7 2.9-.7.9-1.9 1.1-2.8.4-.9-.7-1.1-1.9-.4-2.8.3-.5 3.5-4.5 7.9-4.5h.1c2.7 0 5.1 1.6 7.2 4.5a2 2 0 01-1.7 3.2z"/>', 9, NULL, 2, 1),
('Epic', '<path class="color1" d="M220.3 144.4c-3.7-4.7-6.9-9.7-10.3-14.7-2.3-3.5-4.7-7-7.3-10.3a48.6 48.6 0 00-19.3-17c-5.8-3-11.8-4.4-17.7-4.4h-1.4c-5.9.1-12 1.4-17.7 4.4a49.4 49.4 0 00-19.3 17c-2.6 3.3-5 6.8-7.3 10.3-3.3 5-6.6 10-10.3 14.7-3.4 4.3-9.3 9.4-9.8 15.1 6-3.5 20.5-8 20.5-8v-6.8c.5-4.6 7.1-9.8 7-9.6-.6 2.1-1.6 4.3-3.4 5.7-.5.3-1 .7-1.3 1.1-.4.6-.6 1.4-.5 2.1.1.6.3 1.1.7 1.4.4.3.9.5 1.7.5 1.6-.2 3.3-.5 5-1a23.4 23.4 0 0014.2-10.3 25 25 0 002.8-7c.5-2.4.7-5 2.1-7.1 2.9-4.4 8.6-6 12.6-2.2 1 .9 1.9 2.2 3.3 2.2 1.6 0 2.6-1.5 3.7-2.4a6.8 6.8 0 014.5-1.9c2.6.1 5.1 1 6.8 3 1.3 1.5 1.7 3.5 2.2 5.4 1.2 4.4 2.5 8.8 5.3 12.4 2.6 3.5 6.3 5.9 10.4 7.2l1.5.5c1.8.6 3.7 1 5.6 1.2l1.1-.1c.5-.2.9-.5 1.1-1 .3-.7.3-1.8-.1-2.5-.3-.5-.8-.9-1.3-1.3l-.1-.1-1.7-1.6c-.5-.7-.9-1.4-1.2-2.2l-.6-1.4-.3-.9c4.3 1.8 7.2 6 7.2 10.9v6l1.5.3a63.2 63.2 0 0119.8 8.2c.2-6.2-6.2-11.3-9.7-15.8z"/>', 10, 1, 2, 1),
('Trimmed', '<path class="color1" d="M134.8 137.2h-4.5v2h4.5c.6 0 1-.5 1-1 0-.6-.4-1-1-1zM134.8 140.2h-4.5v2h4.5c.6 0 1-.5 1-1 0-.6-.4-1-1-1zM134.8 143.2h-4.5v2h4.5c.6 0 1-.5 1-1 0-.6-.4-1-1-1zM194.2 138.2c0 .6.5 1 1 1h4.5v-2h-4.5c-.6 0-1 .4-1 1zM194.2 141.2c0 .6.5 1 1 1h4.5v-2h-4.5c-.6 0-1 .4-1 1zM194.2 144.2c0 .6.5 1 1 1h4.5v-2h-4.5c-.6 0-1 .4-1 1zM199.6 132.3a34.8 34.8 0 00-20.8-27.9l.6-.6.5-.5.2-.8c0-.6-.3-1-.8-1.3l-.5-.2a39.5 39.5 0 00-26.4.3h-.1c-.5.2-.9.7-.9 1.3 0 .3.1.6.3.8l.4.4.1.1a34.6 34.6 0 00-21.8 32.1h3.9c.9-.1 1.7-.7 1.9-1.6v-.5l.6-7.4c1.4-.6 2.8-1.5 3.9-2.6 2.6-2.5 3.3-6.1 5.6-8.8a12 12 0 018.7-3.9c1.4 0 2.8.3 4.1.8 2.5 1 4.3 3.7 7.2 3.2.7-.1 1.5-.4 2.1-.8l1.5-1.2a10.1 10.1 0 015.9-1.9c2.3 0 4.6.8 6.4 2.2 3.4 2.7 4.2 7.4 7.3 10.4 1.1 1.1 2.5 2 3.9 2.6l.6 7.4v.5c.2.9.9 1.5 1.9 1.6h3.8l-.1-3.7z"/>', 10, 1, 2, 1),
('Mohawk', '<path class="color1" d="M174.5 83.3h-19c-.9 0-1.6.7-1.6 1.6l.1.5v.1l9.4 27.4.1.1c.8.6 2.1.6 2.9 0l9.4-27.3.2-.5v-.3c.1-.8-.6-1.5-1.5-1.6z"/>', 10, 1, 2, 1),
('Wavy', '<path class="color1" d="M228.7 174.4c-1.3-12.6-17.1-12.9-13.3-26.9 0-6.8-9-7.8-9.8-13.7.5-5.8 0-13.3-6.4-15.6-21.5-23.6-46.8-24.4-68.1-.2-6.8 2.2-7.1 9.8-6 15.8-.7 5.9-9.3 6.7-9.4 13.6 4 14.3-13.5 14.4-13.3 28 8.6-8.3 31.4-16.1 31.4-16.1l-.1-1.8s-4.6-.7-5.8-2.5c-4.9-4.3-5.3-14.5-.4-18.7 3.7-3.4 9.2-1.8 14-3.3a32 32 0 0022.3-18.2c.2-.6 1.1-.5 1.3 0 4.2 8.9 10.6 18.7 28.2 18.9l2.4.3c12.9-.6 13.4 23.6.5 23.6l-.1 1.8s24.8 9.5 32 16.7c.8-.4.7-1 .6-1.7z"/>', 10, 2, 2, 1),
('Frizzy', '<path class="color1" d="M228.7 174.4c-.4-3.8-1.1-4.4-2.6-6.9-1.6-.2-3.4-7.3-4.7-4.9-2.5-1.1-4.9-5-6-7.5-1.7-2.9 1.7-12-2.4-12.8-.7-1.7-1.4 1.1-2.2-1.9-.8-3.2-1.6-1.4-2.3-1.8-3.1 1.3-2.6-8.9-2.9-10.8-.4-8.5-7.1-9.4-11.5-15.2-.8-3.1-3.5-3.1-4.3-4.5-1.7-1.3-3.9.1-5.5-2.1-.9 0-1.7.1-2.6-1.4-.9-.7-1.3-.2-2.1-2.1-.9-1.6-2.3-.4-3.3-.1-5.5-1.3-10.9-3.2-18.8-1.5-1.9 2.9-4.2-1.8-6.1 1.5-.8-1.9-3.5 1.4-4.3 1.8a80.4 80.4 0 01-5.9 3.7c-1.7 1.8-1.9 1.2-3.1 4-.7.5-2.5-.6-3.1 1.7-.7 1-2 2.3-2 2.3l-4.2 3.9-2.6 2.2s-1 3-1.6 5.8c-.1.3 0 3 0 3.2.1-2 .1 4.3.4 3.1-.2 1.4-2.7 4.6-2.7 4.6s-1.8.9-2.2 1.8c-.6 2.3-1.1 1.5-2.2 1.9-.3 1.7-1.6 2.3-1.6 2.3s-3.1 5.5-2.1 8.3-3.8 8.9-3.8 8.9-1.6-.5-2.3 2.1c-.8-1.8-1.5 3.1-2.2 2.3-2.3 1.6-3.5 6.5-3.5 9.4 7.4-6.4 17.2-10.4 26.2-14.2 1 1.9 5.1-1.9 5.1-1.9v-1.8s-6.9-2.7-7.9-5.1c-2.6-6.7-2-7.9-.2-13.6 3.2-4 11.9-3.4 15.9-5.8 1.1.6 4.9-8.6 13.5-13.1 1.1-3.1 2.4-1.4 3.7-1.6 1.3-4.7 2.6 0 3.8-1.3 1.2.4 2.2-1.7 2.9-.9.7 2.2 4.7-.7 5.3-1 0 2.9 6.9 12 9.6 14.3.7-.7 1.5 2.6 2.3 1.7 1.6 3.5 3.7 0 6.3 2.3 1 3.3 3.5-.9 6.8.6 12.7.6 13.5 23.4.5 23.6-.3.5 0 1.8 0 1.8s18.2 7 23 10.5c2.3-.8 10.4 8.4 9.5 4.2z"/>', 10, 2, 2, 1),
('Straight', '<path class="color1" d="M201.2 121.7c-15.1-35.7-62.7-29.7-73.1-1.2-5.7 15.6-8.2 43.1-8.2 43.1l13.9-4.4-.1-1.8s-4.6-.7-5.8-2.5c-4.9-4.3-5.3-14.5-.4-18.7 3.7-3.4 9.2-1.8 14-3.3a32 32 0 0022.3-18.2c.2-.6 1.1-.5 1.3 0 4.2 8.9 10.6 18.7 28.2 18.9l2.4.3c12.9-.6 13.4 23.6.5 23.6l-.1 1.8 12.8 4c.1 0 .7-21.6-7.7-41.6z"/>', 10, 2, 2, 1),
('Angular', '<path class="color1" d="M156.6 140.8l-1-.2-7.6-3.2-3.8 1.8c-1.3.6-2.7.1-3.3-1.2a2.4 2.4 0 011.2-3.3l4.9-2.3c.6-.3 1.4-.3 2 0l8.6 3.6c1.3.5 1.9 2 1.3 3.3-.4.9-1.3 1.5-2.3 1.5zM173.5 140.8c-1 0-1.9-.6-2.3-1.5-.5-1.3.1-2.7 1.3-3.3l8.6-3.6c.7-.3 1.4-.3 2 0l4.9 2.3c1.2.6 1.8 2.1 1.2 3.3-.6 1.2-2.1 1.8-3.3 1.2l-3.8-1.8-7.6 3.2-1 .2z"/>', 9, NULL, 2, 1),
('Slanted', '<path class="color1" d="M157 138.3a2.7 2.7 0 01-3 2.2l-10.3-1.7a2.7 2.7 0 01-2.2-3 2.7 2.7 0 013-2.2l10.3 1.7c1.5.3 2.5 1.6 2.2 3zM173 138.3a2.7 2.7 0 003 2.2l10.3-1.7a2.7 2.7 0 002.2-3 2.7 2.7 0 00-3-2.2l-10.3 1.7a2.7 2.7 0 00-2.2 3z"/>', 9, NULL, 2, 1),
('Yelling', '<path fill="#CB636D" d="M174,173.4c-0.5-3.8-4.3-6.7-9-6.7s-8.5,3-9,6.7H174z"/>\n	<path fill="#F28897" d="M170.3,173.4c0-0.9-0.8-1.7-1.8-1.7h-7c-1,0-1.7,0.8-1.8,1.7H170.3z"/>', 5, NULL, 2, 0),
('Serious', '<path fill="#8E2829" d="M172 167.1c0 .7-.6 1.3-1.3 1.3h-11.5c-.7 0-1.3-.6-1.3-1.3 0-.7.6-1.3 1.3-1.3h11.5c.7 0 1.3.6 1.3 1.3z"/>', 5, NULL, 2, 0),
('Toothy', '<path fill="#CB636D" d="M171.1 173.8h-12.2a4.1 4.1 0 01-4.1-4.1v-.5c0-2.2 1.8-4.1 4.1-4.1h12.2c2.2 0 4.1 1.8 4.1 4.1v.5c-.1 2.2-1.9 4.1-4.1 4.1z"/>\n  <path fill="#fff" d="M174.9 168c-.5-1.6-2-2.9-3.9-2.9h-12.2a4.2 4.2 0 00-3.9 2.9c.5.5 1.2.8 2 .8 1.3 0 2.4-1 2.6-2.2a2.6 2.6 0 002.6 2.2c1.4 0 2.5-1.1 2.7-2.5a2.7 2.7 0 002.7 2.5c1.4 0 2.6-1.1 2.7-2.5a2.7 2.7 0 002.7 2.5c.9.1 1.6-.2 2-.8z"/>', 5, NULL, 2, 0),
('Smile', '<path fill="#8D2200" d="M165 173.9c-3.2 0-6.3-1.3-8.6-3.5-.3-.3-.3-.7 0-1 .3-.3.7-.3 1 0a10.6 10.6 0 0015 0c.3-.3.7-.3 1 0s.3.7 0 1a11.3 11.3 0 01-8.4 3.5z"/>', 5, NULL, 2, 0),
('Happy', '<path fill="#CB636D" d="M174.1 171.4c1-1.3 1.7-2.8 1.7-4.5h-21.7c0 1.7.6 3.2 1.7 4.6 6-2.8 12.3-2.2 18.3-.1z"/>\n  <path fill="#F28897" d="M155.9 171.4c1.9 2.4 5.3 3.9 9.1 3.9 3.9 0 7.2-1.6 9.2-4-6.1-2-12.4-2.6-18.3.1z"/>', 5, NULL, 2, 0),
('Large Moustache', '<path class="color1" d="M205.4 177.2c-5.5-.1-10.9-.1-15.1-4.3-3.4-3.3-4.1-7-6.2-10.9-2.7-4.9-7-6.7-12.4-6.9l-.4.1c-2.2-.2-4.2.2-6.3 1.2-2.1-1-4.1-1.4-6.3-1.2l-.4-.1c-5.5.2-9.7 2-12.4 6.9-2.2 3.9-2.8 7.5-6.2 10.9-4.2 4.2-9.6 4.2-15.1 4.3-1.8 0-1.9 2.4-.4 2.9 8.3 3.2 18.3 4.5 26.6.5a26 26 0 0014.2-17.7c2.1 7.7 6.6 14 14.2 17.7 8.3 4 18.3 2.8 26.6-.5 1.5-.5 1.4-2.9-.4-2.9z"/>', 6, 1, 2, 1),
('Moustache', '<path class="color1" d="M187.4 160.7c-1.9-2.4-5-4-8.6-4h-27.5c-3.5 0-6.6 1.6-8.6 4a9.2 9.2 0 00-1.8 8.1h13.3c2.9 0 5.6-1 7.7-2.6 1.3-1 2.3-2.2 3.1-3.6a12.5 12.5 0 0010.8 6.2h13.3c.2-.7.3-1.5.3-2.3.1-2.2-.7-4.2-2-5.8z"/>', 6, 1, 2, 1),
('Braided Beard', '<path class="color1" d="M188.6 154.1s-1 3.1-1.1 5.3c0 0-.9-5.1-6-5.3h-33c-5.1.3-6 5.3-6 5.3-.1-2.2-1.1-5.3-1.1-5.3l-.1-.1-.5-1.2v-.1l-.5-1.1-.1-.2-.6-1-.1-.2-.6-.9-.1-.1-.7-.9-.2-.2-.7-.8-.1-.1-.7-.7-.2-.2-.7-.6-.2-.2-.5-.4-.2-.2-.6-.5-.3-.2-.4-.2-.5-.4-.2-.1c-1-.7-1.9-1.1-2.4-1.4v32.3a34.8 34.8 0 0028.6 34.3 8.2 8.2 0 00-1.7 5.1v11.4c0 3.6 2.2 6.6 5.3 7.8-1 .9-1.6 2.1-1.6 3.5v8a4.7 4.7 0 109.4 0v-8c0-1.4-.6-2.7-1.6-3.5a8.5 8.5 0 005.4-7.8v-11.4c0-2-.7-3.9-1.9-5.3a34.9 34.9 0 0027.6-34.1v-8.8-23.3c-1.9.9-8.5 4.6-11.1 11.8zm-13.1 26.4c0-3.5-2.9-6.3-6.3-6.3h-7.7a6.3 6.3 0 00-6.3 6.3v.3a10.1 10.1 0 01-5.8-9.1c0-2 .6-3.9 1.7-5.5h28a9.7 9.7 0 011.7 5.5c-.1 3.8-2.2 7.1-5.3 8.8z"/>\n  <path class="color2" d="M174.3 208.4c0 .6-.5 1.1-1 1.1h-15.5c-.6 0-1-.5-1-1.1 0-.6.5-1.1 1-1.1h15.5c.5 0 1 .5 1 1.1zM170.5 233.1c0 .6-.4 1-.9 1h-8c-.5 0-.9-.5-.9-1 0-.6.4-1 .9-1h8c.5 0 .9.4.9 1z"/>', 6, 1, 2, 2),
('Epic Moustache', '<path class="color1" d="M204.4 159.1l-.2-.7a9.9 9.9 0 00-7.6-7l.5 1.1.2.5.1.4.1.7c0 2.3-2 4.2-4.5 4.3h-56c-2.5-.1-4.5-2-4.5-4.3l.1-.7.1-.4.2-.5.5-1.1a10 10 0 00-7.6 7l-.2.7c-.3 1.2-.4 2.5-.4 3.7 0 10.4 9.1 18.9 20.2 18.9 9.5 0 17.5-6.1 19.6-14.4a20 20 0 0019.6 14.4c11.2 0 20.2-8.5 20.2-18.9 0-1.2-.2-2.5-.4-3.7z"/>', 6, 1, 2, 1),

-- Orcs
('Orc Male', '<path class="color1" d="M207.7 393.8l31-46.5a9.9 9.9 0 001.6-6c-1.4-22.4-8.6-37.3-8.6-37.3l-66.7 3.8-66.7-3.8s-7.2 15-8.6 37.3c-.1 2.1.4 4.2 1.6 6 7 10.7 31 46.5 31 46.5-4.1 2.7-41.9 5.6-36.2 11.7 12.8 3 26.5 0 39.7.2 9.9-1 26.1 3.5 17-12.4-.1-.2-1.2-2.6-1.3-2.4 0 0-11.9-34.2-11.9-43.2 0-7.4 26.2-29.5 35.5-37.1 9.3 7.6 35.5 29.7 35.5 37.1 0 9-11.9 43.2-11.9 43.2-.1-.1-1.2 2.2-1.3 2.4-9.1 15.9 7.1 11.5 17 12.4 13.3-.2 26.9 2.8 39.7-.2 5.5-6.1-32.2-9-36.4-11.7zM295.5 280.3c5.6-57.1-12.1-96-12.1-96l-28.2 28.1a148.1 148.1 0 0115.2 70.4h-.1s-4.5 4.1-8.9 13.2l-6.9 8.8a5 5 0 00.8 7 5 5 0 007-.8l5.9-7.4a40 40 0 01-9 20.5 5 5 0 00.7 7c.9.8 2 1.1 3.1 1.1 1.5 0 2.9-.6 3.9-1.9 5.7-7 8.6-14 10.1-20.1a35.5 35.5 0 01-4.5 17.9 5 5 0 001.4 6.9 5.2 5.2 0 007-1.4 47.7 47.7 0 006.2-25.6c.5 5.1.7 11.6.1 19.7a5 5 0 004.6 5.3h.4a5 5 0 005-4.6c.6-9.1.4-16.4-.2-22.3.9 4 1.6 8.7 1.6 14a5 5 0 005 5 5 5 0 005-5 61.6 61.6 0 00-13.1-39.8zM34.5 280.3c-5.6-57.1 12.1-96 12.1-96l28.2 28.1a148.1 148.1 0 00-15.2 70.4h.1s4.5 4.1 8.9 13.2l6.9 8.8a5 5 0 01-.8 7 5 5 0 01-7-.8l-5.9-7.4a40 40 0 009 20.5 5 5 0 01-.7 7c-.9.8-2 1.1-3.1 1.1-1.5 0-2.9-.6-3.9-1.9-5.7-7-8.6-14-10.1-20.1.2 6.5 1.4 13.2 4.5 17.9a5 5 0 01-1.4 6.9 5.2 5.2 0 01-7-1.4 47.7 47.7 0 01-6.2-25.6c-.5 5.1-.7 11.6-.1 19.7a5 5 0 01-4.6 5.3h-.4a5 5 0 01-5-4.6c-.6-9.1-.4-16.4.2-22.3-.9 4-1.6 8.7-1.6 14a5 5 0 01-5 5 5 5 0 01-5-5 61.6 61.6 0 0113.1-39.8zM221.1 106.6l-.1-.6a2.9 2.9 0 00-2.7-2.2c-.7 0-1.3.2-1.7.6l-.4.4-9.2 9.5v-2.9a41.9 41.9 0 10-83.8 0v2.9l-9.2-9.5-.4-.4c-.5-.4-1.1-.6-1.7-.6-1.3 0-2.4.9-2.7 2.2l-.1.6c-1 10.7-1.2 32.1 14.1 30.7v3.9c0 13.7 6.5 25.8 16.7 33.5.7.5 1.4 1.1 2.2 1.5a41.3 41.3 0 0046.1 0l2.2-1.5a42 42 0 0016.7-33.5v-3.9c15.1 1.4 15-20 14-30.7z"/>\n  <path opacity=".1" d="M216.2 104.9l.4-.4c.5-.4 1.1-.6 1.7-.6 1.3 0 2.4.9 2.7 2.2l.1.6c1 10.7 1.2 32.1-14.1 30.7v-22.9l9.2-9.6z"/>\n  <path opacity=".3" d="M213.1 112.8l.3-.3c.3-.3.7-.4 1.2-.4.9 0 1.6.6 1.8 1.5v.4c.7 7.2.8 21.5-9.5 20.6v-15.3l6.2-6.5z"/>\n  <path opacity=".1" d="M113.8 104.9l-.4-.4c-.5-.4-1.1-.6-1.7-.6-1.3 0-2.4.9-2.7 2.2l-.1.6c-1 10.7-1.2 32.1 14.1 30.7v-22.9l-9.2-9.6z"/>\n  <path opacity=".3" d="M116.9 112.8l-.3-.3c-.3-.3-.7-.4-1.2-.4-.9 0-1.6.6-1.8 1.5v.4c-.7 7.2-.8 21.5 9.5 20.6v-15.3l-6.2-6.5z"/>', 1, 1, 7, 1),
('Shoes', '<path class="color1" d="M207.8 393.7s-16.1 2.6-19.3-2.9c-.1-.2-1.9 2.3-2 2.5-9.1 15.9 7.8 11.9 17.7 12.8 13.3-.2 27.6 2.6 40.4-.3 5.7-6-20.2-7.9-36.8-12.1zM122.2 393.7s16.1 2.6 19.3-2.9c.1-.2 1.9 2.3 2 2.5 9.1 15.9-7.8 11.9-17.7 12.8-13.3-.2-27.6 2.6-40.4-.3-5.7-6 20.2-7.9 36.8-12.1z"/>', 2, 1, 7, 1),
('Pants', '<path class="color1" d="M244.1 332.3l-9.5-32H95.7l-9.5 32c-3 10.1 2.8 19.2 9 26.6l14.8 18.2c.3.4 3.2 4.7 3.8 4.6l25-3c-1.6-6.7-4-13.1-5.6-19.8-1-4.1-2.9-9.2-.2-13 4.3-6.2 20.2-14.3 20.3-14.4 3.4-1.3 6.7-2.2 9.9-2.6h4.3c3.1.4 9.9 2.7 9.9 2.8 0 0 15.9 8 20.2 14.2 2.6 3.8.8 9-.2 13-1.6 6.7-4 13.1-5.6 19.8l25 3 3-3.7 13-16c4.9-6.1 10.5-11.9 11.7-20 .2-3 .4-6.8-.4-9.7z"/>', 3, 1, 7, 1),
('Shirt', '<path class="color1" d="M287 186.8c-18.6-42.6-47.9-58.9-66-65.1-1.1 8.6-4.7 16.4-14.1 15.5v3.9a42 42 0 01-65 35l-2.2-1.5a42 42 0 01-16.7-33.5v-3.9c-9.4.8-13-7-14.1-15.5-18 6.2-47.4 22.4-66 65.1l29.4 26.3 4.2 3.7.3-.4s6.5-11.5 11.6-18.2v40c-.1 10.7-.1 47.6 1 65.8 0 0 75.8 14.6 151.6-.8 0 0 .8-49.6.7-70.6v-34.4c5.1 6.7 11.6 18.2 11.6 18.2l.3.4 4.2-3.7 29.2-26.3z"/>', 4, 1, 7, 1),
('Curly Horns', '<path fill="#E1DEDB" d="M141.9 94.2c-3.7-11-10.6-22.7-22.5-19.4a10 10 0 015 1.2c3.9 1.9 6.3 5.5 7.8 9.5a28.5 28.5 0 011.4 5.3l2-1.1-1.8 3.3c.1 1.5.3 3.2.5 3.2 0 0 6.6 2.3 12.3-1.5-2.4.5-4.2.5-4.7-.5zM188.1 94.2c3.7-11 10.6-22.7 22.5-19.4a10 10 0 00-5 1.2 16.4 16.4 0 00-7.8 9.5c-.6 1.6-1 3.2-1.3 4.8l-.1.5-2-1.1 1.8 3.3c-.1 1.5-.3 3.2-.5 3.2 0 0-6.6 2.3-12.3-1.5 2.4.5 4.2.5 4.7-.5z"/>\n  <path fill="#EEECEA" d="M151.5 80.9a68.6 68.6 0 00-19.6-20.2c-3.4-2-7.3-3.7-11.3-4.2A27.1 27.1 0 0099.9 64a17.7 17.7 0 00-5 14.7c.6 5.9 3.6 10.5 7.4 14.8 4.8 5.4 14 7.8 21 6.5 2-.4 4.7-1.2 6.4-2.5 1.5-1.1 2.9-2.8 4-4.5l1.8-3.3s-.7.5-2 1.1c-3 1.5-8.8 3.5-13.4.5-4.2-2.8-9-8.6-5.6-13.8 1.2-1.9 3-2.7 4.9-2.7 11.8-3.4 18.8 8.4 22.5 19.4.5.9 2.3 1 4.5.6 2.8-1.9 5.3-5.2 6.7-11 .2-.8-1.2-2.3-1.6-2.9zM230.1 64.1a27 27 0 00-20.7-7.5c-4 .4-7.9 2.1-11.3 4.2-8.5 5-14 12.3-19.6 20.2-.4.6-1.9 2.1-1.7 3 1.4 5.8 3.9 9.1 6.7 11 2.2.4 4 .4 4.5-.6 3.7-11 10.6-22.7 22.5-19.4 1.9.1 3.7.8 4.9 2.7 3.4 5.2-1.4 10.9-5.6 13.8-4.6 3.1-10.4 1-13.4-.5l-2-1.1 1.8 3.3c1.1 1.7 2.5 3.3 4 4.5 1.7 1.3 4.3 2.1 6.4 2.5 7 1.3 16.2-1 21-6.5 3.9-4.3 6.8-8.9 7.4-14.8.7-5.5-.9-11-4.9-14.8z"/>', 10, 1, 7, 0),
('One Horn', '<path fill="#E1DEDB" d="M174.5 78c.4-.9.8-2.2.5-3.2l-.4-1.3-7.2-22.4c.5 9.6 2.8 18.4-3.8 25.5-1.8 1-2.5 1.7-4.4.8-1.3-.8-2.8-1.5-4.1-2.7 0 .8.2 1.8.4 2.4 1 2.7 4.1 4.4 6.7 5.1 2.7.7 5.4.5 7.9-.6l2.4-1.4c.7-.6 1.5-1.3 2-2.2z"/>\n  <path fill="#EEECEA" d="M164.5 77.8c2.1-1.2 3-3.7 3.5-6 1-4.3.6-8.7.1-13l-.8-7.8-.3-1-.2-.6-.4-.6c-.9-.8-2.2-.8-3 .1l-.3.5-.2.7-7.2 22.5-.4 1.3-.1.8c0 .3 1.5 1.3 1.7 1.6l2 1.4c1.3.7 2.9 1 4.4.7l1.2-.6z"/>', 10, 1, 7, 0),
('Big Horns', '<path fill="#F4F4F2" d="M147.3 83.5c-.2-1-1.1-1.7-2.1-1.9a15.6 15.6 0 01-9-22c.4-5.6 20.2-18 5-11.6-12.9 5.5-23.1 20.9-17 34.7 3.9 10.3 22.5 19.7 23.1.8zM207.4 72c-1-11.9-13.2-25.2-24.6-25.2 6.3 5.2 12.7 11.7 12.8 20.6 0 6.3-4.9 12.2-10.8 14.1-1 .2-1.8.9-2.1 1.9-1.5 6.9 6.5 11.3 6.6 11 10.8.2 19.8-11.8 18.1-22.4z"/>\n  <path fill="#C9C5BF" d="M189.9 53.6l-1.4-1.6c3.2 1.5 5.8.7 7.2-.1l.8.7c-1 .6-2.5 1.3-4.3 1.3-.7.1-1.5 0-2.3-.3zM193.7 59.6l-.7-1.5c3.5 2.1 6.9 1.5 9.1.7l.5.8c-1.3.5-2.8.9-4.7.9a10 10 0 01-4.2-.9zM195.6 67.5v-.8l-.1-.5c3.9 3 8.5 2.9 11.3 2.4l.2.9c-.9.2-2.1.3-3.3.3-2.5 0-5.5-.5-8.1-2.3zM192.9 75.4l.6-.9a15 15 0 0012.6 7.3l-.4 1c-3.1-.2-9-1.5-12.8-7.4zM187.2 83c-.1-.8 0-1.7.1-2.4l.9-.5.4-.3c-.4 1-.5 2-.4 3.1.5 4.3 4.5 8.6 6.7 10.6l-1.1.4c-2.4-2.3-6.1-6.5-6.6-10.9zM140.2 53.6l1.5-1.4a8 8 0 01-7.2 0l-.8.6c1 .6 2.3 1.2 4.1 1.2l2.4-.4zM136.3 59.6l.4-1.4a9.6 9.6 0 01-8.8.5l-.6.8c1.3.5 2.9.9 4.8.9 1.2.1 2.7-.1 4.2-.8zM134.4 67.5v-.8l.1-.5c-3.9 3-8.5 2.9-11.4 2.4l-.2.9a16.5 16.5 0 0011.5-2zM137.1 75.4l-.6-.9a15 15 0 01-12.6 7.3l.4 1c3.1-.2 9-1.5 12.8-7.4zM142.8 83c.1-.8 0-1.7-.1-2.4l-.9-.5-.4-.3c.4 1 .5 2 .4 3.1-.5 4.3-4.4 8.5-6.7 10.5l1.1.4c2.4-2.3 6.1-6.4 6.6-10.8z"/>', 10, 1, 7, 0),
('Sawed Off Horns', '<path fill="#D6D2CB" d="M149.7 76.2l-5.7-4.9c.8 1.1-1.6 4.4-5.4 7.3-3.8 2.9-7.6 4.4-8.5 3.3l-.1-.1 3.9 8.7c7.9-1 15.6-6.8 15.8-14.3zM180.3 76.2l5.7-4.9c-.8 1.1 1.6 4.4 5.4 7.3 3.8 2.9 7.6 4.4 8.5 3.3l.1-.1-3.9 8.7c-7.9-1-15.6-6.8-15.8-14.3z"/>\n  <path fill="#BEBAB0" d="M151.7 77.9c-.2-.3-.5-.5-.8-.6l-1.2-1c-.2 7.5-7.9 13.3-15.6 14.3v.1l.2.4c1.6 2 6.7.7 11.4-2.9 4.9-3.7 7.5-8.3 6-10.3zM178.3 77.9c.2-.3.5-.5.8-.6l1.2-1c.2 7.5 7.9 13.3 15.6 14.3v.1l-.2.4c-1.6 2-6.7.7-11.4-2.9-4.9-3.7-7.5-8.3-6-10.3z"/>\n  <path fill="#EEECEA" d="M144.1 71.4c-.1-.1-.1-.1 0 0-.9-1.1-4.6.3-8.5 3.3-3.7 2.8-6 5.9-5.4 7.1v.1l.1.1c.9 1.1 4.6-.4 8.5-3.3 3.7-3 6.1-6.2 5.3-7.3zM185.9 71.4c.1-.1.1-.1 0 0 .9-1.1 4.6.3 8.5 3.3 3.7 2.8 6 5.9 5.4 7.1v.1l-.1.1c-.9 1.1-4.6-.4-8.5-3.3-3.7-3-6.1-6.2-5.3-7.3z"/>', 10, 1, 7, 0),
('Horns', '<path fill="#EEE" d="M147.4 83.5C147.2 80 126.1 71 123.7 69c-2.2-1.9-5.5-.5-3.9 2.5l10.3 21.2.6 1.3c6 7.5 18.8-2.2 16.7-10.5zM208.6 69.2c-2.1.6-22.7 10.8-24.4 11.7-7.4 7.2 6.4 22.1 13.7 14.5l.7-1.2 11.2-20.7c1.3-1.4 1.3-4.2-1.2-4.3z"/>', 10, 1, 7, 0),
('Topknot', '<path class="color1" d="M165,73.6c4.9,0.5,6-2.1,6-2.1s1.1-6.7,3.1-8.4c10.8-9.2,12.3,5.7,12.5,12.5 c3.4,2,6.5,4.5,9.1,7.4c3.1-11.2,3.3-21.5,0.5-26.1c-4.5-7.4-18.5-14.3-28.4-6.4c-3.7,2.9-9.1,10.2-8.5,20.9 C159.3,71.5,160.1,73.2,165,73.6z"/>', 10, 1, 7, 1),
('Serious', '<path fill="#fff" d="M173.2 152.6l.6-1.6 1.4-3.9c.2-.4.4-1.1.8-1.3.2-.1.4 0 .6.1.4.3.5.7.6 1.2l.3 1.6.5 3.2.1.7h-4.9zM156.9 152.6l-.6-1.6-1.4-3.9c-.2-.4-.4-1.1-.8-1.3-.2-.1-.4 0-.6.1-.4.3-.5.7-.6 1.2l-.3 1.6a42 42 0 00-.5 3.2l-.1.7h4.9z"/>\n  <path fill="#5C4D50" d="M178.1 153.4H152c-.6 0-1-.4-1-1s.4-1 1-1h26.1c.6 0 1 .4 1 1s-.5 1-1 1z"/>', 5, 1, 7, 0),
('Frown', '<path fill="#fff" d="M143.7 155.3s-4.5-9.2 1.6-15.8c0 0 1.5-1.4 1.6-.1 0 0 .6 9.8 6 12.7l-9.2 3.2zM186.4 155.3s4.5-9.2-1.6-15.8c0 0-1.5-1.4-1.6-.1 0 0-.6 9.8-6 12.7l9.2 3.2z"/>\n  <path fill="#5C4D50" d="M187.5 156.7l-.4-.1c-25.3-10.6-43.9-.2-44.1-.1-.5.3-1.1.1-1.4-.4-.3-.5-.1-1.1.4-1.4.2-.1 19.7-11 45.9-.1.5.2.7.8.5 1.3-.1.6-.5.8-.9.8z"/>', 5, 1, 7, 0),
('Frown One Tooth', '<path fill="#fff" d="M186.4 155.3s4.5-9.2-1.6-15.8c0 0-1.5-1.4-1.6-.1 0 0-.6 9.8-6 12.7l9.2 3.2z"/>\n  <path fill="#5C4D50" d="M187.5 156.7l-.4-.1c-25.3-10.6-43.9-.2-44.1-.1-.5.3-1.1.1-1.4-.4-.3-.5-.1-1.1.4-1.4.2-.1 19.7-11 45.9-.1.5.2.7.8.5 1.3-.1.6-.5.8-.9.8z"/>', 5, 1, 7, 0),
('Teeth', '<path fill="#8A2100" d="M144.8 157.9c3.5-3.5 9.4-8.2 17.1-9 7.4-.7 15 2.2 22.6 8.8a54.5 54.5 0 00-39.7.2z"/>\n  <path fill="#541D0C" d="M188.4 158.9c-8.8-8.2-17.7-12-26.5-11.2a30.6 30.6 0 00-20.4 11.5 1 1 0 00.2 1.4l.6.2.3-.1.5-.1c.2-.1 18.9-10.5 44.1.1l.4.1.4-.1c.2 0 .3-.1.5-.3.3-.5.3-1.1-.1-1.5zm-42.1-1.9c3.2-2.9 8.7-6.7 15.8-7.3 6.9-.6 13.9 1.8 20.9 7.2a55.7 55.7 0 00-36.7.1z"/>\n  <path fill="#fff" d="M178.7 155.7c-.3-6.2-.7-9.9-4.2-1.2-1.2-8.1-2.2-8.1-4.6-.3-1.2-8-2.2-7.7-4.5-.2-1.6-8-2.6-7.3-4.5.1-1.9-8.5-2.9-6.6-4.5.5-3.1-9.5-3.7-6.5-4.7.9 13.6-2.1 13.9-2 27 .2zM185.3 157.7s5.9-7.2-.2-13.7c0 0-1.5-1.4-1.6-.1 0 0 .8 8.9-4.6 11.8 2.2.5 4.3 1.1 6.4 2zM145.1 157.4s-5.9-7.2.2-13.7c0 0 1.5-1.4 1.6-.1 0 0-.8 8.9 4.6 11.8-2.2.5-4.3 1.1-6.4 2z"/>', 5, 1, 7, 0),
('Smile One Tooth', '<path fill="#fff" d="M178.2 140.8s-1.6-1.2-1.6.1c0 0 .4 9.8-4.6 13.3 0 0 7.8-2.5 10.4-3.1 0-3.2-.8-7.2-4.2-10.3z"/>\n  <path fill="#541D0C" d="M165.7 156.2c-12.4 0-19.8-7.7-19.9-7.8-.4-.4-.4-1 .1-1.4a1 1 0 011.4 0c.6.6 13.9 14.5 35.6 2.2.5-.3 1.1-.1 1.4.4.3.5.1 1.1-.4 1.4a36 36 0 01-18.2 5.2z"/>', 5, 1, 7, 0),
('Triangular with Ring', '<path opacity=".6" d="M172.6 137.9h-15.3c-2.1 0-3.8-1.9-3.9-4.2v.3c0 2.4 1.7 4.4 3.9 4.4h15.3c2.2 0 3.9-2 3.9-4.4v-.3c-.1 2.3-1.8 4.2-3.9 4.2z"/>\n  <path opacity=".6" class="color1" d="M176.1 131.5a28.6 28.6 0 01-5.9-14.4 5.4 5.4 0 00-5.2-3.8 5.4 5.4 0 00-5.2 3.9 28.2 28.2 0 01-6 14.3c-.2.6-.4 1.2-.4 1.9 0 2.4 1.7 4.4 3.9 4.4h15.3c2.2 0 3.9-2 3.9-4.4a4 4 0 00-.4-1.9z"/>\n  <path fill="#d3d3d3" d="M165.1 143.9h-.5c-.6 0-1.4-.2-2.1-.5a4.7 4.7 0 01-2.9-2.6 4 4 0 01-.2-2.2c.1-.5.3-1.6.9-2.4.1-.2.4-.2.6-.1.2.1.2.4.1.6-.3.4-.6 1.2-.7 2.1-.1.6-.1 1.2.2 1.7.4.9 1.3 1.7 2.4 2.1.7.2 1.3.4 1.9.4.8.1 2.4 0 3.5-.8.9-.7 1.9-2 1.8-3.2a3 3 0 00-.9-2c-.2-.2-.2-.4 0-.6.2-.2.4-.2.6 0a4 4 0 011.1 2.5c.1 1.6-1 3.1-2.1 3.9a5.8 5.8 0 01-3.7 1.1z"/>', 7, 1, 7, 1),
('Round', '<ellipse opacity=".6" class="color1" cx="165" cy="130.5" rx="6.1" ry="8.3"/>\n  <path opacity=".6" d="M165 138.8c-3.3 0-6-3.6-6.1-8.1v.2c0 4.6 2.7 8.3 6.1 8.3s6.1-3.7 6.1-8.3v-.2c-.1 4.5-2.8 8.1-6.1 8.1z"/>', 7, 1, 7, 1),
('Ring', '<path opacity=".6" class="color1" d="M171.9 126.8h-1l-1-5c0-3.5-1.6-6.3-4.8-6.3-3.2 0-4.8 2.8-4.8 6.3l-1 5h-1c-2.5 0-4.5 2.1-4.5 4.8 0 2.7 2 4.8 4.5 4.8h1.4a6 6 0 005.4 3.9 6 6 0 005.4-3.9h1.4c2.5 0 4.5-2.1 4.5-4.8-.1-2.7-2.1-4.8-4.5-4.8z"/>\n  <path opacity=".6" d="M171.9 136.4h-1.4a6 6 0 01-5.4 3.9 6 6 0 01-5.4-3.9h-1.4c-2.4 0-4.3-2-4.5-4.6v.2c0 2.7 2 4.8 4.5 4.8h1.4a6 6 0 005.4 3.9 6 6 0 005.4-3.9h1.4c2.5 0 4.5-2.1 4.5-4.8v-.2c-.2 2.6-2.1 4.6-4.5 4.6z"/>\n  <path fill="#d3d3d3" d="M165.4 144h-.6c-.8 0-1.7-.2-2.6-.6a6 6 0 01-3.5-3.1c-.3-.8-.4-1.7-.3-2.6a9 9 0 011-2.9c.2-.2.5-.3.7-.1.2.2.3.5.1.7-.4.5-.7 1.5-.9 2.5-.1.7-.1 1.4.2 2 .5 1.1 1.5 2 2.9 2.5.8.3 1.6.5 2.3.5 1 .1 2.9 0 4.3-1 1.1-.8 2.2-2.4 2.2-3.9 0-.8-.5-1.8-1.1-2.4-.2-.2-.2-.5 0-.7s.5-.2.7 0c.7.7 1.3 2 1.4 3 .1 1.9-1.2 3.8-2.6 4.7a6 6 0 01-4.2 1.4z"/>', 7, 1, 7, NULL),
('Medium', '<path opacity=".6" class="color1" d="M172.6 127.5l-2.2-1-1-9.3c0-3-1.4-5.4-4.4-5.4s-4.4 2.4-4.4 5.4l-1 9.3-2.2 1c-2.2 0-3.9 2-3.9 4.4 0 2.4 1.7 4.4 3.9 4.4h15.3c2.2 0 3.9-2 3.9-4.4-.1-2.4-1.8-4.4-4-4.4z"/>\n  <path opacity=".6" d="M172.6 136.3h-15.3c-2.1 0-3.8-1.9-3.9-4.2v.2c0 2.4 1.7 4.4 3.9 4.4h15.3c2.2 0 3.9-2 3.9-4.4v-.2c-.1 2.4-1.8 4.2-3.9 4.2z"/>', 7, 1, 7, 1),
('Wide', '<path opacity=".6" class="color1" d="M173.6 131.4c0 2.6-2.1 4.7-4.7 4.7H161a4.7 4.7 0 110-9.4h7.9c2.6 0 4.7 2.1 4.7 4.7z"/>\n  <path opacity=".6" d="M168.9 136.1H161c-2.5 0-4.5-2-4.7-4.4v.2c0 2.6 2.1 4.7 4.7 4.7h7.9c2.6 0 4.7-2.1 4.7-4.7v-.2a4.7 4.7 0 01-4.7 4.4z"/>', 7, 1, 7, 1),
('Big', '<path opacity=".6" class="color1" d="M176.5 133l-.1-.2-.2-.2a13.6 13.6 0 01-6.3-6.5c-4.8-5-9.7 0-9.7 0a16.9 16.9 0 01-6.8 7c-.7.9-1.2 2-1.2 3.1a5.3 5.3 0 006.7 4.9c1.4 1.8 3.6 2.9 6.1 2.9s4.7-1.2 6.1-2.9l1.3.2c3 0 5.4-2.3 5.4-5.1a5.4 5.4 0 00-1.3-3.2z"/>\n  <path opacity=".6" d="M172.3 141.3l-1.3-.2c-1.4 1.8-3.6 2.9-6.1 2.9s-4.7-1.2-6.1-2.9l-1.3.2a5.4 5.4 0 01-5.4-4.9v.3a5.3 5.3 0 006.7 4.9c1.4 1.8 3.6 2.9 6.1 2.9s4.7-1.2 6.1-2.9l1.3.2c3 0 5.4-2.3 5.4-5.1v-.3c-.1 2.8-2.5 4.9-5.4 4.9z"/>', 7, 1, 7, 1),
('Stitched Shut', '<path fill="#fff" d="M181 120.6a5.6 5.6 0 1011.2 0c0-.9-.2-1.8-.6-2.6l-10.5 1.6-.1 1z"/>\n  <path fill="#545151" d="M150.3 122.5h-.3l-10.8-3.5a1 1 0 01-.6-1.3 1 1 0 011.3-.6l10.8 3.5c.5.2.8.7.6 1.3-.2.3-.6.6-1 .6z"/>\n  <path fill="#7B7A7B" d="M136.6 130l-.3-.1c-.2-.2-.3-.5-.1-.7l12.9-15.9c.2-.2.5-.3.7-.1.2.2.3.5.1.7L137 129.8c-.1.2-.2.2-.4.2z"/>\n  <path fill="#7B7A7B" d="M150.7 117.7h-.2l-5.9-2.5c-.3-.1-.4-.4-.3-.7s.4-.4.7-.3l5.9 2.5c.3.1.4.4.3.7-.1.2-.3.3-.5.3zm-6.1 7.1h-.2l-5.9-2.5c-.3-.1-.4-.4-.3-.7s.4-.4.7-.3l5.9 2.5c.3.1.4.4.3.7-.1.2-.3.3-.5.3zm-2.5 3.4h-.2l-5.9-2.5c-.3-.1-.4-.4-.3-.7.1-.3.4-.4.7-.3l5.9 2.5c.3.1.4.4.3.7-.1.2-.3.3-.5.3z"/>\n  <path class="color1" d="M191.4 116.4l-1 .2-3.6.6-1.5.2-3.6.5-1.1.2c-.5.1-.8.5-.7.9.1.4.4.7.8.7h.3l2.2-.3c-.2.4-.3.8-.3 1.3 0 1.9 1.6 3.5 3.5 3.5s3.5-1.6 3.5-3.5c0-.8-.3-1.6-.8-2.2l2.3-.3h.1c.5-.1.8-.5.7-.9.1-.7-.3-1-.8-.9z"/>', 8, 1, 7, 1),
('Angry', '<path fill="#fff" d="M142.9 127.2a8.2 8.2 0 0010.4-4.9l-15.3-5.5a8.2 8.2 0 004.9 10.4zM187.1 127.2a8.2 8.2 0 01-10.4-4.9l15.3-5.5a8.2 8.2 0 01-4.9 10.4z"/>\n  <path class="color1" d="M144.9 125.1c2.9 1 6-.5 7-3.3l-10.3-3.7c-1 2.8.4 5.9 3.3 7zM185.1 125.1c-2.9 1-6-.5-7-3.3l10.3-3.7c1 2.8-.4 5.9-3.3 7z"/>\n  <path fill="#6B7C8D" d="M142 129h-.2c-5.9-2.3-6-7.1-6-7.3 0-.3.2-.5.5-.5s.5.2.5.5c0 .2.1 4.3 5.4 6.4.3.1.4.4.3.6-.1.2-.3.4-.5.3zM188 129h.2c5.9-2.3 6-7.1 6-7.3 0-.3-.2-.5-.5-.5s-.5.2-.5.5c0 .2-.1 4.3-5.4 6.4-.3.1-.4.4-.3.6.1.2.3.4.5.3z"/>', 8, 1, 7, 1),
('Animal', '<path fill="#fff" d="M152.7 119.5a4 4 0 01-4 4h-5.2a4 4 0 01-4-4 4 4 0 014-4h5.2a4 4 0 014 4zM190.6 119.5a4 4 0 01-4 4h-5.2a4 4 0 01-4-4 4 4 0 014-4h5.2a4 4 0 014 4z"/>\n    <path class="color1" d="M148.7 120.5c0 1.5-1.2 2.7-2.7 2.7a2.7 2.7 0 01-2.7-2.7v-2c0-1.5 1.2-2.7 2.7-2.7 1.5 0 2.7 1.2 2.7 2.7v2zM186.7 120.5c0 1.5-1.2 2.7-2.7 2.7a2.7 2.7 0 01-2.7-2.7v-2c0-1.5 1.2-2.7 2.7-2.7 1.5 0 2.7 1.2 2.7 2.7v2z"/>', 8, 1, 7, 1),
('Narrowed', '<path fill="#fff" d="M187.8 118.9c0 1.5-1.2 2.7-2.7 2.7s-2.7-1.2-2.7-2.7v-1h-3.9a4 4 0 004 4h5.2a4 4 0 004-4h-3.9v1zM147.6 118.9c0 1.5-1.2 2.7-2.7 2.7s-2.7-1.2-2.7-2.7v-1h-3.9a4 4 0 004 4h5.2a4 4 0 004-4h-3.9v1z"/>\n    <path class="color1" d="M191.8 116.6h-13.4c-.4 0-.7.3-.7.7 0 .4.3.7.7.7h4v1c0 1.5 1.2 2.7 2.7 2.7s2.7-1.2 2.7-2.7v-1h4c.4 0 .7-.3.7-.7 0-.4-.3-.7-.7-.7zM151.6 116.6h-13.4c-.4 0-.7.3-.7.7 0 .4.3.7.7.7h4v1c0 1.5 1.2 2.7 2.7 2.7s2.7-1.2 2.7-2.7v-1h4c.4 0 .7-.3.7-.7 0-.4-.3-.7-.7-.7z"/>', 8, 1, 7, 1),
('Round', '<circle fill="#fff" cx="145.3" cy="124.1" r="6.7"/>\n    <circle class="color1" cx="145.3" cy="124.1" r="3.9"/>\n    <circle fill="#fff" cx="184.7" cy="124.1" r="6.7"/>\n    <circle class="color1" cx="184.7" cy="124.1" r="3.9"/>', 8, 1, 7, 1),
('Suspicious', '<path class="color1" d="M151.7 118.7H139c-.6 0-1 .4-1 1s.4 1 1 1h2.6l-.1 1c0 2.4 2 4.4 4.5 4.4s4.5-2 4.5-4.4l-.1-1h1.4c.6 0 1-.4 1-1s-.6-1-1.1-1zM178.3 118.7H191c.6 0 1 .4 1 1s-.4 1-1 1h-2.6l.1 1c0 2.4-2 4.4-4.5 4.4s-4.5-2-4.5-4.4l.1-1h-1.4c-.6 0-1-.4-1-1s.6-1 1.1-1z"/>', 8, 1, 7, 1),
('Dotted', '<circle class="color1" cx="137.4" cy="105.5" r="1.7"/>\n<circle class="color1" cx="141.7" cy="106.9" r="1.7"/>\n<circle class="color1" cx="146" cy="108.3" r="1.7"/>\n<circle class="color1" cx="150.3" cy="109.6" r="1.7"/>\n<circle class="color1" cx="154.5" cy="111" r="1.7"/>\n<circle class="color1" cx="192.6" cy="105.5" r="1.7"/>\n<circle class="color1" cx="188.3" cy="106.9" r="1.7"/>\n<circle class="color1" cx="184" cy="108.3" r="1.7"/>\n<circle class="color1" cx="179.7" cy="109.6" r="1.7"/>\n<circle class="color1" cx="175.5" cy="111" r="1.7"/>', 9, 1, 7, 1),
('Thick', '<path class="color1" d="M154.6 107.3c0 2-1.6 3.5-3.5 3.5h-11c-2 0-3.5-1.6-3.5-3.5 0-2 1.6-3.5 3.5-3.5h11c1.9 0 3.5 1.6 3.5 3.5zM193.5 107.3c0 2-1.6 3.5-3.5 3.5h-11c-2 0-3.5-1.6-3.5-3.5 0-2 1.6-3.5 3.5-3.5h11c1.9 0 3.5 1.6 3.5 3.5z"/>', 9, 1, 7, 1),
('Small', '<path class="color1" d="M150.5 108.7c0 1.8-1.5 3.2-3.2 3.2h-3.9a3.2 3.2 0 01-3.2-3.2c0-1.8 1.5-3.2 3.2-3.2h3.9c1.7 0 3.2 1.4 3.2 3.2zM189.9 108.7c0 1.8-1.5 3.2-3.2 3.2h-3.9a3.2 3.2 0 01-3.2-3.2c0-1.8 1.5-3.2 3.2-3.2h3.9c1.7 0 3.2 1.4 3.2 3.2z"/>', 9, 1, 7, 1),
('Lines', '<path class="color1" d="M134.6 109.8c-.2 0-.4-.1-.4-.3l-1.6-3.1c-.1-.2 0-.5.2-.7.2-.1.5 0 .7.2l1.6 3.1c.1.2 0 .5-.2.7l-.3.1zM137 110.2c-.2 0-.4-.1-.4-.3l-1.6-3.1c-.1-.2 0-.5.2-.7.2-.1.5 0 .7.2l1.6 3.1c.1.2 0 .5-.2.7l-.3.1zM139.4 110.6c-.2 0-.4-.1-.4-.3l-1.6-3.1c-.1-.2 0-.5.2-.7.2-.1.5 0 .7.2l1.6 3.1c.1.2 0 .5-.2.7l-.3.1zM141.7 111c-.2 0-.4-.1-.4-.3l-1.6-3.1c-.1-.2 0-.5.2-.7.2-.1.5 0 .7.2l1.6 3.1c.1.2 0 .5-.2.7l-.3.1zM144.1 111.4c-.2 0-.4-.1-.4-.3L142 108c-.1-.2 0-.5.2-.7.2-.1.5 0 .7.2l1.6 3.1c.1.2 0 .5-.2.7l-.2.1zM146.4 111.8c-.2 0-.4-.1-.4-.3l-1.6-3.1c-.1-.2 0-.5.2-.7.2-.1.5 0 .7.2l1.6 3.1c.1.2 0 .5-.2.7l-.3.1zM148.8 112.2c-.2 0-.4-.1-.4-.3l-1.6-3.1c-.1-.2 0-.5.2-.7.2-.1.5 0 .7.2l1.6 3.1c.1.2 0 .5-.2.7l-.3.1zM151.1 112.6c-.2 0-.4-.1-.4-.3l-1.6-3.1c-.1-.2 0-.5.2-.7.2-.1.5 0 .7.2l1.6 3.1c.1.2 0 .5-.2.7l-.3.1zM153.5 113c-.2 0-.4-.1-.4-.3l-1.6-3.1c-.1-.2 0-.5.2-.7.2-.1.5 0 .7.2l1.6 3.1c.1.2 0 .5-.2.7l-.3.1zM195.4 109.8l-.2-.1c-.2-.1-.3-.4-.2-.7l1.6-3.1c.1-.2.4-.3.7-.2.2.1.3.4.2.7l-1.6 3.1-.5.3zM193 110.2l-.2-.1c-.2-.1-.3-.4-.2-.7l1.6-3.1c.1-.2.4-.3.7-.2.2.1.3.4.2.7l-1.6 3.1c-.1.2-.3.3-.5.3zM190.6 110.6l-.2-.1c-.2-.1-.3-.4-.2-.7l1.6-3.1c.1-.2.4-.3.7-.2.2.1.3.4.2.7l-1.6 3.1c-.1.2-.3.3-.5.3zM188.3 111l-.2-.1c-.2-.1-.3-.4-.2-.7l1.6-3.1c.1-.2.4-.3.7-.2.2.1.3.4.2.7l-1.6 3.1c-.2.2-.3.3-.5.3zM185.9 111.4l-.2-.1c-.2-.1-.3-.4-.2-.7l1.6-3.1c.1-.2.4-.3.7-.2.2.1.3.4.2.7l-1.6 3.1c-.1.2-.3.3-.5.3zM183.6 111.8l-.2-.1c-.2-.1-.3-.4-.2-.7l1.6-3.1c.1-.2.4-.3.7-.2.2.1.3.4.2.7l-1.6 3.1c-.2.2-.3.3-.5.3zM181.2 112.2l-.2-.1c-.2-.1-.3-.4-.2-.7l1.6-3.1c.1-.2.4-.3.7-.2.2.1.3.4.2.7l-1.6 3.1c-.1.2-.3.3-.5.3zM178.9 112.6l-.2-.1c-.2-.1-.3-.4-.2-.7l1.6-3.1c.1-.2.4-.3.7-.2.2.1.3.4.2.7l-1.6 3.1-.5.3zM176.5 113l-.2-.1c-.2-.1-.3-.4-.2-.7l1.6-3.1c.1-.2.4-.3.7-.2.2.1.3.4.2.7l-1.6 3.1c-.1.2-.3.3-.5.3z"/>', 9, 1, 7, 1),
('Slanted', '<path class="color1" d="M156.4 112.9c-.6 1.7-2.2 2.6-3.6 2.1l-17-6.3c-1.4-.5-2-2.3-1.4-3.9.6-1.7 2.2-2.6 3.6-2.1l17 6.3c1.4.5 2 2.3 1.4 3.9zM173.6 112.9c.6 1.7 2.2 2.6 3.6 2.1l17-6.3c1.4-.5 2-2.3 1.4-3.9-.6-1.7-2.2-2.6-3.6-2.1l-17 6.3c-1.4.5-2 2.3-1.4 3.9z"/>', 9, 1, 7, 1),
('Tapered', '<path class="color1" d="M195.5,108.6c-6-0.2-19.4-5.5-21.6,2.5c-0.2,2.9,2.5,3.9,4.9,3C180.7,113,201.2,111.8,195.5,108.6z"/>\n<path class="color1" d="M134.5,108.6c6-0.2,19.4-5.5,21.6,2.5c0.2,2.9-2.5,3.9-4.9,3C149.3,113,128.8,111.8,134.5,108.6z"/>', 9, 1, 7, 1),
('Chin Beard', '<path class="color1" d="M184.6 174.6l-.1-1.1-12.6-12.6a10 10 0 00-14.1 0l-12.5 12.5V175.8c-.3 11.4 5.2 25.6 16.9 29.7a14.3 14.3 0 008.3.3c-.4.1-1.4-.9-1.7-1-.9-.7-1.9-1.8-2-3-.2-2.9 3.7-4.5 5.9-5.4 6-2.5 10.2-7 11.7-13.4.6-3 .5-5.7.2-8.4z"/>', 6, 1, 7, 1),
('Goatee', '<path class="color1" d="M172 167.7a10 10 0 00-14.1 0l-12.5 12.5s17.6 19.5 39.3.1L172 167.7z"/>', 6, 1, 7, 1),
('Full Beard', '<path class="color1" d="M207.2 116.7l-8.5 17.8.4 17.9s-3.4 14.1-15.8 21.4l-17.6-10.6-19.8 9.6-1-.7a37 37 0 01-12.5-17.6l-.3-.9-.2-.7-.2-.6.4-17.9-8.6-17.9s-5.1 19.4-2.2 34.6c0 0 5.8 37 41.6 37.3 0 0 25.3 1.5 36.8-15.7-.2.1 16.4-16.3 7.5-56z"/>', 6, 1, 7, 1),
('Orc Female', '<path class="color1" d="M156.5 399.4c-.3-1.6-.3-3.4-.8-4.7-.4-1.2-6.6-54.2-7-57.6-.3-3.4 4.8-50.1 4.8-50.1L112 297.8s1.3 41.8 7.7 58c1.7 4.4 20.3 30.6 24.1 37.9-6.9 3-13.2 3.5-20.5 4.9-3.3.6-4.6 6.2-1.3 6.6 4.3.5 8.3.2 12.5.1l15.9.5c3.6.2 6.4-3.5 6.1-6.4zM173.5 399.4c.3-1.6.3-3.4.8-4.7.4-1.2 6.6-54.2 7-57.6.3-3.4-4.8-50.1-4.8-50.1l41.5 10.8s-1.3 41.8-7.7 58c-1.7 4.4-20.3 30.6-24.1 37.9 6.9 3 13.2 3.5 20.5 4.9 3.3.6 4.6 6.2 1.3 6.6-4.3.5-8.3.2-12.5.1l-15.9.5c-3.6.2-6.4-3.5-6.1-6.4z"/>\n  <path class="color1" d="M84.9 234.2c-.9 15.8-1.6 31.7.5 47.4a48 48 0 00-8.9 31.3 4 4 0 004.1 3.7c6.5-1 2.6-11.1 4.5-14.9.9 4-3.2 19.8 5.1 20.7 7.2-2.3.9-15.6 2.8-19.6.9 6.7 1.4 19.1 9.2 21.2 3-.1 4.9-3.8 3-6.2a27.3 27.3 0 01-4.2-13.7c1.7 4.3 5 14.7 11.8 16.4 3.2 0 5-4.1 2.8-6.5a30.5 30.5 0 01-7.8-15.5c2.4 2.2 6.4 9.6 10.5 5.9 4.6-3.7-3.6-9.2-5.4-12-3.9-7-7.4-9.9-7.6-9.9-.6-23.4 5.5-48.4 8.8-71.2l-23.8-15a246.3 246.3 0 00-5.4 37.9zM245.1 234.2c.9 15.8 1.6 31.7-.5 47.4a48 48 0 018.9 31.3 4 4 0 01-4.1 3.7c-6.5-1-2.6-11.1-4.5-14.9-.9 4 3.2 19.8-5.1 20.7-7.2-2.3-.9-15.6-2.8-19.6-.9 6.7-1.4 19.1-9.2 21.2-3-.1-4.9-3.8-3-6.2 2.6-3.5 3.8-8.7 4.2-13.7-1.7 4.3-5 14.7-11.8 16.4-3.2 0-5-4.1-2.8-6.5a30.5 30.5 0 007.8-15.5c-2.4 2.2-6.4 9.6-10.5 5.9-4.6-3.7 3.6-9.2 5.4-12 3.9-7 7.4-9.9 7.6-9.9.6-23.4-5.5-48.4-8.8-71.2l23.8-15c2.5 10.6 4.4 23.8 5.4 37.9zM219.4 99.5v-.6a2.8 2.8 0 00-2.5-2.4c-.7-.1-1.3.1-1.8.5l-.5.4-9.6 8.5-.1-9c0-23.4-17.9-42.3-40-42.3s-40 18.9-40 42.3v9l-9.7-8.5-.5-.4c-.5-.4-1.1-.6-1.8-.5a2.8 2.8 0 00-2.5 2.4v.6c-.1 10.7 1.4 31.9 16.5 29.5a63 63 0 008.1 20.9 38 38 0 0029.9 17.7h.1c11.9 0 21.5-8.2 21.4-8.2a50.5 50.5 0 0015.5-25.9l1.2-4.4c14.9 2.1 16.5-18.9 16.3-29.6z"/>\n  <path opacity=".1" d="M214.6 97.4l.5-.4c.5-.4 1.1-.6 1.8-.5 1.3.1 2.3 1.1 2.5 2.4v.6c.1 10.8-1.5 32-16.6 29.5l1.9-22.8 9.9-8.8zM115.4 97.4l-.5-.4c-.5-.4-1.1-.6-1.8-.5a2.8 2.8 0 00-2.5 2.4v.6c-.1 10.8 1.5 32 16.6 29.5l-1.9-22.8-9.9-8.8z"/>\n  <path opacity=".3" d="M211 105.1l.3-.3c.3-.2.8-.4 1.2-.3a2 2 0 011.7 1.6v.4c.1 7.2-1 21.5-11.1 19.7l1.3-15.3 6.6-5.8zM119 105.1l-.3-.3c-.3-.2-.8-.4-1.2-.3a2 2 0 00-1.7 1.6v.4c-.1 7.2 1 21.5 11.1 19.7l-1.3-15.3-6.6-5.8z"/>', 1, 2, 7, 1),
('Shoes', '<path class="color1" d="M173.2 399.4c.3-1.6.3-3.5 1.1-4.7 5.3 2.5 11.8-1.1 11.8-1.1 6.9 3 14.7 3.6 22 5.1 3.3.6 3.4 6.4 0 6.7-4.4.5-8.7.7-13 .5-5.1-.2-14.4.3-19.2-.3-2.2-.3-3.1-2.9-2.7-6.2zM156.8 399.4c-.3-1.6-.3-3.5-1.1-4.7-5.3 2.5-11.8-1.1-11.8-1.1-6.9 3-14.7 3.6-22 5.1-3.3.6-3.4 6.4 0 6.7 4.4.5 8.7.7 13 .5 5.1-.2 14.4.3 19.2-.3 2.2-.3 3.1-2.9 2.7-6.2z"/>', 2, 2, 7, 1),
('Pants', '<path class="color1" d="M217.3 271.2H112.7S93.3 338 135 380.7h19.7c-2.4-16.2-8.5-64.3.8-74.1 5.5-5.8 13.7-6.5 18.9 0 9.3 9.8 3.2 57.9.8 74.1H195c41.7-42.7 22.3-109.5 22.3-109.5z"/>', 3, 2, 7, 1),
('Shorts', '<path class="color1" d="M217.3 271.2H112.7s-7.5 61.8 2.3 65 35.7 0 35.7 0-4.5-19.8 4.8-29.6c5.5-5.8 13.7-6.5 18.9 0 9.3 9.8 4.8 29.6 4.8 29.6s25.6 5.5 36.2 0c10.7-5.5 1.9-65 1.9-65z"/>', 3, 2, 7, 1),
('Shirt', '<path class="color1" d="M239.7 197.4l2.4-1.5c-8.7-42.8-35.9-59.1-40.4-61.6a50 50 0 01-15.3 25s-9.5 8.2-21.4 8.2h-.1c-11.9 0-21.5-8.2-21.5-8.2-3.2-2.6-6.2-6-8.4-9.5-3.1-5-5.3-10.2-6.8-15.6-4.3 2.3-31.7 18.6-40.5 61.6l2.4 1.5 23.8 15 .7.4-3.5 47.4-.8 10.9-.4 5.4s11.1 5.2 28.8 8.2c7.5 1.3 16.1 2.2 25.6 2.2h1a149.9 149.9 0 0054.4-10.4l-.4-5.4-.8-10.9-3.5-47.4.7-.4 24-14.9z"/>', 4, 2, 7, 1),
('Long Hair', '<path class="color1" d="M203.2 129a36 36 0 01-1.2 4.4l-.3.9 3.3 2a81.2 81.2 0 0128.6 34.1c1.3-6.5 1-13.4-1-19.7-1.9-6-6.6-10.1-9.3-15.6a27 27 0 01-1.9-15.8c.9-4.7 1.1-9.7-.2-14.4-.4-1.6-1.2-2.9-1.9-4.1 0 11.3-1.9 29.8-16.1 28.2zM110.6 101.3l-.5.7c-5.4 8.7-3.8 18.8-3.7 28.5.1 9-7 14-9.9 21.8a30.5 30.5 0 00-.6 19.1l1.4-2.9.3-.6 1.4-2.7.1-.2 1.4-2.4.3-.4 1.6-2.5.2-.2 1.6-2.3.1-.2 1.7-2.3.1-.1c1.2-1.5 2.3-3 3.5-4.3l4-4.3c7-7 13-10.5 14.8-11.5l-1.3-5.3c-14.4 1.8-16.4-16.7-16.5-27.9zM212 87.8c-2-10-3.6-20.6-11.2-27.8a49.7 49.7 0 00-41.1-11.6c-15 2.1-29.2 7.3-37 21.1a55.3 55.3 0 00-4.5 10.7c-1.1 3.5-.5 7.3-1.4 10.8-.5 1.9-1.6 3.8-2.8 5.5l.8.4.5.4 9.8 8.6v-2.2-7l.1-2.3 2.7-.3c2.9-.3 5.7-.7 8.4-1.2 26.8-4.6 40.4-15.7 46.9-24a36.7 36.7 0 0021.7 24.8l.1 2.9.1 9.2 9.8-8.6.5-.4 1.2-.5c-2-2.2-3.8-4.7-4.6-8.5z"/>', 10, 2, 7, 1),
('Ponytail', '<path class="color1" d="M236.4 82.6c-.5-11.7-1.4-24.1-11.9-31.1-9.6-6.4-21.5-2.4-30.3 3.3-2.2 1.5-.4 10.7 2.5 10.3 3.4-.5 6.5-.4 9.8.7 3.7 1.2 5.5 4.7 6.4 8.2 2.2 8.1 1.7 16.8 1.7 25.1.1 7.5-1.5 28.1-2.3 30.9-1 3.8-1.4 6-2.5 9.8 6.6 5.2 13.2 13.4 13.2 13.4l3.4-7.7c3-6.4 5.5-13 7.4-19.9a151 151 0 002.6-43z"/>\n  <path opacity=".5" class="color1" d="M205 102v-5.2a43 43 0 00-17.7-35.2l-1.5 8.3 2.8 1.2c1.7 1 3.2 2 4.5 3.6 1.3 1.6.9 3.9 1 5.8.1 2 .5 4 1.4 5.7.8 1.5 2.3 2.3 3.4 3.5 1.7 1.9 2.4 4.6 2.8 7.1.3 1.8.3 3.8.3 5.7l3-.5z"/>\n  <path class="color1" d="M189.7 56.1a16 16 0 00-3.9-3.5c-8.7-5.9-21.4-5.5-31.2-3.4-15.6 3.3-30.9 17.4-32.8 33.7a61.4 61.4 0 002.4 22.8 3.4 3.4 0 003.1 2.1c1.1 0 2.2-.6 2.8-1.4l.5-1.2 1.5-2.9c2.2-3.9 5.7-6.9 9.3-9.3 14.4-9.7 32.7-11.2 47.2-21 6.5-4.4 5.1-11.1 1.1-15.9z"/>\n  <path class="color2" d="M197.9 66.5c-1.4.9-3.2.7-4-.6l-5.3-7.6c-.9-1.3-.4-3 .9-4 1.4-.9 3.2-.7 4 .6l5.3 7.6c.9 1.3.4 3-.9 4z"/>', 10, 2, 7, 2),
('Short', '<path opacity=".5" class="color1" d="M135.4 72.2l-2-2.4c-5.5 7.3-8.5 15-8.5 25v8.9c8.3-3 10.5-31.5 10.5-31.5zM195.3 72.2l1.2-1.9a38 38 0 018.4 24.5v8.9c-8.1-3-9.6-31.5-9.6-31.5z"/>\n  <path class="color1" d="M197.3 62a19.7 19.7 0 00-6.1-8.3 41.5 41.5 0 00-26-8.8h-1.1-.4l-1.2.1-1 .1h-.3c-7 .7-25.7 3.5-36.7 15.3 0 0 2.9-2 8.7-2.1-3.2 2.1-6.4 4.9-6.5 7.7 0 0 2.8-1.6 6-2.2-2.2 1.8-5 4.6-5.6 7.2 0 0 2.3-.9 5-1.1a12 12 0 005.5 9.4 26 26 0 0011.6 3.3c7.3.9 15 .6 22.3-.3 6.7-.8 15-1.9 20.9-5.9 6-3.7 6.8-9.3 4.9-14.4z"/>', 10, 2, 7, 1),
('Rounded', '<ellipse class="color1" cx="149.4" cy="94.3" rx="5.6" ry="2.7"/>\n    <ellipse class="color1" cx="180.6" cy="94.3" rx="5.6" ry="2.7"/>', 9, 2, 7, 1),
('Arched', '<path class="color1" d="M156.4 93.5c-5.4-5.1-11.9-4.7-17.3.3-1.8 1.7.8 4.5 2.5 2.8 3.7-3.5 8.4-3.9 12.2-.3 1.8 1.7 4.3-1.1 2.6-2.8zM173.6 93.5c5.4-5.1 11.9-4.7 17.3.3 1.8 1.7-.8 4.5-2.5 2.8-3.7-3.5-8.4-3.9-12.2-.3-1.8 1.7-4.3-1.1-2.6-2.8z"/>', 9, 2, 7, 1),
('Slanted', '<path class="color1" d="M156.7 96.9a2 2 0 01-2.1 1.8l-10.7-.9a2 2 0 01-1.8-2.1 2 2 0 012.1-1.8l10.7.9a2 2 0 011.8 2.1zM173.3 96.9a2 2 0 002.1 1.8l10.7-.9a2 2 0 001.8-2.1 2 2 0 00-2.1-1.8l-10.7.9a2 2 0 00-1.8 2.1z"/>', 9, 2, 7, 1),
('Tapered', '<path class="color1" d="M155.3 96c-3.5-6.3-13.8-3.3-17.2 1.8.1-.5 18.1.6 17.2-1.8zM174.7 96c3.5-6.3 13.8-3.3 17.2 1.8-.1-.5-18.1.6-17.2-1.8z"/>', 9, 2, 7, 1),
('Pointed', '<path class="color1" d="M155.4 90.5l.3 3.3-17.5.9 1.8-1.4c1.5-1.1 2.9-2 4.7-2.3 2.2-.4 4.5-.6 6.7-.7.4.1 4 0 4 .2zM174.6 90.5l-.3 3.3 17.5.9-1.8-1.4c-1.5-1.1-2.9-2-4.7-2.3-2.2-.4-4.5-.6-6.7-.7-.4.1-4 0-4 .2z"/>', 9, 2, 7, 1),
('Roar', '<path fill="#CB636D" d="M159.6 133.6l-5.4 2.7-5.3 4.4 32.3.3-3.9-3.3-8-4z"/>\n  <path fill="#5C4D50" d="M182.3 140.3l-2.8-2.2c-.2 1.2-.7 1.9-.7 2h-28.2c2.7-2.7 9-7.7 18.3-5.2 1.9.5 3.9 1.4 5.9 2.5.2-.6.4-1.3.4-1.9v-.1c-1.9-1-3.8-1.9-5.7-2.4-13.5-3.7-21.7 7.4-21.8 7.5-.2.3-.3.7-.1 1 .1.3.4.4.6.5h.3l33.2.1c.4 0 .8-.3.9-.6.1-.5 0-1-.3-1.2z"/>\n  <path fill="#fff" d="M179.5 138c.4-2.1.3-5.4-3.7-8.3-.2-.2-.7-.3-.9-.2-.7.2-.6 1.1-.4 1.6.5 1.4.7 2.7.7 4.2v.1c0 .7-.2 1.3-.4 1.9l-.3.9c-.3.6-.7 1.6-1.5 1.9h5.8l.7-2.1zM150.6 140s-3.4-5.7 2.9-10.3c.2-.2.7-.3.9-.2.7.2.6 1.1.4 1.6-.5 1.4-.7 2.8-.6 4.3a9 9 0 00.8 2.8c.3.7.9 1.8 1.8 1.9l-6.2-.1z"/>', 5, 2, 7, 0),
('Teeth', '<path fill="#C97F83" d="M149.1 141.3l6.1-4.7 3.5-1.5 3.5-.6 3.6-.5 3 .6 2.7 1 2.8 1.5 2 1 2.5 1.9 1.4 1.2-4.2-.6-4.6-1.1-3-.8-3.5-.1h-3.3l-3.5.4-3.5 1-3.8 1.3-1.5.7c.2 0-.2-.7-.2-.7z"/>\n  <path fill="#5C4D50" d="M149.3 142.4l-.4-.1c-.3-.2-.4-.7-.1-1 .2-.3 5.4-7.1 14.1-7.9 6.1-.5 12.2 2.1 18.3 7.8.3.3.3.7 0 1-.3.3-.7.3-1 0-5.8-5.4-11.6-7.9-17.2-7.4a20 20 0 00-13.1 7.4l-.6.2z"/>\n  <path fill="#fff" d="M178.7 130.5s-1-1-1.1-.1c0 0-.4 5.6-3.2 8.1l-.1-1.8-.1-1.1-.3-.9-.4-.1c-.3.1-.5.5-.6.8l-1.3 2.8-.4-2.5-.2-1.1-.4-.8-.4-.1c-.3.1-.4.6-.6.9l-.9 2.7-.2.5-.4-2.2-.2-1.1-.4-.8-.4-.1c-.3.1-.4.6-.6.9l-.9 2.7-.1.4-.5-2.1-.3-1.1c-.1-.3-.2-.6-.5-.8a.3.3 0 00-.4 0c-.3.1-.4.6-.5.9l-.8 2.7-.1.4-.5-2-.3-1.1c-.1-.3-.2-.6-.5-.8a.3.3 0 00-.4 0c-.3.2-.4.6-.5.9a23 23 0 00-.7 2.8l-.2.6v-.1l-.8-2.1-.4-1.1c-.1-.3-.3-.6-.6-.7a.3.3 0 00-.4 0c-.3.2-.3.7-.4 1l-.5 2.8-.1.9c-3-2.5-3.4-8.3-3.4-8.3-.1-.9-1.1.1-1.1.1-4.2 4.5-1.1 10.9-1.1 10.9s4.1-3 14.5-3 15 3 15 3 2.9-6.4-1.3-10.9z"/>\n  <path fill="#5C4D50" d="M180.6 142.3l-.3-.1a36 36 0 00-30.4 0c-.3.2-.8.1-.9-.3-.2-.3-.1-.8.3-.9.1-.1 13.6-7.6 31.7 0 .4.1.5.6.4.9-.3.3-.5.4-.8.4z"/>', 5, 2, 7, 0),
('Smile One Tooth', '<path fill="#fff" d="M162.2 145.6l-.2-.6-.5-.7-.2-.3c-1.5-2.4-3.6-8.9-4-10.1l-.3-.8c-.1-.2-.3-.3-.6-.3s-.6.2-.8.5l-.1.4-1.9 7.7c.6.2 1.2.5 1.6 1 .9.2 1.6.7 2.2 1.2.8.1 1.4.4 2 .8l.6.9.8-.4.5.2.9.5z"/>\n  <path fill="#5C4D50" d="M165.2 146.8c-8.2 0-14-5.5-14.1-5.6a1 1 0 010-1.4 1 1 0 011.4 0c.5.5 12 11.2 25.1 0 .4-.4 1.1-.3 1.4.1.4.4.3 1.1-.1 1.4a21.1 21.1 0 01-13.7 5.5z"/>', 5, 2, 7, NULL),
('Serious', '<path fill="#fff" d="M156.4 131.1l-.1-.5c-.1-.8-.8-1.4-1.6-1.4-.4 0-.8.2-1.1.4l-.2.2a11.8 11.8 0 00-1.7 2.4c-2.2 5 .6 7.6.6 7.6h4.4l2.1.5V139a5 5 0 01-1.8-2.5 12 12 0 01-.7-4.2l.1-1.2zM173.6 131.2v-.5c.1-.8.8-1.4 1.6-1.4.4 0 .8.2 1.1.4l.2.2c1.1 1.2 1.7 2.4 1.7 2.4 2.2 5-.6 7.6-.6 7.6h-4.4l-2.1.5v-1.3c.9-.5 1.4-1.5 1.8-2.5.5-1.4.7-2.8.7-4.2v-1.2z"/>\n  <path fill="#5C4D50" d="M178.5 141h-26.9c-.6 0-1.1-.4-1.1-1s.5-1 1.1-1h26.9c.6 0 1.1.4 1.1 1s-.5 1-1.1 1z"/>', 5, 2, 7, 0),
('Toothy Frown', '<path fill="#C97F83" d="M150.5,139.5l5.6-4.3l3.2-1.4l3.2-0.6l3.3-0.5l2.8,0.6l2.4,0.9l2.6,1.4l1.8,0.9l2.3,1.7l1.3,1.1\n      c0,0-3.8-0.5-3.8-0.6s-4.2-1-4.2-1l-2.8-0.7l-3.2-0.1l-3,0l-3.2,0.3l-3.2,0.9l-3.5,1.1c0,0-1.4,0.8-1.4,0.7\n      C150.8,140.1,150.5,139.5,150.5,139.5z"/>\n      <path fill="#5C4D50" d="M150.7,140.4c-0.1,0-0.3,0-0.4-0.1c-0.3-0.2-0.3-0.6-0.1-0.9c0.2-0.3,5-6.5,12.9-7.2\n        c5.5-0.5,11.2,1.9,16.7,7.1c0.3,0.2,0.3,0.6,0,0.9c-0.2,0.3-0.6,0.3-0.9,0c-5.3-4.9-10.6-7.2-15.7-6.8c-7.3,0.6-11.9,6.7-12,6.7\n        C151.1,140.4,150.9,140.4,150.7,140.4z"/>\n    <path fill="#fff" d="M157.3,137.3c0.1-0.3,0.2-0.7,0.3-1c0.2-0.8,0.4-1.7,0.7-2.5c0.1-0.3,0.2-0.7,0.4-0.8c0.1-0.1,0.3,0,0.4,0\n      c0.2,0.2,0.4,0.4,0.4,0.7c0.1,0.3,0.2,0.7,0.3,1c0.2,0.7,0.4,1.3,0.5,2c0,0,0.1,0.5,0.1,0.5C160.5,137.1,157.3,137.3,157.3,137.3z\n      "/>\n    <path fill="#fff" d="M163.1,136.8c0.1-0.3,0.2-0.7,0.3-1c0.2-0.8,0.4-1.7,0.6-2.5c0.1-0.3,0.2-0.7,0.4-0.8c0.1-0.1,0.3,0,0.4,0\n      c0.2,0.2,0.4,0.4,0.5,0.7c0.1,0.3,0.2,0.7,0.3,1c0.2,0.7,0.4,1.3,0.6,2c0,0,0.1,0.5,0.1,0.4C166.2,136.6,163.1,136.8,163.1,136.8z\n      "/>\n    <path fill="#fff" d="M168.4,137.1c0.1-0.3,0.2-0.7,0.3-1c0.3-0.8,0.5-1.7,0.8-2.5c0.1-0.3,0.2-0.7,0.5-0.8c0.1,0,0.3,0,0.4,0.1\n      c0.2,0.2,0.3,0.5,0.4,0.7c0.1,0.3,0.1,0.7,0.2,1c0.1,0.7,0.3,1.4,0.4,2c0,0,0.1,0.5,0.1,0.5L168.4,137.1z"/>\n      <path fill="#5C4D50" d="M179.2,140.4c-0.1,0-0.2,0-0.2,0c-15.9-6.7-27.7-0.1-27.8,0c-0.3,0.2-0.7,0.1-0.9-0.2\n        c-0.2-0.3-0.1-0.7,0.2-0.9c0.1-0.1,12.4-6.9,28.9,0c0.3,0.1,0.5,0.5,0.3,0.8C179.7,140.3,179.5,140.4,179.2,140.4z"/>', 5, 2, 7, 0),
('Triangular', '<path opacity=".6" class="color1" d="M173.6 114.7c-2-1.4-5.9-7.3-8.6-7.1-2.7-.2-6.7 5.7-8.6 7.1-2.6 2.4-.1 7.1 3.3 6.3.9-.1 1.8.4 2.3 1.1 1.4 2 4.7 1.9 6-.2l-.1.2c.5-.7 1.5-1.2 2.4-1.1h-.2c3.6.9 6.1-3.8 3.5-6.3z"/>\n  <path opacity=".6" d="M170.2 121h.2c-1-.1-1.9.4-2.4 1.1l.1-.2c-1.3 2.1-4.6 2.1-6 .2a2.6 2.6 0 00-2.3-1.1c-2.3.5-4.2-1.5-4.4-3.5-.1 2.3 1.9 4.6 4.4 4 .9.2 1.8.7 2.3 1.1 1.4 2 4.7 1.9 6-.2l-.1.2c.6-.4 1.6-.9 2.4-1.1h-.2c2.5.6 4.6-1.7 4.5-4-.2 2.1-2.1 4.1-4.5 3.5z"/>', 7, 2, 7, 1),
('Small', '<path opacity=".6" class="color1" d="M172.1 116.6c0 2.6-1.7 4.7-3.8 4.7h-6.5c-2.1 0-3.8-2.1-3.8-4.7s1.7-4.7 3.8-4.7h6.5c2.1 0 3.8 2.1 3.8 4.7z"/>\n  <path opacity=".6" d="M168.2 121.3h-6.5c-2 0-3.7-2-3.8-4.4v.2c0 2.6 1.7 4.7 3.8 4.7h6.5c2.1 0 3.8-2.1 3.8-4.7v-.2c-.1 2.4-1.7 4.4-3.8 4.4z"/>', 7, 2, 7, 1),
('Big', '<path opacity=".6" class="color1" d="M169.6 116.4l-1-5.2c0-3.1-.5-5.6-3.6-5.6s-3.6 2.5-3.6 5.6l-1 5.2c-3 .1-6.4 2.6-6.4 5.6 0 3.1 2.5 5.6 5.6 5.6h10.7c3.1 0 5.6-2.5 5.6-5.6.1-3-3.3-5.5-6.3-5.6z"/>\n  <path opacity=".6" d="M170.4 127.6h-10.7c-3 0-5.4-2.4-5.6-5.3v.2c0 3.1 2.5 5.6 5.6 5.6h10.7c3.1 0 5.6-2.5 5.6-5.6v-.2a5.7 5.7 0 01-5.6 5.3z"/>', 7, 2, 7, 1),
('Medium', '<path opacity=".6" class="color1" d="M172.8 117.7a4.2 4.2 0 00-3.3-1.7l-1-4.1c0-2.5-1-4.4-3.4-4.4-2.5 0-3.4 2-3.4 4.4l-1 4.3c-1.3 0-2.5.5-3.3 1.7a4 4 0 00.9 5.5c1.8.4 3.2 1.3 4.1 2.9l1.5.6 1.3.2c1.1 0 2.1-.4 2.9-1.1a5.4 5.4 0 014-2.8 4.3 4.3 0 00.7-5.5z"/>\n  <path opacity=".6" d="M171.8 123.1a5.4 5.4 0 00-4 2.8 4.4 4.4 0 01-4.2.9l-1.5-.6a5.9 5.9 0 00-4.1-2.9c-1-.7-1.6-1.8-1.6-3a4 4 0 001.6 3.5c1.6.7 3 1.7 4.1 2.9l1.5.6 1.3.2c1.1 0 2.1-.4 2.9-1.1a11 11 0 014-2.8 3.8 3.8 0 001.6-3.5c0 1.2-.5 2.3-1.6 3z"/>', 7, 2, 7, 1),
('Narrow', '<path opacity=".6" class="color1" d="M169.4 119.2c0 2.5-2 4.4-4.4 4.4-2.5 0-4.4-2-4.4-4.4v-6.5c0-2.5 2-4.4 4.4-4.4 2.5 0 4.4 2 4.4 4.4v6.5z"/>\n  <path opacity=".6" d="M165 123.6c-2.5 0-4.4-2-4.4-4.4v.5c0 2.5 2 4.4 4.4 4.4s4.4-2 4.4-4.4v-.5c0 2.4-1.9 4.4-4.4 4.4z"/>', 7, 2, 7, 1),
('Stiched', '<path fill="#845600" d="M153.3 110.9h-.2l-10-2a1 1 0 01-.8-1.2 1 1 0 011.2-.8l10 2c.5.1.9.6.8 1.2a1 1 0 01-1 .8z"/>\n  <ellipse fill="#fff" cx="181.8" cy="108.8" rx="6.2" ry="5.8"/>\n  <ellipse class="color1" cx="181.8" cy="108.8" rx="4.5" ry="4.1"/>\n  <path fill="#D8BC94" d="M152.3 101.3l-9.3 15.9"/>\n  <path fill="#DF9600" d="M143 118.2l-.5-.1c-.5-.3-.6-.9-.4-1.4l9.3-15.9c.3-.5.9-.6 1.4-.4.5.3.6.9.4 1.4l-9.3 15.9c-.3.3-.6.5-.9.5z"/>', 8, 2, 7, 1),
('Eyelashes', '<path fill="#00416F" d="M143.8,106.9c-0.1,0-0.1,0-0.2,0c-2.5-0.9-2.7-3.5-2.7-3.6c0-0.3,0.2-0.5,0.5-0.5\n      c0.3,0,0.5,0.2,0.5,0.5c0,0.1,0.2,2.1,2.1,2.7c0.3,0.1,0.4,0.4,0.3,0.6C144.2,106.8,144,106.9,143.8,106.9z"/>\n    <path fill="#00416F" d="M186.2,106.9c0.1,0,0.1,0,0.2,0c2.5-0.9,2.7-3.5,2.7-3.6c0-0.3-0.2-0.5-0.5-0.5\n      c-0.3,0-0.5,0.2-0.5,0.5c0,0.1-0.2,2.1-2.1,2.7c-0.3,0.1-0.4,0.4-0.3,0.6C185.8,106.8,186,106.9,186.2,106.9z"/>\n    <ellipse fill="#fff" cx="146.6" cy="109.8" rx="4.7" ry="5.8"/>\n    <ellipse class="color1" cx="146.6" cy="109.8" rx="3.3" ry="4"/>\n    <ellipse fill="#fff" cx="183.4" cy="109.8" rx="4.7" ry="5.8"/>\n    <ellipse class="color1" cx="183.4" cy="109.8" rx="3.3" ry="4"/>', 8, 2, 7, 1),
('Serious', '<circle class="color1" cx="147.3" cy="106.7" r="3.5"/>\n    <circle class="color1" cx="182.7" cy="106.7" r="3.5"/>\n    <path fill="#603936" d="M153.9,105.6c-2-2.9-6.1-3.7-9.4-2.7c-2.1-0.4-2.9-1.1-2.9-1.1c-0.4-0.4-1-0.4-1.4,0\n      c-0.4,0.4-0.4,1,0,1.4c0.1,0.1,0.6,0.6,1.8,1.1c-0.5,0.4-0.9,0.8-1.3,1.3c-0.8,1,1,2,1.7,1c2.1-2.8,7.8-2.8,9.8,0.1\n      C152.9,107.7,154.6,106.7,153.9,105.6z"/>\n    <path fill="#603936" d="M176.1,105.6c2-2.9,6.1-3.7,9.4-2.7c2.1-0.4,2.9-1.1,2.9-1.1c0.4-0.4,1-0.4,1.4,0\n      c0.4,0.4,0.4,1,0,1.4c-0.1,0.1-0.6,0.6-1.8,1.1c0.5,0.4,0.9,0.8,1.3,1.3c0.8,1-1,2-1.7,1c-2.1-2.8-7.8-2.8-9.8,0.1\n      C177.1,107.7,175.4,106.7,176.1,105.6z"/>', 8, 2, 7, 1),
('Round Lids', '<circle fill="#fff" cx="146.3" cy="110.7" r="5.5"/>\n  <circle class="color1" cx="146.3" cy="110.7" r="3.7"/>\n  <circle fill="#fff" cx="183.7" cy="110.7" r="5.5"/>\n  <circle class="color1" cx="183.7" cy="110.7" r="3.7"/>\n  <path fill="#545151" d="M153.3 108.8a7 7 0 00-2.3-4c-2-1.7-5.6-2-8.1-.7l-.2.1s-4.5 2.8-6.1 3.6l.5.4 1.2.9c.4.3.7.6 1.2.7h.1c.2 0 .4-.1.5-.4.5-2 1.8-3.7 3.3-4.5 2.2-1.1 5.4-.8 7.1.6 1 .9 1.6 1.9 2 3.5.1.3.3.4.6.4l.2-.6zM176.7 108.8a7 7 0 012.3-4 7.8 7.8 0 018.3-.6s4.5 2.8 6.1 3.6l-.5.4-1.2.9c-.4.3-.7.6-1.2.7h-.1c-.2 0-.4-.1-.5-.4-.5-2-1.8-3.7-3.3-4.5-2.2-1.1-5.4-.8-7.1.6-1 .9-1.6 1.9-2 3.5-.1.3-.3.4-.6.4l-.2-.6z"/>', 8, 2, 7, 1),
('Angry', '<path fill="#fff" d="M145.7 113.4c4 1 8.1-1.4 9.1-5.4l-14.5-3.7c-1 4 1.4 8.1 5.4 9.1z"/>\n  <path class="color1" d="M146.3 110.7c2.5.6 5.1-.9 5.7-3.4l-9.1-2.3c-.6 2.5.9 5.1 3.4 5.7z"/>\n  <path fill="#fff" d="M183.7 113.4c-4 1-8.1-1.4-9.1-5.4l14.5-3.7c1 4-1.4 8.1-5.4 9.1z"/>\n  <path class="color1" d="M183.1 110.7c-2.5.6-5.1-.9-5.7-3.4l9.1-2.3c.7 2.5-.9 5-3.4 5.7z"/>\n  <path fill="#4C6B00" d="M146 115.8c-9-.9-8.7-9.7-8.7-9.8 0-.3.2-.5.5-.5s.5.2.5.5-.2 7.9 7.8 8.8c.3 0 .5.3.4.5 0 .3-.2.5-.5.5zM184 115.8c9-.9 8.7-9.7 8.7-9.8 0-.3-.2-.5-.5-.5s-.5.2-.5.5.2 7.9-7.8 8.8c-.3 0-.5.3-.4.5 0 .3.2.5.5.5z"/>', 8, 2, 7, 1);


INSERT INTO avatar_image_type_colors (avatar_image_type_id, display_seq, css_color) VALUES
(1, 01, '#866551'),
(1, 02, '#9e755c'),
(1, 03, '#d39861'),
(1, 04, '#eac08a'),
(1, 05, '#d9bd9a'),
(1, 06, '#fad6b3'),
(1, 07, '#fae0b7'),
(1, 08, '#efc5bc'),
(1, 09, '#fdeadf'),
(1, 10, '#fef1d9'),
(1, 11, '#eef2d4'),
(1, 12, '#c2c4c3'),
(1, 13, '#75a4c1'),
(1, 14, '#b3cc95'),
(1, 15, '#91ab72'),
(1, 16, '#dd896c'),
(1, 17, '#ebb463'),
(2, 00, 'transparent'),
(2, 01, '#EDEDED'),
(2, 02, '#CFD0D0'),
(2, 03, '#B3AFA5'),
(2, 04, '#8C95A2'),
(2, 05, '#63646F'),
(2, 06, '#545151'),
(2, 07, '#42524F'),
(2, 08, '#403D3C'),
(2, 09, '#A5772E'),
(2, 10, '#845600'),
(2, 11, '#794650'),
(2, 12, '#857EBA'),
(2, 13, '#8E6DAE'),
(2, 14, '#B75F9B'),
(2, 15, '#C65A92'),
(2, 16, '#A23B91'),
(2, 17, '#A670AD'),
(2, 18, '#5E6295'),
(2, 19, '#3650A1'),
(2, 20, '#003365'),
(2, 21, '#3273AE'),
(2, 22, '#3E7EBB'),
(2, 23, '#2796D4'),
(2, 24, '#0099D4'),
(2, 25, '#5A85B9'),
(2, 26, '#76A4C2'),
(2, 27, '#5CA3CC'),
(2, 28, '#83CEE9'),
(2, 29, '#59BAD8'),
(2, 30, '#5FC1C7'),
(2, 31, '#00A8AF'),
(2, 32, '#41B9B2'),
(2, 33, '#45AC68'),
(2, 34, '#00773A'),
(2, 35, '#71AF3A'),
(2, 36, '#829033'),
(2, 37, '#A7C000'),
(2, 38, '#B4C834'),
(2, 39, '#C1D05C'),
(2, 40, '#FFE500'),
(2, 41, '#FBC400'),
(2, 42, '#F8B300'),
(2, 43, '#F5A33D'),
(2, 44, '#F2902C'),
(2, 45, '#F18C47'),
(2, 46, '#EC694B'),
(2, 47, '#DB534E'),
(2, 48, '#EA5461'),
(2, 49, '#DD4973'),
(2, 50, '#E7355B'),
(2, 51, '#D8152A'),
(2, 52, '#E73349'),
(2, 53, '#972946'),
(2, 54, '#F198A8'),
(2, 55, '#EE84A1'),
(3, 01, '#EDEDED'),
(3, 02, '#CFD0D0'),
(3, 03, '#969797'),
(3, 04, '#898989'),
(3, 05, '#878690'),
(3, 06, '#545151'),
(3, 07, '#5F5D56'),
(3, 08, '#403D3C'),
(3, 09, '#DC4760'),
(3, 10, '#EA5461'),
(3, 11, '#EA5752'),
(3, 12, '#DF5546'),
(3, 13, '#EC6D72'),
(3, 14, '#EE869A'),
(3, 15, '#EF8585'),
(3, 16, '#EFADA3'),
(3, 17, '#F8B300'),
(3, 18, '#FBC400'),
(3, 19, '#FCCC27'),
(3, 20, '#FDD557'),
(3, 21, '#CCAD69'),
(3, 22, '#966A07'),
(3, 23, '#B8D200'),
(3, 24, '#B8D669'),
(3, 25, '#A8C75A'),
(3, 26, '#77B55A'),
(3, 27, '#40AF36'),
(3, 28, '#5F8B4A'),
(3, 29, '#59797C'),
(3, 30, '#00A8AF'),
(3, 31, '#00B6CC'),
(3, 32, '#68C5D8'),
(3, 33, '#98D4E0'),
(3, 34, '#83CEE9'),
(3, 35, '#5C94B6'),
(3, 36, '#007EAC'),
(3, 37, '#006CA4'),
(3, 38, '#007DBF'),
(3, 39, '#006D9E'),
(3, 40, '#3B6A9B'),
(3, 41, '#547EAF'),
(3, 42, '#6E97BC'),
(3, 43, '#6C84BC'),
(3, 44, '#5E6295'),
(3, 45, '#ABB4D8'),
(3, 46, '#BB84B4'),
(3, 47, '#895B77'),
(3, 48, '#A25195'),
(4, 01, '#EDEDED'),
(4, 02, '#B5B5B6'),
(4, 03, '#B9B0AA'),
(4, 04, '#7F7C73'),
(4, 05, '#6A6969'),
(4, 06, '#665F59'),
(4, 07, '#5F5853'),
(4, 08, '#545151'),
(4, 09, '#403D3C'),
(4, 10, '#4E5059'),
(4, 11, '#834E00'),
(4, 12, '#9E755C'),
(4, 13, '#815499'),
(4, 14, '#BD569C'),
(4, 15, '#93283C'),
(4, 16, '#C5497C'),
(4, 17, '#EF8C91'),
(4, 18, '#CD7474'),
(4, 19, '#DB534E'),
(4, 20, '#F39800'),
(4, 21, '#FBC400'),
(4, 22, '#DAAF47'),
(4, 23, '#FFE500'),
(4, 24, '#C5D945'),
(4, 25, '#C1AF8C'),
(4, 26, '#45AC68'),
(4, 27, '#40AF36'),
(4, 28, '#42524F'),
(4, 29, '#81846D'),
(4, 30, '#5A6978'),
(4, 31, '#00B3BA'),
(4, 32, '#00558A'),
(4, 33, '#006CA4'),
(4, 34, '#808B9A'),
(4, 35, '#5177BB'),
(4, 36, '#539CD0'),
(4, 37, '#8BCBE2'),
(6, 01, '#403D3C'),
(6, 02, '#59413C'),
(6, 03, '#5D4C39'),
(6, 04, '#725341'),
(6, 05, '#7C5C48'),
(6, 06, '#A5772E'),
(6, 07, '#C59D55'),
(6, 08, '#ECBA60'),
(6, 09, '#FFE789'),
(6, 10, '#F8B300'),
(6, 11, '#924423'),
(6, 12, '#B4421A'),
(6, 13, '#B14631'),
(6, 14, '#89505B'),
(7, 1, '#ffffff'),
(7, 1, '#ffe3cc'),
(7, 2, '#ffd9bb'),
(7, 3, '#fad6a8'),
(7, 4, '#dab68a'),
(7, 5, '#af8b6b'),
(8, 01, '#545050'),
(8, 02, '#a57c62'),
(8, 03, '#53302d'),
(8, 04, '#0e77a0'),
(8, 05, '#14501b'),
(9, 01, '#403D3C'),
(9, 02, '#59413C'),
(9, 03, '#5D4C39'),
(9, 04, '#725341'),
(9, 05, '#7C5C48'),
(9, 06, '#A5772E'),
(9, 07, '#C59D55'),
(9, 08, '#ECBA60'),
(9, 09, '#FFE789'),
(9, 10, '#F8B300'),
(9, 11, '#924423'),
(9, 12, '#B4421A'),
(9, 13, '#B14631'),
(9, 14, '#89505B'),
(10, 01, '#403D3C'),
(10, 02, '#59413C'),
(10, 03, '#5D4C39'),
(10, 04, '#725341'),
(10, 05, '#7C5C48'),
(10, 06, '#A5772E'),
(10, 07, '#C59D55'),
(10, 08, '#ECBA60'),
(10, 09, '#FFE789'),
(10, 10, '#F8B300'),
(10, 11, '#924423'),
(10, 12, '#B4421A'),
(10, 13, '#B14631'),
(10, 14, '#89505B');