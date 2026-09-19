<?php
/**
 * Validates public HTTP(S) destinations before the plugin makes a request.
 *
 * @package SearchCounsel_SEO_Audit
 */

defined( 'ABSPATH' ) || exit;

class SCSA_URL_Validator {
	/**
	 * Validates one public website URL.
	 *
	 * Explicitly limiting destinations to web ports and public IP addresses helps
	 * prevent the audit form being used to reach local services.
	 *
	 * @param string $url Candidate URL.
	 * @return string|WP_Error Normalized URL or an error.
	 */
	public function validate( $url ) {
		$url = trim( (string) $url );

		if ( empty( $url ) || strlen( $url ) > 2048 || preg_match( '/[\r\n]/', $url ) ) {
			return new WP_Error( 'scsa_invalid_url', __( 'Enter a valid website URL.', 'searchcounsel-seo-audit' ) );
		}

		$parts = wp_parse_url( $url );

		if ( false === $parts || empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
			return new WP_Error( 'scsa_invalid_url', __( 'Enter a complete URL including http:// or https://.', 'searchcounsel-seo-audit' ) );
		}

		$scheme = strtolower( $parts['scheme'] );
		$host   = strtolower( trim( $parts['host'], '[]' ) );

		if ( ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
			return new WP_Error( 'scsa_invalid_scheme', __( 'Only HTTP and HTTPS website URLs can be audited.', 'searchcounsel-seo-audit' ) );
		}

		if ( isset( $parts['user'], $parts['pass'] ) || isset( $parts['user'] ) || isset( $parts['pass'] ) ) {
			return new WP_Error( 'scsa_invalid_url', __( 'URLs containing login credentials are not allowed.', 'searchcounsel-seo-audit' ) );
		}

		if ( isset( $parts['port'] ) && ! in_array( (int) $parts['port'], array( 80, 443 ), true ) ) {
			return new WP_Error( 'scsa_invalid_port', __( 'Only standard web ports (80 and 443) are allowed.', 'searchcounsel-seo-audit' ) );
		}

		if ( ! $this->is_valid_host( $host ) || $this->is_local_hostname( $host ) ) {
			return new WP_Error( 'scsa_invalid_host', __( 'This URL does not point to a public website.', 'searchcounsel-seo-audit' ) );
		}

		$ips = $this->resolve_host( $host );

		if ( empty( $ips ) ) {
			return new WP_Error( 'scsa_unresolvable_host', __( 'The website hostname could not be resolved safely.', 'searchcounsel-seo-audit' ) );
		}

		foreach ( $ips as $ip ) {
			if ( ! $this->is_public_ip( $ip ) ) {
				return new WP_Error( 'scsa_private_host', __( 'Private, reserved, or local network destinations cannot be audited.', 'searchcounsel-seo-audit' ) );
			}
		}

		return $this->without_fragment( $url );
	}

	/**
	 * Removes a fragment because it is not sent in an HTTP request.
	 *
	 * @param string $url URL to normalize.
	 * @return string
	 */
	public function without_fragment( $url ) {
		$fragment_position = strpos( $url, '#' );

		return false === $fragment_position ? $url : substr( $url, 0, $fragment_position );
	}

	/**
	 * Determines whether a host uses a valid hostname or IP-literal form.
	 *
	 * @param string $host Host name without IPv6 brackets.
	 * @return bool
	 */
	private function is_valid_host( $host ) {
		if ( false !== filter_var( $host, FILTER_VALIDATE_IP ) ) {
			return true;
		}

		return (bool) filter_var( $host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME );
	}

	/**
	 * Blocks local hostname conventions even if a local DNS resolver accepts them.
	 *
	 * @param string $host Host name.
	 * @return bool
	 */
	private function is_local_hostname( $host ) {
		return 'localhost' === $host
			|| false !== strpos( $host, '.localhost' )
			|| false !== strpos( $host, '.local' )
			|| false !== strpos( $host, '.internal' );
	}

	/**
	 * Resolves a hostname to every available A and AAAA record.
	 *
	 * @param string $host Hostname or IP literal.
	 * @return string[]
	 */
	private function resolve_host( $host ) {
		if ( false !== filter_var( $host, FILTER_VALIDATE_IP ) ) {
			return array( $host );
		}

		$ips     = array();
		$records = function_exists( 'dns_get_record' ) ? @dns_get_record( $host, DNS_A | DNS_AAAA ) : false;

		if ( is_array( $records ) ) {
			foreach ( $records as $record ) {
				if ( ! empty( $record['ip'] ) ) {
					$ips[] = $record['ip'];
				}
				if ( ! empty( $record['ipv6'] ) ) {
					$ips[] = $record['ipv6'];
				}
			}
		}

		if ( empty( $ips ) && function_exists( 'gethostbynamel' ) ) {
			$fallback_ips = @gethostbynamel( $host );
			$ips          = is_array( $fallback_ips ) ? $fallback_ips : array();
		}

		return array_values( array_unique( $ips ) );
	}

	/**
	 * Allows globally routable IPv4 and IPv6 addresses only.
	 *
	 * @param string $ip Candidate IP address.
	 * @return bool
	 */
	private function is_public_ip( $ip ) {
		$flags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;

		if ( false === filter_var( $ip, FILTER_VALIDATE_IP, $flags ) ) {
			return false;
		}

		// Shared address space is neither public internet nor a safe audit target.
		if ( false !== filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			$number = sprintf( '%u', ip2long( $ip ) );
			$start  = sprintf( '%u', ip2long( '100.64.0.0' ) );
			$end    = sprintf( '%u', ip2long( '100.127.255.255' ) );

			if ( $number >= $start && $number <= $end ) {
				return false;
			}
		}

		return true;
	}
}
