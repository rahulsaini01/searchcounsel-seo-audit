<?php
/**
 * Shared DOM helpers for document-level audit checks.
 *
 * @package SearchCounsel_SEO_Audit
 */

defined( 'ABSPATH' ) || exit;

abstract class SCSA_DOM_Check implements SCSA_Audit_Check {
	/**
	 * Returns the first non-empty meta content value matching an attribute.
	 *
	 * @param DOMXPath $xpath     Page XPath helper.
	 * @param string   $attribute Meta attribute to match.
	 * @param string   $expected  Case-insensitive attribute value.
	 * @return string
	 */
	protected function meta_content( DOMXPath $xpath, $attribute, $expected ) {
		$nodes = $xpath->query( '//meta[@' . $attribute . ']' );

		if ( false === $nodes ) {
			return '';
		}

		foreach ( $nodes as $node ) {
			if ( strtolower( trim( $node->getAttribute( $attribute ) ) ) === strtolower( $expected ) ) {
				return $this->clean_text( $node->getAttribute( 'content' ) );
			}
		}

		return '';
	}

	/**
	 * Gets all Open Graph meta property names that contain content.
	 *
	 * @param DOMXPath $xpath Page XPath helper.
	 * @return string[]
	 */
	protected function open_graph_properties( DOMXPath $xpath ) {
		$properties = array();
		$nodes      = $xpath->query( '//meta[@property]' );

		if ( false === $nodes ) {
			return $properties;
		}

		foreach ( $nodes as $node ) {
			$property = strtolower( trim( $node->getAttribute( 'property' ) ) );
			$content  = $this->clean_text( $node->getAttribute( 'content' ) );

			if ( 0 === strpos( $property, 'og:' ) && '' !== $content ) {
				$properties[] = $property;
			}
		}

		return array_values( array_unique( $properties ) );
	}

	/**
	 * Normalizes text extracted from a document node.
	 *
	 * @param string $text Raw DOM text.
	 * @return string
	 */
	protected function clean_text( $text ) {
		return trim( preg_replace( '/\s+/u', ' ', (string) $text ) );
	}
}
