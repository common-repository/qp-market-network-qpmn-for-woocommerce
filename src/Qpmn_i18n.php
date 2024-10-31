<?php

namespace QPMN\Partner;

class Qpmn_i18n
{


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain()
	{
		//domain always same to plugin name
		load_plugin_textdomain(
			Qpmn_Install::PLUGIN_NAME,
			false,
			dirname(plugin_basename(__FILE__)) . '/Languages/'
		);
	}

	/**
	 *  compatibility filter to load plugin own translation after WP 4.6
	 * 
	 * https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/#loading-text-domain
	 * If you still want to load your own translations and not the ones from translate, you will have to use a hook filter named load_textdomain_mofile. 
	 *
	 * @param mixed $mofile
	 * @param string $domain //domain must same with plugin name
	 * @return void
	 */
	public function load_my_own_textdomain($mofile, $domain = Qpmn_Install::PLUGIN_NAME)
	{
		if (Qpmn_Install::PLUGIN_NAME === $domain && false !== strpos($mofile, WP_LANG_DIR . '/plugins/')) {
			$locale = apply_filters('plugin_locale', determine_locale(), $domain);
			$mofile = WP_PLUGIN_DIR . '/' . dirname(plugin_basename(__FILE__)) . '/Languages/' . $domain . '-' . $locale . '.mo';
		}
		return $mofile;
	}

	/**
	 * determine and return api required langauge code
	 *
	 * @return void
	 */
	public static function apiLangCode()
	{
		$currLang = get_locale();
		$isChinese = strpos($currLang, 'zh') === 0;

		//support zh and cn only
		return $isChinese ? 'zh' : 'en';
	}

	public static function __($text, $domain = Qpmn_Install::PLUGIN_NAME)
	{
		return __($text, $domain);
	}

	public static function _e($text, $domain = Qpmn_Install::PLUGIN_NAME)
	{
		_e($text, $domain);
	}
}
