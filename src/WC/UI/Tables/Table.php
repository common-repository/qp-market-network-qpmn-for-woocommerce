<?php

namespace QPMN\Partner\WC\UI\Tables;

/**
 * create WP style table from WP list table class which cloned from WP core private class
 * WP suggest to copy and paste this class for reduce risk
 * Ref: https://developer.wordpress.org/reference/classes/wp_list_table/
 * 
 */
interface Table 
{
	/**
	 * Returns the count of records in the database.
	 *
	 * @return null|string
	 */
	public function record_count();
	

	public function no_items();
	

	/**
	 * Render a column when no column specific method exists.
	 *
	 * @param array $item
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	function column_default($item, $column_name);
	

	/**
	 * Render the bulk edit checkbox
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	function column_cb($item);

	/**
	 * Associative array of columns
	 *
	 * @return array
	 */
	function get_columns();
	

	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
	public function get_sortable_columns();

	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */
	public function get_bulk_actions();
	

	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items();
	

	public function process_bulk_action();
	
}
