<?php
defined('ABSPATH') || exit;

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}


class SE_Events_List_Table extends WP_List_Table {

    private $repository;
    private $db_counts;
    private string $status = 'all';
    private string $selected_date = 'future';
  

    public function __construct() {
        $this->repository = new SE_Event_Repository();
        $this->db_counts = $this->repository->get_db_table_counts();
        parent::__construct([
            'singular' => 'event',
            'plural'   => 'events',
            'ajax'     => false,
        ]);
        $this->status = $this->get_current_status();
        $this->selected_date = $this->get_current_date();
       

        
    }    
    /* =========================
       Haeder Layout
    ========================= */
    public function inline_edit() {
            ?>
            <form method="post">
                <table style="display:none">
                    <tbody id="the-list">

                        <tr id="inline-edit" class="inline-edit-row inline-edit-row-post quick-edit-row quick-edit-row-post inline-edit-post">

                            <td colspan="<?php echo $this->get_column_count(); ?>" class="colspanchange">

                                <fieldset class="inline-edit-col-left">
                                    <div class="inline-edit-col">

                                        <label>
                                            <span class="title">Titel</span>
                                                <input type="text" name="post_title" value="">
                                            
                                        </label>
                                        <!--
                                        <label>
                                            <span class="title">Event Status</span>
                                            <select name="post_status">
                                                <option value="publish">Veröffentlicht</option>
                                                <option value="draft">Entwurf</option>
                                                <option value="trash">Papierkorb</option>
                                            </select>
                                        </label>
                                        -->
                                        <label>
                                            <span class="title">Eigentümer</span>
                                                <input type="text" name="post_author" value="">
                                        </label>

                                    </div>
                                </fieldset>

                                <!-- 🔥 DEINE FELDER -->
                                <fieldset class="inline-edit-col-right">
                                    <div class="inline-edit-col">
                                        <!---
                                        <label>
                                            <span class="title">Event Typ</span>
                                            <select name="se_event_type">
                                                <option value="se">Single</option>
                                                <option value="rpe">Parent</option>
                                                <option value="rce">Recurring</option>
                                            </select>
                                        </label>
                                        --->
                                        <label>
                                            <span class="title">Datum</span>
                                            <input type="date" name="se_event_date">
                                        </label>

                                        <label>
                                            <span class="title">Start</span>
                                            <input type="time" name="se_start_time">
                                        </label>

                                        <label>
                                            <span class="title">Ende</span>
                                            <input type="time" name="se_end_time">
                                        </label>

                                    </div>
                                </fieldset>

                                <div class="inline-edit-save submit">
                                    <button type="button" class="button cancel alignleft">Abbrechen</button>
                                    <button type="button" class="button button-primary save alignright">Aktualisieren</button>
                                    <span class="spinner"></span>
                                </div>

                            </td>
                        </tr>

                    </tbody>
                </table>
            </form>
            <?php
    }

    public function single_row($item) {
        echo '<tr id="post-' . (int)$item['post_id'] . '" class="type-post">';
        $this->single_row_columns($item);
        echo '</tr>';
    }
    // obere menue zeile
    protected function get_views() { 
        $this->status = $this->get_current_status();
        $views = [];
        foreach ($this->db_counts as $status => $count) {
            $label = $this->match_lable($status);
            $views[$status] = sprintf(
                '<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
                esc_url(add_query_arg(['status' => $status])),
                $this->status === $status ? 'current' : '',
                esc_html($label),
                $count
            );
        }
        return $views; 
    }
     /* =========================
       Events List Layout
    ========================= */



    protected function column_default($item, $column_name) {
        return ($item[$column_name] ?? '');
    }

    protected function column_cb($item): string {
        return sprintf(
            '<input type="checkbox" name="event_ids[]" value="%d" />',
            (int) $item['post_id'],
            $item['status'] 

        );
        
    }
    
