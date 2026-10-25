<?php
defined('ABSPATH') || exit;
function se_events_modern_shortcode($atts = []) {
    global $wpdb;
    
    ob_start();
    
    $table = SE_EVENTS_TABLE;

    // ==========================
    // 🔹 Pagination
    // ==========================

    $per_page = 5;

    $paged = max(
        1,
        (int) ($_GET['se_page'] ?? 1)
    );

    $offset = ($paged - 1) * $per_page;


    // ==========================
    // 🔹 DB Querry instanzieren
    // ==========================
    $table = SE_EVENTS_TABLE;
    $event_db = new SE_Event_Repository();

    $search = sanitize_text_field($_GET['se_search'] ?? '');
    $event_date = sanitize_text_field($_GET['se_date'] ?? 'future');
    // ==========================
    // 🔹 Gesamtanzahl
    // ==========================
   
    $args = [
    'status'     => 'publish',
    'event_type' => ['se, se_event, rce'],
    'event_date' => 'future',
    'sort'       => 'ASC',
    'search'     => sanitize_text_field($_GET['se_search'] ?? ''),
];
    $total_items = $event_db->count_filtered($args);
  
    $total_pages = ceil($total_items / $per_page);
    // debug
    //print_r('total items: ' . $total_items . ' total pages: ' . $total_pages);

    $paged = max(
        1,
        (int) ($_GET['se_page'] ?? 1)
    );
    if ($paged > $total_pages) {
        $paged =  $total_pages;
    }
    $offset = ($paged - 1) * $per_page;
    // ==========================
    // 🔹 Events laden
    // ==========================

    $today = current_time('Y-m-d H:i');
    $search = sanitize_text_field($_GET['se_search'] ?? '');

    //------------------------------------
   
    $args = [
        'status'     => 'publish',
        'event_type' => ['se, se_event, rce'],
        'event_date' => 'future',
        'sort'       => 'ASC',
        'search'     => sanitize_text_field(
            $_GET['se_search'] ?? ''
        ),
        'limit'      => $per_page,
        'offset'     => $offset,
    ];
    $events = $event_db->get_events($args);
    $total_items = $event_db->count_filtered($args);
    //----------------------------------------------
    if (empty($events)) {
        return '<p>Keine Veranstaltungen gefunden.</p>';
    }
    // =====================================================
    //    SEARCH BAR
    // ===================================================== 
    ?>
    <div class="se-events-engine">
    <form class="se-modern-search" method="get">

    <label class="se-search-field">

        <span class="screen-reader-text">
            Veranstaltungen suchen
        </span>

        <input
            type="search"
            class="search-field"
            name="se_search"
            placeholder="Veranstaltungen suchen ..."
            value="<?php echo esc_attr($_GET['se_search'] ?? ''); ?>"
        />

    </label>

    <button type="submit" class="search-submit se-search-button">

        <?php echo tortuga_get_svg('search'); ?>

        <span class="screen-reader-text">
            Veranstaltungen suchen
        </span>

    </button>

</form>

        
   
    </div>
    <?php
    // ==========================
    // 🔹 Pagination HTML
    // ==========================

    $pagination = '';

    if ($total_pages > 1) {

        $base_url = remove_query_arg('se_page');

        $pagination .= '<div class="se-pagination">';

        // Anzahl
        $pagination .= '<span class="se-pagination-count">';
        $pagination .= $total_items . ' Einträge';
        $pagination .= '</span>';

        // Erste Seite
        if ($paged > 1) {

            $pagination .= sprintf(
                '<a class="se-page-nav" href="%s">«</a>',
                esc_url(add_query_arg('se_page', 1, $base_url))
            );

            $pagination .= sprintf(
                '<a class="se-page-nav" href="%s">‹</a>',
                esc_url(add_query_arg('se_page', $paged - 1, $base_url))
            );

        } else {

            $pagination .= '<span class="se-page-nav disabled">«</span>';
            $pagination .= '<span class="se-page-nav disabled">‹</span>';
        }

        // Input
        $pagination .= '
            <form class="se-pagination-form" method="get">

                <input
                    type="number"
                    min="1"
                    max="' . esc_attr($total_pages) . '"
                    name="se_page"
                    value="' . esc_attr($paged) . '"
                    class="se-page-input"
                >

            </form>
        ';

        // Gesamtseiten
        $pagination .= '
            <span class="se-pagination-total">
                von ' . $total_pages . '
            </span>
        ';

        // Weiter
        if ($paged < $total_pages) {

            $pagination .= sprintf(
                '<a class="se-page-nav" href="%s">›</a>',
                esc_url(add_query_arg('se_page', $paged + 1, $base_url))
            );

            $pagination .= sprintf(
                '<a class="se-page-nav" href="%s">»</a>',
                esc_url(add_query_arg('se_page', $total_pages, $base_url))
            );

        } else {

            $pagination .= '<span class="se-page-nav disabled">›</span>';
            $pagination .= '<span class="se-page-nav disabled">»</span>';
        }

        $pagination .= '</div>';
    }

  

    ?>
    <div class="se-events-engine">
        <div class="se-modern-events-wrapper">
            <!-- 🔥 Pagination oben -->
            <?php echo $pagination; ?>
            <?php
            $current_month = '';
            foreach ($events as $event) :
                $timestamp = strtotime(
                    $event['event_start_date']
                    . ' '
                    . $event['event_start_time']
                );
                $month = date_i18n('F Y', $timestamp);
                if ($month !== $current_month) {
                    $current_month = $month;
                    echo '<h3 class="se-modern-month">'
                        . esc_html($month)
                        . '</h3>';
                }
                /*
                $image = '';

                if (!empty($event['post_id'])) {
                    $image = get_the_post_thumbnail_url($event['post_id'], 'medium');
                }
                */
                $image = '';
                $thumbnail_id = 0;
                if (!empty($event['post_id'])) {

                    $thumbnail_id = get_post_thumbnail_id($event['post_id']);

                    if ($thumbnail_id) {
                        $image = wp_get_attachment_image_url(
                            $thumbnail_id,
                            'medium'
                        );
                        
                    }

                }

                $day   = date_i18n('d', strtotime($event['event_start']));
                $month = strtoupper(date_i18n('M', strtotime($event['event_start'])));
                $svg = '
                <svg xmlns="http://www.w3.org/2000/svg" width="200" height="200">
                    <rect width="200" height="200" rx="12" fill="#f3f3f3"/>
                    <rect width="200" height="48" fill="#c62828"/>
                    <text x="100" y="35" font-family="Arial" font-size="20" fill="white" text-anchor="middle">'.$month.'</text>
                    <text x="100" y="135" font-family="Arial" font-size="82" font-weight="bold" fill="#444" text-anchor="middle">'.$day.'</text>
                </svg>';

                $image_calender = 'data:image/svg+xml;utf8,' . rawurlencode($svg);
                ?>
                <div class="se-modern-event">
                    <div class="se-modern-image">
                        <?php if ($image) : ?>
                            <img
                                src="<?php echo esc_url($image); ?>"
                                alt=""
                            >

                            <?php
                            // ki generiertes bild beschriften
                                $ki = get_post_meta($event['post_id'], 'KI', true );
                                if ( ! empty( $ki ) ) {
                                    echo '<div class="se-search-ki">';
                                    echo esc_html( $ki );
                                    echo '</div>';
                                }
                                ?>


                        <?php endif; ?>
                    </div>
                    <div class="se-modern-divider"></div>
                    <div class="se-modern-content">
                        <h2 class="se-modern-event-title">
                            <a href="<?php echo esc_url(get_permalink($event['post_id'])); ?>">
                                <?php echo esc_html($event['event_name']); ?>
                            </a>
                        </h2>
                        <div class="se-modern-meta">
                            <div class="se-modern-meta-item">
                              <img
                                    src="<?php echo esc_attr( $image_calender); ?>"
                                    alt=""
                                    style="width:30px; height:30px; object-fit:contain;"
                                >
                                <?php echo date_i18n('m.Y', $timestamp); ?>


                               
                            </div>
                            <div class="se-modern-meta-item">
                                🕒
                                <?php echo substr($event['event_start_time'],0,5); ?>
                                -
                                <?php echo substr($event['event_end_time'],0,5); ?>
                            </div>
                            <div class="se-modern-meta-item">
                                📍
                                <?php 
                                    $location ='';
                                    if (!empty($event['location_id'])) {
                                        $location =  $event_db->get_location_name($event['location_id']);    
                                    };
                                    echo esc_html($location);
                                ?>
                            </div>
                        </div>
                        <div class="se-modern-description">
                            <?php
                            echo wp_trim_words(
                                wp_strip_all_tags(
                                    get_post_field(
                                        'post_content',
                                        $event['post_id']
                                    )
                                ),
                                35,
                                ' [...]'
                            );
                            ?>
                        </div>
                        <a
                            class="se-modern-button"
                            href="<?php echo esc_url(get_permalink($event['post_id'])); ?>"
                        >
                            WEITERE INFORMATIONEN
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
            <!-- 🔥 Pagination unten -->
            <?php echo $pagination; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
/**
 * Shortcode:
 * [fe_next_events]
 */
function fe_next_events_shortcode(){     
    /*
            $atts = shortcode_atts([
            'limit' => 10,
            
            ], $atts);

        $limit = intval($atts['limit']);
    */
    global $wpdb;
    ob_start();
    $table = SE_EVENTS_TABLE;
    $event_db = new SE_Event_Repository();
    $args = [
                'status'     =>  'publish',
                'event_type' => ['se, rce'],
                'event_date' => 'future',
                'sort' => 'ASC',
                'search'     => '',
                'limit'      => 5,
                'offset'     => 0,
            ];
    $events =  $event_db->get_events($args);
  
    if (empty($events)) {
        return '<div class="fe-events-empty">Keine Veranstaltungen gefunden.</div>';
    }
    ?>
    <div class="fe-events-wrapper">
        <hr>
        <?php foreach ($events as $event):
              $time = substr($event['event_start_time'], 0 ,5);
              $date  = date_i18n(' F ', strtotime($event['event_start']));
                $day   = date_i18n('d', strtotime($event['event_start']));
                $month = strtoupper(date_i18n('M', strtotime($event['event_start'])));
                $svg = '
                <svg xmlns="http://www.w3.org/2000/svg" width="200" height="200">
                    <rect width="200" height="200" rx="12" fill="#f3f3f3"/>
                    <rect width="200" height="48" fill="#c62828"/>
                    <text x="100" y="35" font-family="Arial" font-size="20" fill="white" text-anchor="middle">'.$month.'</text>
                    <text x="100" y="135" font-family="Arial" font-size="82" font-weight="bold" fill="#444" text-anchor="middle">'.$day.'</text>
                </svg>';

                 $image_calender = 'data:image/svg+xml;utf8,' . rawurlencode($svg);


            ?>
            <div class="fe-event-box">
                <div class="fe-event-border"></div>
                <div class="fe-event-content">
                    <div class="fe-event-title">
                       <a href="<?php echo esc_url(get_permalink($event['post_id'])); ?>">
                            <?php echo esc_html($event['event_name']); ?>
                        </a>
                    </div>
                    <div class="fe-event-meta">
                        <div class="fe-event-row">
                               <img
                                    src="<?php echo esc_attr($image_calender); ?>"
                                    alt=""
                                    style="width:30px; height:30px; object-fit:contain;"
                                >
                                <?php echo $date .  ' | ' . $time . ' Uhr'; ?>
                        </div>
                        <?php if (!empty($event['location_id'])): 
                           $location =  $event_db->get_location_name($event['location_id']);
                        ?>
                            <div class="fe-event-row">
                                    📍
                                    <?php echo esc_html( $location ); ?>
                                    <hr>
                                
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode(
    'fe_next_events', 
    'fe_next_events_shortcode');

add_shortcode(
    'se_events_modern',
    'se_events_modern_shortcode'
);
