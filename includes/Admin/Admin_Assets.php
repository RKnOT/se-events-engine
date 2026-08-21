<?php
defined('ABSPATH') || exit;
//----- init FE short cuts
require_once SE_EVENTS_PATH .  'FE/get_events.php';      

wp_enqueue_style(
            'se-rec-ui-style',
            SE_EVENTS_URL . 'FE/assets/css/fe.css',
            [],
            '1.1'
        );



//---- int rpc UI and management 






require_once SE_EVENTS_PATH .  'includes/Repository/Edit/rpe_ui/class-se-get-stat-event-post.php';
require_once SE_EVENTS_PATH  . 'includes/Repository/Edit/rpe_ui/class-se-recurrence-manager-ui.php';
require_once SE_EVENTS_PATH . 'includes/Repository/Edit/rpe_ui/class-se-del-gen-rce-event-posts.php';
require_once SE_EVENTS_PATH . 'includes/Repository/Edit/rpe_ui/se-recurrence-ui-handler.php';  


add_action('init', function(){
        add_action(
            'wp_ajax_se_save_rpe',
            ['SE_RPE_Manager_UI', 'ajax_save_rpe']
        );
});

    
class SE_Events_Admin_Assets {

    public function enqueue($hook) {
        $base = plugin_dir_url(dirname(dirname(__FILE__)));
        wp_enqueue_script(
        'se-admin-js',
         $base . 'includes/assets/js/admin.js',
        ['jquery'],
        '1.0',
        true
    );

    wp_enqueue_style(
        'se-admin-css',
         $base . 'includes/assets/css/admin.css',
        [],
        '1.0'
        );
     
    }

}


