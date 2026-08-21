<?php
defined('ABSPATH') || exit;

class SE_Events_Debug_Deleted {

    private string $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = SE_EVENTS_TABLE;
    }

    /**
     * Gibt alle Events zurück, die im Papierkorb sind
     */
    public function get_deleted_events(): array {
        global $wpdb;
        echo $this->table_name;

        $pt = POSTS_TABLE;


        $sql = "
            SELECT 
                ID,
                post_status,
                post_title,
                post_type
            FROM {$pt}
            WHERE post_status IS event
            ORDER BY deleted_at DESC
        ";

        var_dump($wpdb->get_results($sql, ARRAY_A));
        return $wpdb->get_results($sql, ARRAY_A) ?: [];
    }

    /**
     * Debug Ausgabe als Tabelle (optional)
     */
    public function debug_output(): void {
        $events = $this->get_deleted_events();

        if (empty($events)) {
            echo '<p>Keine gelöschten Events gefunden.</p>';
            return;
        }

        echo '<table class="widefat striped">';
        echo '<thead>
                <tr>
                    <th>Event ID</th>
                    <th>Post ID</th>
                    <th>Name</th>
                    <th>Deleted At</th>
                </tr>
              </thead>';
        echo '<tbody>';

        foreach ($events as $event) {
            echo '<tr>';
            echo '<td>' . esc_html($event['ID']) . '</td>';
            echo '<td>' . esc_html($event['post_status']) . '</td>';
            echo '<td>' . esc_html($event['post_title']) . '</td>';
            echo '<td>' . esc_html($event['post_type']) . '</td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
    }


    
}

class SE_Event_Debug_View {

    private string $events_table;

    public function __construct() {
        global $wpdb;
        $this->events_table = SE_EVENTS_TABLE;

       
    }



    public function render_page(): void {
  
        $post_id  = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
        $event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
        # var_dump($_GET);
        $this->show_BE($post_id, $event_id, $_GET);
         if (isset($_GET['page']) && $_GET['page'] === 'run_debug') {
        
           $this->run_debug($post_id, $event_id);
        }
    }

    public function show_BE($post_id, $event_id, $get) {
        echo '<div class="wrap">';
        echo '<h1>Get Event and Post by event_id oder post_id</h1>';

        echo '<form method="get">';
        echo '<input type="hidden" name="page" value="se-event-debug" />';
        echo '<table class="form-table">';
        echo '<tr>
                <th>Post ID</th>
                <td><input type="number" name="post_id" value="' . esc_attr($post_id) . '" /></td>
              </tr>';
        echo '<tr>
                <th>Event ID</th>
                <td><input type="number" name="event_id" value="' . esc_attr($event_id) . '" /></td>
              </tr>';
        echo '</table>';
        echo '<input type="hidden" name="page" value="' . esc_attr($_GET['page']) . '" />';

        echo '<input type="hidden" name="page" value="run_debug" />';
        submit_button('Ausführen');
        echo '</form>';
        echo '</div>';
    }

    public function run_debug($post_id, $event_id){
        global $wpdb;
    
        $where  = '';
        $params = [];
  
        if ($event_id > 0) {
            $where    = 'event_id = %d';
            $params[] = $event_id;
        } 
        elseif ($post_id > 0) {
            $where    = 'post_id = %d';
            $params[] = $post_id;
        } 

        $event = null;
        $event = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->events_table} WHERE $where LIMIT 1",
                $params
            ),
            ARRAY_A
        );

        $post = null;
        if ($event && !empty($event['post_id'])) {
            $post = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->posts} WHERE ID = %d",
                    $event['post_id']
                ),
                ARRAY_A
            );
        }
     
        if ($post) {
             echo '<div class="wrap">';
            echo '<h2>WP Post Tabelle (wp_posts)</h2>';
            echo '<table class="widefat striped">';

            foreach ($post as $key => $value) {
                echo '<tr>';
                echo '<td><strong>' . esc_html($key) . '</strong></td>';
                echo '<td>' . esc_html($value) . '</td>';
                echo '</tr>';
            }

            echo '</table>';
        } 
        if($post === null && $post_id >  0) {

          echo '<h2> Keinen Post gefunden mit ' .  $post_id . ' gefunden </h2>';
        }

        if ($event) {
           
            echo '<h2>Event Tabelle (wp_se_events)</h2>';
            echo '<table class="widefat striped">';

            foreach ($event as $key => $value) {
                echo '<tr>';
                echo '<td><strong>' . esc_html($key) . '</strong></td>';
                echo '<td>' . esc_html($value) . '</td>';
                echo '</tr>';
            }

            echo '</table>';
        }  
        if($event === null && $event_id > 0)  {
            echo '<h2> Keinen Event gefunden mit ' .  $event_id . ' gefunden </h2>';

        }
           echo '</div>';


    }
}


