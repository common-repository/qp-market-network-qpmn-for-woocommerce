<?php

namespace QPMN\Partner\WP\Core\WPAdmin\Includes;

/**
 * notes for someone who need to maintain this file
 * I (Tim) decide to take the risk to use WP_List_Table directly instead of make a copy inside plugin 
 * 	because wordpress original source code can't passs code check of 
 * 
 * ## Data Must be Sanitized, Escaped, and Validated
 * Example(s) from your plugin:
 * qp-market-network/src/WP/Core/wp-admin/includes/WP_List_Table.php:625: $extra_checks = $wpdb->prepare( ' AND post_status = %s', $_GET['post_status'] );
 * 
 * ## Variables and options must be escaped when echo'd
 * Example(s) from your plugin:
 * qp-market-network/src/WP/Core/wp-admin/includes/WP_List_Table.php:379:	<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); "><?php echo $text; :</label>
 * qp-market-network/src/WP/Core/wp-admin/includes/WP_List_Table.php:429:		echo implode( " |</li>\n", $views ) . "</li>\n";
 * qp-market-network/src/WP/Core/wp-admin/includes/WP_List_Table.php:499:		echo '<select name="action' . $two . '" id="bulk-action-selector-' . esc_attr( $which ) . "\">\n";
 * qp-market-network/src/WP/Core/wp-admin/includes/WP_List_Table.php:1268:			echo "<$tag $scope $id $class>$column_display_name</$tag>";
 * qp-market-network/src/WP/Core/wp-admin/includes/WP_List_Table.php:1284:<table class="wp-list-table <?php echo implode( ' ', $this->get_table_classes() ); ">
 * qp-market-network/src/WP/Core/wp-admin/includes/WP_List_Table.php:1294:			echo " data-wp-lists='list:$singular'";

 */
class WP_List_Table extends \WP_List_Table{
}