    protected function column_title($item): string {

        
        $post_id = (int) $item['post_id'];

        $edit_url = add_query_arg(
            [
                'page' => 'se-events',
                'event_action' => 'edit',
                'post_id' => $post_id
            ],
            admin_url('admin.php')
        );

        $show_url = get_permalink($post_id);

        $trash_url = wp_nonce_url(
            admin_url('admin.php?page=se-events&action=trash&id=' . $post_id),
            'trash_event_' . $post_id
        );

        $copy_url = wp_nonce_url(
            admin_url('admin.php?action=se_duplicate_event&post_id=' . $post_id),
            'se_duplicate_event_' . $post_id
        );

        // 🔥 FIX: echtes WP Quick Edit Markup
        $actions = [
            'edit'  => '<a href="' . esc_url($edit_url) . '">Bearbeiten</a>',
            'quick' => '<a href="#" class="se-quick-edit" data-id="' . $post_id . '">Schnellbearbeitung</a>',
            'trash' => '<a href="' . esc_url($trash_url) . '" class="submitdelete">Papierkorb</a>',
            'view'  => '<a href="' . esc_url($show_url) . '">Anzeigen</a>',
            'copy'  => '<a href="' . esc_url($copy_url) . '">Duplizieren</a>',
        ];

        // 🔥 DEBUG
        #var_dump($item);
        /*
        $debug = sprintf(
            '<div style="color:#888;margin-bottom:4px;">
                %s | %s | %s 
            </div>',
            esc_html($item['event_type'] ?? 'kein type'),
            esc_html($item['status'] ?? ''),
            esc_html($edit_url)
        );
        */
        // 🔥 TITLE
        $title = sprintf(
            '<strong>
                <a href="%s" class="row-title">%s</a>
            </strong>',
            esc_url($edit_url),
            esc_html($item['title'])
        );

        // 🔥 GANZ WICHTIG → Hidden Daten für WP Quick Edit
     
       $post_author_id = (int) get_post_field('post_author', $post_id);
        $post_author_name = get_the_author_meta('display_name', $post_author_id);

        $hidden = sprintf(
            '<div class="hidden" id="inline_%d">
                <div class="post_title">%s</div>
                 <div class="cb_event_type">%s</div>
                <div class="post_status">%s</div>
                <div class="post_author">%d</div>
                <div class="post_author_name">%s</div>
                <div class="se_event_date">%s</div>
                <div class="se_start_time">%s</div>
                <div class="se_end_time">%s</div>
            </div>',
            $post_id,

            esc_html($item['title']),
            esc_html($item['status']),
            esc_html($item['event_type'] ?? ''),
            $post_author_id,
            esc_html($post_author_name),
             
          
            esc_html($item['start_date'] ?? ''),
            esc_html($item['start_time'] ?? ''),
            esc_html($item['end_time'] ?? '')
        );

        // 🔥 Beschreibung
        $description = '';

        if (!empty($item['beschreibung'])) {

            $content = apply_filters('the_content', $item['beschreibung']);
            $content = wp_strip_all_tags($content);
            $content = wp_trim_words($content, 80, '...');

            $description = sprintf(
                '<div class="row-description" style="margin-top:5px; color:#555;">%s</div>',
                esc_html($content)
            );
        }

        return $title
            . $hidden   // 🔥 EXTREM wichtig für Quick Edit
            . '' //$debug''
            . $description
            . $this->row_actions($actions);
    }
 


     /* =========================
       BULK ACTIONS
       ========================= */
    protected function get_bulk_actions(): array {
        // Status kommt aus prepare_items()
        $status = $this->status ?? 'all';

        // Wenn wir im Papierkorb sind
        if ($status === 'trash') {

            return [
                'restore'            => 'Wiederherstellen',
                'delete_permanently' => 'Endgültig löschen',
            ];
        }

        // Standard (alle anderen Status)
        return [
            'trash' => 'In den Papierkorb',
        ];
    }

   

