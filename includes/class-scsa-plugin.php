<?php
/**
 * Plugin service registration.
 *
 * @package SearchCounsel_SEO_Audit
 */

defined( 'ABSPATH' ) || exit;

class SCSA_Plugin {
	/**
	 * Registers the admin-only services once.
	 *
	 * @return void
	 */
	public static function init() {
		static $initialized = false;

		if ( $initialized ) {
			return;
		}

		$initialized = true;

		load_plugin_textdomain(
			'searchcounsel-seo-audit',
			false,
			dirname( plugin_basename( SCSA_PLUGIN_FILE ) ) . '/languages'
		);

		new SCSA_Audit_Controller();
		new SCSA_Public();

		if ( is_admin() ) {
			new SCSA_Settings();
			new SCSA_Admin();
		}
	}

	/**
	 * Creates the plugin's local storage on activation.
	 *
	 * @return void
	 */
	public static function activate() {
		SCSA_History_Repository::install();
	}
}
