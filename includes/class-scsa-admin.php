<?php
/**
 * Protected WordPress admin pages for configuration and audit history.
 *
 * @package SearchCounsel_SEO_Audit
 */

defined( 'ABSPATH' ) || exit;

class SCSA_Admin {
	/**
	 * Registers the SearchCounselco admin menu.
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_pages' ) );
	}

	/**
	 * Creates overview, settings, and history pages.
	 *
	 * @return void
	 */
	public function register_pages() {
		$capability = 'manage_options';

		add_menu_page(
			__( 'SearchCounsel SEO Audit', 'searchcounsel-seo-audit' ),
			__( 'SEO Audit', 'searchcounsel-seo-audit' ),
			$capability,
			'searchcounsel-seo-audit',
			array( $this, 'render_overview' ),
			'dashicons-chart-area',
			58
		);
		add_submenu_page( 'searchcounsel-seo-audit', __( 'SEO Audit', 'searchcounsel-seo-audit' ), __( 'Overview', 'searchcounsel-seo-audit' ), $capability, 'searchcounsel-seo-audit', array( $this, 'render_overview' ) );
		add_submenu_page( 'searchcounsel-seo-audit', __( 'SEO Audit Settings', 'searchcounsel-seo-audit' ), __( 'Settings', 'searchcounsel-seo-audit' ), $capability, 'scsa-settings', array( $this, 'render_settings' ) );
		add_submenu_page( 'searchcounsel-seo-audit', __( 'SEO Audit History', 'searchcounsel-seo-audit' ), __( 'Audit History', 'searchcounsel-seo-audit' ), $capability, 'scsa-history', array( $this, 'render_history' ) );
	}

	/**
	 * Renders the shortcode usage overview.
	 *
	 * @return void
	 */
	public function render_overview() {
		$this->authorize();
		require SCSA_PLUGIN_DIR . 'templates/admin-page.php';
	}

	/**
	 * Renders the WordPress Settings API form.
	 *
	 * @return void
	 */
	public function render_settings() {
		$this->authorize();
		require SCSA_PLUGIN_DIR . 'templates/admin-settings.php';
	}

	/**
	 * Renders compact saved audit history.
	 *
	 * @return void
	 */
	public function render_history() {
		$this->authorize();
		$history = new SCSA_History_Repository();
		$rows    = $history->recent();
		require SCSA_PLUGIN_DIR . 'templates/admin-history.php';
	}

	/**
	 * Ensures only WordPress administrators access protected data.
	 *
	 * @return void
	 */
	private function authorize() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'searchcounsel-seo-audit' ) );
		}
	}
}