        /**
     * Zusätzliche Filter über der Tabelle
     */
    protected function extra_tablenav($which) {

        if ($which === 'top') {
            $selected_date = $_GET['event_date'] ?? '';
            $selected_type = $_GET['event_type'] ?? '';

            echo '<div class="alignleft actions">';

        

            // ================================
            // 🔹 Datumsfilter
            // ================================

        
            echo '<select name="event_date">';
            echo '<option value="future" ' . selected($this->selected_date, 'future', false) . '>Zukünftige Veranstaltungen</option>';
            echo '<option value="past" ' . selected($this->selected_date, 'past', false) . '>Vergangene Veranstaltungen</option>';
            echo '<option value="date" ' . selected($this->selected_date, 'date', false) . '>Nach Datum filtern</option>';
            echo '</select>';

            // ================================
            // 🔹 Event Type Filter
            // ================================

            echo '<select name="event_type">';
            echo '<option value="all" ' . selected($selected_type, 'all', false) . '>Alle Events</option>';
            echo '<option value="se" ' . selected($selected_type, 'se', false) . '>Einzel Events</option>';
            echo '<option value="rce" ' . selected($selected_type, 'rce', false) . '>Wiederkehrende Events</option>';
            echo '<option value="rpe" ' . selected($selected_type, 'rpe', false) . '>Recurring Parent Events</option>';
            echo '</select>';

            // ================================
            // 🔹 Status
            // ================================
            echo '<select name="status">';
            echo '<option value= "all" ' . selected($this->status, 'all', false). '>Alle</option>';
            echo '<option value= "mine" ' . selected($this->status, 'mine', false). '>Meine</option>';
            echo '<option value= "publish" ' . selected($this->status, 'publish', false). '>Veröffentlich</option>';
            echo '<option value="draft" '.  selected($this->status, 'draft', false) . '>Entwürfe</option>';
            echo '<option value="trash" ' . selected($this->status, 'trash', false) . '>Papierkorb</option>';
            echo '</select>';

            submit_button('Filtern', '', 'filter_action', false);
            echo '</div>';

        }
    }

   
      /* =========================
       table list items
       ========================= */
    public function prepare_items(): void {

        // ==========================================
        // 🔹 WICHTIG: Bulk Actions verarbeiten
        // ==========================================

        $this->process_bulk_action();
        // ---------------------------------
        // Grundparameter
        // ---------------------------------

        $per_page     = $this->get_items_per_page('events_table_page', 10);
        $current_page = $this->get_pagenum();
        $offset       = ($current_page - 1) * $per_page;
       
        $search = isset($_REQUEST['s']) ? trim($_REQUEST['s']) : '';

        // =====================================================
        // 🔹 NEU: zusätzliche Filter aus extra_tablenav()
        // =====================================================

        $this->selected_date = $_GET['event_date'] ?? '';
   


        // Einzelaktion (robuster)
        /*
        if (isset($_GET['action'], $_GET['id'])) {

            $action = sanitize_key($_GET['action']);
            $id     = (int) $_GET['id'];

            if ($action === 'trash') {
                $this->repository->move_to_trash($id);
            }

            if ($action === 'delete_permanently') {
                $this->repository->delete_permanently($id);
            }
        }
        */
        // ---------------------------------
        // Repository Argumente
        // ---------------------------------
     
    
        $args = [
            'status'     =>  $this->status,
            'event_type' => $_GET['event_type'] ?? 'all',
            'event_date' => $_GET['event_date'] ??  'public',
            'sort' => 'ASC',
            'search'     => $search,
            'limit'      => $per_page,
            'offset'     => $offset,
        ];


    
            #var_dump($args);

                

        // ---------------------------------
        // Events laden
        // ---------------------------------

        $results = $this->repository->get_events($args);
        if($results  === NULL) {
            $results = [];
        }

        //var_dump($results[0]);

            
    
        // ---------------------------------
        // Gesamtanzahl für Pagination
        // ---------------------------------
        
      
        $total_items = $this->repository->count_filtered($args);
   
        // ---------------------------------
        // Mapping DB → List Table Array
        // ---------------------------------

        $data = [];
      

        foreach ($results as $row) {
           
               $event_owner_name = $this->repository->get_owner_name((int) $row['event_owner']);
               $event_location   = $this->repository->get_location_name((int) $row['location_id']);
               $start_date = $row['event_start_date'] ?? '';
               $start_time = $row['event_start_time'] ?? '';
               $end_time   = $row['event_end_time'] ?? '';

                $datum = '';

                if ($start_date) {
                    $datum .= date('d. F Y', strtotime($start_date));
                }

                if ($start_time && $end_time) {
                    $datum .= '<br>' . substr($start_time, 0, 5) . ' - ' . substr($end_time, 0, 5);
                }
                $recurrence_text = '';

                if (!empty($row['recurrence']) && $row['recurrence'] == 1) {

                    // Wochentag Mapping
                    $days_map = [
                        'MO' => 'Montag',
                        'TU' => 'Dienstag',
                        'WE' => 'Mittwoch',
                        'TH' => 'Donnerstag',
                        'FR' => 'Freitag',
                        'SA' => 'Samstag',
                        'SU' => 'Sonntag',
                    ];

                    $day = '';
                    if (!empty($row['recurrence_byday']) && isset($days_map[$row['recurrence_byday']])) {
                        $day = $days_map[$row['recurrence_byday']];
                    }

                    if (!empty($row['recurrence_freq'])) {

                        switch ($row['recurrence_freq']) {

                            case 'daily':
                                $recurrence_text = 'Täglich';
                                break;

                            case 'weekly':
                                $recurrence_text = $day ? "$day jede Woche" : 'Jede Woche';
                                break;

                            case 'monthly':
                                $recurrence_text = 'Jeden Monat';
                                break;
                        }
                    }
                }
                if ($recurrence_text) {
                    $datum .= '<div style="color:#646970;font-size:12px;">' . esc_html($recurrence_text) . '</div>';
                }
                #var_dump($row);
                #  var_dump([
                #    'event_post_id' => $row['post_id'],
                #    'joined_status' => $row['post_status']
                #]);
             

                $data[] = [
                    'post_id'      => (int) $row['post_id'],
                    'title'        => $row['event_name'],
                    'ort'          => $event_location,
                    'datum'        => $datum,
                    'eigentuemer'  => $event_owner_name,
                    'beschreibung' => $row['post_content'],
                    'status'       => $row['post_status'] ?? '',
                    'event_type'   => $row['event_type'] ?? '',
                    'start_date'   => $row['event_start_date'] ?? '',
                    'end_date'     => $row['event_end_date'] ?? '',
                    'start_time'   => $row['event_start_time'] ?? '',
                    'end_time'     => $row['event_end_time'] ?? '',

                  
                    
                ];
               # var_dump($data);
               
            }
              
        // ---------------------------------
        // Column Header setzen
        // ---------------------------------

        $columns  = $this->get_columns();
        $hidden   = ['post_id']; // ID IMMER hidden
        $sortable = [];

        $this->_column_headers = [
            $columns,
            $hidden,
            $sortable,
        ];
        // ---------------------------------
        // Pagination setzen
        // ---------------------------------
        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page),
        ]);
        //var_dump( $this->set_pagination_args);
        $this->items = $data;
    
       
    }   

    /* =========================
       ACTION HANDLING (NEU)
    ========================= */
    public function process_bulk_action() {

        $action = $this->current_action();

        if (!$action) {
            return;
        }

        // ==========================
        // 🔹 Helper Funktionen
        // ==========================

        // 🔹 Status speichern (vor Trash)
        $store_previous_status = function($id) {

            $post = get_post($id);
            if (!$post) {
                error_log('❌ Kein Post für ID: ' . $id);
                return;
            }
            if ($post->post_status === 'trash') {
                return;
            }
            update_post_meta($id, '_previous_status', $post->post_status);

           
         

        };
        // 🔹 Status wiederherstellen
        $get_valid_status = function($id) {
           
            $prev_status = get_post_meta($id, '_previous_status', true);       
            if (!$prev_status || !in_array($prev_status, ['publish','draft','pending','private'])) {
                return 'draft';
            }
            return $prev_status;
        };

     

        // ==========================
        // 🔹 IDs sammeln
        // ==========================
        $ids = [];

        if (isset($_GET['id'])) {
            $ids[] = (int) $_GET['id'];
        }

        if (!empty($_REQUEST['event_ids']) && is_array($_REQUEST['event_ids'])) {
            $ids = array_merge($ids, array_map('intval', $_REQUEST['event_ids']));
        }

        $ids = array_filter(array_unique($ids));

        if (empty($ids)) {
            return;
        }

        // ==========================
        // 🔹 Aktionen
        // ==========================
        switch ($action) {

            case 'trash':
                foreach ($ids as $id) {
                    $store_previous_status($id); // 🔥 zuerst speichern!
                }
                if (count($ids) === 1) {
                    $this->repository->move_to_trash($ids[0]);
                } else {
                    $this->repository->bulk_move_to_trash($ids);
                }
                break;
            case 'restore':
                foreach ($ids as $id) {
                    $status = $get_valid_status($id);
                    wp_update_post([
                        'ID'          => $id,
                        'post_status' => $status,
                    ]);
                    # debug  !!!!!!
                    #delete_post_meta($id, '_previous_status');
        
                }
                break;

            case 'delete_permanently':

                if (count($ids) === 1) {
                    $this->repository->delete_permanently($ids[0]);
                } else {
                    $this->repository->bulk_delete_permanently($ids);
                }

                break;
        }

        // ==========================
        // 🔹 Counts aktualisieren
        // ==========================
        $this->db_counts = $this->repository->get_db_table_counts();

       

    }



    /* =========================
       Helper functions
    ========================= */
    private function get_current_status(): string {
        $user_id = get_current_user_id();
        #var_dump($user_id );
        #var_dump($_GET );
        // Wenn Status in URL → speichern
        if (isset($_GET['status'])) {
            $status = sanitize_key($_GET['status']);
            update_user_meta($user_id, 'se_events_last_status', $status);
            return $status;
        }
        // Wenn kein URL-Status → gespeicherten Wert laden
        $saved = get_user_meta($user_id, 'se_events_last_status', true);
        return $saved ?: 'all';
    }  
     private function get_current_date(): string {
        $user_id = get_current_user_id();
        #var_dump($user_id );
        #var_dump($_GET );
        // Wenn Status in URL → speichern
        if (isset($_GET['event_date'])) {
            $event_date = sanitize_key($_GET['event_date']);
            update_user_meta($user_id, 'se_events_last_event_date', $event_date);
            return $event_date;
        }
        // Wenn kein URL-Status → gespeicherten Wert laden
        $saved = get_user_meta($user_id, 'se_events_last_event_date', true);
        return $saved ?: 'future';
    }  
    public function get_columns(): array {
        return [
            'cb'          => '<input type="checkbox" />',
            'post_id'     => 'ID',
            'title'       => 'Titel',
            'ort'         => 'Veranstaltungsort',
            'datum'       => 'Datum und Uhrzeit',
            'eigentuemer' => 'Eigentümer',
        ];
    }

     public function match_lable($stat) {
            return $label = match($stat) {
                'all'     => 'Alle',
                'mine'    => 'Meine',
                'publish' => 'Veröffentlicht',
                'draft'   => 'Entwürfe',
                'trash'  => 'Papierkorb',
            };
    }

}

