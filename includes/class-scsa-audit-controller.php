<?php
/**
 * Public AJAX API for audits and free-consultation lead requests.
 *
 * @package SearchCounsel_SEO_Audit
 */

defined( 'ABSPATH' ) || exit;

class SCSA_Audit_Controller {
	/**
	 * Registers public and authenticated AJAX endpoints.
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'wp_ajax_scsa_run_public_audit', array( $this, 'run_public_audit' ) );
		add_action( 'wp_ajax_nopriv_scsa_run_public_audit', array( $this, 'run_public_audit' ) );
		add_action( 'wp_ajax_scsa_run_pagespeed', array( $this, 'run_pagespeed' ) );
		add_action( 'wp_ajax_nopriv_scsa_run_pagespeed', array( $this, 'run_pagespeed' ) );
		add_action( 'wp_ajax_scsa_submit_consultation', array( $this, 'submit_consultation' ) );
		add_action( 'wp_ajax_nopriv_scsa_submit_consultation', array( $this, 'submit_consultation' ) );
	}

	/**
	 * Runs a nonce-protected, rate-limited public audit.
	 *
	 * @return void
	 */
	public function run_public_audit() {
		if ( ! check_ajax_referer( 'scsa_public_audit', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Your security token expired. Refresh this page and try again.', 'searchcounsel-seo-audit' ) ), 403 );
		}

		$settings = SCSA_Settings::get();
		$limiter  = new SCSA_Rate_Limiter();
		$allowed  = $limiter->consume( 'audit', absint( $settings['audit_rate_limit'] ), HOUR_IN_SECONDS );

		if ( is_wp_error( $allowed ) ) {
			wp_send_json_error( array( 'message' => $allowed->get_error_message() ), 429 );
		}

		$raw_url = isset( $_POST['url'] ) && is_string( $_POST['url'] ) ? wp_unslash( $_POST['url'] ) : '';
		$service = new SCSA_Audit_Service();
		$report  = $service->run( $raw_url );

		if ( is_wp_error( $report ) ) {
			wp_send_json_error( array( 'message' => $report->get_error_message() ), 422 );
		}

		if ( SCSA_PageSpeed_Insights::is_configured() ) {
			$report['pagespeed_request'] = array(
				'enabled' => true,
				'nonce'   => wp_create_nonce( $this->pagespeed_nonce_action( $report['audited_url'] ) ),
			);
		} else {
			$report['pagespeed_request'] = array( 'enabled' => false );
		}

		nocache_headers();
		wp_send_json_success( $report );
	}

	/**
	 * Retrieves one PageSpeed strategy after the main SEO report has rendered.
	 *
	 * Desktop and mobile use separate browser requests so WordPress can execute
	 * the two bounded Google calls concurrently without delaying the SEO audit.
	 *
	 * @return void
	 */
	public function run_pagespeed() {
		$raw_url      = isset( $_POST['url'] ) && is_string( $_POST['url'] ) ? wp_unslash( $_POST['url'] ) : '';
		$raw_strategy = isset( $_POST['strategy'] ) && is_string( $_POST['strategy'] ) ? wp_unslash( $_POST['strategy'] ) : null;

		// Validate raw input strictly so malformed values cannot normalize to valid ones.
		if ( ! is_string( $raw_strategy ) || ! in_array( $raw_strategy, array( 'desktop', 'mobile' ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'The requested performance strategy is invalid.', 'searchcounsel-seo-audit' ) ), 400 );
		}

		$strategy = $raw_strategy;

		if ( ! check_ajax_referer( $this->pagespeed_nonce_action( $raw_url ), 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Your security token expired. Run the SEO audit again.', 'searchcounsel-seo-audit' ) ), 403 );
		}

		$settings = SCSA_Settings::get();
		$limiter  = new SCSA_Rate_Limiter();
		$allowed  = $limiter->consume( 'pagespeed_' . $strategy, absint( $settings['audit_rate_limit'] ), HOUR_IN_SECONDS );

		if ( is_wp_error( $allowed ) ) {
			wp_send_json_error( array( 'message' => $allowed->get_error_message() ), 429 );
		}

		$validator = new SCSA_URL_Validator();
		$url       = $validator->validate( $raw_url );

		if ( is_wp_error( $url ) ) {
			wp_send_json_error( array( 'message' => $url->get_error_message() ), 422 );
		}

		$pagespeed = new SCSA_PageSpeed_Insights();
		$result    = $pagespeed->analyze_strategy( $url, $strategy );

		nocache_headers();
		wp_send_json_success(
			array(
				'strategy' => $strategy,
				'result'   => $result,
			)
		);
	}

	/**
	 * Creates a nonce action bound to the validated audit URL.
	 *
	 * @param string $url Validated audit URL.
	 * @return string
	 */
	private function pagespeed_nonce_action( $url ) {
		return 'scsa_pagespeed_' . substr( hash( 'sha256', (string) $url ), 0, 32 );
	}

	/**
	 * Sends a consultation request using the site owner's configured recipient.
	 *
	 * @return void
	 */
	public function submit_consultation() {
		if ( ! check_ajax_referer( 'scsa_consultation', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Your security token expired. Refresh this page and try again.', 'searchcounsel-seo-audit' ) ), 403 );
		}

		$limiter = new SCSA_Rate_Limiter();
		$allowed = $limiter->consume( 'consultation', 3, HOUR_IN_SECONDS );

		if ( is_wp_error( $allowed ) ) {
			wp_send_json_error( array( 'message' => $allowed->get_error_message() ), 429 );
		}

		$name    = isset( $_POST['name'] ) && is_string( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$email   = isset( $_POST['email'] ) && is_string( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$url     = isset( $_POST['website'] ) && is_string( $_POST['website'] ) ? esc_url_raw( wp_unslash( $_POST['website'] ) ) : '';
		$message = isset( $_POST['message'] ) && is_string( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';

		if ( '' === $name || ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => __( 'Enter your name and a valid email address.', 'searchcounsel-seo-audit' ) ), 400 );
		}

		$settings  = SCSA_Settings::get();
		$recipient = sanitize_email( $settings['consultation_email'] );

		if ( ! is_email( $recipient ) ) {
			wp_send_json_error( array( 'message' => __( 'Consultation requests are not configured yet. Please use the contact link instead.', 'searchcounsel-seo-audit' ) ), 503 );
		}

		$subject = sprintf( __( 'New SEO consultation request from %s', 'searchcounsel-seo-audit' ), $name );
		$body    = sprintf(
			"%s\n\n%s\n%s\n\n%s",
			sprintf( __( 'Name: %s', 'searchcounsel-seo-audit' ), $name ),
			sprintf( __( 'Email: %s', 'searchcounsel-seo-audit' ), $email ),
			sprintf( __( 'Audited website: %s', 'searchcounsel-seo-audit' ), '' !== $url ? $url : __( 'Not provided', 'searchcounsel-seo-audit' ) ),
			sprintf( __( "Message:\n%s", 'searchcounsel-seo-audit' ), '' !== $message ? $message : __( 'Not provided', 'searchcounsel-seo-audit' ) )
		);
		$headers = array( 'Reply-To: ' . $name . ' <' . $email . '>' );

		if ( ! wp_mail( $recipient, $subject, $body, $headers ) ) {
			wp_send_json_error( array( 'message' => __( 'Your request could not be sent right now. Please use the contact link instead.', 'searchcounsel-seo-audit' ) ), 500 );
		}

		wp_send_json_success( array( 'message' => __( 'Thanks — SearchCounselco will be in touch shortly.', 'searchcounsel-seo-audit' ) ) );
	}
}
