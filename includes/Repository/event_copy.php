<?php


class SE_Event_Duplicator {
 
    private $table;

    public function __construct() {
        global $wpdb;
        $this->table = SE_EVENTS_TABLE;
      
        add_action('admin_action_se_duplicate_event', [$this, 'duplicate_event']);
    }
    public function duplicate_event() {
      
        if (!isset($_GET['post_id'], $_GET['_wpnonce'])) {
            wp_die('Ungültige Anfrage');
        }

        $post_id = intval($_GET['post_id']);

        if (!wp_verify_nonce($_GET['_wpnonce'], 'se_duplicate_event_' . $post_id)) {
            wp_die('Nonce Fehler');
        }

        global $wpdb;

        /**
         * 🔹 1. Event über post_id holen
         */
        $event = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table} WHERE post_id = %d", $post_id),
            ARRAY_A
        );
       
        if (!$event) {
            wp_die('Event nicht gefunden');
        }

        /**
         * 🔹 2. WP POST DUPLIZIEREN
         */
        $post = get_post($post_id);

        if (!$post) {
            wp_die('Post nicht gefunden');
        }

        $new_post_id = wp_insert_post([
            'post_title'   => $post->post_title . ' (Kopie)',
            'post_content' => $post->post_content,
            'post_status'  => 'draft',
            'post_type'    => 'se_event',
            'post_author'  => get_current_user_id(),
        ]);

        // Meta kopieren
        $meta = get_post_meta($post->ID);

        foreach ($meta as $key => $values) {

            if (in_array($key, ['_edit_lock','_edit_last'])) {
                continue;
            }

            foreach ($values as $value) {
                add_post_meta($new_post_id, $key, maybe_unserialize($value));
            }
        }

        /**
         * 🔹 3. EVENT DUPLIZIEREN
         */
        unset($event['event_id']); // 🔥 wichtig

        $event['post_id'] = $new_post_id;
        $event['event_owner'] = get_current_user_id();

        $event['event_date_created']  = current_time('mysql');
        $event['event_date_modified'] = current_time('mysql');

        $event['event_slug'] = sanitize_title($event['event_name'] . '-copy-' . time());

        $event['event_status'] = 0;
        #$event['post_status']  = 'draft';

        if (!empty($event['event_name'])) {
            $event['event_name'] .= ' (Kopie)';
        }

        /**
         * 🔥 SEHR EMPFOHLEN (bei dir wichtig!)
         */
        $event['recurrence'] = 0;
        #$event['recurrence_id'] = 0;
        $event['recurrence_set_id'] = null;
        $event['event_parent'] = null;

      

        /**
         * 🔹 4. INSERT
         */
        $result = $wpdb->insert($this->table, $event);
     


        wp_redirect(admin_url('admin.php?page=se-events&event_action=edit&post_id=' . $new_post_id));
        exit;


    }
}

new SE_Event_Duplicator();