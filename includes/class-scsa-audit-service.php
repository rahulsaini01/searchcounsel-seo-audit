<?php
/**
 * Backend workflow for a single public audit.
 *
 * @package SearchCounsel_SEO_Audit
 */

defined( 'ABSPATH' ) || exit;

class SCSA_Audit_Service {
	/**
	 * Runs validation, retrieval, analysis, scoring, and persistence.
	 *
	 * @param string $raw_url User-supplied URL.
	 * @return array<string,mixed>|WP_Error
	 */
	public function run( $raw_url ) {
		if ( ! is_string( $raw_url ) ) {
			return new WP_Error( 'scsa_invalid_url', __( 'Enter a valid website URL.', 'searchcounsel-seo-audit' ) );
		}

		$validator = new SCSA_URL_Validator();
		$url       = $validator->validate( $raw_url );

		if ( is_wp_error( $url ) ) {
			return $url;
		}

		$client   = new SCSA_HTTP_Client( $validator );
		$response = $client->get_page( $url );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$analyzer = new SCSA_Page_Analyzer( $client );
		$audit    = $analyzer->audit( $response );

		if ( is_wp_error( $audit ) ) {
			return $audit;
		}

		$builder = new SCSA_Report_Builder( new SCSA_Score_Engine() );
		$report  = $builder->build( $audit );

		// Performance is supplemental report data and never enters SEO scoring.
		$pagespeed             = new SCSA_PageSpeed_Insights();
		$report['performance'] = $pagespeed->analyze( $report['audited_url'] );

		// History is useful to site owners but must never prevent a visitor report.
		$history = new SCSA_History_Repository();
		$history->save( $report );

		return $report;
	}
}
