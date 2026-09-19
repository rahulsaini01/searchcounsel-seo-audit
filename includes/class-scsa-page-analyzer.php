<?php
/**
 * Parses a downloaded HTML document and runs registered audit modules.
 *
 * @package SearchCounsel_SEO_Audit
 */

defined( 'ABSPATH' ) || exit;

class SCSA_Page_Analyzer {
	/**
	 * Safe HTTP client shared with request-based checks.
	 *
	 * @var SCSA_HTTP_Client
	 */
	private $client;

	/**
	 * Constructor.
	 *
	 * @param SCSA_HTTP_Client $client Safe HTTP client.
	 */
	public function __construct( SCSA_HTTP_Client $client ) {
		$this->client = $client;
	}

	/**
	 * Runs all audit modules against a downloaded response.
	 *
	 * @param array<string,mixed> $response HTTP response returned by the client.
	 * @return array<string,mixed>|WP_Error
	 */
	public function audit( array $response ) {
		if ( ! class_exists( 'DOMDocument' ) ) {
			return new WP_Error( 'scsa_missing_dom', __( 'This server does not have the PHP DOM extension required to analyze HTML.', 'searchcounsel-seo-audit' ) );
		}

		if ( empty( $response['body'] ) ) {
			return new WP_Error( 'scsa_empty_page', __( 'The URL returned an empty page, so an SEO audit could not be completed.', 'searchcounsel-seo-audit' ) );
		}

		$document = $this->load_document( $response['body'] );

		if ( ! $document ) {
			return new WP_Error( 'scsa_unreadable_page', __( 'The page could not be parsed as HTML.', 'searchcounsel-seo-audit' ) );
		}

		$context = array(
			'document' => $document,
			'xpath'    => new DOMXPath( $document ),
			'url'      => $response['url'],
			'response' => $response,
			'client'   => $this->client,
		);
		$checks  = array(
			new SCSA_Title_Check(),
			new SCSA_Meta_Description_Check(),
			new SCSA_Heading_Check(),
			new SCSA_Canonical_Check(),
			new SCSA_Robots_Meta_Check(),
			new SCSA_Robots_File_Check(),
			new SCSA_HTTPS_Check(),
			new SCSA_Image_Alt_Check(),
			new SCSA_Link_Checks(),
			new SCSA_Word_Count_Check(),
			new SCSA_Open_Graph_Check(),
			new SCSA_Twitter_Card_Check(),
			new SCSA_Schema_Check(),
			new SCSA_Sitemap_Check(),
			new SCSA_Response_Check(),
			new SCSA_Viewport_Check(),
			new SCSA_HTML_Language_Check(),
			new SCSA_Page_Size_Check(),
			new SCSA_Core_Web_Vitals_Check(),
		);

		/**
		 * Filters the audit modules run for each page.
		 *
		 * Custom modules must implement SCSA_Audit_Check and return the standard
		 * result arrays, allowing future checks without changing the controller.
		 *
		 * @param SCSA_Audit_Check[] $checks  Audit modules.
		 * @param array<string,mixed> $context Audit context.
		 */
		$checks = apply_filters( 'scsa_audit_checks', $checks, $context );
		$results = array();

		foreach ( $checks as $check ) {
			if ( ! $check instanceof SCSA_Audit_Check ) {
				continue;
			}

			try {
				$check_results = $check->run( $context );
			} catch ( Throwable $exception ) {
				$check_results = array(
					SCSA_Check_Result::make(
						'audit_module',
						__( 'Audit module', 'searchcounsel-seo-audit' ),
						'warning',
						__( 'One audit module could not complete.', 'searchcounsel-seo-audit' ),
						__( 'Try the audit again or disable a custom audit extension that may be causing the problem.', 'searchcounsel-seo-audit' )
					)
				);
			}

			if ( is_array( $check_results ) ) {
				foreach ( $check_results as $check_result ) {
					if ( is_array( $check_result ) && isset( $check_result['key'], $check_result['status'] ) ) {
						$results[] = $check_result;
					}
				}
			}
		}

		return array(
			'audited_url' => $response['url'],
			'generated_at' => current_time( 'c' ),
			'summary'     => $this->summarize( $results ),
			'results'     => $results,
		);
	}

	/**
	 * Safely loads an HTML document without displaying markup errors.
	 *
	 * @param string $html Remote HTML, already byte-limited by the HTTP client.
	 * @return DOMDocument|false
	 */
	private function load_document( $html ) {
		$previous_errors = libxml_use_internal_errors( true );
		$document        = new DOMDocument();
		$options         = LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING;
		$loaded          = $document->loadHTML( '<?xml encoding="utf-8" ?>' . $html, $options );

		libxml_clear_errors();
		libxml_use_internal_errors( $previous_errors );

		return $loaded ? $document : false;
	}

	/**
	 * Counts each status for the dashboard summary.
	 *
	 * @param array<int,array<string,mixed>> $results Audit results.
	 * @return array<string,int>
	 */
	private function summarize( array $results ) {
		$summary = array(
			'pass'    => 0,
			'warning' => 0,
			'critical' => 0,
		);

		foreach ( $results as $result ) {
			if ( isset( $result['status'] ) && isset( $summary[ $result['status'] ] ) ) {
				$summary[ $result['status'] ]++;
			}
		}

		return $summary;
	}
}
