<?php
/**
 * Server-side Google PageSpeed Insights integration.
 *
 * @package SearchCounsel_SEO_Audit
 */

defined( 'ABSPATH' ) || exit;

class SCSA_PageSpeed_Insights {
	/**
	 * Google PageSpeed Insights endpoint.
	 *
	 * @var string
	 */
	const ENDPOINT = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed';

	/**
	 * Maximum wall-clock budget for all uncached PageSpeed work.
	 *
	 * Each strategy is capped below this value so a slow Desktop request cannot
	 * consume the time reserved for Mobile (or vice versa). The two five-second
	 * request caps leave additional headroom inside this twelve-second budget for
	 * WordPress HTTP setup and response normalization.
	 *
	 * @var float
	 */
	const TOTAL_BUDGET_SECONDS = 12.0;

	/**
	 * Maximum timeout for one uncached strategy request.
	 *
	 * @var float
	 */
	const REQUEST_TIMEOUT_SECONDS = 5.0;

	/**
	 * Minimum useful time remaining before another request may start.
	 *
	 * @var float
	 */
	const MINIMUM_REQUEST_SECONDS = 1.0;

	/**
	 * Retrieves normalized desktop and mobile performance data.
	 *
	 * The URL has already passed the plugin's URL validator and redirect checks
	 * before this method is called. Google is the only remote destination used
	 * by this integration.
	 *
	 * @param string $url Validated final audit URL.
	 * @return array<string,array<string,mixed>>
	 */
	public function analyze( $url ) {
		try {
			return $this->analyze_internal( $url );
		} catch ( Throwable $exception ) {
			$empty = $this->unavailable_result();

			return array(
				'desktop' => $empty,
				'mobile'  => $empty,
			);
		}
	}

	/**
	 * Runs the configured PageSpeed workflow.
	 *
	 * @param string $url Validated final audit URL.
	 * @return array<string,array<string,mixed>>
	 */
	private function analyze_internal( $url ) {
		$settings = SCSA_Settings::get();
		$empty    = $this->unavailable_result();

		if ( empty( $settings['pagespeed_enabled'] ) ) {
			return array(
				'desktop' => $empty,
				'mobile'  => $empty,
			);
		}

		$api_key = SCSA_Settings::get_pagespeed_api_key();

		if ( '' === $api_key ) {
			return array(
				'desktop' => $empty,
				'mobile'  => $empty,
			);
		}

		$cache_duration = max( 5, min( 1440, absint( $settings['pagespeed_cache_duration'] ) ) ) * MINUTE_IN_SECONDS;
		$strategies     = array( 'desktop', 'mobile' );
		$results        = array();
		$pending        = array();

		// Resolve both independent caches before starting the shared time budget.
		foreach ( $strategies as $strategy ) {
			$cached = $this->get_cached_result( $url, $strategy );

			if ( false !== $cached ) {
				$results[ $strategy ] = $cached;
			} else {
				$results[ $strategy ] = $empty;
				$pending[]             = $strategy;
			}
		}

		if ( empty( $pending ) ) {
			return $results;
		}

		$started_at = microtime( true );

		foreach ( $pending as $strategy ) {
			$remaining = self::TOTAL_BUDGET_SECONDS - ( microtime( true ) - $started_at );

			if ( $remaining < self::MINIMUM_REQUEST_SECONDS ) {
				continue;
			}

			$timeout = min( self::REQUEST_TIMEOUT_SECONDS, $remaining );

			try {
				$results[ $strategy ] = $this->request_strategy( $url, $strategy, $api_key, $cache_duration, $timeout );
			} catch ( Throwable $exception ) {
				// A strategy-specific failure must not suppress the other strategy.
				$results[ $strategy ] = $empty;
			}
		}

		return $results;
	}

	/**
	 * Returns one valid independently cached strategy result.
	 *
	 * @param string $url      Validated final audit URL.
	 * @param string $strategy Desktop or mobile.
	 * @return array<string,mixed>|false
	 */
	private function get_cached_result( $url, $strategy ) {
		$cached = get_transient( $this->cache_key( $url, $strategy ) );

		if ( is_array( $cached ) && ! empty( $cached['available'] ) && isset( $cached['score'] ) && is_numeric( $cached['score'] ) ) {
			return $cached;
		}

		return false;
	}

