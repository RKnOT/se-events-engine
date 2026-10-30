<?php

defined('ABSPATH') || exit;


class SE_Events_Database {
    public function __construct() {
        global $wpdb;
        if (!defined('SE_EVENTS_TABLE')) {
            define('SE_EVENTS_TABLE', $wpdb->prefix . 'se_events');
        }
        if (!defined('SE_LOCATION_TABLE')) {
            define('SE_LOCATION_TABLE', $wpdb->prefix . 'se_locations');
        }
        if (!defined('SE_EVENT_REURRENCES_TABLE')) {
            define('SE_EVENT_REURRENCES_TABLE', $wpdb->prefix . 'se_event_recurrences');
        }
        if (!defined('SE_EVENT_META')) {
            define('SE_EVENT_META', $wpdb->prefix . 'se_meta');
        }

    }
    /**
     * Tabellen des Plugins
     */
    private static function get_tables() {
        global $wpdb;
        return [
            SE_EVENTS_TABLE => self::sql_events(),
            SE_EVENT_REURRENCES_TABLE => self::sql_event_recurrences(),
            SE_LOCATION_TABLE  => self::sql_locations(),
            SE_EVENT_META => self::sql_meta(),
        ];
    }


    /**
     * Alle Tabellen prüfen und fehlende Tabellen erzeugen
     */
    public static function check_tables() {
        global $wpdb;
        $tables = self::get_tables();
        $result = [];
        foreach ($tables as $table_name => $sql) {
            $exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SHOW TABLES LIKE %s",
                    $table_name
                )
            );
            if ($exists === $table_name) {
                $result[$table_name] = [
                    'status' => 'exists'
                ];
                continue;
            }
            /*
             * Tabelle existiert nicht
             */
            $created = $wpdb->query($sql);
            if ($created === false) {
                $result[$table_name] = [
                    'status' => 'error',
                    'error'  => $wpdb->last_error
                ];

            } else {
                $result[$table_name] = [
                    'status' => 'created'
                ];
            }
        }

        return $result;
    }


    /**
     * Prüfen, ob eine einzelne Tabelle existiert
     */
    public static function table_exists($table_name) {

        global $wpdb;

        $table_name = $wpdb->prefix . $table_name;

        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $table_name
            )
        );

        return ($exists === $table_name);
    }


    /**
     * Events
     */
    private static function sql_events() {

        global $wpdb;

        $table = $wpdb->prefix . 'se_events';

        return "
            CREATE TABLE `$table` (
                `event_id` bigint unsigned NOT NULL AUTO_INCREMENT,
                `post_id` bigint unsigned DEFAULT NULL,
                `event_parent` bigint unsigned DEFAULT NULL,
                `event_slug` varchar(200) DEFAULT NULL,
                `event_owner` bigint unsigned DEFAULT NULL,
                `event_status` tinyint(1) DEFAULT NULL,
                `event_name` mediumtext DEFAULT NULL,

                `event_start_date` date DEFAULT NULL,
                `event_end_date` date DEFAULT NULL,
                `event_start_time` time DEFAULT NULL,
                `event_end_time` time DEFAULT NULL,

                `event_all_day` tinyint unsigned DEFAULT NULL,

                `event_start` datetime DEFAULT NULL,
                `event_end` datetime DEFAULT NULL,

                `event_timezone` text DEFAULT NULL,
                `post_content` longtext DEFAULT NULL,

                `event_rsvp` tinyint unsigned NOT NULL DEFAULT 0,
                `event_rsvp_date` date DEFAULT NULL,
                `event_rsvp_time` time DEFAULT NULL,
                `event_rsvp_spaces` int DEFAULT NULL,

                `event_spaces` int DEFAULT 0,
                `event_private` tinyint unsigned NOT NULL DEFAULT 0,

                `location_id` bigint unsigned DEFAULT NULL,
                `recurrence_id` bigint unsigned DEFAULT NULL,

                `event_date_created` datetime DEFAULT NULL,
                `event_date_modified` datetime DEFAULT NULL,

                `recurrence` tinyint unsigned NOT NULL DEFAULT 0,
                `recurrence_interval` int DEFAULT NULL,
                `recurrence_freq` text DEFAULT NULL,
                `recurrence_byday` text DEFAULT NULL,
                `recurrence_byweekno` int DEFAULT NULL,
                `recurrence_days` int DEFAULT NULL,
                `recurrence_rsvp_days` int DEFAULT NULL,

                `blog_id` bigint unsigned DEFAULT NULL,
                `group_id` bigint unsigned DEFAULT NULL,

                `event_language` varchar(14) DEFAULT NULL,
                `event_translation` tinyint unsigned NOT NULL DEFAULT 0,

                `event_location_type` varchar(15) DEFAULT NULL,
                `event_active_status` tinyint DEFAULT 1,
                `event_type` varchar(20) DEFAULT 'event',

                `recurrence_set_id` bigint unsigned DEFAULT NULL,
                `exclude_ranges` longtext DEFAULT NULL,

                PRIMARY KEY (`event_id`),

                KEY `post_id` (`post_id`),
                KEY `event_status` (`event_status`),
                KEY `event_start_date` (`event_start_date`),
                KEY `event_end_date` (`event_end_date`),
                KEY `event_start` (`event_start`),
                KEY `event_end` (`event_end`),
                KEY `location_id` (`location_id`),
                KEY `blog_id` (`blog_id`),
                KEY `group_id` (`group_id`),
                KEY `event_active_status` (`event_active_status`),
                KEY `event_type` (`event_type`)
            )
            ENGINE=InnoDB
            DEFAULT CHARSET=utf8mb4
            COLLATE=utf8mb4_unicode_ci
        ";
    }


    /**
     * Event-Recurrences
     */
    private static function sql_event_recurrences() {

        global $wpdb;

        $table = $wpdb->prefix . 'se_event_recurrences';

        return "
            CREATE TABLE `$table` (
                `recurrence_set_id` bigint unsigned NOT NULL AUTO_INCREMENT,

                `event_id` bigint unsigned NOT NULL,
                `recurrence_order` int unsigned DEFAULT NULL,

                `recurrence_type`
                    enum('include','exclude')
                    DEFAULT 'include',

                `recurrence_interval` int DEFAULT NULL,
                `recurrence_freq` tinytext DEFAULT NULL,
                `recurrence_byday` tinytext DEFAULT NULL,
                `recurrence_byweekno` int DEFAULT NULL,

                `recurrence_duration` int DEFAULT NULL,
                `recurrence_dates` mediumtext DEFAULT NULL,

                `recurrence_start_date` date DEFAULT NULL,
                `recurrence_end_date` date DEFAULT NULL,
                `recurrence_end_after` int DEFAULT NULL,

                `recurrence_start_time` time DEFAULT NULL,
                `recurrence_end_time` time DEFAULT NULL,

                `recurrence_all_day` tinyint unsigned DEFAULT NULL,
                `recurrence_timezone` tinytext DEFAULT NULL,

                `recurrence_status` tinyint(1) DEFAULT NULL,

                PRIMARY KEY (`recurrence_set_id`),

                KEY `event_id` (`event_id`)
            )
            ENGINE=InnoDB
            DEFAULT CHARSET=utf8mb4
            COLLATE=utf8mb4_unicode_ci
        ";
    }


    /**
     * Locations
     */
    private static function sql_locations() {

        global $wpdb;

        $table = $wpdb->prefix . 'se_locations';

        return "
            CREATE TABLE `$table` (
                `location_id` bigint unsigned NOT NULL AUTO_INCREMENT,

                `post_id` bigint unsigned NOT NULL,
                `blog_id` bigint unsigned DEFAULT NULL,

                `location_parent` bigint unsigned DEFAULT NULL,
                `location_slug` varchar(200) DEFAULT NULL,
                `location_name` mediumtext DEFAULT NULL,

                `location_owner` bigint unsigned NOT NULL DEFAULT 0,

                `location_address` varchar(200) DEFAULT NULL,
                `location_town` varchar(200) DEFAULT NULL,
                `location_state` varchar(200) DEFAULT NULL,
                `location_postcode` varchar(10) DEFAULT NULL,
                `location_region` varchar(200) DEFAULT NULL,
                `location_country` char(2) DEFAULT NULL,

                `location_latitude` decimal(9,6) DEFAULT NULL,
                `location_longitude` decimal(9,6) DEFAULT NULL,

                `post_content` longtext DEFAULT NULL,

                `location_status` int DEFAULT NULL,
                `location_private` tinyint unsigned NOT NULL DEFAULT 0,

                `location_language` varchar(14) DEFAULT NULL,
                `location_translation` tinyint unsigned NOT NULL DEFAULT 0,

                PRIMARY KEY (`location_id`),

                KEY `post_id` (`post_id`),
                KEY `blog_id` (`blog_id`),
                KEY `location_state` (`location_state`),
                KEY `location_region` (`location_region`),
                KEY `location_country` (`location_country`)
            )
            ENGINE=InnoDB
            DEFAULT CHARSET=utf8mb4
            COLLATE=utf8mb4_unicode_ci
        ";
    }


    /**
     * Meta
     */
    private static function sql_meta() {

        global $wpdb;

        $table = $wpdb->prefix . 'se_meta';

        return "
            CREATE TABLE `$table` (
                `meta_id` bigint unsigned NOT NULL AUTO_INCREMENT,

                `object_id` bigint unsigned NOT NULL,
                `meta_key` varchar(255) DEFAULT NULL,
                `meta_value` longtext DEFAULT NULL,

                `meta_date`
                    timestamp NOT NULL
                    DEFAULT CURRENT_TIMESTAMP,

                PRIMARY KEY (`meta_id`),

                KEY `object_id` (`object_id`),
                KEY `meta_key` (`meta_key`)
            )
            ENGINE=InnoDB
            DEFAULT CHARSET=utf8mb4
            COLLATE=utf8mb4_unicode_ci
        ";
    }
}