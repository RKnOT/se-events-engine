<?php
/**
 * Recurrence-Objekt-Klasse (Nachbildung von EM_Recurrence).
 * Verwaltet die Wiederholungslogik.
 */
class SE_Recurrence extends SE_Object {
    public $id_field = 'recurrence_id';
    public $table_name; // Wird in se-event-manager.php gesetzt
    
    // Felder, die in der Custom Table gespeichert werden
    public $fields = array(
        'recurrence_id', 'recurrence_interval', 'recurrence_freq', 
        'recurrence_byday', 'recurrence_byweekno', 'recurrence_days', 
        'recurrence_rsvp_days', 'blog_id', 'group_id', 'event_id'
    );

    public function __construct( $id = false, $search_by = 'recurrence_id' ) {
        echo SE_EVENT_REURRENCES_TABLE;
        global $wpdb;
        $this->table_name = $wpdb->SE_EVENT_REURRENCES_TABLE;
        parent::__construct( $id, $search_by );
    }

    /**
     * Speichert die Wiederholungsdaten.
     * @return bool
     */
    public function save() {
        $this->blog_id = get_current_blog_id();
        $result = parent::save();
        
        do_action( 'se_recurrence_save', $this, $result );
        
        return $result;
    }
}
