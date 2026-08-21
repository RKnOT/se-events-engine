<?php
defined('ABSPATH') || exit;

   

    require_once SE_EVENTS_PATH . 'includes/Admin/class-se-events-list-table.php';
    require_once SE_EVENTS_PATH . 'includes/Admin/Views/List_Events_View.php';

    require_once SE_EVENTS_PATH . 'includes/Repository/copy_events_db.php';
    require_once SE_EVENTS_PATH . 'includes/Repository/Events_Repository.php';
    

   require_once SE_EVENTS_PATH . 'includes/Repository/Edit/edit_single_event.php';
        
   
  
  
   
   add_action('admin_enqueue_scripts', function () {
            wp_add_inline_style(
                'wp-admin',
                '
                .wp-list-table .column-title {
                    width: 60%;
                }
                '
            );
            });
   
    

class SE_Events_Admin_Menu {

        
    public function register() {

        add_menu_page(
           'SE Events',
            'SE Events by RKnOT',
            'manage_options',
            'se-events',
            [$this, 'events_table_page'],
            'dashicons-calendar-alt',
            10
        );
        add_submenu_page(
            'db-daten-management', // Eltern-Slug
            'DB Daten Management', // Seiten-Titel
            'DB Daten Management', // Menü-Titel
            'manage_options', // Berechtigungen
            'db-management', // Slug
            array($this, 'hauptseite_callback') // Callback-Funktion
        );
        $hook_suffix_01 = add_submenu_page(
            'se-events',
            'Veranstaltungen',
            'Veranstaltungen',
            'manage_options',
            'se-events',
            [$this, 'events_table_page']
        );

        add_action('load-' . $hook_suffix_01, function () {
            global $EventListTable;    
            $screen = get_current_screen();
            $screen->add_option('per_page', [
                'label'   => 'Items per page',
                'default' => 5,
                'option'  => 'events_table_page',
            ]);
            $EventListTable = new SE_Events_List_Table();
            });   
          
        add_submenu_page(
            'se-events', // parent slug (dein Hauptmenü)
            'Event hinzufügen',
            'Add Single Event',
            'manage_options',
            'se-add-event',
            [$this, 'render_add_event_page']
        );
        add_submenu_page(
            'se-events', // parent slug (dein Hauptmenü)
            'Parent Event hinzufügen',
            'Add Parent Event',
            'manage_options',
            'se-add-parent-event',
            [$this, 'render_add_parent_event_page']
        );

         add_submenu_page(
            'se-events', // parent slug (dein Hauptmenü)
            'Del Past Events',
            'Del Past Events',
            'manage_options',
            'se-del-past-events',
            [$this, 'del_past_events']
        );
           
    } 
                          
                    
        
               
            /* =========================*/
    public function list_events() {
        echo '<div class="wrap"><h1>Events Liste</h1></div>';
        
    }
    /* =========================*/
    public function events_table_page() : void {
        $EventListTable = new SE_Events_List_Table();
        if( $EventListTable) {         
            $EventListTable->prepare_items();
            echo '<h1 class="wp-heading-inline">Veranstaltungen</h1>';
            echo '<hr class="wp-header-end">';
            $EventListTable->views();
            echo '<form method="get">';
            echo '<input type="hidden" name="page" value="' . esc_attr($_REQUEST['page']) . '" />';
            $EventListTable->search_box('Veranstaltungen suchen', 'event');
            $EventListTable->display();
            echo '</form>';
        }
          
    }
            
    public function render_add_event_page() {
                    ?>
                    <div class="wrap">
                    <h1>Neuen Event erstellen</h1>

                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">

                        <?php wp_nonce_field('se_create_event'); ?>
                    

                        <input type="hidden" name="action" value="se_create_event">
                    
                        <!---
                        <p>
                            <label>Titel</label><br>
                            <input type="text" name="title" style="width:300px;" required>
                        </p>

                        <p>
                            <label>Beschreibung</label><br>
                            <textarea name="content" style="width:300px;height:100px;"></textarea>
                        </p>
                        -->
                        <p>
                            <button class="button button-primary">
                                Event erstellen
                            </button>
                        </p>
                        </form>
                        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">

                            <!--------------------->
                            <hr>
                            <h1>Neues Event aus einem Post erstellen</h1>
                            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">

                                <?php wp_nonce_field('se_create_event_by_ID'); ?>

                                <input type="hidden" name="action" value="se_create_event_by_ID">

                                <p >
                                    <label>Post ID</label><br>
                                    <input type="number" name="post_id" style="width:120px;" required>
                                </p>

                                <p>
                                    <button class="button">
                                        Event aus Post erstellen
                                    </button>
                                </p>

                        </form>
                
                    </div>
                    <?php
    }

    public function render_add_parent_event_page() {
        
                #require_once plugin_dir_path(__FILE__) . 'Views/Add_Event_View.php';
                
            
                    ?>
                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">

                        <?php wp_nonce_field('se_create_recurring_event'); ?>

                        <input type="hidden" name="action" value="se_create_recurring_event">

                        <h1>
                            <strong>Recurring Event</strong>
                        </h1>

                        <p>
                            <label>Titel</label><br>
                            <input type="text" name="title" value="Recurring Event" style="width:250px;">
                        </p>
                       
                        <p>
                            <button class="button button-primary">
                                Recurring Event erstellen
                            </button>
                        </p>

                    </form>
                    <hr>

                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">

                        <?php wp_nonce_field('se_create_parent_by_ID'); ?>

                        <input type="hidden" name="action" value="se_create_parent_by_ID">

                        <h1>
                            <strong>Recurring Parent aus Post</strong>
                        </h1>

                        <p>
                            <label>Post ID</label><br>
                            <input type="number" name="post_id" style="width:120px;" required>
                        </p>
                       
                        <p>
                            <button class="button button-primary">
                                Recurring Parent aus Post erstellen
                            </button>
                        </p>

                    </form>
                    <script>
                document.addEventListener('DOMContentLoaded', function () {
                        console.log('JS läuft');
                        

                    });
                    </script>
                    <?php           
    }

     public function del_past_events() {
        //D:\SERVER\www\MGH\2026_04_12\wp-content\plugins\se-events-engine\includes\Repository\Del_events\class_del_events.php
       require_once SE_EVENTS_PATH . '/includes/Repository/Del_events/class_del_events.php';
        $del = new SE_Events_Del_History_Events();
        $del->render();
    }   
                    
        
}
    


