<?php

if (!defined('ABSPATH')) exit;


class SE_Recurrence_UI_Plugin {
    public $rpe_post_id;
    public function __construct($rpe_post_id) {
        $this->rpe_post_id = $rpe_post_id;
        
    }

   
    public function assets() {
        global $wpdb;
        $post_id = isset($_GET['post_id'])
            ? intval($_GET['post_id'])
            : $this->rpe_post_id;
        // =====================================
        // 🔹 Event ID
        // =====================================
        $event_id = $wpdb->get_var(
            $wpdb->prepare(
                "
                SELECT event_id
                FROM {$wpdb->prefix}se_events
                WHERE post_id = %d
                ",
                $post_id
            )
        );
      
      
        // =====================================
        // 🔹 Event laden
        // =====================================
        $event = $wpdb->get_row(
            $wpdb->prepare(
                "
                SELECT *
                FROM {$wpdb->prefix}se_events
                WHERE event_id = %d
                ",
                $event_id
            )
        );
        // =====================================
        // 🔹 RRULE laden
        // =====================================
        $rrule_row = $wpdb->get_row(
            $wpdb->prepare(
                "
                SELECT *
                FROM {$wpdb->prefix}se_event_recurrences
                WHERE event_id = %d
                ",
                $event_id
            )
        );
        $rrule = [];
        if ($rrule_row && !empty($rrule_row->rrule)) {
            $rrule = json_decode(
                $rrule_row->rrule,
                true
            );
        }

        $exclude_ranges = [];

        if (!empty($event->exclude_ranges)) {

            $exclude_ranges = json_decode(
                $event->exclude_ranges,
                true
            );

        }
        // =====================================
        // 🔹 Localize
        // =====================================
        wp_localize_script('se-rec-ui-js', 'SE_REC_UI',
            [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'adminurl' => admin_url(),
                'nonce' => wp_create_nonce('se_recurrence_nonce'),
                'event_id' => intval($event_id),
                'post_id' => intval($post_id),
                // 🔥 Event Daten
                'start_date' => $event->event_start_date ?? '',
                'end_date' => $event->event_end_date ?? '',
                'start_time' => $event->event_start_time ?? '',
                'end_time' => $event->event_end_time ?? '',
                // 🔥 RRULE
                'interval' => $rrule['interval'] ?? 1,
                'recurrence_type' => $event->recurrence_freq, // nicht von $rrule!!
                'days' => $event->recurrence_byday, // nicht von $rrule!!
                'exclude_ranges' => $exclude_ranges
            ]
        );
    }
    public function render() {
        $post_id = isset($_GET['post_id'])
            ? intval($_GET['post_id'])
            : $this->rpe_post_id;
        $ui = new SE_RPE_Manager_UI($post_id);
        echo '<div class="wrap">';
       // echo '<h1>SE Event Recurrence UI</h1>';
        $ui->render_ui();
        echo '</div>';
        $this->sync_event_datetime_fields_by_post_id($post_id);
    }
   
    public function sync_event_datetime_fields_by_post_id($post_id) {

        global $wpdb;
        $table = SE_EVENTS_TABLE;
        // Event laden
        $event = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT *
                FROM $table
                WHERE post_id = %d",
                $post_id
            )
        );
        if(!$event){
            return false;
        }
        // Nur syncen wenn Felder leer sind
        $needs_sync = false;
        if(
            $event->event_start_date === '0000-00-00' ||
            $event->event_end_date === '0000-00-00' ||
            $event->event_start_time === '00:00:00' ||
            $event->event_end_time === '00:00:00'
        ){
            $needs_sync = true;
        }
        if(!$needs_sync){
            return 'already_synced';
        }
        // event_start zerlegen
        $start = strtotime($event->event_start);
        $event_start_date = date(
            'Y-m-d',
            $start
        );
        $event_start_time = date(
            'H:i:s',
            $start
        );
        // event_end zerlegen
        $end = strtotime($event->event_end);
        $event_end_date = date(
            'Y-m-d',
            $end
        );
        $event_end_time = date(
            'H:i:s',
            $end
        );
        // Update
        $updated = $wpdb->update(
            $table,
            [
                'event_start_date' => $event_start_date,
                'event_end_date' => $event_end_date,
                'event_start_time' => $event_start_time,
                'event_end_time' => $event_end_time
            ],
            [
                'post_id' => $post_id
            ]
        );
        return $updated;
    }

}


