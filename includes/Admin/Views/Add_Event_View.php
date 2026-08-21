<?php

if (!defined('ABSPATH')) exit;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    # require_once plugin_dir_path(__FILE__) . '../../Repository/Events_Repository.php';
   
    echo '<div class="updated"><p>Event erstellt!</p></div>';

/*
    $errors = get_settings_errors('se-events');

    $has_error = false;
    $has_success = false;

    foreach ($errors as $error) {
        if ($error['type'] === 'error') {
            $has_error = true;
        }

        if ($error['type'] === 'updated') {
            $has_success = true;
        }
    }

    if ($has_success) {
        echo "✅ Alles gut!";
    }

    if ($has_error) {
        echo "❌ Da lief was schief";
    }
*/



}
?>
<div class="wrap">
    <h1><?php _e( 'NE Event Kopier-Tool', 'se-em' ); ?></h1>
    
    <?php settings_errors(); ?>
    
    <form method="post" action="">
        <table class="form-table">
            <tr>
                <th scope="row"><label for="se_source_id"><?php _e( 'Quell-ID (Post oder Event)', 'se_em' ); ?></label></th>
                <td>
                    <input type="number" id="se_source_id" name="se_source_id" required class="regular-text" value="<?php echo isset( $_POST['se_source_id'] ) ? esc_attr( $_POST['se_source_id'] ) : ''; ?>">
                    <p class="description"><?php _e( 'Geben Sie die ID eines Posts (z.B. 123) oder eines bestehenden NE Events (event_id) ein.', 'se_em' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e( 'Quelltyp', 'se_em' ); ?></th>
                <td>
                    <label>
                        <input type="radio" name="se_source_type" value="post" required <?php checked( 'post', isset( $_POST['se_source_type'] ) ? $_POST['se_source_type'] : 'post' ); ?>>
                        <?php _e( 'Standard WordPress Post', 'se_em' ); ?>
                    </label>
                    <br>
                    <label>
                        <input type="radio" name="se_source_type" value="se_event" required <?php checked( 'se_event', isset( $_POST['se_source_type'] ) ? $_POST['se_source_type'] : '' ); ?>>
                        <?php _e( 'Bestehendes SE Event', 'se_em' ); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e( 'Als wiederkehrendes Event erstellen?', 'se_em' ); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="se_is_recurring" value="1" <?php checked( 1, isset( $_POST['se_is_recurring'] ) ? 1 : 0 ); ?>>
                        <?php _e( 'Ja, mit Standard-Wiederholungsregel (wöchentlich)', 'se_em' ); ?>
                    </label>
                    <p class="description"><?php _e( 'Wenn aktiviert, wird das neue Event als wiederkehrend markiert und erhält eine Standard-Wiederholungsregel.', 'se_em' ); ?></p>
                </td>
            </tr>
        </table>
        
        <?php wp_nonce_field( 'se_copy_action', 'se_copy_nonce' ); ?>
        <?php submit_button( __( 'Event kopieren und erstellen', 'se-em' ) ); ?>
    </form>

</div>
