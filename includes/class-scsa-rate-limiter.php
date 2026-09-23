<?php
/**
 * Privacy-conscious, transient-backed public request throttling.
 *
 * @package SearchCounsel_SEO_Audit
 */

defined( 'ABSPATH' ) || exit;

class SCSA_Rate_Limiter {
	/**
	 * Consumes one rate-limit token for the requesting network address.
	 *
	 * The address is never stored directly: its site-salted hash is used only as
	 * a short-lived transient key.
	 *
	 * @param string $scope      Request category.
	 * @param int    $limit      Maximum requests in the window.
	 * @param int    $window     Window length in seconds.
	 * @return true|WP_Error
	 */
	public function consume( $scope, $limit, $window ) {
		$limit  = max( 1, absint( $limit ) );
		$window = max( MINUTE_IN_SECONDS, absint( $window ) );
		$key    = $this->key( $scope );
		$lock   = SCSA_Database_Lock::acquire( 'rate', $key, 2 );

		// Failing closed prevents lock contention from becoming a quota bypass.
		if ( is_wp_error( $lock ) ) {
			return new WP_Error( 'scsa_rate_limit_unavailable', __( 'Request capacity is temporarily busy. Please try again shortly.', 'searchcounsel-seo-audit' ) );
		}

		try {
			// The narrow database lock makes this transient update atomic per identity.
			$state = get_transient( $key );
			$count = is_array( $state ) && isset( $state['count'] ) ? absint( $state['count'] ) : 0;

			if ( $count >= $limit ) {
				return new WP_Error( 'scsa_rate_limited', __( 'Too many requests have been made from this network. Please try again later.', 'searchcounsel-seo-audit' ) );
			}

			$stored = set_transient(
				$key,
				array( 'count' => $count + 1 ),
				$window
			);

			if ( false === $stored ) {
				return new WP_Error( 'scsa_rate_limit_unavailable', __( 'Request capacity is temporarily unavailable. Please try again shortly.', 'searchcounsel-seo-audit' ) );
			}

			return true;
		} finally {
			$lock->release();
		}
	}

	/**
	 * Builds a non-reversible transient key from the direct connection address.
	 *
	 * Deliberately ignores forwarded headers: their values are client-controlled
	 * unless a site has configured a trusted proxy layer.
	 *
	 * @param string $scope Rate-limit scope.
	 * @return string
	 */
	private function key( $scope ) {
		$address = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) wp_unslash( $_SERVER['REMOTE_ADDR'] ) : 'unknown';

		if ( false === filter_var( $address, FILTER_VALIDATE_IP ) ) {
			$address = 'unknown';
		}

		$hash = hash_hmac( 'sha256', sanitize_key( $scope ) . '|' . $address, wp_salt( 'nonce' ) );

		return 'scsa_rate_' . substr( $hash, 0, 40 );
	}
}
