<?php
/**
 * Canonical, robots, social metadata, structured data, and sitemap checks.
 *
 * @package SearchCounsel_SEO_Audit
 */

defined( 'ABSPATH' ) || exit;

class SCSA_Canonical_Check extends SCSA_DOM_Check {
	/**
	 * @inheritDoc
	 */
	public function run( array $context ) {
		$nodes      = $context['xpath']->query( '//link[@rel][@href]' );
		$canonicals = array();

		if ( $nodes ) {
			foreach ( $nodes as $node ) {
				$relations = preg_split( '/\s+/', strtolower( trim( $node->getAttribute( 'rel' ) ) ) );

				if ( in_array( 'canonical', $relations, true ) ) {
					$canonicals[] = SCSA_HTTP_Client::resolve_url( $context['url'], $node->getAttribute( 'href' ) );
				}
			}
		}

		$canonicals = array_values( array_unique( $canonicals ) );

		if ( empty( $canonicals ) ) {
			return array( SCSA_Check_Result::make( 'canonical', __( 'Canonical URL', 'searchcounsel-seo-audit' ), 'warning', __( 'No canonical link was found.', 'searchcounsel-seo-audit' ), __( 'Add a self-referencing canonical URL to clarify the preferred version of this page.', 'searchcounsel-seo-audit' ) ) );
		}

		if ( count( $canonicals ) > 1 ) {
			return array( SCSA_Check_Result::make( 'canonical', __( 'Canonical URL', 'searchcounsel-seo-audit' ), 'warning', sprintf( __( '%d different canonical URLs were found.', 'searchcounsel-seo-audit' ), count( $canonicals ) ), __( 'Use a single, absolute canonical URL so search engines receive an unambiguous signal.', 'searchcounsel-seo-audit' ), array( __( 'First canonical', 'searchcounsel-seo-audit' ) => $canonicals[0] ) ) );
		}

		return array( SCSA_Check_Result::make( 'canonical', __( 'Canonical URL', 'searchcounsel-seo-audit' ), 'pass', __( 'One canonical URL was found.', 'searchcounsel-seo-audit' ), __( 'Confirm that this canonical remains the preferred, indexable version of the content.', 'searchcounsel-seo-audit' ), array( __( 'Canonical', 'searchcounsel-seo-audit' ) => $canonicals[0] ) ) );
	}
}

class SCSA_Robots_Meta_Check extends SCSA_DOM_Check {
	/**
	 * @inheritDoc
	 */
	public function run( array $context ) {
		$robots = $this->meta_content( $context['xpath'], 'name', 'robots' );

		if ( '' === $robots ) {
			return array( SCSA_Check_Result::make( 'robots_meta', __( 'Robots meta', 'searchcounsel-seo-audit' ), 'warning', __( 'No robots meta tag was found.', 'searchcounsel-seo-audit' ), __( 'This normally permits indexing; add a robots directive only when you need to control search engine behavior.', 'searchcounsel-seo-audit' ) ) );
		}

		$directives = array_filter( array_map( 'trim', explode( ',', strtolower( $robots ) ) ) );

		if ( in_array( 'noindex', $directives, true ) || in_array( 'none', $directives, true ) ) {
			return array( SCSA_Check_Result::make( 'robots_meta', __( 'Robots meta', 'searchcounsel-seo-audit' ), 'critical', sprintf( __( 'The robots meta tag contains: %s.', 'searchcounsel-seo-audit' ), $robots ), __( 'Remove noindex (or none) if this page should appear in search results.', 'searchcounsel-seo-audit' ), array( __( 'Directives', 'searchcounsel-seo-audit' ) => $robots ) ) );
		}

		if ( in_array( 'nofollow', $directives, true ) ) {
			return array( SCSA_Check_Result::make( 'robots_meta', __( 'Robots meta', 'searchcounsel-seo-audit' ), 'warning', sprintf( __( 'The robots meta tag contains: %s.', 'searchcounsel-seo-audit' ), $robots ), __( 'Remove nofollow unless you intentionally want search engines to avoid following links on this page.', 'searchcounsel-seo-audit' ), array( __( 'Directives', 'searchcounsel-seo-audit' ) => $robots ) ) );
		}

		return array( SCSA_Check_Result::make( 'robots_meta', __( 'Robots meta', 'searchcounsel-seo-audit' ), 'pass', sprintf( __( 'The robots meta tag contains: %s.', 'searchcounsel-seo-audit' ), $robots ), __( 'Review directives after publishing changes to ensure they match your indexing intent.', 'searchcounsel-seo-audit' ), array( __( 'Directives', 'searchcounsel-seo-audit' ) => $robots ) ) );
	}
}

