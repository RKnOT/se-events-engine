<?php
defined('ABSPATH') || exit;

require_once ABSPATH . 'wp-admin/includes/post.php';
require_once ABSPATH . 'wp-admin/includes/template.php';

if (!current_user_can('se_events_edit')) {
    wp_die('Keine Berechtigung');
}

$event_id = (int) $_GET['event_id'];
#$event_id =	13855;

$event = SE_Events_Repository::get_event($event_id);
$notes = SE_Events_Repository::get_notes($event_id);

echo '<div class="wrap"><h1>Event bearbeiten</h1>';

echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
wp_nonce_field('se_event_save');

echo '<input type="hidden" name="action" value="se_event_save">';
echo '<input type="hidden" name="event_id" value="' . esc_attr($event_id) . '">';

echo '<div id="poststuff"><div id="post-body" class="metabox-holder columns-2">';
echo '<div id="post-body-content">';

echo '<input type="text" name="event_name" class="widefat" style="font-size:1.6em;margin-bottom:15px" value="' . esc_attr($event['event_name'] ?? '') . '">';

wp_editor($notes, 'event_description', [
    'textarea_name' => 'event_description',
    'media_buttons' => true,
    'textarea_rows' => 14,
]);

echo '</div><div id="postbox-container-1" class="postbox-container">';
echo '<div class="postbox"><h2 class="handle">Veröffentlichen</h2><div class="inside">';
submit_button('Aktualisieren', 'primary', 'submit', false);
echo '</div></div></div></div></div></form></div>';
