<?php

namespace QPMN\Partner\WC\UI\Tables;

use QPMN\Partner\Qpmn_i18n;
use QPMN\Partner\Qpmn_Install;
use QPMN\Partner\WC\QPMN_WC_Order;
use QPMN\Partner\WP\Core\WPAdmin\Includes\WP_List_Table;
use WP_Query;

/**
 * create WP style table from WP list table class which cloned from WP core private class
 * WP suggest to copy and paste this class for reduce risk
 * Ref: https://developer.wordpress.org/reference/classes/wp_list_table/
 */
class Order extends WP_List_Table implements Table
{
	private $orderArgs;
	const ORDER_FLAG = Qpmn_Install::META_IS_QPMN_ORDER;
	const ORDER_NONCE_NAME = 'qpmn_cgp_order';

	const META_QPMN_ORDER_LAST_SYNC_AT = Qpmn_Install::META_QPMN_ORDER_LAST_SYNC_AT;
	const META_QPMN_ORDER_ID     		= Qpmn_Install::META_QPMN_ORDER_ID;
	const META_QPMN_ORDER_NUMBER     	= Qpmn_Install::META_QPMN_ORDER_NUMBER;
	const META_QPMN_ORDER_STATUS 		= Qpmn_Install::META_QPMN_ORDER_STATUS;
	const META_QPMN_ORDER_DATETIME   	= Qpmn_Install::META_QPMN_ORDER_DATETIME;
	const META_QPMN_ORDER_ERROR_MSG   	= Qpmn_Install::META_QPMN_ORDER_ERROR_MSG;

	const META_QPMN_ORDER_SUBTOTAL      = Qpmn_Install::META_QPMN_ORDER_SUBTOTAL;
	const META_QPMN_ORDER_SHIPPING      = Qpmn_Install::META_QPMN_ORDER_SHIPPING;
	const META_QPMN_ORDER_TOTAL         = Qpmn_Install::META_QPMN_ORDER_TOTAL;

	const PAYMENT_STATUS = Qpmn_Install::CGP_ORDER_PAYMENT_STATUS;

	public function __construct()
	{
		parent::__construct([
			'singlar' => Qpmn_i18n::__('Order'),
			'plural' => Qpmn_i18n::__('Orders'),
			'ajax' => false
		]);

		$this->orderArgs = array(
			'post_type' => 'shop_order',
			'post_status' => array_keys(wc_get_order_statuses()),
			'meta_query' => array(
				array(
					'key' => self::ORDER_FLAG,
					'value' => true,
				),
			),
		);
	}

	public function get_orders($per_page = 5, $page_number = 1)
	{

		$args = $this->orderArgs;

		$args['posts_per_page'] = 5;
		if ($per_page > 0) {
			$args['posts_per_page'] = $per_page;
			$args['paged'] = $page_number;
		}

		$order_query = new WP_Query($args);
		$orders = array_map(function ($d) {
			return (array) $d;
		}, $order_query->get_posts());

		return $orders;
	}

	/**
	 * Returns the count of records in the database.
	 *
	 * @return null|string
	 */
	public function record_count()
	{
		$args = $this->orderArgs;
		$order_query = new WP_Query($args);
		$ordersCount = $order_query->found_posts;

		return $ordersCount;
	}

	/**
	 * Render a column when no column specific method exists.
	 *
	 * @param array $item
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	public function column_default($item, $column_name)
	{
		switch ($column_name) {
			case 'post_title':
				return $item[$column_name];
		}
	}

	/**
	 * Render the bulk edit checkbox
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	function column_cb($item)
	{
		return sprintf(
			'<input type="checkbox" name="bulk[]" value="%s" />',
			esc_attr($item['ID'])
		);
	}

	/**
	 * Associative array of columns
	 *
	 * @return array
	 */
	function get_columns()
	{
		$columns = [
			'cb' => '<input type="checkbox" />',
			'ID' => Qpmn_i18n::__('ID'),
			'post_title' 	=> Qpmn_i18n::__('Title'),
			'post_status' 	=> Qpmn_i18n::__('Order Status'),
			'cgp_order_number' => Qpmn_i18n::__('QPMN order Number'),
			'cgp_order_status' => Qpmn_i18n::__('QPMN order Status'),
			'cgp_order_sync_msg' => Qpmn_i18n::__('QPMN order Sync Message'),
			'cgp_order_last_sync' => Qpmn_i18n::__('QPMN order Last Sync'),
		];

		return $columns;
	}

	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
	public function get_sortable_columns()
	{
		$sortable_columns = array(
			'ID' => array('ID', true),
			'post_title' => array('post_title', false),
			'post_status' => array('post_status', false)
		);

		return $sortable_columns;
	}

	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */
	public function get_bulk_actions()
	{
		$actions = [
			'bulk-create' => Qpmn_i18n::__('Create QPMN orders'),
			'bulk-update' => Qpmn_i18n::__('Update QPMN orders'),
		];

		return $actions;
	}

	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items()
	{

		$this->_column_headers = $this->get_column_info();

		/** Process bulk action */
		$this->process_bulk_action();

		//defined in admin menu screen option
		$per_page = $this->get_items_per_page('order_per_page', 5);
		$current_page = $this->get_pagenum();
		$total_items = $this->record_count();

		$this->set_pagination_args([
			'total_items' => $total_items, //WE have to calculate the total number of items
			'per_page' => $per_page //WE have to determine how many items to show on a page
		]);

		$this->items = $this->get_orders($per_page, $current_page);
	}

