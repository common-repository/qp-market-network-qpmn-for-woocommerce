<?php
namespace QPMN\Partner;

class Qpmn_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {

		//uncomment this line to test uninstall functions
		// (new Qpmn_Install())->uninstall();
		(new Qpmn_Install())->deactivate();
		delete_option('rewrite_rules');
	}

}
