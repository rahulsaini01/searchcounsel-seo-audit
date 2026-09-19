<?php
/**
 * Small, redirect-aware wrapper around the WordPress HTTP API.
 *
 * @package SearchCounsel_SEO_Audit
 */

defined( 'ABSPATH' ) || exit;

class SCSA_HTTP_Client {
	/**
	 * URL safety service.
	 *
	 * @var SCSA_URL_Validator
	 */
	private $validator;

	/**
	 * Constructor.
	 *
	 * @param SCSA_URL_Validator $validator URL safety service.
	 */
	public function __construct( SCSA_URL_Validator $validator ) {
		$this->validator = $validator;
	}

	/**
	 * Retrieves the page that is being audited.
	 *
	 * @param string $url Public URL.
	 * @return array<string,mixed>|WP_Error
	 */
	public function get_page( $url ) {
		return $this->request( $url, 'GET', 12, 1024 * 1024 );
	}

	/**
	 * Makes a small GET or HEAD request to verify a linked URL.
	 *
	 * @param string $url Public URL.
	 * @return array<string,mixed>|WP_Error
	 */
	public function probe( $url ) {
		$response = $this->request( $url, 'HEAD', 4, 0 );

		if ( ! is_wp_error( $response ) && in_array( (int) $response['code'], array( 405, 501 ), true ) ) {
			$response = $this->request( $url, 'GET', 4, 16384 );
		}

		return $response;
	}

	/**
	 * Issues one safe HTTP request and manually validates each redirect.
	 *
	 * WordPress's safe request functions add another URL safety layer. Redirects
	 * are followed manually so every Location destination is DNS/IP validated by
	 * this plugin before WordPress contacts it.
	 *
	 * @param string $url        Initial URL.
	 * @param string $method     GET or HEAD.
	 * @param int    $timeout    Timeout in seconds.
	 * @param int    $body_limit Maximum body bytes.
	 * @return array<string,mixed>|WP_Error
	 */
	public function request( $url, $method = 'GET', $timeout = 8, $body_limit = 65536 ) {
		$current_url = $url;
		$redirects   = 0;
		$started_at  = microtime( true );

		while ( $redirects <= 3 ) {
			$validated_url = $this->validator->validate( $current_url );

			if ( is_wp_error( $validated_url ) ) {
				return $validated_url;
			}

			$args = array(
				'timeout'            => absint( $timeout ),
				'redirection'        => 0,
				'reject_unsafe_urls' => true,
				'headers'            => array(
					'User-Agent' => 'SearchCounsel-SEO-Audit/' . SCSA_VERSION . '; ' . home_url( '/' ),
					'Accept'     => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.5',
				),
			);

			if ( 'GET' === $method ) {
				$args['limit_response_size'] = absint( $body_limit );
				$response                    = wp_safe_remote_get( $validated_url, $args );
			} else {
				$response = wp_safe_remote_head( $validated_url, $args );
			}

			if ( is_wp_error( $response ) ) {
				return new WP_Error(
					'scsa_request_failed',
					__( 'The website could not be reached safely. Check the URL and try again.', 'searchcounsel-seo-audit' )
				);
			}

			$code     = (int) wp_remote_retrieve_response_code( $response );
			$location = wp_remote_retrieve_header( $response, 'location' );

			if ( $this->is_redirect( $code ) && ! empty( $location ) ) {
				$current_url = self::resolve_url( $validated_url, $location );
				$redirects++;
				continue;
			}

			$body = 'HEAD' === $method ? '' : (string) wp_remote_retrieve_body( $response );

			return array(
				'url'       => $validated_url,
				'code'      => $code,
				'headers'   => wp_remote_retrieve_headers( $response ),
				'body'      => $body,
				'body_size' => strlen( $body ),
				'duration_ms' => (int) round( ( microtime( true ) - $started_at ) * 1000 ),
				'redirects' => $redirects,
			);
		}

		return new WP_Error( 'scsa_too_many_redirects', __( 'The website redirected too many times.', 'searchcounsel-seo-audit' ) );
	}

	/**
	 * Resolves an absolute or relative reference against a base URL.
	 *
	 * @param string $base      Fully qualified source URL.
	 * @param string $reference Link or redirect target.
	 * @return string
	 */
	public static function resolve_url( $base, $reference ) {
		$reference = trim( html_entity_decode( (string) $reference, ENT_QUOTES, 'UTF-8' ) );

		if ( '' === $reference ) {
			return $base;
		}

		if ( preg_match( '#^[a-z][a-z0-9+.-]*:#i', $reference ) ) {
			return $reference;
		}

		$base_parts = wp_parse_url( $base );

		if ( false === $base_parts || empty( $base_parts['scheme'] ) || empty( $base_parts['host'] ) ) {
			return $reference;
		}

		if ( 0 === strpos( $reference, '//' ) ) {
			return $base_parts['scheme'] . ':' . $reference;
		}

		$scheme   = $base_parts['scheme'];
		$host     = $base_parts['host'];
		$port     = isset( $base_parts['port'] ) ? ':' . (int) $base_parts['port'] : '';
		$origin   = $scheme . '://' . $host . $port;
		$fragment = '';
		$query    = '';

		if ( false !== strpos( $reference, '#' ) ) {
			$reference = explode( '#', $reference, 2 )[0];
		}

		if ( false !== strpos( $reference, '?' ) ) {
			$reference_parts = explode( '?', $reference, 2 );
			$reference       = $reference_parts[0];
			$query           = '?' . $reference_parts[1];
		}

		if ( '' === $reference ) {
			$path = isset( $base_parts['path'] ) ? $base_parts['path'] : '/';

			return $origin . $path . $query . $fragment;
		}

		if ( '/' === $reference[0] ) {
			$path = $reference;
		} else {
			$base_path = isset( $base_parts['path'] ) ? $base_parts['path'] : '/';
			$directory = '/' === substr( $base_path, -1 ) ? $base_path : dirname( $base_path ) . '/';
			$path      = $directory . $reference;
		}

		return $origin . self::normalize_path( $path ) . $query . $fragment;
	}

	/**
	 * Removes dot segments from an URL path.
	 *
	 * @param string $path URL path.
	 * @return string
	 */
	private static function normalize_path( $path ) {
		$segments = explode( '/', $path );
		$output   = array();

		foreach ( $segments as $segment ) {
			if ( '' === $segment || '.' === $segment ) {
				continue;
			}
			if ( '..' === $segment ) {
				array_pop( $output );
				continue;
			}
			$output[] = $segment;
		}

		return '/' . implode( '/', $output ) . ( '/' === substr( $path, -1 ) ? '/' : '' );
	}

	/**
	 * Tests whether a status code represents a redirect.
	 *
	 * @param int $code HTTP response code.
	 * @return bool
	 */
	private function is_redirect( $code ) {
		return $code >= 300 && $code < 400;
	}
}
