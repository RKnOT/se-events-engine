<?php

 //require_once SE_EVENTS_PATH . 'debug/error_log.php';
 //$db_se = new debug_tests();
 //global $db_se;

class SE_Event_Editor extends SE_GET_EVENT_TYP{

  
    protected $table ; 
    protected $table_post ; 
    protected $post_id;   // 🔥 NEU
    protected $event_id;  // 🔥 NEU

    protected $se_debug_flag;

    public function __construct() {
        // 🔥 Hooks IMMER registrieren
        add_action('admin_init', [$this,'maybe_load_editor']);
        add_action('admin_post_se_save_event', [$this,'save_event']);
        add_filter('update_footer', '__return_empty_string', 11);
        add_filter('admin_footer_text', '__return_empty_string');

        add_action('admin_enqueue_scripts', [$this,'enqueue_editor_scripts']);
        add_action('wp_ajax_se_save_recurrence', [$this, '']);

      
        $this->post_id  = intval($_GET['post_id'] ?? 0);
        $this->event_id = intval($_GET['event_id'] ?? 0);
        parent::__construct($this->post_id);

      
       
        # global $db_se;     
        # $db_se->se_debug('START  constuct from SE_Event_Editor');
        #$this->se_debug_flag = FALSE;
    }
    public function ajax_save_recurrence() {

        if (ob_get_length()) ob_clean();

        if (!isset($_POST['payload'])) {
            wp_send_json_error("Kein Payload");
        }

        $data = json_decode(stripslashes($_POST['payload']), true);

        if (!$data) {
            wp_send_json_error("JSON Fehler");
        }

        wp_send_json_success([
            'msg' => 'läuft',
            'data' => $data
        ]);
    }

    /* ==============================
       NEU: Editor Scripts laden
       ============================== */