class SCSA_Robots_File_Check implements SCSA_Audit_Check {
	/**
	 * @inheritDoc
	 */
	public function run( array $context ) {
		$parts = wp_parse_url( $context['url'] );
		$port  = isset( $parts['port'] ) ? ':' . (int) $parts['port'] : '';
		$url   = $parts['scheme'] . '://' . $parts['host'] . $port . '/robots.txt';
		$probe = $context['client']->request( $url, 'GET', 4, 65536 );

		if ( ! is_wp_error( $probe ) && $probe['code'] >= 200 && $probe['code'] < 300 ) {
			return array( SCSA_Check_Result::make( 'robots_txt', __( 'robots.txt', 'searchcounsel-seo-audit' ), 'pass', __( 'An accessible robots.txt file was found.', 'searchcounsel-seo-audit' ), __( 'Review disallow rules regularly and reference the XML sitemap where appropriate.', 'searchcounsel-seo-audit' ), array( __( 'robots.txt', 'searchcounsel-seo-audit' ) => $probe['url'] ) ) );
		}

		return array( SCSA_Check_Result::make( 'robots_txt', __( 'robots.txt', 'searchcounsel-seo-audit' ), 'warning', __( 'No accessible robots.txt file could be confirmed.', 'searchcounsel-seo-audit' ), __( 'Publish a robots.txt file to document crawler access rules and reference your XML sitemap.', 'searchcounsel-seo-audit' ), array( __( 'Checked', 'searchcounsel-seo-audit' ) => $url ) ) );
	}
}

class SCSA_Open_Graph_Check extends SCSA_DOM_Check {
	/**
	 * @inheritDoc
	 */
	public function run( array $context ) {
		$properties = $this->open_graph_properties( $context['xpath'] );
		$required   = array( 'og:title', 'og:description', 'og:image', 'og:url' );
		$missing    = array_values( array_diff( $required, $properties ) );

		if ( empty( $properties ) ) {
			return array( SCSA_Check_Result::make( 'open_graph', __( 'Open Graph tags', 'searchcounsel-seo-audit' ), 'warning', __( 'No Open Graph tags were found.', 'searchcounsel-seo-audit' ), __( 'Add Open Graph title, description, image, and URL tags to improve social sharing previews.', 'searchcounsel-seo-audit' ) ) );
		}

		if ( ! empty( $missing ) ) {
			return array( SCSA_Check_Result::make( 'open_graph', __( 'Open Graph tags', 'searchcounsel-seo-audit' ), 'warning', sprintf( __( '%1$d Open Graph tags were found; missing: %2$s.', 'searchcounsel-seo-audit' ), count( $properties ), implode( ', ', $missing ) ), __( 'Provide the missing core Open Graph tags to control how this page appears when shared.', 'searchcounsel-seo-audit' ), array( __( 'Tags found', 'searchcounsel-seo-audit' ) => implode( ', ', $properties ) ) ) );
		}

		return array( SCSA_Check_Result::make( 'open_graph', __( 'Open Graph tags', 'searchcounsel-seo-audit' ), 'pass', __( 'All core Open Graph tags were found.', 'searchcounsel-seo-audit' ), __( 'Keep social titles, descriptions, images, and URLs updated when the page changes.', 'searchcounsel-seo-audit' ), array( __( 'Tags found', 'searchcounsel-seo-audit' ) => implode( ', ', $properties ) ) ) );
	}
}

