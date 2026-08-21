<?php

if (!defined('ABSPATH')) exit;

class SE_get_RPE_stat_event_post {

    protected $event;
    protected $post_id;

    public function __construct($post_id) {

        global $wpdb;

        $this->post_id = intval($post_id);

        $this->event = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}se_events WHERE post_id=%d",
                $this->post_id
            )
        );
    }

    public function get_event() {

        return $this->event;
    }

   



}