class SE_Event_Debug_Check_Trash_Post_SE_Events {
    
        

    private string $events_table;
    private string $posts_table;

    public function __construct() {
        $this->events_table = SE_EVENTS_TABLE;
        $this->posts_table = POSTS_TABLE;
    }



    public function run_integrity_check(): void {

        global $wpdb;

        echo '<h2>Integrity Check</h2>';

        

        // >>> NEU: JOIN mit Posts
      
        $from_01 = "FROM {$this->events_table} e";
        $from_02 = "F FROM {$this->events_table} e";
        $join = "INNER JOIN {$this->posts_table} p ON e.post_id = p.ID";
        $where =  "WHERE p.post_status = 'trash' AND p.post_type = 'event'";
        $select ='e.event_id, e.post_id, p.ID, p.post_status, p.post_type, p.post_title';
        
        $trash_events = $wpdb->get_results(
        "
        SELECT  {$select}
        {$from_01}
        {$join}
        {$where}
        ",
        ARRAY_A
        );
        #var_dump( $trash_events);
        
        $trash_events_count = $wpdb->get_results(
        "
            SELECT COUNT(*)
            {$from_02}
            {$join}
            {$where}
        ",
           ARRAY_A
        );
        #var_dump( $trash_events_count);


       

        // 🟡 2. Events ohne Post
        $orphan_events = $wpdb->get_results(
            "
            SELECT 
                e.id AS event_id,
                e.post_id
            FROM {$this->events_table} e
            LEFT JOIN {$wpdb->posts} p 
                ON e.post_id = p.ID
            WHERE p.ID IS NULL
            ",
            ARRAY_A
        );
        #var_dump($orphan_events);

        // 🔴 Ausgabe Trash-Count
        if (!empty($trash_events_count)) {

   

            echo '<table class="widefat striped">';
            echo '<tr><th>Trash Count</th></tr>';

            foreach ($trash_events_count as $row) {
                echo '<tr>';
                echo '<td>' . esc_html($row['F']) . '</td>';
           
        
                echo '</tr>';
            }

            echo '</table>';

        } else {
            echo '<div class="notice notice-success"><p>Keine Trash-Konflikte gefunden.</p></div>';
        }


        // 🔴 Ausgabe Trash-
        if (!empty($trash_events)) {
            echo '<div class="notice notice-error">';
            echo '<p><strong>Trash-Posts mit existierendem Event-Datensatz:</strong></p>';
            echo '</div>';

            echo '<table class="widefat striped">';
            echo '<tr>
                <th>Post ID</th>
                <th>Event ID</th>
                <th>Post Status</th>
                 <th>Post Type</th>
                  <th>Titel</th>
                
                </tr>';
           
            foreach ($trash_events as $row) {
                echo '<tr>';
                echo '<td>' . esc_html($row['post_id']) . '</td>';
                echo '<td>' . esc_html($row['event_id']) . '</td>';
                echo '<td>' . esc_html($row['post_status']) . '</td>'; 
                echo '<td>' . esc_html($row['post_type']) . '</td>';
                echo '<td>' . esc_html($row['post_title']) . '</td>';
                
                echo '</tr>';
            }

            echo '</table>';

        } else {
            echo '<div class="notice notice-success"><p>Keine Trash-Konflikte gefunden.</p></div>';
        }

        // 🟡 Ausgabe Orphan-Events
        if (!empty($orphan_events)) {

            echo '<div class="notice notice-warning">';
            echo '<p><strong>Events ohne existierenden Post:</strong></p>';
            echo '</div>';

            echo '<table class="widefat striped">';
            echo '<tr><th>Event ID</th><th>Post ID</th></tr>';

            foreach ($orphan_events as $row) {
                echo '<tr>';
                echo '<td>' . esc_html($row['event_id']) . '</td>';
                echo '<td>' . esc_html($row['post_id']) . '</td>';
                echo '</tr>';
            }

            echo '</table>';

        } else {
            echo '<div class="notice notice-success"><p>Keine verwaisten Events gefunden.</p></div>';
        }
            
    }


}
