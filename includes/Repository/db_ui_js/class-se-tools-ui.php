<?php

class SE_Tools_UI {

    public static function init() {
        add_action('admin_menu', [self::class, 'menu']);
        add_action('admin_enqueue_scripts', [self::class, 'scripts']);
    }

    public static function menu() {
        add_menu_page(
            'SE Tools',
            'SE Tools',
            'manage_options',
            'se-tools',
            [self::class, 'render']
        );
    }

    public static function scripts($hook) {
        if ($hook !== 'toplevel_page_se-tools') return;
       
        wp_enqueue_script(
            'se-tools-js',
             SE_EVENTS_URL . 'includes/Repository/db_ui_js/se-tools.js',
            ['jquery'],
            null,
            true
        );

        wp_localize_script('se-tools-js', 'SE_TOOLS', [
            'ajax_url' => admin_url('admin-ajax.php')
        ]);
    }

    public static function render() {
        ?>
        <div class="wrap">
            <h1>SE Event Tools</h1>

            <button id="dry" class="button">Dry Run</button>
            <button id="run" class="button button-primary">Ausführen</button>

            <pre id="output" style="margin-top:20px;background:#111;color:#0f0;padding:15px;"></pre>
        </div>
        <?php
    }
}