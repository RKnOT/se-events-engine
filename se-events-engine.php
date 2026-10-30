<?php
/**
 * Plugin Name: SE Events Engine from RKnOT
 * Description: SE Event Manager developed by RKnOT
 * Version: 3.4.0
 * Author: RKnOT
 * Copyright(C) 2026, RKnOT 
 * 
 * version 3.4,0    -> event ki feld mit edit hinzugefügt und db struktur erzeugen, wenn nicht vorhanden
 * version 3.2.9    -> 2026_10_28 Änderung bei Umstellung auf PHP 8.5.12 in se_event_add.php
 * version 3.2.8    -> 2026_08_20 Vereinheitlichung der globalen Variablen und Vereinfachungen
 * version 3.2.7    -> 2026_08_19 Vereinheitlichung der globalen Variablen
 * version 3.2.6    -> 2026_08_18 Vereinheitlicht  $wpdb->prefix . 'se_events' in SE_EVENTS_TABLE
 * version 3.2.5    -> 2026_08_17 Sortierfunktion von Angeboten im Child Themes verbessert 
 * version 3.2.4    -> 2026_08_12 ui wird verwendet für neue events und deren post per ID 
 * version 3.2.3    -> 2026_08_08 in fe copy kategorien 
 * version 3.2.2    -> 2026_08_06 FE / BE  Suchfilter
 * version 3.2.1    -> 2026_08_03
 * version 3.2      -> 2026_08_01
 * version 3.1      -> 2026_07_31
 */

defined('ABSPATH') || exit;
define('SE_EVENTS_PATH', plugin_dir_path(__FILE__));
define('SE_EVENTS_URL', plugin_dir_url(__FILE__));

global $wpdb;
define('SE_EVENTS_TABLE', $wpdb->prefix . 'se_events');
define('SE_LOCATION_TABLE', $wpdb->prefix . 'se_locations');
define('SE_EVENT_REURRENCES_TABLE', $wpdb->prefix . 'se_event_recurrences');

if (!defined('POSTS_TABLE')) {
    define('POSTS_TABLE', $wpdb->prefix . 'posts');
}
if (!defined('USERS_TABLE')) {
    define('USERS_TABLE', $wpdb->prefix . 'users');
}
// check if db exsists
require_once SE_EVENTS_PATH . 'includes/SE-Events-Setup/Check-se-event-db.php';
$check_db = new SE_Events_Database();
$db_array = $check_db->check_tables();


require_once SE_EVENTS_PATH . 'includes/Core/Engine.php';
require_once SE_EVENTS_PATH . 'debug/stats.php';
if(!$db_array) {
    wp_die('die se db konnte nicht erstellt werden');
}

add_action('plugins_loaded', ['SE_Events_Engine', 'run']);

//require_once SE_EVENTS_PATH . 'includes/Core/debug/Admin_tool.php';