    public function enqueue_editor_scripts(){

        wp_enqueue_script('postbox');
        wp_enqueue_script('autosave');

        /* notwendig für Taxonomie UI */
        wp_enqueue_script('post');
        wp_enqueue_script('wp-lists');

    }
    public function ajax_get_rooms(){
        check_ajax_referer('se_rooms_nonce', 'nonce');
        global $wpdb;

        $location_id = intval($_POST['location_id'] ?? 0);

        if(!$location_id){
            wp_send_json([]);
        }

        $rooms = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT room_id, room_name 
                FROM {$wpdb->prefix}se_rooms 
                WHERE location_id=%d 
                ORDER BY room_name",
                $location_id
            )
        );

        wp_send_json($rooms);
    }

    public function box_owner(){

        $owner_id = $this->event->event_owner ?? 0;

        if(!$owner_id){
            echo "<p>Kein Owner gesetzt</p>";
            return;
        }

        $user = get_userdata($owner_id);

        if(!$user){
            echo "<p>Owner nicht gefunden</p>";
            return;
        }

        ?>

        <p><strong>Name:</strong><br>
        <?php echo esc_html($user->display_name); ?></p>

        <p><strong>E-Mail:</strong><br>
        <?php echo esc_html($user->user_email); ?></p>

        <p><strong>Benutzername:</strong><br>
        <?php echo esc_html($user->user_login); ?></p>

        <?php
    }

    /**
     * Prüft ob Editor aufgerufen werden soll
     */
    public function maybe_load_editor(){

        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }

        if(!is_admin())
            return;
        if(!isset($_GET['page']) || $_GET['page'] !== 'se-events')
            return;
        if(($_REQUEST['event_action'] ?? '') !== 'edit')
            return;
        global $post, $typenow, $title;
        if(!$this->post_id)
            return;
        $GLOBALS['post'] = $this->post_id;
        $typenow = 'se_event';
        $title   = 'SE Event Editor';
       


        //require_once ABSPATH.'wp-admin/includes/screen.php';
        //$screen = convert_to_screen('se_event');
        //set_current_screen($screen);
        //$screen = get_current_screen();
        //if ($screen) {
        //    $screen->show_screen_options = false;
        //}
        require_once ABSPATH.'wp-admin/admin-header.php';
        $this->render_page();
        require_once ABSPATH.'wp-admin/admin-footer.php';
        exit;
    }
    #	Time	Memory	Function
    /**
     * Event laden
     */
    /*
    public function load_event(){
        global $wpdb;
        $post_id  = intval($_GET['post_id'] ?? 0);
        $event_id = intval($_GET['event_id'] ?? 0);
        if($event_id){
            $this->event = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$this->table} WHERE event_id=%d",
                    $event_id
                )
            );
            if( $this->event) {
                $post_id = $this->event->post_id;
                $this->post = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT * FROM {$this->table_post} WHERE ID=%d",
                        $post_id 
                    )
                );
                #echo $this->post_id . ' | ' . $this->event_id;        
                #var_dump($this->event);
                if($this->post) {
                    $this->post_id = $this->event->post_id;
                    $this->event_id = $this->event->event_id;
                }
            }
            return;
        }
        if($post_id){        
            $this->event = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$this->table} WHERE post_id=%d",
                    $post_id
                )
            );  
            
            $this->post = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$this->table_post} WHERE ID=%d",
                     $post_id 
                )
            );
            #echo $this->post_id . ' | ' . $this->event_id;        
            #var_dump($this->event);
            if($this->event) {

                $this->post_id = $this->event->post_id;
                $this->event_id = $this->event->event_id;
            }
            return;
        }  
    }
*/
    /**
     * Screen rendern
     */
    public function render_page() { 

        if(!$this->post_id){
            echo '<div class="wrap"><h1>Event nicht gefunden</h1></div>';
            return;
        }
        $post_id = $this->post_id;
        /* ==============================
           NEU: Post Lock prüfen
           ============================== */
        
        $post_lock = wp_check_post_lock($post_id);

        if($post_lock){

            $user = get_userdata($post_lock);

            echo "<div class='notice notice-warning'>
            Dieser Beitrag wird gerade von {$user->display_name} bearbeitet.
            </div>";
        }


        wp_set_post_lock($post_id);
         $link_back = admin_url('admin.php?page=se-events');
        /* ==============================
           META BOXEN
           ============================== */



        $stat = $this->get_stat();
        if(!$stat) {
            $this->load_event();
            $this->get_event_type();
            $stat = $this->get_stat();
        }
        add_meta_box('se_event_publish','Veröffentlichen',[$this,'box_publish'],'se_event','side','high');
        // KI-Feld für SE und RCE
        add_meta_box('se_event_ki', 'KI',[$this, 'box_ki'],'se_event','side','high');
        if( $stat['EType'] == 'se' || $stat['EType'] == 'rce'){
            add_meta_box('se_event_when','Wann',[$this,'box_when'],'se_event','side');

        }
        add_meta_box('se_event_categories','Veranstaltungskategorien',[$this,'box_categories'],'se_event','side');
        add_meta_box('se_event_owner', 'Event Owner', [$this,'box_owner'], 'se_event', 'side');
        if($this->se_debug_flag) {
        add_meta_box('se_event_structure','Event Struktur',[$this,'box_structure'],'se_event','side');
        }
        add_meta_box('se_event_location', 'Wo', [$this,'box_location'], 'se_event', 'normal');
        
       
        if( $stat['EType'] == 'rpe'){
            // render rce events liste
            add_meta_box('se_event_instances','Event Instanzen',[$this,'box_instances'],'se_event','normal');
            // render rpe enent liste
            add_meta_box('se_event_recurrence', 'Event Recurrences', [$this,'box_recurences'], 'se_event', 'normal');
            }
        ?>
        <div class="wrap">
        <h1 class="wp-heading-inline">Event Editor</h1>
        <a href="<?php echo $link_back; ?>" class="page-title-action"><p>Zurück zur Veanstaltungsliste</p></a>
        <hr class="wp-header-end">
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
        <?php wp_nonce_field('se_save_event'); ?>
        <!-- ==============================
             NEU: Nonce für Kategorien AJAX
             ============================== -->
        <?php wp_nonce_field( 'add-term', '_ajax_nonce-add-term' ); ?>
        <!-- MetaBox Speicherung -->
        <?php wp_nonce_field('meta-box-order','meta-box-order-nonce'); ?>
        <?php wp_nonce_field('closedpostboxes','closedpostboxesnonce'); ?>
        <input type="hidden" name="action" value="se_save_event">
        <input type="hidden" name="event_id" value="<?php echo $this->event->event_id ?>">
        <div id="poststuff">
        <div id="post-body" class="metabox-holder columns-2">
        <div id="post-body-content">
        <div id="titlediv">
        <input type="text"
        name="event_name"
        value="<?php echo esc_attr($this->event->event_name) ?>"
        style="width:100%;font-size:1.7em">
        <?php
        wp_editor(
            $this->event->post_content,
            'post_content',
            [
                'textarea_name'=>'post_content', 
                  /* entfernt "Medien hinzufügen" */
                'media_buttons' => false
            ]
        );
        
        ?>
        </div>
        </div>
        <div id="postbox-container-1" class="postbox-container">
        <?php do_meta_boxes('se_event','side',$this->event); ?>
        </div>
        <div id="postbox-container-2" class="postbox-container">
        <?php do_meta_boxes('se_event','normal',$this->event); ?>
        </div>
        </div>
        </div>
        </form>
        </div>
        <?php
        // save boxen position im BE
        require_once ABSPATH.'wp-admin/admin-footer.php';
        ?>
        <script>
            jQuery(function($){
                // WP MetaBox System initialisieren
                postboxes.add_postbox_toggles('se_event');
            });
        </script>
        <?php
    }
    /**
     * KI-Metafeld anzeigen und bearbeiten
     */
    public function box_ki() {

        if (empty($this->event->post_id)) {
            echo '<p>Kein zugehöriger WordPress-Beitrag gefunden.</p>';
            return;
        }

        $post_id = (int) $this->event->post_id;

        // KI-Metafeld bei Bedarf leer anlegen
        if (!metadata_exists('post', $post_id, 'KI')) {
            add_post_meta($post_id, 'KI', '', true);
        }

        // Vorhandenen Wert laden
        $ki = get_post_meta($post_id, 'KI', true);
        ?>

        <p>
            <label for="se_event_ki"><strong>KI</strong></label>
        </p>

        <textarea
            id="se_event_ki"
            name="event_ki"
            rows="3"
            style="width:100%;"
        ><?php echo esc_textarea($ki); ?></textarea>

        <p class="description">
            KI-Wert für dieses Event.
        </p>

        <?php
    }

    public function box_recurences() {
       $rpe_event = new SE_Recurrence_UI_Plugin($this->post_id);
       $rpe_event->assets();
       $rpe_event->render();            
    }

    public function box_location() {

        global $wpdb;

        $locations_table = $wpdb->prefix . 'se_locations';

        $locations = $wpdb->get_results(
            "SELECT location_id, location_name
            FROM {$locations_table}
            ORDER BY location_name"
        );

        $selected_location = (int) ($this->event->location_id ?? 0);

        ?>

        <p>
            <strong>Veranstaltungsort</strong>
        </p>

        <select
            name="location_id"
            id="se_location_select"
            style="width:100%;"
        >
            <option value="0">— Veranstaltungsort auswählen —</option>

            <?php foreach ($locations as $location): ?>

                <option
                    value="<?php echo esc_attr($location->location_id); ?>"
                    <?php selected(
                        $selected_location,
                        $location->location_id
                    ); ?>
                >
                    <?php echo esc_html($location->location_name); ?>
                </option>

            <?php endforeach; ?>

        </select>

        <hr>

        <p style="margin-top:10px;">
            <label>
                <input
                    type="checkbox"
                    id="se_new_location_cb"
                    name="create_new_location"
                    value="1"
                >

                Neuen Veranstaltungsort anlegen
            </label>
        </p>

        <div
            id="se_new_location_wrap"
            style="display:none;"
        >

            <input
                type="text"
                name="new_location_name"
                id="se_new_location_name"
                placeholder="Name des neuen Veranstaltungsortes"
                style="width:100%;"
            >

        </div>

        <script>
        jQuery(function($) {

            $('#se_new_location_cb').on('change', function() {

                const checked = $(this).is(':checked');

                $('#se_new_location_wrap').toggle(checked);

                $('#se_location_select').prop(
                    'disabled',
                    checked
                );

                if (checked) {
                    $('#se_new_location_name').focus();
                }

            });

        });
        </script>

        <?php
    }

   
    /* ==============================
       PUBLISH BOX
       ============================== */

    public function box_publish(){

        $post = get_post($this->event->post_id);
        $status = $post->post_status;
        ?>

        <p>Status: <strong><?php echo esc_html($status); ?></strong></p>

        <select name="post_status">
        <option value="publish" <?php selected($status,'publish'); ?>>Veröffentlicht</option>
        <option value="draft" <?php selected($status,'draft'); ?>>Entwurf</option>
        <option value="trash" <?php selected($status,'trash'); ?>>Papierkorb</option>
        </select>

        <p style="margin-top:10px;">
        <input type="submit" class="button button-primary" value="Speichern">
        </p>
        <!-----  Thumbnail added   -->
        <?php
        if (has_post_thumbnail($this->event->post_id)) : ?>
            <div style="padding:20px;text-align:center;">
                <?php
                echo get_the_post_thumbnail(
                    $this->event->post_id,
                    'medium',
                    [
                        'style' =>
                            'max-width:100%;height:auto;border-radius:4px;'
                    ]
                );
                ?>
            </div>
            <?php endif;
    }

    public function box_when(){
        ?>
        <div class="se-row">
        <label>Startdatum</label>
        <input
            type="date"
            name="event_start_date"
            value="<?php echo esc_attr(
                $this->event->event_start_date
            ); ?>"
        >
        </div>
        <!---
        <div class="se-row">
            <label>Enddatum</label>
            <input
                type="date"
                name="event_end_date"
                value="<?php echo esc_attr(
                    $this->event->event_end_date
                ); ?>"
            >
        </div>
            -->
        <div class="se-row">
            <label>Startzeit</label>
            <input
                type="time"
                name="event_start_time"
                value="<?php echo esc_attr(
                    $this->event->event_start_time
                ); ?>"
            >
        </div>
        <div class="se-row">
            <label>Endzeit</label>
            <input
                type="time"
                name="event_end_time"
                value="<?php echo esc_attr(
                    $this->event->event_end_time
                ); ?>"
            >
        </div>
        <script>

            jQuery(function($){

                const $start = $('input[name="event_start_time"]');

                const $end = $('input[name="event_end_time"]');

                // =====================================
                // 🔹 Zeit + Stunden helper
                // =====================================

                function addHour(time, hours){

                    if(!time){
                        return time;
                    }

                    let parts = time.split(':');

                    let h = parseInt(parts[0], 10);

                    let m = parts[1];

                    h += hours;

                    // 24h handling

                    if(h < 0){
                        h = 23;
                    }

                    if(h > 23){
                        h = 0;
                    }

                    return String(h).padStart(2, '0')
                        + ':'
                        + m;
                }

                // =====================================
                // 🔹 Start geändert
                // =====================================

                $start.on('change', function(){

                    const start = $start.val();

                    const end = $end.val();

                    if(end < start){

                        $end.val(
                            addHour(start, 1)
                        );
                    }
                });

                // =====================================
                // 🔹 Ende geändert
                // =====================================

                $end.on('change', function(){

                    const start = $start.val();

                    const end = $end.val();

                    if(end < start){

                        $start.val(
                            addHour(end, -1)
                        );
                    }
                });

            });

            </script>
        <?php
    }

    public function box_structure(){
     
        foreach ($this->stat as $key => $value) {

            if ($value === null) {
                $value = 'NULL'; // schöner anzeigen
            }
            echo "<p><strong>$key :</strong> $value</p>";
        }
    }

    public function box_instances($post) {

        global $wpdb;

        if (empty($this->event)) {
            echo '<p>Kein Event geladen</p>';
            return;
        }

        if (empty($this->event->event_id)) {
            echo '<p>Keine Event ID</p>';
            return;
        }

        $instances = $wpdb->get_results(
            $wpdb->prepare(
                "
                SELECT *
                FROM {$this->table}
                WHERE recurrence_id = %d
                ORDER BY event_start_date
                ",
                (int) $this->event->event_id
            )
        );
        
        if (empty($instances)) {
            echo "<p>Keine Instanzen vorhanden</p>";
            return;
        }

        echo "<table class='wp-list-table widefat fixed striped'>";

        foreach ($instances as $i) {
            // Abfrage nur event_type == 'rce' zulassen
            if($i->event_type == 'rce') {
                $url = admin_url(
                    "admin.php?page=se-events&event_action=edit&event_id=" . $i->event_id
                );
                echo "<tr>";
                echo "<td><a href='$url'>{$i->event_name}</a></td>";
                echo "<td>{$i->event_start_date}</td>";
                echo "<td>{$i->event_start_time}</td>";
                echo "<td>{$i->event_end_date}</td>";
                echo "<td>{$i->event_end_time}</td>";
                echo "</tr>";
            }
        }

        echo "</table>";
    }

    /**
     * Kategorien
     */
    public function box_categories(){

      
    
        $post = get_post($this->event->post_id);
        $status = $post->post_status;
        /* ==============================
           NEU: WP Kategorie MetaBox
           ============================== */

        $box = [
            'id'   => 'event-categoriesdiv',
            'title'=> 'Veranstaltungskategorien',
            'args' => [
                'taxonomy' => 'event-categories'
            ]
        ];
        if (!function_exists('post_categories_meta_box')) {
            require_once ABSPATH . 'wp-admin/includes/meta-boxes.php';
        }
        post_categories_meta_box($post, $box);

        echo '<p>
        <a href="'.admin_url(
        'edit-tags.php?taxonomy=event-categories'
        ).'">
        Kategorien verwalten
        </a>
        </p>';
    }

    /**
     * Speichern
     */
    public function save_event(){

        global $wpdb;
        if(!current_user_can('manage_options'))
            wp_die("Keine Berechtigung");
        check_admin_referer('se_save_event');
             
        $event_id = intval($_POST['event_id']);
        /* zuerst post_id holen */
        $post_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT post_id FROM {$this->table} WHERE event_id=%d",
                $event_id
            )
        );
        if(!$post_id)
            wp_die("Post nicht gefunden");
        /* global post setzen */
        global $post;
        $post = get_post($post_id);
        /* Autosave Schutz */
        if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            return;
        /* Event Tabelle */
          /* WP Post */

        wp_update_post([
            'ID'=>$post_id,
            'post_title'=>sanitize_text_field($_POST['event_name']),
            'post_content'=>wp_kses_post($_POST['post_content']),
            'post_status'=>sanitize_text_field($_POST['post_status'])
        ]);
        

        /*
        * KI-Metafeld speichern
        */
        if (isset($_POST['event_ki'])) {
            $ki = sanitize_textarea_field(wp_unslash($_POST['event_ki']));

            // KI-Wert des aktuellen Events speichern
            update_post_meta($post_id, 'KI', $ki);

            // Event-Typ ermitteln
            $event_type = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT event_type
                    FROM {$this->table}
                    WHERE event_id = %d",
                    $event_id
                )
            );

            // Bei einem RPE den KI-Wert auf alle zugehörigen RCE übertragen,
            // auch wenn der Wert leer ist.
            if ($event_type === 'rpe') {
                $rce_events = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT post_id
                        FROM {$this->table}
                        WHERE recurrence_id = %d
                        AND event_type = %s",
                        $event_id,
                        'rce'
                    )
                );

                if ($rce_events) {
                    foreach ($rce_events as $rce) {
                        $rce_post_id = (int) $rce->post_id;

                        if ($rce_post_id > 0) {
                            update_post_meta($rce_post_id, 'KI', $ki);
                        }
                    }
                }
            }
        }

       
        /*
        * Veranstaltungsort
        */
        $location_id = intval($_POST['location_id'] ?? 0);

        $create_new_location = !empty($_POST['create_new_location']);

        $new_location_name = sanitize_text_field(
            $_POST['new_location_name'] ?? ''
        );
        /*
        * Neuer Veranstaltungsort
        */
        if ($create_new_location) {

            if ($new_location_name === '') {
                wp_die('Bitte einen Namen für den neuen Veranstaltungsort eingeben.');
            }

            $locations_table = $wpdb->prefix . 'se_locations';

            $location_slug = sanitize_title($new_location_name);

            $inserted = $wpdb->insert(
                $locations_table,
                [
                    'post_id'          => $post_id,
                    'location_slug'    => $location_slug,
                    'location_name'    => $new_location_name,
                    'location_owner'   => get_current_user_id(),
                    'location_status'  => 1,
                    'location_private' => 0,
                ],
                [
                    '%d',
                    '%s',
                    '%s',
                    '%d',
                    '%d',
                    '%d',
                ]
            );

            if ($inserted === false) {

                wp_die(
                    'Fehler beim Anlegen des Veranstaltungsortes: ' .
                    esc_html($wpdb->last_error)
                );
            }

            /*
            * ID des gerade angelegten Veranstaltungsortes
            */
            $location_id = (int) $wpdb->insert_id;
        }


        /*
        * Event speichern
        */
        $updated = $wpdb->update(
            $this->table,
            [
                'event_name'   => sanitize_text_field(
                    $_POST['event_name'] ?? ''
                ),

                'post_content' => wp_kses_post(
                    $_POST['post_content'] ?? ''
                ),

                'location_id'  => $location_id,
            ],
            [
                'event_id' => $event_id
            ],
            [
                '%s',
                '%s',
                '%d',
            ],
            [
                '%d',
            ]
        );

        if ($updated === false) {

            wp_die(
                'Fehler beim Speichern des Events: ' .
                esc_html($wpdb->last_error)
            );
        } 
        


        //-----datum und uhrzeit darf nur in mode se und rce --------
        $event_type = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT event_type
                    FROM {$wpdb->prefix}se_events
                    WHERE post_id = %d",
                    $post_id
                )
            );
   
        if($event_type != 'rpe') {
            $event_start_date = sanitize_text_field(
                $_POST['event_start_date'] ?? ''
            );
            $event_end_date = $event_start_date;
            $event_start_time = sanitize_text_field(
                $_POST['event_start_time'] ?? ''
            );
            $event_end_time = sanitize_text_field(
                $_POST['event_end_time'] ?? ''
            );
            $event_start = '';
            $event_end   = '';
            if($event_start_date && $event_start_time){
                $event_start =
                    $event_start_date . ' ' . $event_start_time . ':00';
            }
            if($event_end_date && $event_end_time){
                $event_end =
                    $event_end_date . ' ' . $event_end_time . ':00';
            }
            $wpdb->update(
                $this->table,
                [
                    'event_start_date' => $event_start_date,
                    'event_end_date'   => $event_end_date,
                    'event_start_time' => $event_start_time,
                    'event_end_time'   => $event_end_time,
                    // synchronisierte Datetime-Felder
                    'event_start'      => $event_start,
                    'event_end'        => $event_end
                ],
                ['event_id' => $event_id]
            );
        }
        /* Kategorien */
        if(!empty($_POST['tax_input'])){
            foreach($_POST['tax_input'] as $taxonomy=>$terms){
                wp_set_post_terms(
                    $post_id,
                    array_map('intval',$terms),
                    $taxonomy
                );
            }
        }
        /* Redirect */  


    
        wp_safe_redirect(
          admin_url("admin.php?page=se-events&event_action=edit&event_id=".$event_id."&updated=1")
        );        
       exit;

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
        $event_start_date = date('Y-m-d', $start);
        $event_start_time = date('H:i',$start);
        // event_end zerlegen
        $end = strtotime($event->event_end);
        $event_end_date = date('Y-m-d',$end);
        $event_end_time = date('H:i',$end);
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

