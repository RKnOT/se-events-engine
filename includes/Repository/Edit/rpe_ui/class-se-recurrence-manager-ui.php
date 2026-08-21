<?php
if (!defined('ABSPATH')) exit;

// Ferien zentral
 add_action('wp_ajax_save_holiday_templates', 'save_holiday_templates');

    function save_holiday_templates()
    {
        $templates = $_POST['templates'] ?? [];

        update_option('se_holiday_templates', $templates);

        wp_send_json_success();
    }

wp_enqueue_style(
            'se-rpe-ui-style',
            SE_EVENTS_URL . 'includes/Repository/Edit/rpe_ui/assets/css/style_rpe.css',
            [],
            '1.1'
        );


class SE_RPE_Manager_UI extends SE_get_RPE_stat_event_post {
    protected $event;
    public function __construct($post_id) {


        parent::__construct($post_id);
        $this->event = $this->get_event();
        if (!$this->event) {
            return;
        }
        $this->event_id = $this->event->event_id;
        add_action(
            'admin_enqueue_scripts',
            [$this, 'enqueue_assets']
        );

    }

    private function getExcludeRanges(): array
    {
        if (empty($this->event->exclude_ranges)) {
            return [];
        }

        $ranges = json_decode($this->event->exclude_ranges, true);

        return is_array($ranges) ? $ranges : [];
    }

    public function enqueue_assets()
    {
      
        wp_enqueue_script(
            'se-rec-ui-js',
            SE_EVENTS_URL . 'includes/Repository/Edit/rpe_ui/assets/js/editor_rpe.js',
            ['jquery'],
            '1.1',
            true
        );
        wp_localize_script(
            'se-rec-ui-js',
            'SE_REC_UI',
            [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'adminurl' => admin_url(),
                'nonce' => wp_create_nonce('se_recurrence_nonce'),
                'event_id' => $this->event->event_id,
                'start_date' => $this->event->event_start_date,
                'end_date' => $this->event->event_end_date,
                'start_time' => $this->event->event_start_time,
                'end_time' => $this->event->event_end_time,
                'interval' => $this->event->recurrence_interval,
                'recurrence_type' =>$this->event->recurrence_freq,
                'days' => $this->event->recurrence_byday,
                'exclude_ranges' => $this->getExcludeRanges(),
            ]
        );
    }
  
    public function render_ui() {

        if (!$this->event) {
            echo '<div class="notice notice-error"><p>Event nicht gefunden.</p></div>';
            return;
        }
        $this->enqueue_assets();
        ?>
            <div class="se-box">
                    <div class="se-content">
                        <h3>Datum</h3>
                        <div class="se-row">
                            <label class="radio-wrap">
                                Start:
                                <input type="date"
                                    id="start_date"
                                    value="<?php echo esc_attr($this->event->event_start_date); ?>">    
                            </label>
                            <label class="radio-wrap">              
                                Ende: 
                            </label>
                            <input type="date"
                                id="end_date"
                                value="<?php echo esc_attr($this->event->event_end_date); ?>">
                        </div>
                        <h3>Uhrzeit</h3>
                        <div class="se-row">
                            <label>Start:</label>
                            <input type="time"
                                id="start_time"
                                value="<?php echo esc_attr($this->event->event_start_time); ?>">
                            <label>End:</label>
                            <input type="time"
                                id="end_time"
                                value="<?php echo esc_attr($this->event->event_end_time); ?>">
                        </div>
                        <h3>Wiederholungen</h3>
                        <?php
                        $recurrence_freq = $this->event->recurrence_freq ?? 'weekly';
                        $recurrence_interval =
                            $this->event->recurrence_interval ?? 1;
                        ?>
                        <div class="se-row recurrence-row">
                            <label class="radio-wrap">
                                <input
                                    type="radio"
                                    name="recurrence_type"
                                    value="daily"
                                    <?php checked(
                                        $recurrence_freq,
                                        'daily'
                                    ); ?>
                                >
                                Daily
                            </label>
                            <label class="radio-wrap">
                                <input
                                    type="radio"
                                    name="recurrence_type"
                                    value="weekly"
                                    <?php checked(
                                        $recurrence_freq,
                                        'weekly'
                                    ); ?>
                                >
                                Weekly
                            </label>
                            <label class="radio-wrap">
                                <input
                                    type="radio"
                                    name="recurrence_type"
                                    value="monthly"
                                    <?php checked(
                                        $recurrence_freq,
                                        'monthly'
                                    ); ?>
                                >
                                Monthly
                            </label>
                            <label class="radio-wrap">
                            <input
                                type="number"
                                id="interval"
                                value="<?php echo esc_attr(
                                    $recurrence_interval
                                ); ?>"
                            >
                            Interval 
                            </label>
                        </div>
                        <?php
                        $selected_days = [];
                        if (!empty($this->event->recurrence_byday)) {
                            $selected_days = explode(
                                ',',
                                $this->event->recurrence_byday
                            );
                        }        
                        echo '</br>';
                        ?>
                        <div class="radio-wrap">
                            <div
                                class="se-row"
                                id="weekly-days-wrap"
                            >
                            <label>
                                <input
                                    type="checkbox"
                                    name="days[]"
                                    value="1"
                                    <?php checked(
                                        in_array('1', $selected_days)
                                    ); ?>
                                >
                                Mon
                            </label>
                            <label>
                                <input
                                    type="checkbox"
                                    name="days[]"
                                    value="2"
                                    <?php checked(
                                        in_array('2', $selected_days)
                                    ); ?>
                                >
                                Tue
                            </label>
                            <label>
                                <input
                                    type="checkbox"
                                    name="days[]"
                                    value="3"
                                    <?php checked(
                                        in_array('3', $selected_days)
                                    ); ?>
                                >
                                Wed
                            </label>
                            <label>
                                <input
                                    type="checkbox"
                                    name="days[]"
                                    value="4"
                                    <?php checked(
                                        in_array('4', $selected_days)
                                    ); ?>
                                >
                                Thu
                            </label>
                            <label>
                                <input
                                    type="checkbox"
                                    name="days[]"
                                    value="5"
                                    <?php checked(
                                        in_array('5', $selected_days)
                                    ); ?>
                                >
                                Fri
                            </label>
                            <label>
                                <input
                                    type="checkbox"
                                    name="days[]"
                                    value="6"
                                    <?php checked(
                                        in_array('6', $selected_days)
                                    ); ?>
                                >
                                Sat
                            </label>
                            <label>
                                <input
                                    type="checkbox"
                                    name="days[]"
                                    value="0"
                                    <?php checked(
                                        in_array('0', $selected_days)
                                    ); ?>
                                >
                                Sun
                            </label>
                            </div>
                        </div>
                        <div class="button-row">
                            <button class="button button-primary" id="saveRecurrence">
                                Save Event
                            </button>
                    
