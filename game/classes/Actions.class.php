<?php
/**
 * Gives meaningful names to constants used for the player_action_types IDs from the DB table of this name.
 */
abstract class Actions
{
    const LOGIN = 1;
    const LOGOUT = 2;
    const MOVE_WITH_ENERGY = 3;
    const MOVE_NOT_NEED_PROFESSION = 4;
    const MOVE_WITH_PROFESSION = 5;
    const MOVE_WITHOUT_PROFESSION = 6;
    const MOVE = 7; // This one is deprecated in v3
    const MOVE_WITHOUT_ENERGY_OR_SKILL = 8;
    const MOVE_PORTAL = 9; // Source
    const TELEPORT = 10; // Destination
    const TELEPORT_OFFERED_BY_NPC = 11;
    const MOVE_EXTERNAL_LINK = 12;
    const RANKING_LIST_CHECK = 13;
    const RANKING_LIST_CHECK_FOR_TALKATIVE_HERO = 14;
    const RANKING_LIST_CHECK_FOR_RICHEST = 15;
    const RANKING_LIST_CHECK_FOR_HIGHEST_LEVEL = 16;
    const RANKING_LIST_CHECK_FOR_TRAVELERS = 17;
    const RANKING_LIST_CHECK_FOR_TREASURE_HUNTER = 18;
    const RANKING_LIST_CHECK_FOR_EXPERIENCED_HERO = 19;
    const RANKING_LIST_CHECK_FOR_EXPLORERS = 20;
    const RANKING_LIST_CHECK_FOR_HELPFUL_HERO = 21;
    const RANKING_LIST_CHECK_FOR_FREQUENT_LOGIN_HERO = 22;
    const TREASURE_FOUND_WITHOUT_TOOL = 23;
    const TREASURE_FOUND_WITH_TOOL = 24;
    const TREASURE_FOUND_ITEM_WITHOUT_TOOL = 25;
    const CREATE_PLAYER = 26;
    const SELECT_PLAYER = 27;
}