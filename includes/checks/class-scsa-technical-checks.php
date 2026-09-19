<?php
/**
 * Transport and basic document technical SEO checks.
 *
 * @package SearchCounsel_SEO_Audit
 */

defined( 'ABSPATH' ) || exit;

class SCSA_HTTPS_Check extends SCSA_DOM_Check {
	/**
	 * @inheritDoc
	 */
	public function run( array $context ) {
		$scheme = wp_parse_url( $context['url'], PHP_URL_SCHEME );

		if ( 'https' === strtolower( (string) $scheme ) ) {
			return array( SCSA_Check_Result::make( 'https', __( 'HTTPS', 'searchcounsel-seo-audit' ), 'pass', __( 'The audited page was delivered over HTTPS.', 'searchcounsel-seo-audit' ), __( 'Keep the TLS certificate current and redirect all HTTP variants to HTTPS.', 'searchcounsel-seo-audit' ) ) );
		}

		return array( SCSA_Check_Result::make( 'https', __( 'HTTPS', 'searchcounsel-seo-audit' ), 'critical', __( 'The audited page was delivered over unencrypted HTTP.', 'searchcounsel-seo-audit' ), __( 'Install a valid TLS certificate and redirect HTTP URLs to their HTTPS equivalents.', 'searchcounsel-seo-audit' ) ) );
	}
}

class SCSA_Response_Check extends SCSA_DOM_Check {
	/**
	 * @inheritDoc
	 */
	public function run( array $context ) {
		$code         = (int) $context['response']['code'];
		$content_type = $this->header( $context['response']['headers'], 'content-type' );
		$results      = array();

		if ( $code >= 200 && $code < 300 ) {
			$results[] = SCSA_Check_Result::make( 'http_status', __( 'HTTP response', 'searchcounsel-seo-audit' ), 'pass', sprintf( __( 'The final response returned HTTP %d.', 'searchcounsel-seo-audit' ), $code ), __( 'Maintain a successful response for the preferred page URL.', 'searchcounsel-seo-audit' ), array( __( 'Redirects followed', 'searchcounsel-seo-audit' ) => (int) $context['response']['redirects'] ) );
		} elseif ( $code >= 300 && $code < 400 ) {
			$results[] = SCSA_Check_Result::make( 'http_status', __( 'HTTP response', 'searchcounsel-seo-audit' ), 'warning', sprintf( __( 'The final response returned HTTP %d.', 'searchcounsel-seo-audit' ), $code ), __( 'Use a single, intentional permanent redirect to the final indexable URL where possible.', 'searchcounsel-seo-audit' ), array( __( 'Redirects followed', 'searchcounsel-seo-audit' ) => (int) $context['response']['redirects'] ) );
		} else {
			$results[] = SCSA_Check_Result::make( 'http_status', __( 'HTTP response', 'searchcounsel-seo-audit' ), 'critical', sprintf( __( 'The final response returned HTTP %d.', 'searchcounsel-seo-audit' ), $code ), __( 'Resolve the server or routing issue so search engines and visitors receive a successful page response.', 'searchcounsel-seo-audit' ), array( __( 'Redirects followed', 'searchcounsel-seo-audit' ) => (int) $context['response']['redirects'] ) );
		}

		if ( '' === $content_type ) {
			$results[] = SCSA_Check_Result::make( 'content_type', __( 'HTML content type', 'searchcounsel-seo-audit' ), 'warning', __( 'The response did not declare a Content-Type header.', 'searchcounsel-seo-audit' ), __( 'Return an appropriate text/html Content-Type header with a charset.', 'searchcounsel-seo-audit' ) );
		} elseif ( false === strpos( strtolower( $content_type ), 'html' ) && false === strpos( strtolower( $content_type ), 'xml' ) ) {
			$results[] = SCSA_Check_Result::make( 'content_type', __( 'HTML content type', 'searchcounsel-seo-audit' ), 'warning', sprintf( __( 'The response Content-Type is %s.', 'searchcounsel-seo-audit' ), $content_type ), __( 'Confirm this URL intentionally serves a document that search engines can parse.', 'searchcounsel-seo-audit' ) );
		} else {
			$results[] = SCSA_Check_Result::make( 'content_type', __( 'HTML content type', 'searchcounsel-seo-audit' ), 'pass', sprintf( __( 'The response declares %s.', 'searchcounsel-seo-audit' ), $content_type ), __( 'Keep serving an accurate HTML Content-Type header and character set.', 'searchcounsel-seo-audit' ) );
		}

		return $results;
	}

	/**
	 * Gets a response header across WordPress HTTP header implementations.
	 *
	 * @param mixed  $headers Response headers.
	 * @param string $name    Lowercase header name.
	 * @return string
	 */
	private function header( $headers, $name ) {
		if ( is_object( $headers ) && isset( $headers[ $name ] ) ) {
			return (string) $headers[ $name ];
		}

		if ( is_array( $headers ) && isset( $headers[ $name ] ) ) {
			return (string) $headers[ $name ];
		}

		return '';
	}
}

