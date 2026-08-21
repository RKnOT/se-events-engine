<?php
defined('ABSPATH') || exit;

class SE_Events_List_View {

    /* =========================
       PUBLIC RENDER
       ========================= */

    public static function render(
        string $search = '',
        int $paged = 1
        ): void {

        global $wpdb;

        /* =========================
           PER PAGE
           ========================= */

        $allowed_per_page = [10, 15, 25, 50];
        $per_page = 10;

        if (isset($_GET['per_page']) && in_array((int) $_GET['per_page'], $allowed_per_page, true)) {
            $per_page = (int) $_GET['per_page'];
        }

        $table  = SE_EVENTS_TABLE;
        $offset = ($paged - 1) * $per_page;

        /* =========================
           SEARCH
           ========================= */

        $where  = '';
        $params = [];

        if ($search !== '') {
            $where = "WHERE event_name LIKE %s OR IFNULL(post_content,'') LIKE %s";
            $like  = '%' . $wpdb->esc_like($search) . '%';
            $params[] = $like;
            $params[] = $like;
        }

        /* =========================
           TOTAL
           ========================= */

        $total = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} {$where}",
                ...$params
            )
        );

        /* =========================
           DATA
           ========================= */

        $sql = "
            SELECT
                event_id,
                event_name,
                post_content,
                location_id,
                event_start_date,
                event_start_time,
                event_owner
            FROM {$table}
            {$where}
            ORDER BY event_start_date DESC
            LIMIT %d OFFSET %d
        ";

        $params[] = $per_page;
        $params[] = $offset;

        $events = $wpdb->get_results(
            $wpdb->prepare($sql, ...$params)
        );

        /* =========================
           UI
           ========================= */

        echo '<form method="post">';

        self::render_bulk_actions('top');
        if (!$events) {
            echo '<p>Keine Events gefunden.</p>';
            echo '</form>';
            return;
        }

        echo '<table class="wp-list-table widefat striped table-view-list">';
        self::render_table_head();

        echo '<tbody>';

        foreach ($events as $event) {
            self::render_row($event);
        }

        echo '</tbody></table>';

        self::render_bulk_actions('bottom');
        self::render_pagination($total, $paged, $per_page);

        echo '</form>';
    }

    /* =========================
       TABLE HEAD
       ========================= */

    private static function render_table_head(): void {
        echo '
        <thead>
        <tr>
            <td class="manage-column column-cb check-column">
                <input type="checkbox" />
            </td>
            <th class="column-title column-primary">Titel</th>
            <th>Veranstaltungsort</th>
            <th>Datum und Uhrzeit</th>
            <th>Eigentümer</th>
        </tr>
        </thead>';
    }

    /* =========================
       SINGLE ROW
       ========================= */

    private static function render_row(object $event): void {

        $edit_url = admin_url('admin.php?page=se-events-edit&id=' . $event->event_id);
        $view_url = site_url('/event/' . $event->event_id);

        echo '<tr>';

        /* CHECKBOX */
        echo '<th scope="row" class="check-column">
                <input type="checkbox" name="event_ids[]" value="' . esc_attr($event->event_id) . '" />
              </th>';

        /* TITLE + CONTENT + ACTIONS */
        echo '<td class="column-title column-primary has-row-actions">';
        echo '<strong><a class="row-title" href="' . esc_url($edit_url) . '">' . esc_html($event->event_name) . '</a></strong>';

        echo '<div class="row-excerpt">';
        echo esc_html(self::excerpt($event->post_content, 100));
        echo '</div>';

        echo '<div class="row-actions">';
        echo '<span class="edit"><a href="' . esc_url($edit_url) . '">Bearbeiten</a> | </span>';
        echo '<span class="inline hide-if-no-js"><a href="#">Schnellbearbeitung</a> | </span>';
        echo '<span class="trash"><a href="#">Papierkorb</a> | </span>';
        echo '<span class="view"><a target="_blank" href="' . esc_url($view_url) . '">Anzeigen</a> | </span>';
        echo '<span class="duplicate"><a href="#"></a></span>';
        echo '</div>';

        echo '</td>';

        /* LOCATION */
        echo '<td>' . esc_html($event->location_id) . '</td>';

        /* DATE */
        echo '<td>';
        echo esc_html(
            date_i18n(
                'd. F Y H:i',
                strtotime($event->event_start_date . ' ' . $event->event_start_time)
            )
        );
        echo '</td>';

        /* OWNER */
        echo '<td>' . esc_html($event->event_owner) . '</td>';

        echo '</tr>';
    }

    /* =========================
       BULK ACTIONS
       ========================= */

    private static function render_bulk_actions(string $position): void {

        echo '<div class="tablenav ' . esc_attr($position) . '">';
        echo '<div class="alignleft actions bulk-actions">';

        echo '<label class="screen-reader-text" for="bulk-action-selector-' . esc_attr($position) . '">
                Mehrfachaktionen auswählen
              </label>';


        echo '</div>';
        echo '</div>';
    }



    /* =========================
       PAGINATION
       ========================= */

    private static function render_pagination(
        int $total,
        int $paged,
        int $per_page
        ): void {

        $total_pages = (int) ceil($total / $per_page);
        if ($total_pages <= 1) {
            return;
        }

        echo '<div class="tablenav bottom"><div class="tablenav-pages">';

        echo paginate_links([
            'base'      => add_query_arg('paged', '%#%'),
            'current'   => $paged,
            'total'     => $total_pages,
            'prev_text' => '«',
            'next_text' => '»',
        ]);

        echo '</div></div>';
    }

    /* =========================
       HELPERS
       ========================= */

    private static function excerpt(string $text, int $words = 100): string {
        return wp_trim_words(wp_strip_all_tags($text), $words);
    }
}
