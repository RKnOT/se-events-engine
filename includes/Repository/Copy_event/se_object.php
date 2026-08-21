<?php
/**
 * Basisklasse für alle NE-Objekte (Event, Location, Recurrence).
 * Stellt grundlegende Methoden für das Laden und Speichern von Daten bereit.
 */
abstract class SE_Object {
    public $fields = array();
    public $table_name;
    public $id_field;
    public $post_type;
    public $post_id = 0;
    public $id = 0;
    public $loaded = false;

    /**
     * Konstruktor.
     * @param mixed $id ID des Objekts oder Array von Daten.
     * @param string $search_by Feld, nach dem gesucht werden soll.
     */
    public function __construct( $id = false, $search_by = 'id' ) {
        var_dump($id);
        if ( is_array( $id ) ) {
            $this->to_object( $id );
            echo '1111';
        } elseif ( $id !== false ) {
            $this->load( $id, $search_by );
             echo '222';
        }
       
    
   
    }
    public static function get_all($args = []) {
        $obj = new static();
        return $obj->load_all($args);
        }

    public function load_all( $args = [] ) {
        global $wpdb;
        $table = $this->table_name;
        $table_post = 'wp_posts';
        // Defaults
        $defaults = [
            'where'     => '',
            'order_by'  => 'event_start_date',
            'order'     => 'ASC',
            'limit'     => '',
        ];
        $args = wp_parse_args( $args, $defaults );
        $where_sql = '';
        if ( !empty($args['where']) ) {
            $where_sql = "WHERE " . $args['where'];
        }
        $limit_sql = '';
        if ( !empty($args['limit']) ) {
            $limit_sql = "LIMIT " . intval($args['limit']);
        }
        $sql = "
            SELECT * FROM {$table}
            {$where_sql}
            ORDER BY {$args['order_by']} {$args['order']}
            {$limit_sql}
        ";


        $results = $wpdb->get_results( $sql, ARRAY_A );
        if ( empty($results) ) {
            return [];
        }
        #var_dump($results);
        $objects = [];
        foreach ( $results as $row ) {
            $obj = new static(); // wichtig für Vererbung
            $obj->to_object( $row );
            $obj->loaded = true;
            $objects[] = $obj;
        }
        echo '12345';
        var_dump($objects);
        return $objects;

    }

    /**
     * Lädt das Objekt aus der Datenbank.
     * @param mixed $id ID des Objekts.
     * @param string $search_by Feld, nach dem gesucht werden soll.
     * @return bool
     */
    public function load( $id, $search_by = 'id' ) {
        global $wpdb;
        
        $table = $this->table_name;
        $id_field = $this->id_field;
        
        if ( $search_by == 'post_id' ) {
            $id_field = 'post_id';
        } elseif ( $search_by != 'id' ) {
            $id_field = $search_by;
        }

        $sql = $wpdb->prepare( "SELECT * FROM {$table} WHERE {$id_field} = %s", $id );
        $result = $wpdb->get_row( $sql, ARRAY_A );

        if ( $result ) {
            $this->to_object( $result );
            $this->loaded = true;
            return true;
        }
        return false;
    }

    /**
     * Füllt die Objekteigenschaften mit einem Array von Daten.
     * @param array $array Array von Daten.
     */
    public function to_object( $array ) {
        foreach ( $array as $key => $value ) {
            $this->$key = $value;
        }
        if ( isset( $this->{$this->id_field} ) ) {
            $this->id = $this->{$this->id_field};
        }
    }

    /**
     * Speichert das Objekt in der Datenbank.
     * @return bool
     */
    public function save() {
        global $wpdb;
        
        $table = $this->table_name;
        $id_field = $this->id_field;
        $data = $this->get_db_data();

        if ( $this->loaded ) {
            // Update
            $result = $wpdb->update( $table, $data, array( $id_field => $this->id ) );
            return $result !== false;
        } else {
            // Insert
            $result = $wpdb->insert( $table, $data );
            if ( $result ) {
                $this->id = $wpdb->insert_id;
                $this->{$id_field} = $this->id;
                $this->loaded = true;
                return true;
            }
        }
        return false;
    }

    /**
     * Gibt die für die Datenbank vorbereiteten Daten zurück.
     * @return array
     */
    protected function get_db_data() {
        $data = array();
        foreach ( $this->fields as $field ) {
            if ( isset( $this->$field ) ) {
                $data[$field] = $this->$field;
            }
        }
        return $data;
    }
    
    /**
     * Löscht das Objekt aus der Datenbank.
     * @return bool
     */
    public function delete() {
        global $wpdb;
        
        if ( $this->loaded ) {
            $table = $this->table_name;
            $id_field = $this->id_field;
            
            $result = $wpdb->delete( $table, array( $id_field => $this->id ) );
            return $result !== false;
        }
        return false;
    }
}