new SE_Event_Editor();

class SE_GET_EVENT_TYP {
     protected $table ; 
     protected $table_post ; 

    protected $post_id;   // 🔥 NEU
    protected $event_id;  // 🔥 NEU
    protected $event;
    protected $post;

    protected $stat;
    protected $event_type;
    

    public function __construct($post_id) {
      
     
        $this->post_id  = intval($post_id ?? 0);
        global $wpdb;
        $this->table = $wpdb->prefix.'se_events';
        $this->table_post = $wpdb->prefix.'posts';


        if($this->load_event()){
        $this->post_id = $this->event->post_id;
        $this->event_id = $this->event->event_id;
        }
     
    
    }
  
    public function load_event(): bool {

        global $wpdb;

        $post_id  = (int) ($this->post_id ?: ($_GET['post_id'] ?? 0));
        $event_id = (int) ($this->event_id ?: ($_GET['event_id'] ?? 0));

        // ==========================
        // 🔹 Laden über event_id
        // ==========================
        if ($event_id > 0) {

            $this->event = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT *
                    FROM {$this->table}
                    WHERE event_id = %d",
                    $event_id
                )
            );

            if (!$this->event) {
                return false;
            }

            $this->event_id = (int) $this->event->event_id;
            $this->post_id  = (int) $this->event->post_id;

