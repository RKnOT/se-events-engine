<?php

class SE_Event_Repository_js {

    private static string $table;

    public static function init() {
        global $wpdb;
        self::$table = SE_EVENTS_TABLE;
    }

    public static function count_not_se_event(): int {
        global $wpdb;

        return (int) $wpdb->get_var("
            SELECT COUNT(*)
            FROM " . self::$table . "
            WHERE event_type <> 'se_event'
               OR event_type IS NULL
        ");
    }

    public static function get_preview(int $limit = 10): array {
        global $wpdb;

        return $wpdb->get_results("
            SELECT event_id, event_type
            FROM " . self::$table . "
            WHERE event_type <> 'se_event'
               OR event_type IS NULL
            LIMIT $limit
        ");
    }

    public static function update_event_type(): int {
        global $wpdb;

        return (int) $wpdb->query("
            UPDATE " . self::$table . "
            SET event_type = 'se_event'
            WHERE event_type <> 'se_event'
               OR event_type IS NULL
        ");
    }
}