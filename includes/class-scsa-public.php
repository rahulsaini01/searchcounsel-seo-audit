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
					'criticalLabel'   => __( 'Critical', 'searchcounsel-seo-audit' ),
					'high'            => __( 'High impact', 'searchcounsel-seo-audit' ),
					'medium'          => __( 'Medium impact', 'searchcounsel-seo-audit' ),
					'low'             => __( 'Low impact', 'searchcounsel-seo-audit' ),
					'allClear'        => __( 'All clear', 'searchcounsel-seo-audit' ),
					'oneCheck'        => __( '%d check', 'searchcounsel-seo-audit' ),
					'manyChecks'      => __( '%d checks', 'searchcounsel-seo-audit' ),
					'oneIssue'        => __( '%d issue', 'searchcounsel-seo-audit' ),
					'manyIssues'      => __( '%d issues', 'searchcounsel-seo-audit' ),
					'criticalCount'   => __( '%d critical', 'searchcounsel-seo-audit' ),
					'warningCount'    => __( '%d warning', 'searchcounsel-seo-audit' ),
					'warningsCount'   => __( '%d warnings', 'searchcounsel-seo-audit' ),
					'passedCount'     => __( '%d passed', 'searchcounsel-seo-audit' ),
					'scoreAria'       => __( 'SEO score: %1$d out of %2$d', 'searchcounsel-seo-audit' ),
					'viewCategory'    => __( 'View %s audit checks', 'searchcounsel-seo-audit' ),
					'recommendation'  => __( 'Recommendation', 'searchcounsel-seo-audit' ),
					'technicalDetails' => __( 'Technical details', 'searchcounsel-seo-audit' ),
					'audited'         => __( 'Audited', 'searchcounsel-seo-audit' ),
					'invalidUrl'      => __( 'Enter a valid public website URL to begin.', 'searchcounsel-seo-audit' ),
					'emptyResult'     => __( 'The audit completed but returned no checks. Please try again or use a different public URL.', 'searchcounsel-seo-audit' ),
					'healthyTitle'    => __( 'Your audit looks healthy', 'searchcounsel-seo-audit' ),
					'healthyCopy'     => __( 'Keep monitoring your site and continue improving content, links, and performance.', 'searchcounsel-seo-audit' ),
					'healthExcellent' => __( 'Your website is in great shape. Keep monitoring it as content and technology change.', 'searchcounsel-seo-audit' ),
					'healthStrong'    => __( 'Your SEO foundation is strong, with a few opportunities worth addressing.', 'searchcounsel-seo-audit' ),
					'healthFair'      => __( 'Your website has a workable foundation, but several issues need attention.', 'searchcounsel-seo-audit' ),
					'healthCritical'  => __( 'Important SEO issues are limiting this page. Start with the priority action plan below.', 'searchcounsel-seo-audit' ),
					'nameRequired'    => __( 'Please enter your name.', 'searchcounsel-seo-audit' ),
					'emailRequired'   => __( 'Please enter a valid work email.', 'searchcounsel-seo-audit' ),
					'websiteRequired' => __( 'Please enter a valid website URL.', 'searchcounsel-seo-audit' ),
					'failed'          => __( 'We could not complete this audit. Please try again.', 'searchcounsel-seo-audit' ),
					'contactSuccess'  => __( 'Thanks — your consultation request has been sent.', 'searchcounsel-seo-audit' ),
					'contactFailed'   => __( 'We could not send your request. Please try again or use the contact link.', 'searchcounsel-seo-audit' ),
				),
				'categories'        => array(
					'technical'     => array( 'name' => __( 'Technical SEO', 'searchcounsel-seo-audit' ), 'description' => __( 'Crawl access, responses, mobile setup, and site infrastructure.', 'searchcounsel-seo-audit' ) ),
					'onPage'       => array( 'name' => __( 'On-Page SEO', 'searchcounsel-seo-audit' ), 'description' => __( 'Search metadata, headings, canonicals, and indexability signals.', 'searchcounsel-seo-audit' ) ),
					'content'      => array( 'name' => __( 'Content', 'searchcounsel-seo-audit' ), 'description' => __( 'Visible copy and image accessibility signals.', 'searchcounsel-seo-audit' ) ),
					'performance'  => array( 'name' => __( 'Performance', 'searchcounsel-seo-audit' ), 'description' => __( 'HTML weight and basic experience readiness.', 'searchcounsel-seo-audit' ) ),
					'links'        => array( 'name' => __( 'Links', 'searchcounsel-seo-audit' ), 'description' => __( 'Internal, external, and sampled link availability.', 'searchcounsel-seo-audit' ) ),
					'socialSchema' => array( 'name' => __( 'Social & Schema', 'searchcounsel-seo-audit' ), 'description' => __( 'Share previews and structured data markup.', 'searchcounsel-seo-audit' ) ),
					'other'        => array( 'name' => __( 'Additional Checks', 'searchcounsel-seo-audit' ), 'description' => __( 'Additional checks supplied by the audit.', 'searchcounsel-seo-audit' ) ),
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
		static $instance = 0;

		$this->enqueue_assets();
		$settings    = SCSA_Settings::get();
		$instance++;
		$instance_id = 'scsa-audit-' . $instance;

		ob_start();
		require SCSA_PLUGIN_DIR . 'templates/shortcode-audit.php';

		return (string) ob_get_clean();
	}
}
