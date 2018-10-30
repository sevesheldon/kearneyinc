<?php
/**
 * Autolinking action module
 *
 * @package wpmu-dev-seo
 */

class Smartcrawl_Autolinks {

	/**
	 * Singleton instance
	 *
	 * @var Smartcrawl_Autolinks
	 */
	private static $_instance;

	/**
	 * Singleton instance getter
	 *
	 * @return Smartcrawl_Autolinks
	 */
	public static function get() {
		if ( ! self::$_instance ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}
}