                    </div>
                            <!--<pre id="se-debug-console"></pre> -->
                        </div> 
                        
            </div>
          

            <div class="se-box">
                <div class="se-box se-exclude-box">
                    <div class="se-content">
                        <div class="se-ferien_grid_header">
                                <span><h3>MGH-Ferien-Zeiten:</h3></span>
                                <span><h3>Schulferien-Zeiten:</h3></span>
                        </div>
                        <div class="se-ferien_grid" >
                            <span class="cb">Ja</span>
                            <span>Start</span>
                            <span>Ende</span>
                            <span class="copy">⇐</span>
                            <span>Vorlage Start</span>
                            <span>Vorlage Ende</span>
                        </div>

                        <?php
                        $templates = get_option('se_holiday_templates', []);
                        ?>

                        <?php for($i=0;$i<4;$i++): ?>

                            <div class="se-ferien_grid">

                                <!-- RPE -->
                                <input
                                    type="checkbox"
                                    id="exclude_enabled_<?php echo $i;?>"
                                >
                                <input
                                    type="date"
                                    id="exclude_start_<?php echo $i;?>"
                                >
                                <input
                                    type="date"
                                    id="exclude_end_<?php echo $i;?>"
                                >
                                <!-- nur zum Kopieren -->
                                <input
                                    type="checkbox"
                                    class="copy-template"
                                    data-row="<?php echo $i;?>">

                                <!-- zentrale Vorlage -->
                                <input
                                    type="date"
                                    class="template-start"
                                    data-row="<?php echo $i;?>"
                                    value="<?php echo esc_attr($templates[$i]['start'] ?? '');?>">

                                <input
                                    type="date"
                                    class="template-end"
                                    data-row="<?php echo $i;?>"
                                    value="<?php echo esc_attr($templates[$i]['end'] ?? '');?>">
                            </div>

                        <?php endfor; ?>

                        <div class="se-ferien_grid_header">
                             <span></span>
                            <button
                                type="button"
                                id="saveHolidayTemplates"
                                class="button button-primary">
                                Vorlagen speichern
                            </button>
                        </div>

                    </div>
                </div>

                <p style="padding-left:20px;">
                    developed by RKnOT
                </p>
            </div>
            
        <!----
        <div class="se-box">
               
