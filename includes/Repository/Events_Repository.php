<?php
defined('ABSPATH') || exit;

final class SE_Events_Repository {

    public static function events_table() {
        global $wpdb;
        return SE_EVENTS_TABLE;
    }

    public static function meta_table() {
        global $wpdb;
        return $wpdb->prefix . 'se_meta';
    }

    public static function get_event($event_id) {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM " . self::events_table() . " WHERE event_id = %d",
                $event_id
            ),
            ARRAY_A
        );
    }

    public static function get_notes($event_id) {
        global $wpdb;
        return (string) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT meta_value FROM " . self::meta_table() . " WHERE object_id = %d AND meta_key = %s",
                $event_id,
                'event_notes'
            )
        );
    }

    public static function save_event($event_id, $title) {
        global $wpdb;
        $wpdb->update(
            self::events_table(),
            ['event_name' => $title],
            ['event_id' => $event_id],
            ['%s'],
            ['%d']
        );
    }

    public static function save_notes($event_id, $notes) {
        global $wpdb;
        $wpdb->replace(
            self::meta_table(),
            [
                'object_id'  => $event_id,
                'meta_key'   => 'event_notes',
                'meta_value' => $notes,
            ],
            ['%d', '%s', '%s']
        );
    }

    public static function list_events() {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT event_id, event_name FROM " . self::events_table() . " ORDER BY event_id DESC",
            ARRAY_A
        );
    }
/*
    public function create_event($data) {

        $post_id = wp_insert_post([
            'post_title'   => $data['title'],
            'post_type'    => 'se_event',
            'post_status'  => 'publish'
        ]);

        if (!$post_id) return false;

        // Meta speichern
        update_post_meta($post_id, 'date', $data['date']);
        update_post_meta($post_id, 'location', $data['location']);

        return $post_id;
    }
*/        
}
