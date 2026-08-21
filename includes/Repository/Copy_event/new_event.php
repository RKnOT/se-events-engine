<?php
/**
 * Logik für das Kopieren von Beiträgen in Events.
 */
require_once SE_EVENTS_PATH . 'includes/Repository/Copy_event/se_event_add.php';
require_once SE_EVENTS_PATH . 'includes/Repository/Copy_event/se_recurrence_add.php';

class SE_Copy_Logic {

    /**
     * Kopiert einen beliebigen WordPress-Post in ein neues SE_Event.
     * @param int $post_id Die ID des zu kopierenden Posts.
     * @param bool $is_recurring Soll das neue Event wiederkehrend sein?
     * @return SE_Event|WP_Error
     */

    public static function copy_post_to_event( $post_id, $is_recurring = false ) {
        $post = get_post( $post_id );

      
     
        if ( ! $post || is_wp_error( $post ) ) {
            return new WP_Error( 'invalid_post', __( 'Ungültige Post-ID.', 'se_em' ) );
        }
    
        // 1. Daten für das neue Event vorbereiten
        $event_data = array(
            'event_name' => $post->post_title,
            'post_content' => $post->post_content,
            'event_owner' => $post->post_author,
            'event_status' => 1, // Standardmäßig veröffentlichen
            'event_start_date' => date('Y-m-d', strtotime('+1 day')),
            'event_end_date' => date('Y-m-d', strtotime('+2 day')),
            'event_start_time' => '10:00:00',
            'event_end_time' => '11:00:00',
            'event_start' => date('Y-m-d H:i', strtotime('+1 day 10:00:00')),
            'event_end' => date('Y-m-d H:i', strtotime('+2 day 11:00:00')),
            'event_timezone' => get_option( 'timezone_string' ) ? get_option( 'timezone_string' ) : 'UTC',
            'event_rsvp' => 0,
            'event_type' => 'se_event',
            'location_id' => null,
            'recurrence_id' => null,
          
        );

        // Filter Hook zur Modifikation der Event-Daten (Nachbildung der Erweiterbarkeit)
        $event_data = apply_filters( 'se_copy_post_to_event_data', $event_data, $post );
          

        // 2. Neues SE_Event-Objekt erstellen und speichern
        $new_event = new SE_Event( $event_data );
     
        $save_result = $new_event->save();

        if ( ! $save_result ) {
            return new WP_Error( 'save_failed', __( 'Fehler beim Speichern des neuen Events.', 'se_em' ) );
        }

        // 3. Wiederholungslogik hinzufügen (falls gewünscht)
        if ( $is_recurring ) {
            $recurrence = new SE_Recurrence();
            $recurrence->event_id = $new_event->id;
            $recurrence->recurrence_freq = 'daily'; // Beispiel: Wöchentlich
            $recurrence->recurrence_interval = 1;
            $recurrence->blog_id = get_current_blog_id();
            
            if ( $recurrence->save() ) {
                // Verknüpfung im Event-Objekt aktualisieren
                $new_event->recurrence_id = $recurrence->id;
                $new_event->save();
            }
        }

        return $new_event;
    }
    
    /**
     * Kopiert ein bestehendes SE_Event in ein neues Event.
     * @param int $event_id Die ID des zu kopierenden Events.
     * @param bool $is_recurring Soll das neue Event wiederkehrend sein?
     * @return SE_Event|WP_Error
     */
    public static function copy_event_to_event( $event_id, $is_recurring = false ) {
        $old_event = se_get_event( $event_id );

        if ( ! $old_event->loaded ) {
            return new WP_Error( 'invalid_event', __( 'Ungültige Event-ID.', 'se_em' ) );
        }

        // 1. Daten für das neue Event vorbereiten (alle Felder außer IDs)
        $event_data = (array) $old_event;
        unset( $event_data['event_id'] );
        unset( $event_data['post_id'] );
        unset( $event_data['id'] );
        unset( $event_data['loaded'] );
        
        $event_data['event_name'] = $event_data['event_name'] . ' (Kopie)';
        $event_data['event_status'] = 0; // Als Entwurf speichern
        $event_data['recurrence_id'] = null; // Wiederholung wird neu gesetzt
        $event_data['event_type'] = 'se_event';

        // Filter Hook
        $event_data = apply_filters( 'se_copy_event_to_event_data', $event_data, $old_event );

        // 2. Neues SE_Event-Objekt erstellen und speichern
        $new_event = new SE_Event( $event_data );
        $save_result = $new_event->save();

        if ( ! $save_result ) {
            return new WP_Error( 'save_failed', __( 'Fehler beim Speichern des neuen Events.', 'se_em' ) );
        }

        // 3. Wiederholungslogik hinzufügen (falls gewünscht oder vom Original übernommen)
        if ( $is_recurring || $old_event->is_recurring() ) {
            $recurrence = new SE_Recurrence();
            $recurrence->event_id = $new_event->id;
            
            if ( $old_event->is_recurring() ) {
                // Kopiere die Wiederholungsdaten vom Original
                $old_recurrence = $old_event->get_recurrence();
                if ( $old_recurrence->loaded ) {
                    $recurrence_data = (array) $old_recurrence;
                    unset( $recurrence_data['recurrence_id'] );
                    unset( $recurrence_data['id'] );
                    unset( $recurrence_data['loaded'] );
                    $recurrence->to_object( $recurrence_data );
                }
            }
            
            // Setze Standardwerte, falls keine Daten vorhanden
            if ( empty( $recurrence->recurrence_freq ) ) {
                $recurrence->recurrence_freq = 'daily';
                $recurrence->recurrence_interval = 1;
            }
            $recurrence->blog_id = get_current_blog_id();
            
            if ( $recurrence->save() ) {
                // Verknüpfung im Event-Objekt aktualisieren
                $new_event->recurrence_id = $recurrence->id;
                $new_event->save();
            }
        }

        // 4. Metadaten kopieren (Nachbildung von wp_em_meta)
        // Hier müsste die Logik zum Kopieren der Metadaten aus wp_se_meta implementiert werden.
        // Da die Metadaten-Logik in SE_Object nicht vollständig implementiert ist, 
        // wird dieser Schritt hier nur angedeutet.

        return $new_event;
    }

  
   
}

function se_get_event( $id = false, $search_by = 'event_id' ) {
    
        return new SE_Event( $id, $search_by );
    }