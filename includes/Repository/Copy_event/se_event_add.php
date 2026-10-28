<?php

  /*
        Änderung bei Umstellung auf PHP 8.5.12
        von: 
            $date->modify('+1 day');
            $date->setTime(10, 0);
            $date->setTime(11, 0);

        auf:
            $date = $date->modify('+1 day');
            $date = $date->setTime(10, 0);
            $date = $date->setTime(11, 0);


        */

class Event_Factory {

    public static function create($data) {

        $post_id = wp_insert_post([
            'post_title'   => $data['title'],
            'post_content' => $data['content'],
            'post_type'    => 'se_event',
            'post_status'  => 'publish'
        ]);

        if (!$post_id || is_wp_error($post_id)) {
            return false;
        }

        $post = get_post($post_id, ARRAY_A);
        $user_id = get_current_user_id();

        $date = current_datetime();
        $date = $date->modify('+1 day');

        $dd  = $date->format('Y-m-d');
        $date = $date->setTime(10, 0);
        $ds  = $date->format('Y-m-d H:i');
        $dsu = $date->format('H:i');
        $date = $date->setTime(11, 0);
        $de  = $date->format('Y-m-d H:i');
        $deu = $date->format('H:i');

        global $wpdb;

        $wpdb->insert(SE_EVENTS_TABLE, [
            'post_id' => $post_id,
            'event_name' => $post['post_title'],
            'post_content' => $post['post_content'],
            'event_owner' => $user_id,
            'event_slug' => $post['guid'],
            'event_status' => ($post['post_status'] === 'publish') ? 1 : 0,
            'event_start_date' => $dd,
            'event_end_date' => $dd,
            'event_start_time' => $dsu,
            'event_end_time' => $deu,
            'event_start' => $ds,
            'event_end' => $de,
            'event_all_day' => 0,
            'event_timezone' => get_option('timezone_string') ?: 'UTC',
            'event_date_created' => $post['post_date'],
            'event_date_modified' => $post['post_modified'],
            'recurrence' => $data['recurrence'] ?? 0,
            'recurrence_freq' => 'daily',
            'event_type' => 'se'
        ]);

        $event_id = $wpdb->insert_id;

        // 👉 Edit URL bauen
        $edit_url = add_query_arg([
            'page' => 'se-events',
            'event_action' => 'edit',
            'event_id' => $event_id
        ], admin_url('admin.php'));

        return [
            'post_id' => $post_id,
            'event_id' => $event_id,
            'edit_url' => $edit_url
        ];
    }

    
    public static function create_from_post($post_id) {
        /*
        $original = get_post($post_id, ARRAY_A);

        if (!$original) {
            return false;
        }

        // 🔥 1. NEUEN POST ERSTELLEN (Kopie)
        $new_post_id = wp_insert_post([
            'post_title'   => $original['post_title'],
            'post_content' => $original['post_content'],
            'post_status'  => 'publish',
            'post_type'    => 'se_event',
            'post_author'  => get_current_user_id()
        ]);

        */
        $new_post_id = self::clone_post_to_event($post_id);
        if (!$new_post_id || get_post_type($new_post_id) !== 'se_event') {
            return false;
        }

        // $original = $post_id . ' new =' . $new_post_id;

       
        $post = get_post($new_post_id, ARRAY_A);

        if (!$post) {
            return false;
        }
           

        global $wpdb;

        // 🔥 Optional: verhindern doppelte Events (auf neuen Post bezogen sinnvoller)
       
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT event_id FROM ".SE_EVENTS_TABLE." WHERE post_id = %d",
                $new_post_id
            )
        );

        if ($exists) {
            wp_die('Event existiert bereits');
        }
        //$test = 'original ' . $post_id . 'new post_id ' .  $new_post_id;

        $user_id = get_current_user_id();

        // 🔥 Datum setzen
        $date = current_datetime();
        $date = $date->modify('+1 day');
        $dd = $date->format('Y-m-d');
        $date = $date->setTime(10, 0);
        $ds  = $date->format('Y-m-d H:i');
        $dsu = $date->format('H:i');
        $date = $date->setTime(11, 0);
        $de  = $date->format('Y-m-d H:i');
        $deu = $date->format('H:i');

        // 🔥 2. Event anlegen (mit neuem Post!)
        $wpdb->insert(SE_EVENTS_TABLE, [
            'post_id' => $new_post_id,
            'event_name' => $post['post_title'],
            'post_content' => $post['post_content'],
            'event_owner' => $user_id,
            'event_slug' => $post['guid'],
            'event_status' => ($post['post_status'] === 'publish') ? 1 : 0,
            'event_start_date' => $dd,
            'event_end_date' => $dd,
            'event_start_time' => $dsu,
            'event_end_time' => $deu,
            'event_start' => $ds,
            'event_end' => $de,
            'event_all_day' => 0,
            'event_timezone' => get_option('timezone_string') ?: 'UTC',
            'event_date_created' => $post['post_date'],
            'event_date_modified' => $post['post_modified'],
            'recurrence' => 0,
            'recurrence_freq' => 'daily',
            'event_type' => 'se'
        ]);
        if ($wpdb->last_error) {
            wp_delete_post($new_post_id, true);
            return false;
        }

        $event_id = $wpdb->insert_id;

        if (!$event_id) {
            wp_delete_post($new_post_id, true);
            return false;
        }
        update_post_meta(
            $new_post_id,
            '_se_event_id',
            $event_id
        );
        
        // 🔥 Edit URL
        $edit_url = add_query_arg([
            'page' => 'se-events',
            'event_action' => 'edit',
            'event_id' => $event_id
        ], admin_url('admin.php'));

        return [
            'original_post_id' => $post_id,
            'post_id' => $new_post_id,
            'event_id' => $event_id,
            'edit_url' => $edit_url
        ];
    }
   

    public static function clone_post_to_event($post_id) {

        $original = get_post($post_id);

        if (!$original) {
            return false;
        }

        // 🔥 1. Post kopieren
        $new_post_id = wp_insert_post([
            'post_title'   => $original->post_title,
            'post_content' => $original->post_content,
            'post_excerpt' => $original->post_excerpt,
            'post_status'  => 'publish',
            'post_type'    => 'se_event',
            'post_author'  => get_current_user_id(),
            'menu_order'   => $original->menu_order,
        ]);

        if (!$new_post_id || is_wp_error($new_post_id)) {
            return false;
        }
        // 🔥 2. Taxonomien kopieren nur kategorie mappen zeischen post  & event
        // ==========================================
        // 🔹 Kategorien vom Original-Post übernehmen
        //    über den Namen auf Event-Kategorien mappen
        // ==========================================

        $original_categories = get_the_terms(
            $post_id,
            'category'
        );

        if (
            !empty($original_categories) &&
            !is_wp_error($original_categories)
        ) {

            $event_category_ids = [];

            foreach ($original_categories as $category) {

                // Event-Kategorie mit gleichem Namen suchen
                $event_term = get_term_by(
                    'name',
                    $category->name,
                    'event-categories'
                );

                if (
                    $event_term &&
                    !is_wp_error($event_term)
                ) {

                    $event_category_ids[] =
                        (int) $event_term->term_id;
                }
            }

            // Gefundene Kategorien dem neuen Event zuweisen
            if (!empty($event_category_ids)) {

                wp_set_post_terms(
                    $new_post_id,
                    $event_category_ids,
                    'event-categories'
                );
            }
        }

        //-------------------------------
        // 🔥 2. Taxonomien kopieren
        $taxonomies = get_object_taxonomies($original->post_type);

        foreach ($taxonomies as $taxonomy) {

            $terms = wp_get_object_terms($post_id, $taxonomy, ['fields' => 'ids']);

            if (!is_wp_error($terms) && !empty($terms)) {
                wp_set_object_terms($new_post_id, $terms, $taxonomy);
            }
        }

        // 🔥 3. Post Meta kopieren
        $meta = get_post_meta($post_id);

        foreach ($meta as $key => $values) {

            // 🔥 wichtige WP interne Felder überspringen
            if (in_array($key, [
                '_edit_lock',
                '_edit_last',
                '_wp_old_slug'
            ])) {
                continue;
            }

            foreach ($values as $value) {
                add_post_meta($new_post_id, $key, maybe_unserialize($value));
            }
        }

        // 🔥 4. Featured Image kopieren
        $thumbnail_id = get_post_thumbnail_id($post_id);

        if ($thumbnail_id) {
            set_post_thumbnail($new_post_id, $thumbnail_id);
        }

        // 🔥 5. Referenz speichern (optional aber sehr sinnvoll)
        add_post_meta($new_post_id, '_se_original_post_id', $post_id);

        return $new_post_id;
    }

    private static function normalize_rrule($rrule) {
        
        // Wenn schon Array → perfekt
        if (is_array($rrule)) {
            if (!empty($_POST['weekdays'])) {
                $rrule['byweekday'] = array_map('intval', $_POST['weekdays']);
            }
            return $rrule;
        }

        // Wenn String → versuchen zu decoden
        if (is_string($rrule)) {

            $decoded = json_decode($rrule, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                 if (!empty($_POST['weekdays'])) {
                    $decoded['byweekday'] = array_map('intval', $_POST['weekdays']);
                }
                return $decoded;
            }
        }

        return false;
    }

    public static function create_parent($data) {
        global $wpdb;
        // =====================================================
        // 🔹 Standardwerte
        // =====================================================
        $data['recurrence'] = 1;
        // 🔥 Parent Typ
        $data['event_type'] = 'rpe';
        // =====================================================
        // 🔹 Tägliche RRULE erzeugen
        // =====================================================
        $rrule = [
            'freq' => 'daily',
            'interval' => 1,
        ];
        // =====================================================
        // 🔹 Parent erzeugen
        // =====================================================

        $result = self::create($data);

        if (!$result) {
            return false;
        }

        // =====================================================
        // 🔹 Parent Event aktualisieren
        // =====================================================

        $wpdb->update(
            SE_EVENTS_TABLE,
            [
                'recurrence' => 1,
                'event_type' => 'rpe',
            ],
            [
                'event_id' => $result['event_id']
            ]
        );

        // =====================================================
        // 🔹 RRULE speichern
        // =====================================================
        $wpdb->insert(
            $wpdb->prefix . 'se_event_recurrences',
            [
                'event_id' => $result['event_id'],
                'rrule' => wp_json_encode($rrule),
            ]
        );

        return $result;
    }

    public static function create_parent_from_post($post_id, $rrule) {
        $new_post_id = self::clone_post_to_event($post_id);
        if (!$new_post_id) return false;
        $post = get_post($new_post_id, ARRAY_A);
        if (!$post) return false;
            global $wpdb;
            $user_id = get_current_user_id();
            $date = current_datetime();
            $date = $date->modify('+1 day');
            $dd = $date->format('Y-m-d');
            $date = $date->setTime(10, 0);
            $ds  = $date->format('Y-m-d H:i');
            $dsu = $date->format('H:i');
            $date = $date->setTime(11, 0);
            $de  = $date->format('Y-m-d H:i');
            $deu = $date->format('H:i');
            // RRULE normalisieren
            $rrule = self::normalize_rrule($rrule);
        if (!$rrule) {
            error_log('Invalid RRULE in create_parent_from_post');
            return false;
        }
        // Parent erstellen
        $insert = $wpdb->insert(SE_EVENTS_TABLE, [
            'post_id' => $new_post_id,
            'event_name' => $post['post_title'],
            'post_content' => $post['post_content'],
            'event_owner' => $user_id,
            'event_slug' => $post['guid'],
            'event_status' => ($post['post_status'] === 'publish') ? 1 : 0,
            'event_start_date' => $dd,
            'event_end_date' => $dd,
            'event_start_time' => $dsu,
            'event_end_time' => $deu,
            'event_start' => $ds,
            'event_end' => $de,
            'event_all_day' => 0,
            'event_timezone' => get_option('timezone_string') ?: 'UTC',
            'event_date_created' => $post['post_date'],
            'event_date_modified' => $post['post_modified'],
            'recurrence' => 1,
            'recurrence_id' => null,
            'recurrence_freq' => 'daily',
            'event_type' => 'rpe'
        ]);
        if ($insert === false) {
            error_log('Insert parent failed: '.$wpdb->last_error);
            return false;
        }
        $parent_id = $wpdb->insert_id;
        // RRULE speichern
        $wpdb->insert($wpdb->prefix.'se_event_recurrences', [
            'event_id' => $parent_id,
            'rrule' => json_encode($rrule)
        ]);
       
        
        $edit_url = add_query_arg([
            'page' => 'se-events',
            'event_action' => 'edit',
            'event_id' => $parent_id
        ], admin_url('admin.php'));
        return [
            'post_id' => $new_post_id,
            'event_id' => $parent_id,
            'edit_url' => $edit_url
        ];
    }

    private static function weekday_to_string($day) {
        $map = [
            0 => 'sunday',
            1 => 'monday',
            2 => 'tuesday',
            3 => 'wednesday',
            4 => 'thursday',
            5 => 'friday',
            6 => 'saturday',
        ];

        return $map[$day];
    }
      
    private static function build_dates_from_rrule($start_date, $rrule) {

        $dates = [];

        $count = $rrule['count'] ?? 10;
        $interval = $rrule['interval'] ?? 1;
        $freq = $rrule['freq'] ?? 'daily';

        $start = strtotime($start_date);

        if ($freq === 'daily') {

            $current = $start;

            for ($i = 0; $i < $count; $i++) {
                $dates[] = date('Y-m-d H:i', $current);
                $current = strtotime("+{$interval} day", $current);
            }
        }

        if ($freq === 'weekly') {

            $weekdays = $rrule['byweekday'] ?? [];

            if (empty($weekdays)) {
                $weekdays[] = date('w', $start);
            }

            $generated = 0;
            $current = $start;

            while ($generated < $count) {

                foreach ($weekdays as $weekday) {

                    $target = strtotime(self::weekday_to_string($weekday), $current);

                    if ($target < $current) {
                        $target = strtotime("next " . self::weekday_to_string($weekday), $current);
                    }

                    if ($target >= $start) {
                        $dates[] = date('Y-m-d H:i', $target);
                        $generated++;
                    }

                    if ($generated >= $count) break;
                }

                $current = strtotime("+{$interval} week", $current);
            }
        }

        sort($dates);

        return $dates;
    }

}






