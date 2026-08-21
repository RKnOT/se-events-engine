<?php
defined('ABSPATH') || exit;

class SE_Events_Database_Copy {

    /* =========================
       EVENTS KOPIEREN
       ========================= */
    public function __construct() {
        $this->run();
        #ohne den record
        #$this->add_papierkorb_status();
        $stat = $this->em_migrate_events_to_se_event();
        $this->rename_events_posts_type();
        


    }
    function em_migrate_events_to_se_event() {

        global $wpdb;

        $posts_table     = $wpdb->posts;
        $se_events_table = SE_EVENTS_TABLE;

        $updated_posts = $wpdb->query(
            $wpdb->prepare("
                UPDATE {$posts_table} p
                INNER JOIN {$se_events_table} e ON p.ID = e.post_id
                SET p.post_type = %s
                WHERE p.post_type = %s
            ", 'se_event', 'event')
        );
   
      
 
        $updated_events = $wpdb->query(
            $wpdb->prepare("
                UPDATE {$posts_table} p
                INNER JOIN {$se_events_table} e ON p.ID = e.post_id
                SET e.event_type = %s
                WHERE e.event_type = %s
            ", 'se_event', 'event')
        );

        # Add Ferien record to db 
        $exists = $wpdb->get_var("
            SHOW COLUMNS
            FROM {$se_events_table}
            LIKE 'exclude_ranges'
        ");

        if (!$exists) {

            $wpdb->query("
                ALTER TABLE {$se_events_table}
                ADD COLUMN exclude_ranges LONGTEXT NULL
            ");

        }

        return [
            'posts_updated'  => $updated_posts,
            'events_updated' => $updated_events
        ];
    }   

    public function rename_events_posts_type() {
        global $wpdb;
        $events_table = SE_EVENTS_TABLE;
        $posts_table = POSTS_TABLE;
               
        $count_posts = $this->get_count($posts_table, 'post_type');
        $count_events = $this->get_count($events_table, 'event_type');
        echo 'post recordes count = ' .  $count_posts . ' </br>';
        echo 'event recordes count = ' . $count_events . ' </br>';

        $comon_count  = $this->get_count_same_posts_events($events_table,  $posts_table);

     
        #echo 'same posts id / events id count = ' . $comon_count . ' </br>';
        #echo 'post_id' . ';'  . 'post_type' . ';' . 'event_type' . ';' . 'recurrence' .'</br>';
        #foreach($comon_count as $x) {

        #echo $x['post_id'] . ';'  . $x['post_type'] . ';' . $x['event_type']  . '</br>';
        
    }
    private function get_count_same_posts_events($events_table, $posts_table) {
          global $wpdb;


          $select_01 = 'SELECT COUNT(*)';
          $select_02 = 'SELECT p.ID AS post_id, p.post_type';
          $select_03 = 'SELECT p.ID AS post_id, e.event_type, p.post_type, e.recurrence, e.recurrence_id';
          
          $sql = "
            #SELECT p.ID AS post_id, p.post_type
           {$select_01}
            FROM {$events_table} e
            INNER JOIN {$posts_table} p
             ON e.post_id = p.ID
             
           ";
        return $wpdb->get_var($wpdb->prepare($sql));
        #return $wpdb->get_results($sql, ARRAY_A);
        #return $wpdb->get_col($sql);
        
    }



    private function get_count($table, $type, $search = 'event') {
        #echo $table . ' ' . $type . ' ' . $search . '</br>';
        global $wpdb;
        $table  = esc_sql($table);
        $type = esc_sql($type);
        $search =  esc_sql($search);
        $sql = "
            SELECT COUNT(*)
            FROM {$table}
            WHERE {$type} = %s
        ";

        return (int) $wpdb->get_var(
            $wpdb->prepare($sql, $search)
        );

    }

    public function run(): void {
        global $wpdb;

        $tables = [
            'events',
            'locations',
            'event_recurrences',
            'meta',
        ];

        foreach ($tables as $table) {
            $source = $wpdb->prefix . 'em_' . $table;
            $target = $wpdb->prefix . 'se_' . $table;

            $exists = $wpdb->get_var(
                $wpdb->prepare("SHOW TABLES LIKE %s", $source)
            );

            if ($exists !== $source) {
                continue;
            }

            $wpdb->query("DROP TABLE IF EXISTS `{$target}`");
            $wpdb->query("CREATE TABLE `{$target}` LIKE `{$source}`");
            $wpdb->query("INSERT INTO `{$target}` SELECT * FROM `{$source}`");
        }
    }
    public function add_papierkorb_status() {
             global $wpdb;

            $table_name = SE_EVENTS_TABLE;

            // Prüfen ob Spalte bereits existiert
            $column_exists = $wpdb->get_results(
                $wpdb->prepare(
                    "SHOW COLUMNS FROM {$table_name} LIKE %s",
                    'deleted_at'
                )
            );

            if (empty($column_exists)) {
                $wpdb->query(
                "ALTER TABLE {$table_name} 
                ADD COLUMN post_status VARCHAR(20) NOT NULL DEFAULT 'neutral'"
                );
            }  
               
        
    }
   
}
