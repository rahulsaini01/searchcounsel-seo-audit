<?php
/**
 * Link inventory and bounded link availability checks.
 *
 * @package SearchCounsel_SEO_Audit
 */

defined( 'ABSPATH' ) || exit;

class SCSA_Link_Checks implements SCSA_Audit_Check {
	/**
	 * @inheritDoc
	 */
	public function run( array $context ) {
		$nodes          = $context['xpath']->query( '//a[@href]' );
		$page_host      = $this->normalise_host( wp_parse_url( $context['url'], PHP_URL_HOST ) );
		$internal_count = 0;
		$external_count = 0;
		$unique_links   = array();

		if ( $nodes ) {
			foreach ( $nodes as $node ) {
				$href = trim( html_entity_decode( $node->getAttribute( 'href' ), ENT_QUOTES, 'UTF-8' ) );

				if ( '' === $href || '#' === $href[0] ) {
					continue;
				}

				$url    = SCSA_HTTP_Client::resolve_url( $context['url'], $href );
				$scheme = strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) );
				$host   = $this->normalise_host( wp_parse_url( $url, PHP_URL_HOST ) );

				if ( ! in_array( $scheme, array( 'http', 'https' ), true ) || '' === $host ) {
					continue;
				}

				if ( $host === $page_host ) {
					$internal_count++;
				} else {
					$external_count++;
				}

				$unique_links[ $this->without_fragment( $url ) ] = $url;
			}
		}

		$results   = array();
		$results[] = SCSA_Check_Result::make(
			'internal_links',
			__( 'Internal links', 'searchcounsel-seo-audit' ),
			'pass',
			sprintf( __( '%d internal links were found.', 'searchcounsel-seo-audit' ), $internal_count ),
			__( 'Use descriptive internal links to help visitors and search engines discover related content.', 'searchcounsel-seo-audit' ),
			array( __( 'Internal links', 'searchcounsel-seo-audit' ) => $internal_count )
		);
		$results[] = SCSA_Check_Result::make(
			'external_links',
			__( 'External links', 'searchcounsel-seo-audit' ),
			'pass',
			sprintf( __( '%d external links were found.', 'searchcounsel-seo-audit' ), $external_count ),
			__( 'Periodically review external links to make sure they remain relevant and trustworthy.', 'searchcounsel-seo-audit' ),
			array( __( 'External links', 'searchcounsel-seo-audit' ) => $external_count )
		);

		$results[] = $this->check_link_availability( array_values( $unique_links ), $context );

		return $results;
	}

	/**
	 * Checks a small, configurable sample to avoid turning an admin action into a crawler.
	 *
	 * @param string[]            $links   Unique public HTTP(S) links.
	 * @param array<string,mixed> $context Audit context.
	 * @return array<string,mixed>
	 */
	private function check_link_availability( array $links, array $context ) {
		if ( empty( $links ) ) {
			return SCSA_Check_Result::make( 'broken_links', __( 'Broken links', 'searchcounsel-seo-audit' ), 'pass', __( 'No crawlable HTTP(S) links were found to test.', 'searchcounsel-seo-audit' ), __( 'Run another audit after adding links, and review important links before publishing.', 'searchcounsel-seo-audit' ) );
		}

		$maximum  = (int) apply_filters( 'scsa_max_link_probes', 10, $context );
		$maximum  = max( 1, min( 20, $maximum ) );
		$sample   = array_slice( $links, 0, $maximum );
		$broken   = array();
		$unproven = 0;

		foreach ( $sample as $link ) {
			$probe = $context['client']->probe( $link );

			if ( is_wp_error( $probe ) ) {
				$unproven++;
				continue;
			}

			$code = (int) $probe['code'];

			if ( 404 === $code || 410 === $code || $code >= 500 ) {
				$broken[] = $link . ' (' . $code . ')';
			} elseif ( $code >= 400 ) {
				$unproven++;
			}
		}

		$details = array(
			__( 'Unique links', 'searchcounsel-seo-audit' ) => count( $links ),
			__( 'Links checked', 'searchcounsel-seo-audit' ) => count( $sample ),
		);

		if ( ! empty( $broken ) ) {
			$details[ __( 'Unavailable sample', 'searchcounsel-seo-audit' ) ] = implode( ', ', array_slice( $broken, 0, 3 ) );

			return SCSA_Check_Result::make( 'broken_links', __( 'Broken links', 'searchcounsel-seo-audit' ), 'critical', sprintf( __( '%1$d of %2$d sampled links returned a 404, 410, or server error.', 'searchcounsel-seo-audit' ), count( $broken ), count( $sample ) ), __( 'Update, remove, or redirect the unavailable links. This audit checks a limited sample only.', 'searchcounsel-seo-audit' ), $details );
		}

		if ( $unproven > 0 ) {
			$details[ __( 'Could not verify', 'searchcounsel-seo-audit' ) ] = $unproven;

			return SCSA_Check_Result::make( 'broken_links', __( 'Broken links', 'searchcounsel-seo-audit' ), 'warning', sprintf( __( 'No clearly broken links were found in a sample of %d, but some links could not be verified.', 'searchcounsel-seo-audit' ), count( $sample ) ), __( 'Manually review blocked or unavailable links. The audit never follows private or unsafe destinations.', 'searchcounsel-seo-audit' ), $details );
		}

		return SCSA_Check_Result::make( 'broken_links', __( 'Broken links', 'searchcounsel-seo-audit' ), 'pass', sprintf( __( 'No clearly broken links were found in a sample of %d.', 'searchcounsel-seo-audit' ), count( $sample ) ), __( 'This is a limited availability check; periodically crawl the full site for comprehensive link maintenance.', 'searchcounsel-seo-audit' ), $details );
	}

	/**
	 * Treats www and non-www variants as one site for the link inventory.
	 *
	 * @param string|null $host Raw host.
	 * @return string
	 */
	private function normalise_host( $host ) {
		return preg_replace( '/^www\./i', '', strtolower( (string) $host ) );
	}

	/**
	 * Drops fragments before using a link as a de-duplication key.
	 *
	 * @param string $url URL.
	 * @return string
	 */
	private function without_fragment( $url ) {
		$position = strpos( $url, '#' );

		return false === $position ? $url : substr( $url, 0, $position );
	}
}
