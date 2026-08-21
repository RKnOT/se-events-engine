<?php
defined('ABSPATH') || exit;


class SE_Events_Del_History_Events
{
    protected $repo;
    protected $table;

    public function __construct()
    {
        global $wpdb;
        $this->repo  = new SE_Event_Repository();
        $this->table = SE_EVENTS_TABLE;
    }

    public function render()
    {
        if (
            isset($_POST['se_delete_history']) &&
            check_admin_referer('se_delete_history')
        ) {
            $this->delete_selected(
                array_map('intval', $_POST['event_ids'] ?? [])
            );
        }

        if (
            isset($_POST['se_delete_all_history']) &&
            check_admin_referer('se_delete_all_history')
        ) {
            $this->delete_all();
        }

        $se  = $this->get_history_events('se');
        $rce = $this->get_history_events('rce');
        $posts = $this->get_history_posts();
  

        ?>
        <div class="wrap">
            <h1>Vergangene Events löschen</h1>

            <form method="post">
                <?php wp_nonce_field('se_delete_history'); ?>

                <h2>SE Events</h2>
                <?php $this->render_table($se); ?>

                <h2>RCE Events</h2>
                <?php $this->render_table($rce); ?>

                <h2>SE_EVENT Posts</h2>

                <?php $this->render_post_table($posts); ?>

                <p>
                    <button class="button button-primary"
                            name="se_delete_history"
                            onclick="return confirm('Ausgewählte Events löschen?');">
                        Ausgewählte löschen
                    </button>
                </p>
            </form>

            <hr>

            <form method="post">  
              <?php wp_nonce_field('se_delete_all_history'); ?>
        
    <p>

                <button class="button button-secondary"
                        name="se_delete_all_history"
                        onclick="return confirm('ALLE vergangenen Events löschen?');">
                    Alle vergangenen Events löschen
                </button>
</p>
            </form>

        </div>
        <?php
    }

    protected function get_history_events(string $type): array
    {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT *
                 FROM {$this->table}
                 WHERE event_type=%s
                   AND event_end < %s
                 ORDER BY event_end DESC",
                $type,
                current_time('mysql')
            )
        );
    }
    
    protected function get_history_posts(): array
    {
        global $wpdb;

        $today = current_time('Y-m-d H:i');

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    p.ID,
                    p.post_title,
                    p.post_date,
                    e.event_name,
                    e.event_type
                FROM {$wpdb->posts} p
                INNER JOIN {$this->table} e
                    ON e.event_id = p.ID
                WHERE p.post_type = 'se_event'
                AND p.post_status IN ('publish','private')
                AND e.event_type IN ('se','rce')
                AND p.post_date < %s
                ORDER BY p.post_date DESC",
                $today
            )
        );
    }
    protected function render_table(array $events): void
    {
        ?>
        <table class="widefat striped">
            <thead>
            <tr>
                <th width="40"></th>
                <th>Event</th>
                <th>Beginn</th>
                <th>Ende</th>
                <th>Post-ID</th>
                <th>Event-ID</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($events)): ?>
                <tr><td colspan="6">Keine vergangenen Events vorhanden.</td></tr>
            <?php else: ?>
                <?php 
                    foreach ($events as $e): 
                    
                    ?>
                    <tr>
                        <td>
                            <input type="checkbox"
                                   name="event_ids[]"
                                   value="<?php echo esc_attr($e->event_id); ?>">
                        </td>
                        <td><?php echo esc_html($e->event_name); ?></td>
                        <td><?php echo esc_html($e->event_start); ?></td>
                        <td><?php echo esc_html($e->event_end); ?></td>
                        <td><?php echo intval($e->post_id); ?></td>
                        <td><?php echo intval($e->post_id); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
        <?php
    }

    
    protected function render_post_table(array $posts): void
    {
        ?>
        <table class="widefat striped">
            <thead>
            <tr>
                <th width="40"></th>
                <th>Titel</th>
                <th>Datum</th>
                <th>mit Event Type</th>
                <th>Post-ID</th>
            </tr>
            </thead>

            <tbody>

            <?php if (empty($posts)): ?>

                <tr>
                    <td colspan="4">
                        Keine vergangenen Posts vorhanden.
                    </td>
                </tr>

            <?php else: ?>

                <?php foreach ($posts as $post): ?>

                    <tr>

                        <td>

                            <input
                                type="checkbox"
                                name="post_ids[]"
                                value="<?php echo esc_attr($post->ID); ?>">

                        </td>

                        <td>

                            <?php echo esc_html($post->post_title); ?>

                        </td>
                               <td>

                            <?php echo esc_html($post->post_date); ?>

                        </td>
                         <td>

                            <?php echo esc_html($post->event_type); ?>

                        </td>

                 

                        <td>

                            <?php echo intval($post->ID); ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php endif; ?>

            </tbody>

        </table>
        <?php
    }

    protected function delete_selected(array $event_ids): void
    {
        global $wpdb;

        foreach ($event_ids as $event_id) {

            $event = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT post_id
                    FROM {$this->table}
                    WHERE event_id = %d",
                    $event_id
                )
            );

            if (!$event) {
                continue;
            }

            if ((int)$event->post_id > 0) {
                wp_delete_post((int)$event->post_id, true);
            }

            $wpdb->delete(
                $this->table,
                ['event_id' => $event_id],
                ['%d']
            );

           
        }
        
        foreach ($_POST['post_ids'] ?? [] as $post_id) {

                wp_delete_post(
                    (int)$post_id,
                    true
                );

            }

        
        
            echo '<div class="notice notice-success"><p>Ausgewählte Events wurden gelöscht.</p></div>';
    }



    protected function delete_all(): void
    {
        global $wpdb;

        $event_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT event_id
                FROM {$this->table}
                WHERE event_type IN (%s,%s)
                AND event_end < %s",
                'se',
                'rce',
                current_time('mysql')
            )
        );
        foreach ($this->get_history_posts() as $post) {

            wp_delete_post(
                $post->ID,
                true
            );

        }

        if (empty($event_ids)) {

            echo '<div class="notice notice-warning"><p>Keine vergangenen Events gefunden.</p></div>';
            return;
        }

        $this->delete_selected(
            array_map('intval', $event_ids)
        );
    }
}
