<?php

class SE_Ajax_Handler {

    public static function init() {
        add_action('wp_ajax_se_rename_event_type', [self::class, 'rename']);
    }

    public static function rename() {

        // 🔥 verhindert kaputtes JSON
        while (ob_get_level()) {
            ob_end_clean();
        }

        $dry_run = isset($_POST['dry_run']) && $_POST['dry_run'] == 1;

        $result = SE_Event_Service::rename_event_type($dry_run);

        wp_send_json_success($result);
    }
}