	//request actions
	public function process_bulk_action()
	{

		//Detect when a bulk action is being triggered...
		if ('create' === $this->current_action()) {
			$this->create_cgp_order();
		}

		if ('update' === $this->current_action()) {
			$this->update_cgp_order();
		}

		// If the delete bulk action is triggered
		if ((isset($_POST['action']) && $_POST['action'] == 'bulk-create')) {
			$this->bulk_create_cgp_order();
		}

		if ((isset($_POST['action']) && $_POST['action'] == 'bulk-update')) {
			$this->bulk_update_cgp_order();
		}
	}

	private function create_cgp_order()
	{
		// In our file that handles the request, verify the nonce.
		if (!wp_verify_nonce($_REQUEST['_wpnonce'], self::ORDER_NONCE_NAME)) {
			die('Go get a life script kiddies');
		} else {
			// self::delete_customer(absint($_GET['customer']));
			$QPWCOrder = new QPMN_WC_Order();
			$QPWCOrder->create_cgp_order(absint($_GET['order']));

			wp_redirect(esc_url(add_query_arg()));
			exit;
		}
	}

	private function update_cgp_order()
	{
		// In our file that handles the request, verify the nonce.
		if (!wp_verify_nonce($_REQUEST['_wpnonce'], self::ORDER_NONCE_NAME)) {
			die('Go get a life script kiddies');
		} else {
			// self::delete_customer(absint($_GET['customer']));
			$QPWCOrder = new QPMN_WC_Order();
			$QPWCOrder->get_cgp_order(absint($_GET['order']));
			wp_redirect(esc_url(add_query_arg()));
			exit;
		}
	}

	/**
	 * action function
	 *
	 * @return void
	 */
	private function bulk_create_cgp_order()
	{
		if (is_array($ids = $this->recursive_sanitize_text_field($_POST['bulk']))) {
			$QPWCOrder = new QPMN_WC_Order();
			$QPWCOrder->bulk_create_cgp_order($ids);
		}

		wp_redirect(esc_url(add_query_arg()));
		exit;
	}

	/**
	 * action function
	 *
	 * @return void
	 */
	private function bulk_update_cgp_order()
	{
		if (is_array($ids = $this->recursive_sanitize_text_field($_POST['bulk']))) {
			$QPWCOrder = new QPMN_WC_Order();
			$CGPOrderIds = [];

			foreach ($ids as $id) {
				if (is_numeric($id)) {
					$CGPOrderIds[$id] = $QPWCOrder->get_order_meta($id, self::META_QPMN_ORDER_ID);
				}
			}

			if (!empty($CGPOrderIds)) {

				//remove empty entries
				$CGPOrderIds = array_filter($CGPOrderIds, 'strlen');

				// loop over the array of record IDs and delete them
				$QPWCOrder->bulk_get_cgp_order($CGPOrderIds);
			}
		}

		wp_redirect(esc_url(add_query_arg()));
		exit;
	}

	/**
	 * Recursive sanitation for an array
	 * 
	 * @param $array
	 *
	 * @return mixed
	 */
	function recursive_sanitize_text_field($array)
	{
		if (is_array($array)) {
			foreach ($array as $key => &$value) {
				if (is_array($value)) {
					$value = $this->recursive_sanitize_text_field($value);
				} else {
					$value = sanitize_text_field($value);
				}
			}
		} else {
			//exceptional case handle - sanitize as string for non-array parameter
			$array = sanitize_text_field($array);
		}

		return $array;
	}

