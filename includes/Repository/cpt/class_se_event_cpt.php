<?php
defined('ABSPATH') || exit;
// register cpt
class SE_Event_CPT {

    public function __construct() {
        add_action('init', [$this, 'register_cpt']);
    }

    public function register_cpt(): void {

        $labels = [
            'name'               => 'Events',
            'singular_name'      => 'Event',
            'add_new'            => 'Neues Event',
            'add_new_item'       => 'Neues Event erstellen',
            'edit_item'          => 'Event bearbeiten',
            'new_item'           => 'Neues Event',
            'view_item'          => 'Event ansehen',
            'search_items'       => 'Events durchsuchen',
            'not_found'          => 'Keine Events gefunden',
            'not_found_in_trash' => 'Keine Events im Papierkorb',
            'menu_name'          => 'SE Events'
        ];

        $args = [
            'labels'             => $labels,
            'public'             => true,
            'show_in_menu'       => false,
            'menu_position'      => 20,
            'menu_icon'          => 'dashicons-calendar-alt',
            'supports'           => ['title', 'editor', 'thumbnail', 'excerpt', 'author'],
            'has_archive'        => true,
            'rewrite'            => ['slug' => 'se_event'],
            'show_in_rest'       => true,
            'capability_type'    => 'post',
        ];

       register_post_type('se_event', $args);
    }
}

