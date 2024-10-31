<?php
namespace QPMN\Partner\WC\Schedule;

if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly

interface ScheduleInterface
{
    public function init_hooks();
	public function activate();
	public function deactivate();
    public static function init();
	//anything need to clearnup
    public static function deleteAll();
}
