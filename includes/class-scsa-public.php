<?php
/**
 * Public shortcode, templates, and frontend assets.
 *
 * @package SearchCounsel_SEO_Audit
 */

defined( 'ABSPATH' ) || exit;

class SCSA_Public {
	/**
	 * Registers frontend hooks and the public audit shortcode.
	 *
	 * @return void
	 */
	public function __construct() {
		add_shortcode( 'searchcounsel_audit', array( $this, 'render_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_when_shortcode_is_present' ), 20 );
	}

	/**
	 * Registers public assets without loading them site-wide.
	 *
	 * @return void
	 */
	public function register_assets() {
		wp_register_style( 'scsa-public', SCSA_PLUGIN_URL . 'assets/css/public.css', array(), SCSA_VERSION );
		wp_register_script( 'scsa-public', SCSA_PLUGIN_URL . 'assets/js/public.js', array(), SCSA_VERSION, true );
	}

	/**
	 * Enqueues assets early for pages that contain the shortcode in post content.
	 *
	 * @return void
	 */
	public function enqueue_when_shortcode_is_present() {
		global $post;

		if ( $post instanceof WP_Post && has_shortcode( $post->post_content, 'searchcounsel_audit' ) ) {
			$this->enqueue_assets();
		}
	}

	/**
	 * Enqueues and localizes the public application assets.
	 *
	 * @return void
	 */
	private function enqueue_assets() {
		wp_enqueue_style( 'scsa-public' );
		wp_enqueue_script( 'scsa-public' );

		$settings = SCSA_Settings::get();

		wp_localize_script(
			'scsa-public',
			'SCSA_Public',
			array(
				'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
				'auditNonce'        => wp_create_nonce( 'scsa_public_audit' ),
				'consultationNonce' => wp_create_nonce( 'scsa_consultation' ),
				'contactUrl'         => esc_url_raw( $settings['consultation_url'] ),
				'i18n'              => array(
					'analyzing'       => __( 'Analyzing your website…', 'searchcounsel-seo-audit' ),
					'passed'          => __( 'Passed', 'searchcounsel-seo-audit' ),
					'warning'         => __( 'Warning', 'searchcounsel-seo-audit' ),
					'critical'        => __( 'Critical issue', 'searchcounsel-seo-audit' ),
					'high'            => __( 'High impact', 'searchcounsel-seo-audit' ),
					'medium'          => __( 'Medium impact', 'searchcounsel-seo-audit' ),
					'failed'          => __( 'We could not complete this audit. Please try again.', 'searchcounsel-seo-audit' ),
					'contactSuccess'  => __( 'Thanks — your consultation request has been sent.', 'searchcounsel-seo-audit' ),
					'contactFailed'   => __( 'We could not send your request. Please try again or use the contact link.', 'searchcounsel-seo-audit' ),
				),
			)
		);
	}

	/**
	 * Renders the public audit application.
	 *
	 * @param array<string,mixed> $attributes Shortcode attributes (reserved for future use).
	 * @return string
	 */
	public function render_shortcode( $attributes = array() ) {
		$this->enqueue_assets();
		$settings = SCSA_Settings::get();

		ob_start();
		require SCSA_PLUGIN_DIR . 'templates/shortcode-audit.php';

		return (string) ob_get_clean();
	}
}