class SE_Event_Repository {
    
    private $table_name;

    public function __construct() {
       $this->table_name = SE_EVENTS_TABLE;
    }
    /**
    * Events laden (mit Pagination & Filter)
    */
    public function get_events(array $args): array {

        global $wpdb;

     

        $events_table = SE_EVENTS_TABLE;
        $posts_table  = POSTS_TABLE;
        #var_dump($args);
        // 🔹 WHERE aus zentraler Funktion
        $filter     = $this->build_where($args);
       
        $where_sql  = $filter['sql'];
        $params     = $filter['params']; // 🔥 wichtig!

        $sort = strtoupper($filter['sort'] ?? 'ASC');
        // Sicherheitscheck
        if (!in_array($sort, ['ASC', 'DESC'], true)) {
            $sort = 'ASC';
        }

        // 🔹 Pagination
        $limit  = (int) ($args['limit'] ?? 10);
        $offset = (int) ($args['offset'] ?? 0);

        $params[] = $limit;
        $params[] = $offset;
    
        // 🔥 SQL
        $sql = "
            SELECT 
                e.*,
                p.post_status AS post_status,
                p.post_content
            FROM {$events_table} e
            LEFT JOIN {$posts_table} p 
                ON e.post_id = p.ID
            {$where_sql}
            ORDER BY 
                e.event_start_date {$sort},
                e.event_start_time {$sort}
            LIMIT %d OFFSET %d
        ";

        $query = $wpdb->prepare($sql, ...$params);
        //var_dump($query);
   
      

        return $wpdb->get_results($query, ARRAY_A);
    }
    private function get_post_statuses(string $status): array
    {
        return match ($status) {
            'all', 'mine' => ['publish', 'draft'],
            'publish'     => ['publish'],
            'draft'       => ['draft'],
            'trash'       => ['trash'],
            default       => ['publish', 'draft'],
        };
    }