	/**
	 * Retrieves one uncached strategy and stores only a valid result.
	 *
	 * @param string $url            Validated final audit URL.
	 * @param string $strategy       Desktop or mobile.
	 * @param string $api_key        Server-side Google API key.
	 * @param int    $cache_duration Cache duration in seconds.
	 * @param float  $timeout        Bounded request timeout in seconds.
	 * @return array<string,mixed>
	 */
	private function request_strategy( $url, $strategy, $api_key, $cache_duration, $timeout ) {
		$cache_key   = $this->cache_key( $url, $strategy );
		$request_url = add_query_arg(
			array(
				'url'      => $url,
				'strategy' => $strategy,
				'category' => 'performance',
			),
			self::ENDPOINT
		);
		$response    = wp_safe_remote_get(
			$request_url,
			array(
				'timeout'             => max( self::MINIMUM_REQUEST_SECONDS, (float) $timeout ),
				'redirection'         => 0,
				'reject_unsafe_urls'  => true,
				'limit_response_size' => 3 * MB_IN_BYTES,
				'headers'             => array(
					'Accept'         => 'application/json',
					'X-Goog-Api-Key' => $api_key,
				),
			)
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return $this->unavailable_result();
		}

		$decoded = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $decoded ) ) {
			return $this->unavailable_result();
		}

		$normalized = $this->normalize( $decoded );

		if ( ! empty( $normalized['available'] ) ) {
			set_transient( $cache_key, $normalized, $cache_duration );
		}

		return $normalized;
	}

	/**
	 * Converts Google's response into the small public report contract.
	 *
	 * @param array<string,mixed> $response PageSpeed response.
	 * @return array<string,mixed>
	 */
	private function normalize( array $response ) {
		$lighthouse  = isset( $response['lighthouseResult'] ) && is_array( $response['lighthouseResult'] ) ? $response['lighthouseResult'] : array();
		$categories  = isset( $lighthouse['categories'] ) && is_array( $lighthouse['categories'] ) ? $lighthouse['categories'] : array();
		$performance = isset( $categories['performance'] ) && is_array( $categories['performance'] ) ? $categories['performance'] : array();
		$audits      = isset( $lighthouse['audits'] ) && is_array( $lighthouse['audits'] ) ? $lighthouse['audits'] : array();

		if ( ! isset( $performance['score'] ) || ! is_numeric( $performance['score'] ) ) {
			return $this->unavailable_result();
		}

		$score = max( 0, min( 100, (int) round( (float) $performance['score'] * 100 ) ) );

		return array(
			'available'     => true,
			'score'         => $score,
			'lab'           => array(
				'available' => true,
				'metrics'   => $this->normalize_lab_metrics( $audits ),
			),
			'field'         => $this->normalize_field_data( $response ),
			'opportunities' => $this->normalize_audit_group( $performance, $audits, 'load-opportunities', true ),
			'diagnostics'   => $this->normalize_audit_group( $performance, $audits, 'diagnostics', false ),
		);
	}

	/**
	 * Extracts the requested Lighthouse lab metrics.
	 *
	 * @param array<string,mixed> $audits Lighthouse audits map.
	 * @return array<string,array<string,mixed>>
	 */
	private function normalize_lab_metrics( array $audits ) {
		$metric_ids = array(
			'fcp'         => 'first-contentful-paint',
			'lcp'         => 'largest-contentful-paint',
			'inp'         => 'interaction-to-next-paint',
			'tbt'         => 'total-blocking-time',
			'cls'         => 'cumulative-layout-shift',
			'speed_index' => 'speed-index',
			'ttfb'        => 'server-response-time',
		);
		$metrics = array();

		foreach ( $metric_ids as $key => $audit_id ) {
			$metrics[ $key ] = $this->normalize_lab_metric( isset( $audits[ $audit_id ] ) && is_array( $audits[ $audit_id ] ) ? $audits[ $audit_id ] : array() );
		}

		return $metrics;
	}

	/**
	 * Normalizes one Lighthouse audit metric without inventing a missing value.
	 *
	 * @param array<string,mixed> $audit Lighthouse audit.
	 * @return array<string,mixed>
	 */
	private function normalize_lab_metric( array $audit ) {
		$has_value    = isset( $audit['numericValue'] ) && is_numeric( $audit['numericValue'] );
		$display      = isset( $audit['displayValue'] ) ? sanitize_text_field( $audit['displayValue'] ) : '';
		$is_available = $has_value || '' !== $display;

		if ( ! $is_available ) {
			return array( 'available' => false );
		}

		$metric = array(
			'available' => true,
			'display'   => $display,
			'status'    => $this->lighthouse_status( isset( $audit['score'] ) ? $audit['score'] : null ),
		);

		if ( $has_value ) {
			$metric['value'] = (float) $audit['numericValue'];
			$metric['unit']  = isset( $audit['numericUnit'] ) ? sanitize_key( $audit['numericUnit'] ) : '';
		}

		return $metric;
	}

	/**
	 * Extracts CrUX field data for the URL, falling back to origin data only when
	 * the response identifies that fallback.
	 *
	 * @param array<string,mixed> $response PageSpeed response.
	 * @return array<string,mixed>
	 */
	private function normalize_field_data( array $response ) {
		$experience = isset( $response['loadingExperience'] ) && is_array( $response['loadingExperience'] ) ? $response['loadingExperience'] : array();
		$scope      = 'url';

		if ( empty( $experience['metrics'] ) && isset( $response['originLoadingExperience'] ) && is_array( $response['originLoadingExperience'] ) ) {
			$experience = $response['originLoadingExperience'];
			$scope      = 'origin';
		} elseif ( ! empty( $experience['origin_fallback'] ) ) {
			$scope = 'origin';
		}

		if ( empty( $experience['metrics'] ) || ! is_array( $experience['metrics'] ) ) {
			return array(
				'available' => false,
				'scope'     => '',
				'metrics'   => array(),
			);
		}

		$ids = array(
			'lcp'  => 'LARGEST_CONTENTFUL_PAINT_MS',
			'inp'  => 'INTERACTION_TO_NEXT_PAINT',
			'cls'  => 'CUMULATIVE_LAYOUT_SHIFT_SCORE',
			'fcp'  => 'FIRST_CONTENTFUL_PAINT_MS',
			'ttfb' => 'EXPERIMENTAL_TIME_TO_FIRST_BYTE',
		);
		$metrics = array();

		foreach ( $ids as $key => $metric_id ) {
			$metric          = isset( $experience['metrics'][ $metric_id ] ) && is_array( $experience['metrics'][ $metric_id ] ) ? $experience['metrics'][ $metric_id ] : array();
			$metrics[ $key ] = $this->normalize_field_metric( $metric, 'cls' === $key );
		}

		$available = array_filter(
			$metrics,
			function ( $metric ) {
				return ! empty( $metric['available'] );
			}
		);

		if ( empty( $available ) ) {
			return array(
				'available' => false,
				'scope'     => '',
				'metrics'   => array(),
			);
		}

		return array(
			'available' => true,
			'scope'     => $scope,
			'metrics'   => $metrics,
		);
	}

	/**
	 * Normalizes one CrUX metric.
	 *
	 * @param array<string,mixed> $metric Field metric.
	 * @param bool                $is_cls Whether the value uses CLS hundredths.
	 * @return array<string,mixed>
	 */
	private function normalize_field_metric( array $metric, $is_cls ) {
		if ( ! isset( $metric['percentile'] ) || ! is_numeric( $metric['percentile'] ) ) {
			return array( 'available' => false );
		}

		$percentile = (float) $metric['percentile'];

		return array(
			'available' => true,
			'value'     => $is_cls ? $percentile / 100 : $percentile,
			'unit'      => $is_cls ? 'unitless' : 'millisecond',
			'status'    => $this->field_status( isset( $metric['category'] ) ? $metric['category'] : '' ),
		);
	}

	/**
	 * Extracts compact opportunity or diagnostic entries referenced by the
	 * Performance category.
	 *
	 * @param array<string,mixed> $performance Performance category.
	 * @param array<string,mixed> $audits      Lighthouse audits map.
	 * @param string              $group       Lighthouse audit group.
	 * @param bool                $opportunity Whether opportunity savings are allowed.
	 * @return array<int,array<string,mixed>>
	 */
	private function normalize_audit_group( array $performance, array $audits, $group, $opportunity ) {
		$references = isset( $performance['auditRefs'] ) && is_array( $performance['auditRefs'] ) ? $performance['auditRefs'] : array();
		$items      = array();

		foreach ( $references as $reference ) {
			if ( ! is_array( $reference ) || $group !== ( isset( $reference['group'] ) ? $reference['group'] : '' ) || empty( $reference['id'] ) ) {
				continue;
			}

			$audit = isset( $audits[ $reference['id'] ] ) && is_array( $audits[ $reference['id'] ] ) ? $audits[ $reference['id'] ] : array();
			$score = isset( $audit['score'] ) && is_numeric( $audit['score'] ) ? (float) $audit['score'] : null;

			if ( null !== $score && $score >= 1 ) {
				continue;
			}

			$title = isset( $audit['title'] ) ? sanitize_text_field( $audit['title'] ) : '';

			if ( '' === $title ) {
				continue;
			}

			$item = array(
				'title' => $title,
				'value' => isset( $audit['displayValue'] ) ? sanitize_text_field( $audit['displayValue'] ) : '',
			);

			if ( $opportunity && isset( $audit['details'] ) && is_array( $audit['details'] ) ) {
				if ( isset( $audit['details']['overallSavingsMs'] ) && is_numeric( $audit['details']['overallSavingsMs'] ) ) {
					$item['savings_ms'] = max( 0, (float) $audit['details']['overallSavingsMs'] );
				}
				if ( isset( $audit['details']['overallSavingsBytes'] ) && is_numeric( $audit['details']['overallSavingsBytes'] ) ) {
					$item['savings_bytes'] = max( 0, (float) $audit['details']['overallSavingsBytes'] );
				}
			}

			$items[] = $item;
		}

		if ( $opportunity ) {
			usort(
				$items,
				function ( $first, $second ) {
					$first_savings  = isset( $first['savings_ms'] ) ? $first['savings_ms'] : ( isset( $first['savings_bytes'] ) ? $first['savings_bytes'] / 1000 : 0 );
					$second_savings = isset( $second['savings_ms'] ) ? $second['savings_ms'] : ( isset( $second['savings_bytes'] ) ? $second['savings_bytes'] / 1000 : 0 );

					return $second_savings <=> $first_savings;
				}
			);
		}

		return array_slice( $items, 0, $opportunity ? 5 : 6 );
	}

	/**
	 * Converts a Lighthouse audit score into its documented score band.
	 *
	 * @param mixed $score Lighthouse audit score.
	 * @return string
	 */
	private function lighthouse_status( $score ) {
		if ( ! is_numeric( $score ) ) {
			return '';
		}

		$score = (float) $score;

		if ( $score >= 0.9 ) {
			return 'good';
		}
		if ( $score >= 0.5 ) {
			return 'needs_improvement';
		}

		return 'poor';
	}

	/**
	 * Maps Google CrUX categories to the public status vocabulary.
	 *
	 * @param mixed $category CrUX category.
	 * @return string
	 */
	private function field_status( $category ) {
		$statuses = array(
			'FAST'    => 'good',
			'AVERAGE' => 'needs_improvement',
			'SLOW'    => 'poor',
		);

		return isset( $statuses[ $category ] ) ? $statuses[ $category ] : '';
	}

	/**
	 * Produces a response-safe unavailable state with no internal error detail.
	 *
	 * @return array<string,mixed>
	 */
	private function unavailable_result() {
		return array(
			'available'     => false,
			'score'         => null,
			'lab'           => array( 'available' => false, 'metrics' => array() ),
			'field'         => array( 'available' => false, 'scope' => '', 'metrics' => array() ),
			'opportunities' => array(),
			'diagnostics'   => array(),
		);
	}

	/**
	 * Builds a bounded cache key without storing the audited URL in the key.
	 *
	 * @param string $url      Validated final audit URL.
	 * @param string $strategy Desktop or mobile.
	 * @return string
	 */
	private function cache_key( $url, $strategy ) {
		return 'scsa_psi_' . substr( hash( 'sha256', $url . '|' . $strategy ), 0, 40 );
	}
}
