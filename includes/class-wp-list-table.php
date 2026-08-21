<?php
abstract class WP_List_Table {
    // Eigenschaften (Properties)
    protected $items;
    protected $_column_headers;
    protected $screen;
    protected $_pagination_args;

    // Konstruktor
    public function __construct( $args = [] )

    // ZENTRALE METHODEN
    public function prepare_items()
    public function display()

    // HEADER / FOOTER
    protected function display_tablenav( $which )
    protected function print_column_headers()

    // SPALTEN
    public function get_columns()
    protected function get_sortable_columns()
    protected function get_hidden_columns()

    // ZEILEN
    protected function single_row( $item )
    protected function single_row_columns( $item )

    // SPALTEN-RENDERING
    protected function column_default( $item, $column_name )
    protected function column_cb( $item )

    // BULK ACTIONS
    protected function get_bulk_actions()
    public function current_action()

    // VIEWS (Alle | Entwürfe | …)
    protected function get_views()
    public function views()

    // SUCHE
    public function search_box()

    // PAGINATION
    protected function pagination()
    protected function set_pagination_args()

    // SCREEN OPTIONS
    public function get_items_per_page()

    // HILFSMETHODEN
    protected function row_actions()
}
