<?php
defined('ABSPATH') || exit;

class SE_Events_Quick_Edit {

    public static function get_quick_edit_link(int $post_id): string {
      
        $url = wp_nonce_url(
            admin_url('admin.php?page=se-events&action=quick_edit&id=' . $post_id),
            'quick_edit_event_' . $post_id
        );
        
        return '<a href="' . esc_url($url) . '">Schnellbearbeitung</a>';
    }

    public static function render_row($column_count=5) {
        ?>
        <tr id="inline-edit" class="inline-edit-row quick-edit-row" style="display:none;">
            <td colspan="<?php echo esc_attr($column_count); ?>" class="colspanchange">

                <fieldset class="inline-edit-col-left">
                    <div class="inline-edit-col">

                        <label>
                            <span class="title">Titel</span>
                            <span class="input-text-wrap">
                                <input type="text" name="se_title">
                            </span>
                        </label>

                        <label>
                            <span class="title">Autor</span>
                            <?php wp_dropdown_users(['name' => 'se_author']); ?>
                        </label>

                        <label>
                            <span class="title">Passwort</span>
                            <span class="input-text-wrap">
                                <input type="text" name="se_password">
                            </span>
                        </label>

                        <label>
                            <input type="checkbox" name="se_private">
                            <span class="checkbox-title">Privat</span>
                        </label>

                    </div>
                </fieldset>

                <fieldset class="inline-edit-col-center">
                    <div class="inline-edit-col">

                        <span class="title">Veranstaltungskategorien</span>
                        <div class="categorydiv">
                            <?php
                            wp_terms_checklist(0, [
                                'taxonomy' => 'se_event_category'
                            ]);
                            ?>
                        </div>

                    </div>
                </fieldset>

                <fieldset class="inline-edit-col-right">
                    <div class="inline-edit-col">

                        <label>
                            <span class="title">Veranstaltungsschlagwörter</span>
                            <textarea name="se_tags"></textarea>
                        </label>

                        <label>
                            <span class="title">Status</span>
                            <select name="se_status">
                                <option value="publish">Veröffentlicht</option>
                                <option value="draft">Entwurf</option>
                                <option value="private">Privat</option>
                            </select>
                        </label>

                    </div>
                </fieldset>

                <div class="inline-edit-save submit">
                    <button type="button" class="button cancel alignleft">
                        Abbrechen
                    </button>

                    <button type="button" class="button button-primary save alignright">
                        Aktualisieren
                    </button>

                    <span class="spinner"></span>
                </div>

            </td>
        </tr>
        <?php
    }

}



  