                <div class="se-generated-wrap">
                    <h3>Vorhandene Events</h3>
                    <div id="se-generated-events">
                        <p>No generated events yet.</p>
                    </div>
                </div>
        </div>
        -->
        <?php

       
        
    }

    public static function ajax_save_rpe() {
        /*

                file_put_contents(
                    __DIR__ . '/ajax-test.txt',
                    'AJAX HIT'
                );
                error_log('AJAX RPE HIT');
        */
        check_ajax_referer(
            'se_recurrence_nonce',
           'nonce'
        );

        $event_post_data = [];
        /*    save event post                           */
        if (!empty($_POST['event_post_data'])) {

            $event_post_data = json_decode(
                stripslashes($_POST['event_post_data']),
                true
            );

            if (!is_array($event_post_data)) {
                wp_send_json_error([
                    'message' => 'Ungültige Event-UI-Daten'
                ]);
            }
        }
        $event_post_data = [
            'event_name' => sanitize_text_field(
                $event_post_data['event_name'] ?? ''
            ),

            'post_content' => wp_kses_post(
                $event_post_data['post_content'] ?? ''
            ),

            'post_status' => sanitize_text_field(
                $event_post_data['post_status'] ?? 'draft'
            ),

            'location_id' => intval(
                $event_post_data['location_id'] ?? 0
            )
        ];

        /*---------------------------------------------*/
        global $wpdb;


        # Ferienzeiten
    
        $exclude_ranges = [];
        if (!empty($_POST['exclude_ranges'])) {
            foreach ($_POST['exclude_ranges'] as $range) {
                if(!$range){ break;}
                $exclude_ranges[] = [
                      'enabled' => ($range['enabled'] === 'true'),
                    'start' => sanitize_text_field(
                        $range['start'] ?? ''
                    ),
                    'end' => sanitize_text_field(
                        $range['end'] ?? ''
                    )
                ];
            }
           
            
        }
     
        #---------------------------------

        $event_id = intval($_POST['event_id'] ?? 0);
        if (!$event_id) {
            wp_send_json_error([
                'message' => 'event_id missing'
            ]);
        }
        $days = '';
        if (!empty($_POST['days'])) {
            $days = implode(
                ',',
                array_map(
                    'intval',
                    $_POST['days']
                )
            );
        }
        // check UI same as db
        $table = SE_EVENTS_TABLE;
            /*
            ==========================
            DB EVENT LADEN
            ==========================
            */
            $current = $wpdb->get_row(

                $wpdb->prepare(
                    "SELECT
                        event_start_date,
                        event_end_date,
                        event_start_time,
                        event_end_time,
                        recurrence_freq,
                        recurrence_interval,
                        recurrence_byday
                    FROM $table
                    WHERE event_id = %d",
                    $event_id
                )
            );
            if (!$current) {
                wp_send_json_error([
                    'message' => 'Event not found'
                ]);
            }
            /*
            ==========================
            UI DATEN
            ==========================
            */
            $update = [
            'event_start_date' => sanitize_text_field($_POST['start_date']),
            'event_end_date' => sanitize_text_field($_POST['end_date']),
            'event_start_time' => sanitize_text_field($_POST['start_time']),
            'event_end_time' => sanitize_text_field($_POST['end_time']),
            'recurrence_freq' => sanitize_text_field($_POST['recurrence_type']),
            'recurrence_interval' => intval($_POST['interval']),
            'recurrence_byday' => $days,
            'exclude_ranges' => wp_json_encode($exclude_ranges)
            ];
        //==========================
        // UPDATE
        //==========================
        $updated = $wpdb->update(
            $table,
            $update,
            [
                'event_id' => $event_id
            ]  
        );
        /*
        ==========================
        RRULE UPDATE
        ==========================
        */
        $rrule_table =
            $wpdb->prefix . 'se_event_recurrences';
        // 🔹 bestehende RRULE laden
        $rrule_row = $wpdb->get_row(
            $wpdb->prepare(
                "
                SELECT *
                FROM {$rrule_table}
                WHERE event_id = %d
                ",
                $event_id
            )
        );
        $rrule = [];
        // 🔹 vorhandene RRULE dekodieren
        if ($rrule_row && !empty($rrule_row->rrule)) {
            $rrule = json_decode(
                $rrule_row->rrule,
                true
            );
        }
        // 🔹 neue Werte übernehmen
        $rrule['freq'] =
            sanitize_text_field(
                $_POST['recurrence_type']
            );
        $rrule['interval'] =
            intval($_POST['interval']);
        $rrule['days'] =
            array_map(
                'intval',
                $_POST['days'] ?? []
            );   
            // 🔹 speichern
        $wpdb->update(
            $rrule_table,
            ['rrule' => json_encode($rrule)],
            ['event_id' => $event_id]
        );
        /*
        ==========================
        FEHLER
        ==========================
        */
        if ($updated === false) {
            wp_send_json_error([
                'message' => $wpdb->last_error
            ]);
        }
        /*
        ==========================
        SUCCESS
        ==========================
        */
        $event_post_data = [];

        if (!empty($_POST['event_post_data'])) {

            $event_post_data = json_decode(
                stripslashes($_POST['event_post_data']),
                true
            );

            if (!is_array($event_post_data)) {
                wp_send_json_error([
                    'message' => 'Ungültige Event-UI-Daten'
                ]);
            }
        }

        $stat = 'Änderung vorgenommen';

        SE_Del_Gen_RCE_Event_Posts::regenerate_series(
            $event_id,
            $stat,
            $event_post_data
        );

      
                

    }
}