class SCSA_Twitter_Card_Check extends SCSA_DOM_Check {
	/**
	 * @inheritDoc
	 */
	public function run( array $context ) {
		$nodes = $context['xpath']->query( '//meta[@name or @property]' );
		$tags  = array();

		if ( $nodes ) {
			foreach ( $nodes as $node ) {
				$name    = strtolower( trim( $node->getAttribute( 'name' ) ) );
				$property = strtolower( trim( $node->getAttribute( 'property' ) ) );
				$content = $this->clean_text( $node->getAttribute( 'content' ) );

				if ( '' !== $content && ( 0 === strpos( $name, 'twitter:' ) || 0 === strpos( $property, 'twitter:' ) ) ) {
					$tags[] = '' !== $name ? $name : $property;
				}
			}
		}

		$tags = array_values( array_unique( $tags ) );

		if ( ! in_array( 'twitter:card', $tags, true ) ) {
			return array( SCSA_Check_Result::make( 'twitter_card', __( 'Twitter Card tags', 'searchcounsel-seo-audit' ), 'warning', __( 'No Twitter Card tag was found.', 'searchcounsel-seo-audit' ), __( 'Add a twitter:card tag and supporting title, description, and image metadata to improve sharing previews.', 'searchcounsel-seo-audit' ) ) );
		}

		return array( SCSA_Check_Result::make( 'twitter_card', __( 'Twitter Card tags', 'searchcounsel-seo-audit' ), 'pass', __( 'Twitter Card metadata was found.', 'searchcounsel-seo-audit' ), __( 'Validate social preview metadata after major content or image changes.', 'searchcounsel-seo-audit' ), array( __( 'Tags found', 'searchcounsel-seo-audit' ) => implode( ', ', $tags ) ) ) );
	}
}

class SCSA_Schema_Check extends SCSA_DOM_Check {
	/**
	 * @inheritDoc
	 */
	public function run( array $context ) {
		$nodes          = $context['xpath']->query( '//script[@type]' );
		$valid_json_ld  = 0;
		$invalid_json_ld = 0;
		$microdata_nodes = $context['xpath']->query( '//*[@itemscope]' );
		$microdata_count = $microdata_nodes ? $microdata_nodes->length : 0;

		if ( $nodes ) {
			foreach ( $nodes as $node ) {
				$type = strtolower( trim( $node->getAttribute( 'type' ) ) );

				if ( 0 !== strpos( $type, 'application/ld+json' ) ) {
					continue;
				}

				json_decode( trim( $node->textContent ), true );

				if ( JSON_ERROR_NONE === json_last_error() ) {
					$valid_json_ld++;
				} else {
					$invalid_json_ld++;
				}
			}
		}

		$details = array(
			__( 'Valid JSON-LD blocks', 'searchcounsel-seo-audit' ) => $valid_json_ld,
			__( 'Invalid JSON-LD blocks', 'searchcounsel-seo-audit' ) => $invalid_json_ld,
			__( 'Microdata items', 'searchcounsel-seo-audit' )       => $microdata_count,
		);

		if ( $invalid_json_ld > 0 && 0 === $valid_json_ld ) {
			return array( SCSA_Check_Result::make( 'structured_data', __( 'Schema / structured data', 'searchcounsel-seo-audit' ), 'critical', __( 'JSON-LD structured data was found but could not be parsed.', 'searchcounsel-seo-audit' ), __( 'Correct the JSON syntax, then validate the markup with Google’s Rich Results Test.', 'searchcounsel-seo-audit' ), $details ) );
		}

		if ( $invalid_json_ld > 0 ) {
			return array( SCSA_Check_Result::make( 'structured_data', __( 'Schema / structured data', 'searchcounsel-seo-audit' ), 'warning', __( 'Some JSON-LD blocks could not be parsed.', 'searchcounsel-seo-audit' ), __( 'Correct malformed JSON-LD and validate the remaining markup with a structured data testing tool.', 'searchcounsel-seo-audit' ), $details ) );
		}

		if ( $valid_json_ld > 0 || $microdata_count > 0 ) {
			return array( SCSA_Check_Result::make( 'structured_data', __( 'Schema / structured data', 'searchcounsel-seo-audit' ), 'pass', __( 'Structured data markup was found.', 'searchcounsel-seo-audit' ), __( 'Validate schema types and required properties against the page content before publishing.', 'searchcounsel-seo-audit' ), $details ) );
		}

		return array( SCSA_Check_Result::make( 'structured_data', __( 'Schema / structured data', 'searchcounsel-seo-audit' ), 'warning', __( 'No JSON-LD or microdata structured data was found.', 'searchcounsel-seo-audit' ), __( 'Add schema only when it accurately represents visible page content and supports your search appearance goals.', 'searchcounsel-seo-audit' ), $details ) );
	}
}

