<?php


/*
UPDATE wp_se_events
SET recurrence_id = 0
WHERE recurrence_id IS NULL;



*/
class Rename_event_type_se_rpe_rcp {

    private static string $table_events;
    
    public static function init() {
        global $wpdb;
        self::$table_events = SE_EVENTS_TABLE;

        $updated = $wpdb->query("
     UPDATE " . self::$table_events . "
    SET event_type = CASE
        WHEN recurrence = 0 AND (recurrence_id IS NULL OR recurrence_id = 0) THEN 'se'
        WHEN recurrence = 1 AND (recurrence_id IS NULL OR recurrence_id = 0) THEN 'rpe'
        WHEN recurrence = 0 AND (recurrence_id IS NOT NULL AND recurrence_id != 0) THEN 'rce'
        ELSE event_type
    END
    WHERE
        (recurrence = 0 AND (recurrence_id IS NULL OR recurrence_id = 0) AND event_type <> 'se')
     OR (recurrence = 1 AND (recurrence_id IS NULL OR recurrence_id = 0) AND event_type <> 'rpe')
     OR (recurrence = 0 AND (recurrence_id IS NOT NULL AND recurrence_id != 0) AND event_type <> 'rce')
");

    }
}




class Rename_event_type {

    private static string $table_events;

    public static function init() {
        global $wpdb;
        self::$table_events = SE_EVENTS_TABLE;
    }

    /**
     * @param bool $dry_run → true = nur anzeigen, false = wirklich updaten
     */
    public static function rename(bool $dry_run = false) {
        global $wpdb;

        // 👉 vorher zählen
        $before = $wpdb->get_var("
            SELECT COUNT(*)
            FROM " . self::$table_events . "
            WHERE event_type <> 'se_event'
               OR event_type IS NULL
        ");

        echo "<pre>";
        echo "Vorher betroffen: $before\n";

        // 👉 Preview anzeigen
        $preview = $wpdb->get_results("
            SELECT id, event_type
            FROM " . self::$table_events . "
            WHERE event_type <> 'se_event'
               OR event_type IS NULL
            LIMIT 10
        ");

        echo "Preview (erste 10):\n";
        print_r($preview);

        // 👉 wenn Dry Run → hier stoppen
        if ($dry_run) {
            echo "\n[DRY RUN] Keine Änderungen durchgeführt ❗";
            echo "</pre>";
            return;
        }

        // 👉 echtes UPDATE
        $updated = $wpdb->query("
            UPDATE " . self::$table_events . "
            SET event_type = 'se_event'
            WHERE event_type <> 'se_event'
               OR event_type IS NULL
        ");

        // 👉 nachher zählen
        $after = $wpdb->get_var("
            SELECT COUNT(*)
            FROM " . self::$table_events . "
            WHERE event_type <> 'se_event'
               OR event_type IS NULL
        ");

        echo "\nGeändert: $updated\n";
        echo "Nachher übrig: $after\n";
        echo "</pre>";
    }
}



class SE_Stat_Event {

    private static string $table_events;
    private static string $table_posts;

    // Initialisierung (einmal aufrufen!)
    public static function init() {
        global $wpdb;
        self::$table_events = SE_EVENTS_TABLE;
        self::$table_posts  = $wpdb->posts;
    }

    /**
     * Alle "nicht-recurrence" Events holen
     */
    public static function get_base_events() {
        global $wpdb;

        return $wpdb->get_results("
            SELECT *
            FROM " . self::$table_events . " e
            LEFT JOIN " . self::$table_posts . " p
                ON e.event_id = p.ID
            WHERE e.recurrence = 0
              AND e.recurrence_id IS NULL
        ");
    }

    /**
     * Anzahl dieser Events
     */
    public static function count_base_events() {
        global $wpdb;

        return (int) $wpdb->get_var("
            SELECT COUNT(*)
            FROM " . self::$table_events . " e
            LEFT JOIN " . self::$table_posts . " p
                ON e.event_id = p.ID
            WHERE e.recurrence = 0
              AND e.recurrence_id IS NULL
        ");
    }
}

class SE_Stats {

    private string $db_se_events;
    private string $db_posts;


    public function __construct() {

        $this->db_se_events = 'wp_se_events'; 
        $this->db_posts = 'wp_posts'; 
        $this->get_stats();

    }
    public function get_stats() {
         global $wpdb;
        $se_count = $wpdb->get_var("
         SELECT COUNT(*)
         FROM $this->db_se_events e
        LEFT JOIN $this->db_posts p
         ON e.event_id = p.ID
          WHERE e.recurrence = 0
                AND e.recurrence_id IS NULL

        ");

        $se_count = (string) $se_count;
      

        $rep_count = $wpdb->get_var("
         SELECT COUNT(*)
         FROM $this->db_se_events e
        LEFT JOIN $this->db_posts p
         ON e.event_id = p.ID
       WHERE e.recurrence = 1
        AND e.recurrence_id IS NULL

        ");
        $rep_count = (string) $rep_count;
        


            $re_count = $wpdb->get_var("
         SELECT COUNT(*)
         FROM $this->db_se_events e
        LEFT JOIN $this->db_posts p
         ON e.event_id = p.ID
      WHERE e.recurrence = 0
        AND e.recurrence_id IS NOT NULL

        ");
        $re_count = (string) $re_count;






           echo '<div class="notice notice-success"><p>events counts.</p>';
            echo '<table class="widefat striped">';
            echo '<tr>';
            echo   '<th>single events</th>';
            echo  '<th>parent recurring events</th>';
            echo  '<th>recurring events</th>';
            echo '</tr>';
            echo '<tr>';
            echo   '<th>' . $se_count . '</th>';
            echo   '<th>' . $rep_count . '</th>';
            echo   '<th>' . $re_count . '</th>';
           
            echo '</tr></table>';
    
        echo '</div>';
    }

}