            $this->post = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT *
                    FROM {$this->table_post}
                    WHERE ID = %d",
                    $this->post_id
                )
            );

            return (bool) $this->post;
        }

        // ==========================
        // 🔹 Laden über post_id
        // ==========================
        if ($post_id > 0) {

            $this->post = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT *
                    FROM {$this->table_post}
                    WHERE ID = %d",
                    $post_id
                )
            );

            $this->event = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT *
                    FROM {$this->table}
                    WHERE post_id = %d",
                    $post_id
                )
            );

            $this->post_id = $post_id;

            if ($this->event) {
                $this->event_id = (int) $this->event->event_id;
            }

            return (bool) $this->post;
        }

        return false;
    }
        
    public function get_event_type(){
        $status = 'Fehlerhafte Event';
        $this->stat['Type'] = "Fehlerhafte Event Typ";
        $this->stat['EType'] = '';
        $this->event_type ='na';
          if (
                !is_object($this->post) ||
                !isset($this->post->ID)
            ) {
                $this->post = new stdClass();
                ?>
                <div class="notice notice-error se-error">
                <p>Falsche Post ID</p>
            </div>
            <style>
            .se-error p {
                color: red;
                font-size: 20px;
                font-weight: bold;
            }
            </style>
            <?php
                wp_die();
            }
      
       if (
            !is_object($this->event) ||
            !isset($this->event->post_id)
        ) {
            $this->event = new stdClass();
            $this->event->post_id = 1;
             if (
                !is_object($this->post) ||
                !isset($this->post->ID)
            ) {
                $this->post = new stdClass();
                ?>
                <div class="notice notice-error se-error">
                <p>Falsche Event ID</p>
            </div>
            <style>
            .se-error p {
                color: red;
                font-size: 20px;
                font-weight: bold;
            }
            </style>
            <?php
                wp_die();
            }
            wp_die();
        }
       
        $recurrence = 0;
        $recurrence_id = 0;
        if ($this->post->ID == $this->event->post_id){
            $recurrence = (int)$this->event->recurrence;
            $recurrence_id = (int)$this->event->recurrence_id;
                $status = 'Event und Post ok';
                if ($recurrence === 0 && $recurrence_id === 0) {
                    $this->stat['Type'] = "Single Event";
                    $this->stat['EType'] = 'se';
                } elseif ($recurrence === 1 && $recurrence_id === 0) {
                    $this->stat['Type'] = 'Recurrence Parent Event';
                    $this->stat['EType'] = 'rpe';
                } elseif ($recurrence === 0 && $recurrence_id > 0) {
                    $this->stat['Type'] = 'Recurrence Event';
                    $this->stat['EType'] = 'rce';
                }      
        }
        $this->stat['Status'] = $status;
        $this->stat['PostID'] = $this->post_id;
        $this->stat['EventID'] = $this->event_id;
        $this->stat['Recurrent'] = $recurrence;
        $this->stat['RecurrentID'] = $recurrence_id;
       // $message = print_r($this->stat, true);
       // echo '<div class="notice notice-success"><p>' . esc_html($message) . '</p></div>';
      //   global $db_se;     
      // $db_se->se_debug($this->stat);
    }
    public function get_stat() {
        return $this->stat;
    }
     public function get_event() {
        return $this->event;
    }
    public function get_post() {
        return $this->post;
    }
   



}
class SE_get_stat_even_post {
    protected $stat;
    protected $event;
    protected $post;
    public function __construct($post_id) {
        $ev_post = new SE_GET_EVENT_TYP($post_id);
        $this->stat = $ev_post->get_stat();
        $this->event = $ev_post->get_event();
        $this->post = $ev_post->get_post();
    }
}