class SCSA_Viewport_Check extends SCSA_DOM_Check {
	/**
	 * @inheritDoc
	 */
	public function run( array $context ) {
		$viewport = $this->meta_content( $context['xpath'], 'name', 'viewport' );

		if ( '' === $viewport ) {
			return array( SCSA_Check_Result::make( 'viewport', __( 'Mobile viewport', 'searchcounsel-seo-audit' ), 'warning', __( 'No viewport meta tag was found.', 'searchcounsel-seo-audit' ), __( 'Add a responsive viewport tag, typically width=device-width, initial-scale=1.', 'searchcounsel-seo-audit' ) ) );
		}

		return array( SCSA_Check_Result::make( 'viewport', __( 'Mobile viewport', 'searchcounsel-seo-audit' ), 'pass', __( 'A viewport meta tag was found.', 'searchcounsel-seo-audit' ), __( 'Test the page on mobile devices to confirm its layout remains usable.', 'searchcounsel-seo-audit' ), array( __( 'Viewport', 'searchcounsel-seo-audit' ) => $viewport ) ) );
	}
}

class SCSA_HTML_Language_Check extends SCSA_DOM_Check {
	/**
	 * @inheritDoc
	 */
	public function run( array $context ) {
		$nodes = $context['xpath']->query( '//html' );
		$lang  = ( $nodes && $nodes->length ) ? trim( $nodes->item( 0 )->getAttribute( 'lang' ) ) : '';

		if ( '' === $lang ) {
			return array( SCSA_Check_Result::make( 'html_lang', __( 'Document language', 'searchcounsel-seo-audit' ), 'warning', __( 'The HTML lang attribute is missing.', 'searchcounsel-seo-audit' ), __( 'Set the lang attribute on the HTML element so browsers and assistive technologies can identify the page language.', 'searchcounsel-seo-audit' ) ) );
		}

		return array( SCSA_Check_Result::make( 'html_lang', __( 'Document language', 'searchcounsel-seo-audit' ), 'pass', sprintf( __( 'The document language is set to %s.', 'searchcounsel-seo-audit' ), $lang ), __( 'Keep the declared language aligned with the primary language of the page.', 'searchcounsel-seo-audit' ), array( __( 'Language', 'searchcounsel-seo-audit' ) => $lang ) ) );
	}
}

class SCSA_Page_Size_Check extends SCSA_DOM_Check {
	/**
	 * @inheritDoc
	 */
	public function run( array $context ) {
		$bytes      = isset( $context['response']['body_size'] ) ? absint( $context['response']['body_size'] ) : strlen( $context['response']['body'] );
		$kilobytes  = max( 1, (int) round( $bytes / 1024 ) );
		$is_limited = $bytes >= 1024 * 1024;

		if ( $is_limited || $bytes > 750 * 1024 ) {
			return array( SCSA_Check_Result::make( 'page_size', __( 'Page size', 'searchcounsel-seo-audit' ), 'warning', sprintf( __( 'The downloaded HTML is about %d KB%s.', 'searchcounsel-seo-audit' ), $kilobytes, $is_limited ? __( ' (the audit download limit was reached)', 'searchcounsel-seo-audit' ) : '' ), __( 'Reduce unnecessary HTML, defer non-critical resources, and compress text responses to improve loading efficiency.', 'searchcounsel-seo-audit' ), array( __( 'Downloaded HTML', 'searchcounsel-seo-audit' ) => $kilobytes . ' KB' ) ) );
		}

		return array( SCSA_Check_Result::make( 'page_size', __( 'Page size', 'searchcounsel-seo-audit' ), 'pass', sprintf( __( 'The downloaded HTML is about %d KB.', 'searchcounsel-seo-audit' ), $kilobytes ), __( 'Continue to monitor total page weight, including images, CSS, and JavaScript, in a browser performance tool.', 'searchcounsel-seo-audit' ), array( __( 'Downloaded HTML', 'searchcounsel-seo-audit' ) => $kilobytes . ' KB' ) ) );
	}
}

class SCSA_Core_Web_Vitals_Check extends SCSA_DOM_Check {
	/**
	 * @inheritDoc
	 */
	public function run( array $context ) {
		$duration = isset( $context['response']['duration_ms'] ) ? absint( $context['response']['duration_ms'] ) : 0;
		$size     = isset( $context['response']['body_size'] ) ? absint( $context['response']['body_size'] ) : 0;
		$details  = array(
			__( 'Server response time', 'searchcounsel-seo-audit' ) => $duration . ' ms',
			__( 'HTML downloaded', 'searchcounsel-seo-audit' )      => max( 1, (int) round( $size / 1024 ) ) . ' KB',
		);

		if ( $duration > 1500 || $size > 750 * 1024 ) {
			return array( SCSA_Check_Result::make( 'core_web_vitals', __( 'Core Web Vitals guidance', 'searchcounsel-seo-audit' ), 'warning', __( 'The server response or HTML payload may make a fast user experience harder to achieve.', 'searchcounsel-seo-audit' ), __( 'Use PageSpeed Insights or real-user data to measure LCP, INP, and CLS. Prioritize fast server response, optimized images, and minimal render-blocking code.', 'searchcounsel-seo-audit' ), $details ) );
		}

		return array( SCSA_Check_Result::make( 'core_web_vitals', __( 'Core Web Vitals guidance', 'searchcounsel-seo-audit' ), 'pass', __( 'Basic server-response and HTML-size signals look reasonable.', 'searchcounsel-seo-audit' ), __( 'This is not field Core Web Vitals data. Use PageSpeed Insights or CrUX to validate LCP, INP, and CLS with real visitors.', 'searchcounsel-seo-audit' ), $details ) );
	}
}
