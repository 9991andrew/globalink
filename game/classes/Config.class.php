<?php
/**
 * Global settings available to all of MEGA World.
 */
abstract class Config
{
    /**
     * Database Settings
     * For security reasons database configuration is now located in .megadb.ini outside of the public folder
     * This requires a MySQL/MariaDB server.
     */
     // Set the path for the database config file if it is not in the default path.
     // Default: "../.megadb.ini"
    const DATABASE_CONFIG_FILE = "../.megadb.ini";
    // This configuration is used on megaworld.game-server.ca because it's not currently in the root of the megaworld folder.
    // const DATABASE_CONFIG_FILE = "/var/www/.megadb.ini";

    /**
     * Files
     * If you have moved resources from the default path, you can specify where they are located here.
     */
    // The root path for images. This must end with a forward slash (/)
    // Default: "images/"
    const IMAGE_PATH = "images/";

    /**
     * Gameplay
     * These settings change the way the game works for end users
     */
    // How many seconds a player is visible to others after moving.
    // Default: 120
    const VISIBLE_FOR_SECONDS = 120;

    // Movement points on player creation
    // Default: 2000
    const INITIAL_MOVEMENT_POINTS = 2000;

    // How many tiles away another player can be and still chat with players on the same map.
    // Default: 3
    // Assuming a typical 10x10 map,
    // 10 allows all players to talk to each other
    // 5 means that a player in the middle could talk to anyone else
    // 0 means players have to be on the same tile
    const MAX_CHAT_RANGE = 4;

    // How frequently in seconds does the client request updates to game state
    // Default: 2
    // Increase this value to reduce load on the server, decrease when playing locally.
    const GAME_UPDATE_INTERVAL_SECONDS = 2;

    // In math quests, how precise are the answers required to be?
    // Answers will be rounded to this many decimal places.
    // Default: 2
    const PRECISION_DIGITS = 2;

    /**
     * Localization
     */
    // The application's default time zone. Client-side timestamps will be shown based on browser configuration.
    // WARNING: Client times may display incorrectly if this is not UTC.
    // Default "UTC"
    const TIME_ZONE = "UTC";

    // Language that will be the default for options and generating new users
    // Default "en_CA" (Canadian English)
    const DEFAULT_LOCALE = "en_CA";

}