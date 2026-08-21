<?php
defined('ABSPATH') || exit;

require_once SE_EVENTS_PATH . 'includes/Admin/Admin_Menu.php';
require_once SE_EVENTS_PATH . 'includes/Admin/Admin_Assets.php';
require_once SE_EVENTS_PATH . 'includes/Repository/Copy_event/new_event.php';

final class SE_Events_Engine {
    public static function run() {
        $admin_menu   = new SE_Events_Admin_Menu();  #events list
        $admin_assets = new SE_Events_Admin_Assets(); #css an js
        add_action('admin_menu', [$admin_menu, 'register']);
        add_action('admin_enqueue_scripts', [$admin_assets, 'enqueue']);
    }             
}

    # CPT se_event
    require_once SE_EVENTS_PATH . 'includes/Repository/cpt/class_se_event_cpt.php';
    new SE_Event_CPT();

    add_filter('default_hidden_columns', function ($hidden, $screen) {
        if ($screen->id === 'toplevel_page_se-events') {
            $hidden[] = 'post_id';
        }
        return $hidden;
    }, 10, 2);

    require_once SE_EVENTS_PATH . 'includes/Repository/event_copy.php';
    add_action('admin_init', function() {
        new SE_Event_Duplicator();
    });
    
    add_action('init', function() {
    register_taxonomy(
        'event-categories',
        'se_event',
        [
            'label' => 'Event Kategorien',
            'hierarchical' => true,

            'show_ui' => true,
            'show_in_menu' => true,
            'show_admin_column' => true,

            'show_in_rest' => true, // 🔥 WICHTIG für Gutenberg!

            'rewrite' => ['slug' => 'event-category'],
        ]
    );

});
   