    public function count_by_status(string $status = 'all'): int {

        global $wpdb;

      
        $events_table = SE_EVENTS_TABLE;
        $posts_table  = POSTS_TABLE;

        $where  = [];
        $params = [];

       $post_statuses = $this->get_post_statuses($status);

        $placeholders = implode(',', array_fill(0, count($post_statuses), '%s'));

        $where[] = "p.post_status IN ($placeholders)";
        $params = array_merge($params, $post_statuses);

        if ($status === 'mine') {
            $where[] = 'e.event_owner = %d';
            $params[] = get_current_user_id();
        }
      
        $where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';       

        $sql = "
            SELECT COUNT(*)
            FROM {$events_table} e
            INNER JOIN {$posts_table} p
                ON e.post_id = p.ID
            {$where_sql}
        ";
        return (int) $wpdb->get_var(
            $wpdb->prepare($sql, ...$params)
        );
    }
    public function count_filtered(array $args): int {

        global $wpdb;

        $events_table = SE_EVENTS_TABLE;
        $posts_table  = POSTS_TABLE;

        // 🔹 gleiche WHERE wie get_events()
        $filter = $this->build_where($args);

        $where_sql = $filter['sql'];
        $params    = $filter['params'];

            
        $sql = "
            SELECT COUNT(*)
            FROM {$events_table} e
            INNER JOIN {$posts_table} p 
                ON e.post_id = p.ID
            {$where_sql}
        ";

        return (int) $wpdb->get_var(
            $wpdb->prepare($sql, ...$params)
        );
    }
    private function build_where(array $args): array {
        global $wpdb;
        $where  = [];
        $params = [];
        // ==========================
        // 🔹 STATUS
        // ==========================
        $post_statuses = $this->get_post_statuses($args['status']);

        $placeholders = implode(',', array_fill(0, count($post_statuses), '%s'));

        $where[] = "p.post_status IN ($placeholders)";
        $params = array_merge($params, $post_statuses);

        if ($args['status'] === 'mine') {
            $where[] = 'e.event_owner = %d';
            $params[] = get_current_user_id();
        }
        
        // ==========================
        // 🔹 EVENT TYPE
        // ==========================
        if (!empty($args['event_type'])) {

        $event_types = $args['event_type'];

        /*
        * Unterstützt:
        *
        * 'se'
        * 'rce'
        * 'rpe'
        * 'all'
        * 'se,rce'
        * ['se']
        * ['se','rce']
        * ['se, rce']
        */

        if (!is_array($event_types)) {
            $event_types = [$event_types];
        }

        $normalized = [];

        // ==========================
        // 🔹 Normalisieren
        // ==========================

        foreach ($event_types as $type) {

            $parts = explode(',', $type);

            foreach ($parts as $part) {

                $part = trim($part);

                if (!empty($part)) {
                    $normalized[] = $part;
                }
            }
        }

        // ==========================
        // 🔹 Mapping
        // ==========================

        $mapped_types = [];

        foreach ($normalized as $type) {

            switch ($type) {

                // ==========================
                // 🔹 ALLE
                // ==========================

                case 'all':
                    $mapped_types[] = 'se';
                    $mapped_types[] = 'se_event';
                    $mapped_types[] = 'rce';
                    $mapped_types[] = 'rpe';
                    break;
                // ==========================
                // 🔹 SINGLE EVENT
                // ==========================
                case 'se':
                    $mapped_types[] = 'se';
                    $mapped_types[] = 'se_event';
                    break;
                // ==========================
                // 🔹 RECURRING CHILD
                // ==========================
                case 'rce':
                    $mapped_types[] = 'rce';
                    break;
                // ==========================
                // 🔹 RECURRING PARENT
                // ==========================
                case 'rpe':
                    $mapped_types[] = 'rpe';
                    break;
                // ==========================
                // 🔹 STANDARD
                // ==========================
                default:
                    $mapped_types[] = $type;
            }
        }

            // ==========================
            // 🔹 Doppelte entfernen
            // ==========================

            $event_types = array_unique($mapped_types);
                // ==========================
                // 🔹 SQL erzeugen
                // ==========================
                if (!empty($event_types)) {

                    $placeholders = implode(
                        ',',
                        array_fill(0, count($event_types), '%s')
                    );

                    $where[] = "e.event_type IN ($placeholders)";

                    $params = array_merge($params, $event_types);
                }
            }
           
            // ==========================
            // 🔹 FUTURE / PAST
            // ==========================
            if ($args['event_date'] === 'future') {

                $where[] = "
                    CONCAT(
                        e.event_start_date,
                        ' ',
                        e.event_start_time
                    ) >= %s
                ";
                $params[] = current_time('Y-m-d H:i');
             
            }

            if ($args['event_date'] === 'past') {

                $where[] = "
                    CONCAT(
                        e.event_start_date,
                        ' ',
                        e.event_start_time
                    ) < %s
                ";
                $params[] = current_time('Y-m-d H:i');
                // 🔥 Vergangene Events absteigend
                $args['sort'] = 'DESC';
                
            }

            // ==========================
            // 🔍 SEARCH
            // ==========================
            if (!empty($args['search'])) {
                $search = sanitize_text_field($args['search']);
                $words = preg_split(
                    '/\s+/',
                    $search,
                    -1,
                    PREG_SPLIT_NO_EMPTY
                );
                $search_parts = [];
                foreach ($words as $word) {
                    if (mb_strlen($word) < 2) {
                        continue;
                    }
                    $search_parts[] =
                        '(p.post_title LIKE %s OR p.post_content LIKE %s)';
                    $like = '%' . $wpdb->esc_like($word) . '%';
                    $params[] = $like;
                    $params[] = $like;
                }
                if (!empty($search_parts)) {
                    $where[] =
                        '(' . implode(' AND ', $search_parts) . ')';
                }
            }
            // ==========================
            // 🔹 SORTIERUNG
            // ==========================
            if (!empty($args['sort'])) {
                $sort_arg = strtoupper($args['sort']);
                if (in_array($sort_arg, ['ASC', 'DESC'], true)) {
                    $sort = $sort_arg;
                }
            }
            // ==========================
            // 🔧 SQL
            // ==========================
            $where_sql = $where
                ? 'WHERE ' . implode(' AND ', $where)
                : '';
            /*
            var_dump($args);
            var_dump([
                'sql'    => $where_sql,
                'params' => $params
            ]);
            */
            return [
                'sql'    => $where_sql,
                'params' => $params,
                'sort'   => $sort,
            ];
        }




    

