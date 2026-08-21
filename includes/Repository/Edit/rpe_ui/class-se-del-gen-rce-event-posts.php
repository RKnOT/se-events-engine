<?php

if (!defined('ABSPATH')) exit;

class SE_Del_Gen_RCE_Event_Posts {
    /**
     * =========================
     * GET ALL CURRENT RCE
     * =========================
     */
    public static function get_all_rce_instances($parent_event_id, $stat='--') {
        
        global $wpdb;
        $table = SE_EVENTS_TABLE;
         // den RPE holen
        $event_rpe = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT event_id, post_id, event_start_date, event_end_date, event_start_time, event_end_time
                 FROM $table
                 WHERE event_id = %d",
                $parent_event_id
            )
        );
        $event_id =  $event_rpe[0]->event_id;
        $post_id = $event_rpe[0]->post_id ?? 0;
        
        // Alle RCE holen
        $events = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT event_id, post_id, event_start_date, event_end_date
                 FROM $table
                 WHERE recurrence_id = %d",
                $parent_event_id
            )
        );
       if (empty($events)) {
            return;
        }
      $url = "admin.php?page=se-events&event_action=edit&event_id=". $event_id . "&updated=1";
        wp_send_json_success([
            'message' => 'get_rpe_instances',
            'status' => $stat,
            'generated' => [
                'rpe' => $event_rpe,
                'rce' => $events,
                'url' => $url
            ],
            'rpe_post_id' => $post_id,
            'rpe events' =>  $event_rpe,
            'message' => 'get_rce_instances',
            'parent_event_id' => $parent_event_id,
            'rce events' =>  $events,  
        ]);
       
        return $events;
    }
    public static function get_events($parent_event_id) {

        global $wpdb;

        $table = SE_EVENTS_TABLE;

        // Alle RCE holen
        $events = $wpdb->get_results(

            $wpdb->prepare(
                "SELECT event_id, post_id, event_start_date, event_end_date
                 FROM $table
                 WHERE recurrence_id = %d",
                $parent_event_id
            )
        );
        return $events;


    }
    public static function regenerate_series(
        $parent_event_id,
        $stat = '--',
        $event_post_data = []
    ) {

        global $wpdb;
        $table = SE_EVENTS_TABLE;
        $parent = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT *
                FROM $table
                WHERE event_id = %d",
                $parent_event_id
            )
        );
        if(!$parent){
            return false;
        }
        // alte löschen
        self::delete_rce_instances(
            $parent_event_id
        );
        // neue dates
        $dates = self::build_dates(
            $parent
        );
        //---------------- Ferien berücksichtigen -----------------------

       

        // =========================
        // Ferienzeiten laden
        // =========================
        $excludeRanges = [];
        if (!empty($parent->exclude_ranges)) {
            $excludeRanges = json_decode(
                $parent->exclude_ranges,
                true
            ) ?: [];
     
        }
        // =========================
        // Ferienzeiten herausfiltern
        // =========================
        if (!empty($excludeRanges)) {
       
            $dates = array_filter(
                $dates,
                function($date) use ($excludeRanges) {
                    foreach ($excludeRanges as $range) {
                        if (empty($range['enabled'])) {
                            continue;
                        }
                        if (
                            empty($range['start']) ||
                            empty($range['end'])
                        ) {
                            continue;
                        }
                        if (
                            $date >= $range['start'] &&
                            $date <= $range['end']
                        ) {
                            return false;
                        }
                    }
                    return true;
                }
            );

        }
        

        //---------------- Ferien berücksichtigen -----------------------
        $dates = array_values(
            array_unique($dates)
        );
        sort($dates);

        // neue erzeugen
        foreach($dates as $date){
            self::create_rce(
                $parent,
                $date,
                $event_post_data
            );
        }
        self::get_all_rce_instances($parent_event_id, $stat);
        return true;
    }

    /**
     * =========================
     * DELETE ALL CURRENT RCE
     * =========================
     */
    public static function delete_rce_instances($parent_event_id) {
        $events = '';
        $events = SE_Del_Gen_RCE_Event_Posts::get_events($parent_event_id);
        if (empty($events)) {
            return;
        }
        global $wpdb;
        $table = SE_EVENTS_TABLE;
        foreach ($events as $event) {
            // WP Post löschen
            if (!empty($event->post_id)) {
                wp_delete_post($event->post_id, true);
            }
            // Event löschen
            $wpdb->delete(
                $table, ['event_id' => $event->event_id],['%d']
            );
        }
    }




    public static function check_rpe_dates_and_update($event) {

           $now = new DateTime();

            // Referenzwerte aus event_start / event_end
            $eventStart = new DateTime($event->event_start);
            $eventEnd   = new DateTime($event->event_end);

            $update = [];

            /*
            * event_start_date
            */
            $current = DateTime::createFromFormat(
                'Y-m-d H:i:s',
                $event->event_start_date . ' ' . $event->event_start_time
            );

            if (!$current || $current < $now) {

                $update['event_start_date'] = $now->format('Y-m-d');
                $update['event_start_time'] = $eventStart->format('H:i:s');

                // optional auch event_start synchronisieren
                $update['event_start'] =  $now->format('Y-m-d') . ' ' . $eventStart->format('H:i:s');
            }

            /*
            * event_end_date
            */
            $current = DateTime::createFromFormat(
                'Y-m-d H:i:s',
                $event->event_end_date . ' ' . $event->event_end_time
            );

            if (!$current || $current < $now) {

                $update['event_end_date'] = $now->format('Y-m-d');
                $update['event_end_time'] = $eventEnd->format('H:i:s');

                // optional auch event_end synchronisieren
            
                $update['event_end'] =  $now->format('Y-m-d') . ' ' . $eventEnd->format('H:i:s');
            }

            if (empty($update)) {
                return 'already_synced';
            }
            $wpdb->update(
                $table,
                $update,
                [
                    'post_id' => $post_id
                ]
            );
        return true;

        }
    /**
     * =========================
     * generate new RCE
     * =========================
     */

    public static function create_rce($parent, $date, $event_post_data = []) {

        global $wpdb;

        $table = SE_EVENTS_TABLE;

        /*
        ==========================
        ORIGINAL POST LADEN
        ==========================
        */

        $parent_post = get_post(
            $parent->post_id
        );

        if (!$parent_post) {
            return false;
        }

        /*
        ==========================
        WP POST ERZEUGEN
        ==========================
        */

        $post_id = wp_insert_post([

            'post_title' =>
                $event_post_data['event_name']
                ?? $parent_post->post_title,

            'post_content' =>
                $event_post_data['post_content']
                ?? $parent_post->post_content,

            'post_excerpt' =>
                $parent_post->post_excerpt,

            'post_status' =>
                $event_post_data['post_status']
                ?? $parent_post->post_status,

            'post_type' =>
                $parent_post->post_type,

            'post_author' =>
                $parent_post->post_author
        ]);

        if (is_wp_error($post_id)) {
            return false;
        }

        /*
        ==========================
        FEATURED IMAGE
        ==========================
        */

        $thumbnail_id = get_post_thumbnail_id(
            $parent->post_id
        );

        if ($thumbnail_id) {
            set_post_thumbnail(
                $post_id,
                $thumbnail_id
            );
        }

        /*
        ==========================
        EVENT DATEN
        ==========================
        */

        $wpdb->insert(
            $table,
            [

                'post_id' => $post_id,

                'event_name' =>
                    $event_post_data['event_name']
                    ?? $parent->event_name,

                'post_content' =>
                    $event_post_data['post_content']
                    ?? $parent->post_content,

                'event_start_date' =>
                    $date,

                'event_end_date' =>
                    $date,

                'event_start_time' =>
                    $parent->event_start_time,

                'event_end_time' =>
                    $parent->event_end_time,

                'event_start' =>
                    $date . ' ' .
                    $parent->event_start_time,

                'event_end' =>
                    $date . ' ' .
                    $parent->event_end_time,

                'event_owner' =>
                    $parent->event_owner,

                'event_status' =>
                    $parent->event_status,

                'location_id' =>
                    $parent->location_id,

                'event_timezone' =>
                    $parent->event_timezone,

                'event_language' =>
                    $parent->event_language,

                'event_slug' =>
                    sanitize_title(
                        (
                            $event_post_data['event_name']
                            ?? $parent->event_name
                        )
                        . '-' .
                        $date
                    ),

                'recurrence' => 0,

                'recurrence_id' =>
                    $parent->event_id,

                'event_type' => 'rce'
            ]
        );

        /*
        ==========================
        KATEGORIEN
        ==========================
        */

        $taxonomies = get_object_taxonomies(
            $parent_post->post_type
        );

        foreach ($taxonomies as $taxonomy) {

            $terms = wp_get_object_terms(
                $parent->post_id,
                $taxonomy,
                [
                    'fields' => 'ids'
                ]
            );

            wp_set_object_terms(
                $post_id,
                $terms,
                $taxonomy
            );
        }

        /*
        ==========================
        POST META
        ==========================
        */

        $meta = get_post_meta(
            $parent->post_id
        );

        foreach ($meta as $key => $values) {

            foreach ($values as $value) {

                add_post_meta(
                    $post_id,
                    $key,
                    maybe_unserialize($value)
                );
            }
        }

        return $post_id;
    }


     /**
     * =========================
     * CHECK OLDER DATES IN RPE AND REPLACE IT CURRENT DATE
     * =========================
     */
    public static function normalizeEventDates($event)
    {
            $now = current_datetime();

            /*
            * Start
            */
            $start = DateTime::createFromFormat(
                'Y-m-d H:i:s',
                $event->event_start_date . ' ' . $event->event_start_time,
                wp_timezone()
            );

            if (!$start) {
                $start = new DateTime(
                    $event->event_start,
                    wp_timezone()
                );
            }

            if ($start < $now) {

                // Nur das Datum auf heute setzen, Uhrzeit bleibt erhalten
                $start->setDate(
                    (int)$now->format('Y'),
                    (int)$now->format('m'),
                    (int)$now->format('d')
                );

                $event->event_start_date = $start->format('Y-m-d');
                $event->event_start_time = $start->format('H:i:s');
                $event->event_start      = $start->format('Y-m-d H:i:s');
            }

            /*
            * Ende
            */
            $end = DateTime::createFromFormat(
                'Y-m-d H:i:s',
                $event->event_end_date . ' ' . $event->event_end_time,
                wp_timezone()
            );

            if (!$end) {
                $end = new DateTime(
                    $event->event_end,
                    wp_timezone()
                );
            }

            if ($end < $now) {

                // Nur das Datum auf heute setzen, Uhrzeit bleibt erhalten
                $end->setDate(
                    (int)$now->format('Y'),
                    (int)$now->format('m'),
                    (int)$now->format('d')
                );

                $event->event_end_date = $end->format('Y-m-d');
                $event->event_end_time = $end->format('H:i:s');
                $event->event_end      = $end->format('Y-m-d H:i:s');
            }

            global $wpdb;
            $table = SE_EVENTS_TABLE;    
            $wpdb->update(
            $table,
            [
                'event_start'      => $event->event_start,
                'event_end'        => $event->event_end,
                'event_start_date' => $event->event_start_date,
                'event_end_date'   => $event->event_end_date,
                'event_start_time' => $event->event_start_time,
                'event_end_time'   => $event->event_end_time,
            ],
            [
                'post_id' => $event->post_id
            ]
        );



        return $event;
    }



    /**
     * =========================
     * BUILD RECURRENCE DATES
     * =========================
     */
    public static function build_dates($event) {
        //check events dates 
        $event = self::normalizeEventDates($event);
        $dates = [];
        if (
            empty($event->event_start_date) ||
            empty($event->event_end_date)
        ) {
            return $dates;
        }
        $start = new DateTime(
            $event->event_start_date
        );
        $end = new DateTime(
            $event->event_end_date
        );
        $interval = max(
            1,
            intval(
                $event->recurrence_interval
            )
        );
        $freq =
            $event->recurrence_freq ?? 'weekly';
        $days = [];
        if (!empty($event->recurrence_byday)) {
            $days = array_map(
                'intval',
                explode(
                    ',',
                    $event->recurrence_byday
                )
            );
        }
        /**
         * DAILY
         */
        if ($freq === 'daily') {

            while ($start <= $end) {

                $dates[] =
                    $start->format('Y-m-d');

                $start->modify(
                    "+{$interval} day"
                );
            }
        }
        /**
         * WEEKLY
         */
        elseif ($freq === 'weekly') {
            $weekdayMap = [
                1 => 'monday',
                2 => 'tuesday',
                3 => 'wednesday',
                4 => 'thursday',
                5 => 'friday',
                6 => 'saturday',
                0 => 'sunday'
            ];
            while ($start <= $end) {
                $weekBase = clone $start;
                foreach ($days as $day) {
                    if (!isset($weekdayMap[$day])) {
                        continue;
                    }
                    $temp = clone $weekBase;
                    $temp->modify(
                        $weekdayMap[$day]
                    );
                    if (
                        $temp >= $start &&
                        $temp <= $end
                    ) {
                        $dates[] =
                            $temp->format('Y-m-d');
                    }
                }
                $start->modify(
                    "+{$interval} week"
                );
            }
        }
        /**
         * MONTHLY
         */
        elseif ($freq === 'monthly') {
            while ($start <= $end) {
                $dates[] =
                    $start->format('Y-m-d');
                $start->modify(
                    "+{$interval} month"
                );
            }
        }
        // doppelte entfernen
        $dates = array_unique($dates);
        // sortieren
        sort($dates);
        return $dates;
    }
}