	/**
	 * table column: id
	 *
	 * @param [type] $item
	 * @return void
	 */
	public function column_id($item)
	{
		$page = sanitize_text_field($_REQUEST['page']);
		$id = absint($item['ID']);
		$title = '#' . $id;
		$postURL = get_edit_post_link($id, $title);
		$url = sprintf('<a href="%s">%s</a>', $postURL, $title);
		// create a nonce
		$nonce = wp_create_nonce(self::ORDER_NONCE_NAME);
		$cgpOrderId = sanitize_text_field(get_post_meta($id, self::META_QPMN_ORDER_ID, true));

		if ($cgpOrderId) {
			$actions = [
				'update_cgp' => sprintf(
					'<a href="?page=%s&action=%s&order=%s&_wpnonce=%s">%s</a>',
					esc_attr($page),
					'update',
					esc_attr($id),
					$nonce,
					Qpmn_i18n::__('Update QPMN order')
				)
			];
		} else {
			$actions = [
				'create_cgp' => sprintf(
					'<a href="?page=%s&action=%s&order=%s&_wpnonce=%s">%s</a>',
					esc_attr($page),
					'create',
					esc_attr($id),
					$nonce,
					Qpmn_i18n::__('Create QPMN order')
				),
			];
		}

		return $url . $this->row_actions($actions);
	}

	/**
	 * table column: post_status
	 *
	 * @param [type] $item
	 * @return void
	 */
	public function column_post_status($item)
	{
		return wc_get_order_status_name($item['post_status']);
	}

	// /**
	//  * table column: cgp_order_id
	//  *
	//  * @param [type] $item
	//  * @return void
	//  */
	// public function column_cgp_order_id($item)
	// {
	// 	return get_post_meta($item['ID'], self::META_QPMN_ORDER_ID, true);
	// }

	public function column_cgp_order_number($item)
	{
		return get_post_meta($item['ID'], self::META_QPMN_ORDER_NUMBER, true);
	}

	/**
	 * table column: cpg_order_status
	 *
	 * @param [type] $item
	 * @return void
	 */
	public function column_cgp_order_status($item)
	{
		$id = sanitize_text_field($item['ID']);
		$status = get_post_meta($id, self::META_QPMN_ORDER_STATUS, true);
		$orderId = get_post_meta($id, self::META_QPMN_ORDER_NUMBER, true);
		$display = $status;
		if (!empty($orderId) && $status == self::PAYMENT_STATUS) {
			$cb = sprintf(
				'<input id="%s" type="checkbox" name="payment[]" value="%s" autocomplete="off"/>',
				esc_attr($id),
				esc_attr($id)
			);
			$display = '<label>' . $cb . esc_html($display) . '</label>';
		}
		return $display;
	}

	public function column_cgp_order_subtotal($item)
	{
		return get_post_meta($item['ID'], self::META_QPMN_ORDER_SUBTOTAL, true);
	}

	public function column_cgp_order_shipping($item)
	{
		return get_post_meta($item['ID'], self::META_QPMN_ORDER_SHIPPING, true);
	}

	public function column_cgp_order_total($item)
	{
		return get_post_meta($item['ID'], self::META_QPMN_ORDER_TOTAL, true);
	}

	public function column_cgp_order_sync_msg($item)
	{
		$msg = get_post_meta($item['ID'], self::META_QPMN_ORDER_ERROR_MSG, true);
		$html = '<p style="color: red;"> ' . esc_html($msg) . ' </p>';
		return $html;
	}

	// table column 
	public function column_cgp_order_last_sync($item)
	{
		$lastSyncAt = get_post_meta($item['ID'], self::META_QPMN_ORDER_LAST_SYNC_AT, true);
		$lastSyncDateTime = Qpmn_i18n::__('N/A');
		if ($lastSyncAt) {
			$lastSyncDateTime = new \DateTime();
			$lastSyncDateTime->setTimezone(wp_timezone());
			$lastSyncDateTime->setTimestamp($lastSyncAt);
			$lastSyncDateTime = $lastSyncDateTime->format('Y-m-d H:i:s');
		}
		return $lastSyncDateTime;
	}

	public function column_cgp_order_bulk_order($item)
	{
		$id = sanitize_text_field($item['ID']);
		$status = get_post_meta($id, self::META_QPMN_ORDER_STATUS, true);
		$orderId = get_post_meta($id, self::META_QPMN_ORDER_NUMBER, true);

		$cb = '';
		if (!empty($orderId) || $status == self::PAYMENT_STATUS) {
			$cb = sprintf(
				'<input id="%s" type="checkbox" name="bulkorder[]" value="%s" autocomplete="off"/>',
				esc_attr($id),
				esc_attr($id)
			);
		}

		return $cb;
	}
}