        /**
         * Gesamtanzahl (für Pagination)
         */
    public function get_db_table_counts(): array {

            return [
                'all'     => $this->count_by_status('all'),
                'mine'    => $this->count_by_status('mine'),
                'publish' => $this->count_by_status('publish'),
                'draft'   => $this->count_by_status('draft'),
                'trash'   => $this->count_by_status('trash'),
            ];
        }


    public function restore(int $id): void {
            global $wpdb;

            $wpdb->update(
                $this->table_name,
                ['deleted_at' => null],
                ['id' => $id],
                ['%s'],
                ['%d']
            );
        }

    public function bulk_restore(array $ids): void {
            global $wpdb;

            $ids = implode(',', array_map('intval', $ids));

            $wpdb->query(
                "UPDATE {$this->table_name}
                SET deleted_at = NULL
                WHERE id IN ($ids)"
            );
        }

        /* =========================
        TRASH & DELETE (NEU)
        ========================= */

    
    public function move_to_trash(int $post_id): bool {

            $result = wp_update_post([
                'ID' => $post_id,
                'post_status' => 'trash'
            ], true);

            if (is_wp_error($result)) {
                return false;
            }

            return true;
        }

    public function bulk_move_to_trash(array $ids): void {

            foreach ($ids as $id) {
                $this->move_to_trash((int) $id);
            }
        }

