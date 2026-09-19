<?php
/**
 * Plugin Name: SearchCounsel SEO Audit
 * Plugin URI:  https://searchcounsel.co/
 * Description: A public-facing, secure SEO audit and lead-generation tool for SearchCounselco.
 * Version:     1.1.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author:      SearchCounsel
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: searchcounsel-seo-audit
 * Domain Path: /languages
 *
 * @package SearchCounsel_SEO_Audit
 */

defined( 'ABSPATH' ) || exit;

define( 'SCSA_VERSION', '1.1.0' );
define( 'SCSA_PLUGIN_FILE', __FILE__ );
define( 'SCSA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SCSA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once SCSA_PLUGIN_DIR . 'includes/class-scsa-check-result.php';
require_once SCSA_PLUGIN_DIR . 'includes/class-scsa-url-validator.php';
require_once SCSA_PLUGIN_DIR . 'includes/class-scsa-http-client.php';
require_once SCSA_PLUGIN_DIR . 'includes/class-scsa-rate-limiter.php';
require_once SCSA_PLUGIN_DIR . 'includes/class-scsa-history-repository.php';
require_once SCSA_PLUGIN_DIR . 'includes/class-scsa-score-engine.php';
require_once SCSA_PLUGIN_DIR . 'includes/class-scsa-report-builder.php';
require_once SCSA_PLUGIN_DIR . 'includes/checks/interface-scsa-audit-check.php';
require_once SCSA_PLUGIN_DIR . 'includes/checks/abstract-scsa-dom-check.php';
require_once SCSA_PLUGIN_DIR . 'includes/checks/class-scsa-content-checks.php';
require_once SCSA_PLUGIN_DIR . 'includes/checks/class-scsa-discoverability-checks.php';
require_once SCSA_PLUGIN_DIR . 'includes/checks/class-scsa-technical-checks.php';
require_once SCSA_PLUGIN_DIR . 'includes/checks/class-scsa-link-checks.php';
require_once SCSA_PLUGIN_DIR . 'includes/class-scsa-page-analyzer.php';
require_once SCSA_PLUGIN_DIR . 'includes/class-scsa-audit-service.php';
require_once SCSA_PLUGIN_DIR . 'includes/class-scsa-audit-controller.php';
require_once SCSA_PLUGIN_DIR . 'includes/class-scsa-public.php';
require_once SCSA_PLUGIN_DIR . 'includes/class-scsa-settings.php';
require_once SCSA_PLUGIN_DIR . 'includes/class-scsa-admin.php';
require_once SCSA_PLUGIN_DIR . 'includes/class-scsa-plugin.php';

/**
 * Starts the plugin after WordPress is available.
 *
 * @return void
 */
function scsa_boot_plugin() {
	SCSA_Plugin::init();
}

add_action( 'plugins_loaded', 'scsa_boot_plugin' );

register_activation_hook( __FILE__, array( 'SCSA_Plugin', 'activate' ) );