class SCSA_Sitemap_Check implements SCSA_Audit_Check {
	/**
	 * @inheritDoc
	 */
	public function run( array $context ) {
		$origin       = $this->origin( $context['url'] );
		$robots_url   = $origin . '/robots.txt';
		$robots       = $context['client']->request( $robots_url, 'GET', 4, 65536 );
		$sitemap_urls = array();

		if ( ! is_wp_error( $robots ) && $robots['code'] >= 200 && $robots['code'] < 300 ) {
			if ( preg_match_all( '/^\s*sitemap\s*:\s*(\S+)\s*$/im', $robots['body'], $matches ) ) {
				foreach ( $matches[1] as $sitemap_url ) {
					$sitemap_urls[] = SCSA_HTTP_Client::resolve_url( $robots['url'], $sitemap_url );
				}
			}
		}

		if ( empty( $sitemap_urls ) ) {
			$sitemap_urls[] = $origin . '/sitemap.xml';
			$sitemap_urls[] = $origin . '/wp-sitemap.xml';
		}

		$sitemap_urls = array_slice( array_values( array_unique( $sitemap_urls ) ), 0, 3 );

		foreach ( $sitemap_urls as $sitemap_url ) {
			$probe = $context['client']->probe( $sitemap_url );

			if ( ! is_wp_error( $probe ) && $probe['code'] >= 200 && $probe['code'] < 300 ) {
				return array( SCSA_Check_Result::make( 'sitemap', __( 'Sitemap availability', 'searchcounsel-seo-audit' ), 'pass', __( 'An accessible XML sitemap was found.', 'searchcounsel-seo-audit' ), __( 'Keep the sitemap current and submit it in the relevant search engine webmaster tools.', 'searchcounsel-seo-audit' ), array( __( 'Sitemap', 'searchcounsel-seo-audit' ) => $probe['url'] ) ) );
			}
		}

		return array( SCSA_Check_Result::make( 'sitemap', __( 'Sitemap availability', 'searchcounsel-seo-audit' ), 'warning', __( 'No accessible sitemap could be confirmed.', 'searchcounsel-seo-audit' ), __( 'Publish a valid XML sitemap, reference it in robots.txt, and make sure it returns a successful HTTP response.', 'searchcounsel-seo-audit' ), array( __( 'Checked', 'searchcounsel-seo-audit' ) => implode( ', ', $sitemap_urls ) ) ) );
	}

	/**
	 * Builds the scheme and authority component of a URL.
	 *
	 * @param string $url Full URL.
	 * @return string
	 */
	private function origin( $url ) {
		$parts = wp_parse_url( $url );
		$port  = isset( $parts['port'] ) ? ':' . (int) $parts['port'] : '';

		return $parts['scheme'] . '://' . $parts['host'] . $port;
	}
}