    public function delete_permanently(int $post_id): bool {           
            $event_id = $this->check_rpe($post_id); 
            #echo $event_id;
            if($event_id != 0) { 
                if (!isset($_GET['delete_confirmed'])) {
                    ?>
                    <script>
                    const result = confirm('Soll das wiederkehrende Event einschließlich aller erzeugten Einzeltermine (RCE) wirklich gelöscht werden?');
                    const url = new URL(window.location);
                    url.searchParams.set('delete_confirmed',result ? '1' : '0');
                    window.location.href = url;
                    </script>
                    <?php
                    exit; // rce's werden nicht gelöscht
                }
                if ($_GET['delete_confirmed'] === '1') { //rce's werden gelöscht
                    SE_Del_Gen_RCE_Event_Posts::delete_rce_instances($event_id);
                } else { return true;} // rpe wird nicht gelöscht
            }

            global $wpdb;
            // WP löschen
            $wp_deleted = wp_delete_post($post_id, true);
            if (!$wp_deleted) {
                return false;
            }
            // Danach Custom Table
            $wpdb->delete(
                SE_EVENTS_TABLE,
                ['post_id' => $post_id],
                ['%d']
            );
            return true;
    }
    public function check_rpe($post_id) {
          
            $ev = new SE_GET_EVENT_TYP($post_id);
            $event = $ev->get_event();
            if($event) {
            if($event->event_type == 'rpe') {
                 return $event->event_id;
            }
            }
            return 0; //keine aktion


    }

    public function bulk_delete_permanently(array $ids): void {

            foreach ($ids as $id) {
                $this->delete_permanently((int) $id);
            }
        }


        

    public function get_location_name(int $user_id): ?string {

            global $wpdb;
            
            $user_name = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT location_name FROM " . SE_LOCATION_TABLE . " WHERE location_id = %d",
                    $user_id
                )
        );
        return $user_name ?: null;
    }

    public function get_owner_name(int $user_id): ?string {

            global $wpdb;
            
            $user_name = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT display_name FROM " . USERS_TABLE . " WHERE ID = %d",
                    $user_id
                )
            );        
            return $user_name ?: null;
    }
    
}


    /**
    * Screen Option von der Anzahl der events im listview speichern 
    */
        add_filter('set-screen-option', function ($status, $option, $value) {

            if ($option === 'events_table_page') {
                return (int) $value;
            }

            return $status;

        }, 10, 3);
    /*-------------------*/