/**
 * Event-Objekt-Klasse (Nachbildung von EM_Event).
 * Verwaltet Event-Daten, einschließlich der Logik für wiederkehrende Events.
 */

require_once SE_EVENTS_PATH . 'includes/Repository/Copy_event/se_object.php';
class SE_Event extends SE_Object {
    public $id_field = 'event_id';
    public $table_name; // Wird in se-event-manager.php gesetzt
    public $post_type = 'se_event';
    
    // Felder, die in der Custom Table gespeichert werden
    public $fields = array(
        'event_id', 'post_id', 'event_slug', 'event_owner', 'event_status', 'event_name', 
        'event_start_time', 'event_end_time', 'event_all_day', 'event_start_date', 
        'event_end_date', 'post_content', 'event_rsvp', 'event_rsvp_date', 
        'event_rsvp_time', 'event_spaces', 'event_private', 'location_id', 
        'recurrence_id', 'event_date_modified', 'event_timezone',  'event_type'
    );

    public function __construct( $id = false, $search_by = 'event_id' ) {

    #-------------------------------
    return;
        global $wpdb;
        $this->table_name = SE_EVENTS_TABLE;
        parent::__construct( $id, $search_by );
    }

    /**
     * Speichert das Event in wp_posts und wp_se_events.
     * @return bool
     */
    public function save() {
        // 1. Speichere/Aktualisiere den Custom Post Type (CPT)
        $post_array = array(
            'post_title' => $this->event_name,
            'post_content' => $this->post_content,
            'post_status' => ( $this->event_status == 1 ) ? 'publish' : 'draft',
            'post_type' => $this->post_type,
            'post_author' => $this->event_owner,
        );
        if ( $this->post_id > 0 ) {
            $post_array['ID'] = $this->post_id;
            $post_id = wp_update_post( $post_array );
        } else {
            $post_id = wp_insert_post( $post_array );
            $this->post_id = $post_id;
        }

        if ( is_wp_error( $post_id ) || $post_id === 0 ) {
            return false;
        }

        // 2. Speichere/Aktualisiere die Custom Table Daten
        $this->event_date_modified = current_time( 'mysql' );
        $this->event_slug = sanitize_title( $this->event_name );
        
        $result = parent::save();
        
        // Action Hook nach dem Speichern (Nachbildung von em_event_save)
        do_action( 'se_event_save', $this, $result );

        return $result;
    }

    /**
     * Prüft, ob das Event wiederkehrend ist.
     * @return bool
     */
    public function is_recurring() {
        return ! empty( $this->recurrence_id );
    }

    /**
     * Gibt das zugehörige SE_Recurrence-Objekt zurück.
     * @return SE_Recurrence|null
     */
    public function get_recurrence() {
        if ( $this->is_recurring() ) {
            return new SE_Recurrence( $this->recurrence_id );
        }
        return null;
    }
    
    /**
     * Löscht das Event (CPT und Custom Table).
     * @return bool
     */
    public function delete() {
        // Action Hook vor dem Löschen (Nachbildung von em_event_delete)
        do_action( 'se_event_delete', $this );
        
        // 1. Lösche den Custom Post Type
        if ( $this->post_id > 0 ) {
            wp_delete_post( $this->post_id, true );
        }
        
        // 2. Lösche die Custom Table Daten
        return parent::delete();
    }
}