// Handler für die Kopierfunktion
function se_em_handle_copy_action() {
    if ( ! isset( $_POST['se_copy_nonce'] ) || ! wp_verify_nonce( $_POST['se_copy_nonce'], 'se_copy_action' ) ) {
        return;
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $source_id = isset( $_POST['se_source_id'] ) ? intval( $_POST['se_source_id'] ) : 0;
    $source_type = isset( $_POST['se_source_type'] ) ? sanitize_text_field( $_POST['se_source_type'] ) : '';
    $is_recurring = isset( $_POST['se_is_recurring'] ) ? true : false;
   
    if ( $source_id <= 0 ) {
        add_settings_error( 'se-events', 'invalid-id', __( 'Ungültige Quell-ID.', 'se-em' ), 'error' );
        return;
    }
    
    $result = null;
    if ( $source_type === 'post' ) {
      
        $result = SE_Copy_Logic::copy_post_to_event( $source_id, $is_recurring );
     
    } elseif ( $source_type === 'se_event' ) {
        $result = SE_Copy_Logic::copy_event_to_event( $source_id, $is_recurring );
    } else {
        add_settings_error( 'se-events', 'invalid-type', __( 'Ungültiger Quelltyp.', 'se-em' ), 'error' );
        return;
    }
     
    if ( is_wp_error( $result ) ) {
        add_settings_error( 'se-events', 'copy-error', $result->get_error_message(), 'error' );
    } else {
      
        add_settings_error( 'se-events', 'copy-success', __( 'Erfolgreich kopiert! Neue Event-ID / Post_ID: ', 'se-em' ) . $result->id . ' | ' .  $result->post_id ,
        
        'updated' );
    }


}

add_action( 'admin_init', 'se_em_handle_copy_action' );

add_action('admin_post_se_create_event', function() {

    if (!current_user_can('manage_options')) {
        wp_die('Keine Berechtigung');
    }

    check_admin_referer('se_create_event');

     $data = [
        'title' => sanitize_text_field('Dummy Single Event'),
        'content' => wp_kses_post('Dummy Single Event Text'),
        'recurrence' => 0
    ];

    $result = Event_Factory::create($data);

    if (!$result) {
        wp_die('SE konnte nicht erstellt werden');
    }

    wp_safe_redirect($result['edit_url']);
    exit;
});

add_action('admin_post_se_create_event_by_ID', function() {

    if (!current_user_can('manage_options')) {
        wp_die('Keine Berechtigung');
    }

    check_admin_referer('se_create_event_by_ID');

    $post_id = intval($_POST['post_id'] ?? 0);

     if (!get_post($post_id)) {
        wp_die('Ungültige Post ID');
    }
    $result = Event_Factory::create_from_post($post_id);

    if (!$result) {
        wp_die('SE mit ID konnte nicht erstellt werden');
    }

    wp_safe_redirect($result['edit_url']);
    exit;
});


add_action('admin_post_se_create_recurring_event', function() {

    if (!current_user_can('manage_options')) {
        wp_die('Keine Berechtigung');
    }
    check_admin_referer('se_create_recurring_event');
    $rrule = [
        'freq'     => 'daily', //sanitize_text_field($_POST['freq']),
        'count'    => 1, //intval($_POST['count'] ?? 5),
        'interval' => 1, //intval($_POST['interval'] ?? 1)
    ];
    $data = [
        'title' => sanitize_text_field($_POST['title']),
        'content' => 'Recurring Event Auto Generated',
        'recurrence' => 1,
        'rrule' => json_encode($rrule)
    ];
    $result = Event_Factory::create_parent($data);
    if (!$result) {
        wp_die('RPE konnte nicht erstellt werden');
    }
    wp_safe_redirect($result['edit_url']);
    exit;
});

add_action('admin_post_se_create_parent_by_ID', function() {

    if (!current_user_can('manage_options')) {
        wp_die('Keine Berechtigung');
    }

    check_admin_referer('se_create_parent_by_ID');

    $post_id = intval($_POST['post_id'] ?? 0);

    if (!get_post($post_id)) {
        wp_die('Ungültige Post ID');
    }
   

    $rrule = [
        'freq'     => 'daily', //sanitize_text_field($_POST['freq']),
        'count'    => 1, //intval($_POST['count'] ?? 5),
        'interval' => 1, //intval($_POST['interval'] ?? 1)
    ];

    $result = Event_Factory::create_parent_from_post($post_id, $rrule);

    if (!$result) {
        wp_die('RPE mit ID konnte nicht erstellt werden');
    }
    #var_dump($result);
    #wp_die('rpe mit id');
    wp_safe_redirect($result['edit_url']);
    exit;
});




class SE_Helper { 

    public static function url($path = '') {
        return SE_EVENTS_URL . ltrim($path, '/');
    }

    public static function path($path = '') {
        return SE_EVENTS_PATH . ltrim($path, '/');
    }
}

add_action('save_post', function($post_id) {
    global $wpdb;

    $wpdb->update(
        SE_EVENTS_TABLE,
        ['event_name' => get_the_title($post_id)],
        ['post_id' => $post_id]
    );
});


add_action('admin_enqueue_scripts', function($hook) {

                    // 🔥 Nur auf deiner Seite
                    if ($hook !== 'toplevel_page_se-events') {
                        return;
                    }

                    // 🔥 DAS ist der Fix
                    wp_enqueue_script('se-quick-edit');
                });
                add_action('admin_footer', function() {
                    ?>
                    <table style="display:none">
                        <tbody id="the-list">
                            <?php
                            $table = new SE_Events_List_Table();
                            $table->inline_edit();
                            ?>
                        </tbody>
                    </table>
                    <?php
                });
            
                add_action('wp_ajax_se_quick_edit_save', function() {

                    $post_id = (int) $_POST['post_id'];

                    // 🔥 WP Post Update
                    wp_update_post([
                        'ID'         => $post_id,
                        'post_title' => sanitize_text_field($_POST['post_title']),
                        'post_status'=> sanitize_text_field($_POST['post_status']),
                        'post_owner' =>  sanitize_text_field($_POST['post_owner']),
                        'post_author'  => (int) $_POST['post_author'],
                    ]);

                    // 🔥 Deine Event Daten (Custom Table!)
                        $event_start_date = sanitize_text_field($_POST['se_event_date'] ?? '');
                        $event_start_time = sanitize_text_field($_POST['se_start_time'] ?? '');
                        $event_end_time   = sanitize_text_field($_POST['se_end_time'] ?? '');
                        $event_type = isset($_POST['cb_event_type']) ? sanitize_text_field($_POST['cb_event_type']) : '';

                        // Enddatum = Startdatum, da im Formular kein Enddatum existiert
                        $event_end_date = $event_start_date;

                        $event_start = '';
                        $event_end   = '';

                        if ($event_start_date && $event_start_time) {
                            $event_start = $event_start_date . ' ' . $event_start_time;
                        }

                        if ($event_end_date && $event_end_time) {
                            $event_end = $event_end_date . ' ' . $event_end_time;
                        }
                        global $wpdb;

                        $wpdb->update(
                            SE_EVENTS_TABLE,
                            [
                                'event_type'       => $event_type, 
                                'event_start_date' => $event_start_date,
                                'event_end_date'   => $event_end_date,
                                'event_start_time' => $event_start_time,
                                'event_end_time'   => $event_end_time,
                                'event_start'      => $event_start,
                                'event_end'        => $event_end,
                            ],
                            ['post_id' => $post_id],
                            ['%s','%s','%s','%s'],
                            ['%d']
                        );
                        if ($wpdb->last_error) {
                        wp_send_json_error($wpdb->last_error);
                    }

                        wp_send_json_success();
                });


           








