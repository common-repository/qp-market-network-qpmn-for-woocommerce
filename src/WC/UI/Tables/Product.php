<?php

namespace QPMN\Partner\WC\UI\Tables;

use QPMN\Partner\Qpmn_i18n;
use QPMN\Partner\Qpmn_Install;
use QPMN\Partner\WP\Core\WPAdmin\Includes\WP_List_Table;
use WC_Product_Simple;
use WP_Query;

/**
 * create WP style table from WP list table class which cloned from WP core private class
 * WP suggest to copy and paste this class for reduce risk
 * Ref: https://developer.wordpress.org/reference/classes/wp_list_table/
 */
class Product extends WP_List_Table implements Table
{
	private $args;
	const PRODUCT_FLAG = Qpmn_Install::META_IS_QPPP_PRODUCT;

	public function __construct()
	{
		parent::__construct([
			'singlar' => Qpmn_i18n::__('Product'),
			'plural' => Qpmn_i18n::__('Products'),
			'ajax' => false
		]);

        $this->args = array(
            'post_type' => 'product',
            'meta_query' => array(
                array(
                    'key' => self::PRODUCT_FLAG,
                    'value' => true,
                ),
            ),
        );
	}

	public function get_products($per_page = 5, $page_number = 1)
	{

		$args = $this->args;

		$args['posts_per_page'] = 5;
		if ($per_page > 0) {
			$args['posts_per_page'] = $per_page;
			$args['paged'] = $page_number;
		}

		$query = new WP_Query($args);
		$result = array_map(function ($d) {
			$tmp = (array) $d;
			$tmp['wc_product'] = wc_get_product($d->ID);
			return $tmp;
		}, $query->get_posts());

		return $result;
	}

	/**
	 * Returns the count of records in the database.
	 *
	 * @return null|string
	 */
	public function record_count()
	{
		$args = $this->args;
		$query = new WP_Query($args);
		$count = $query->found_posts;

		return $count;
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
		return $item[$column_name];
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
			// 'cb' => '<input type="checkbox" />',
			'name' => Qpmn_i18n::__('Name'),
			'stock' => Qpmn_i18n::__('Stock'),
			'price' => Qpmn_i18n::__('Price'),
			'Date' => Qpmn_i18n::__('Date'),
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
		];

		return $actions;
	}

	public function process_bulk_action()
	{

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
		$per_page = $this->get_items_per_page('product_per_page', 5);
		$current_page = $this->get_pagenum();
		$total_items = $this->record_count();

		$this->set_pagination_args([
			'total_items' => $total_items, //WE have to calculate the total number of items
			'per_page' => $per_page //WE have to determine how many items to show on a page
		]);

		$this->items = $this->get_products($per_page, $current_page);
	}

	public function column_name($item)
	{
		$id = absint($item['ID']);
		$title = sanitize_text_field($item['post_title']);
		$postURL = get_edit_post_link($id, $title);
		$url = sprintf('<a href="%s">%s</a>', esc_url($postURL), esc_html($title));
		return $url;
	}

	public function column_stock($item)
	{
		/**
		 * @var WC_Product_Simple $product
		 */
		$product = $item['wc_product'];

		return $product->get_stock_quantity();
	}

	public function column_price($item)
	{
		/**
		 * @var WC_Product_Simple $product
		 */
		$product = $item['wc_product'];

		return $product->get_price();
	}

	public function column_date($item)
	{
		$post_statuses = get_post_statuses();
		return $post_statuses[$item['post_status']] . '<br>' . esc_html($item['post_date']);
	}
}
