<?php
/**
 * Content, headings, and image accessibility checks.
 *
 * @package SearchCounsel_SEO_Audit
 */

defined( 'ABSPATH' ) || exit;

class SCSA_Title_Check extends SCSA_DOM_Check {
	/**
	 * @inheritDoc
	 */
	public function run( array $context ) {
		$nodes = $context['xpath']->query( '//title' );
		$title = ( $nodes && $nodes->length ) ? $this->clean_text( $nodes->item( 0 )->textContent ) : '';
		$size  = function_exists( 'mb_strlen' ) ? mb_strlen( $title ) : strlen( $title );

		if ( '' === $title ) {
			return array( SCSA_Check_Result::make( 'title', __( 'Title tag', 'searchcounsel-seo-audit' ), 'critical', __( 'No title tag was found.', 'searchcounsel-seo-audit' ), __( 'Add one unique, descriptive <title> tag to the page.', 'searchcounsel-seo-audit' ) ) );
		}

		if ( $size < 30 || $size > 60 ) {
			return array( SCSA_Check_Result::make( 'title', __( 'Title tag', 'searchcounsel-seo-audit' ), 'warning', sprintf( __( 'A title tag was found (%d characters).', 'searchcounsel-seo-audit' ), $size ), __( 'Aim for a clear title of roughly 30–60 characters without keyword stuffing.', 'searchcounsel-seo-audit' ), array( __( 'Title', 'searchcounsel-seo-audit' ) => $title ) ) );
		}

		return array( SCSA_Check_Result::make( 'title', __( 'Title tag', 'searchcounsel-seo-audit' ), 'pass', sprintf( __( 'A title tag was found (%d characters).', 'searchcounsel-seo-audit' ), $size ), __( 'Keep this title unique and aligned with the page’s primary topic.', 'searchcounsel-seo-audit' ), array( __( 'Title', 'searchcounsel-seo-audit' ) => $title ) ) );
	}
}

class SCSA_Meta_Description_Check extends SCSA_DOM_Check {
	/**
	 * @inheritDoc
	 */
	public function run( array $context ) {
		$description = $this->meta_content( $context['xpath'], 'name', 'description' );
		$size        = function_exists( 'mb_strlen' ) ? mb_strlen( $description ) : strlen( $description );

		if ( '' === $description ) {
			return array( SCSA_Check_Result::make( 'meta_description', __( 'Meta description', 'searchcounsel-seo-audit' ), 'critical', __( 'No meta description was found.', 'searchcounsel-seo-audit' ), __( 'Add a concise description that explains the page’s value in search results.', 'searchcounsel-seo-audit' ) ) );
		}

		if ( $size < 70 || $size > 160 ) {
			return array( SCSA_Check_Result::make( 'meta_description', __( 'Meta description', 'searchcounsel-seo-audit' ), 'warning', sprintf( __( 'A meta description was found (%d characters).', 'searchcounsel-seo-audit' ), $size ), __( 'Refine it to a compelling, page-specific description of about 70–160 characters.', 'searchcounsel-seo-audit' ), array( __( 'Description', 'searchcounsel-seo-audit' ) => $description ) ) );
		}

		return array( SCSA_Check_Result::make( 'meta_description', __( 'Meta description', 'searchcounsel-seo-audit' ), 'pass', sprintf( __( 'A meta description was found (%d characters).', 'searchcounsel-seo-audit' ), $size ), __( 'Keep the description accurate, unique, and useful to searchers.', 'searchcounsel-seo-audit' ), array( __( 'Description', 'searchcounsel-seo-audit' ) => $description ) ) );
	}
}

class SCSA_Heading_Check extends SCSA_DOM_Check {
	/**
	 * @inheritDoc
	 */
	public function run( array $context ) {
		$h1_nodes = $context['xpath']->query( '//h1' );
		$h2_nodes = $context['xpath']->query( '//h2' );
		$h3_nodes = $context['xpath']->query( '//h3' );
		$h1_count = $h1_nodes ? $h1_nodes->length : 0;
		$h2_count = $h2_nodes ? $h2_nodes->length : 0;
		$h3_count = $h3_nodes ? $h3_nodes->length : 0;
		$results  = array();

		if ( 0 === $h1_count ) {
			$results[] = SCSA_Check_Result::make( 'h1', __( 'H1 heading', 'searchcounsel-seo-audit' ), 'critical', __( 'No H1 heading was found.', 'searchcounsel-seo-audit' ), __( 'Add one descriptive H1 that introduces the main topic of the page.', 'searchcounsel-seo-audit' ) );
		} elseif ( $h1_count > 1 ) {
			$results[] = SCSA_Check_Result::make( 'h1', __( 'H1 heading', 'searchcounsel-seo-audit' ), 'warning', sprintf( __( '%d H1 headings were found.', 'searchcounsel-seo-audit' ), $h1_count ), __( 'Use one clear H1 unless multiple H1s are essential to the document structure.', 'searchcounsel-seo-audit' ), array( __( 'First H1', 'searchcounsel-seo-audit' ) => $this->clean_text( $h1_nodes->item( 0 )->textContent ) ) );
		} else {
			$results[] = SCSA_Check_Result::make( 'h1', __( 'H1 heading', 'searchcounsel-seo-audit' ), 'pass', __( 'One H1 heading was found.', 'searchcounsel-seo-audit' ), __( 'Keep the H1 focused on the page’s main subject.', 'searchcounsel-seo-audit' ), array( __( 'H1', 'searchcounsel-seo-audit' ) => $this->clean_text( $h1_nodes->item( 0 )->textContent ) ) );
		}

		if ( 0 === $h2_count ) {
			$results[] = SCSA_Check_Result::make( 'h2', __( 'H2 headings', 'searchcounsel-seo-audit' ), 'warning', __( 'No H2 headings were found.', 'searchcounsel-seo-audit' ), __( 'Use H2 headings to organize substantial sections where it improves readability.', 'searchcounsel-seo-audit' ) );
		} else {
			$results[] = SCSA_Check_Result::make( 'h2', __( 'H2 headings', 'searchcounsel-seo-audit' ), 'pass', sprintf( __( '%d H2 headings were found.', 'searchcounsel-seo-audit' ), $h2_count ), __( 'Keep headings descriptive and in a logical hierarchy.', 'searchcounsel-seo-audit' ), array( __( 'H2 count', 'searchcounsel-seo-audit' ) => $h2_count ) );
		}

		if ( 0 === $h3_count ) {
			$results[] = SCSA_Check_Result::make( 'h3', __( 'H3 headings', 'searchcounsel-seo-audit' ), 'pass', __( 'No H3 headings were found.', 'searchcounsel-seo-audit' ), __( 'H3 headings are optional; use them only to structure subsections beneath relevant H2 headings.', 'searchcounsel-seo-audit' ) );
		} else {
			$results[] = SCSA_Check_Result::make( 'h3', __( 'H3 headings', 'searchcounsel-seo-audit' ), 'pass', sprintf( __( '%d H3 headings were found.', 'searchcounsel-seo-audit' ), $h3_count ), __( 'Ensure H3 headings describe subsections within a logical H2 hierarchy.', 'searchcounsel-seo-audit' ), array( __( 'H3 count', 'searchcounsel-seo-audit' ) => $h3_count ) );
		}

		return $results;
	}
}

class SCSA_Image_Alt_Check extends SCSA_DOM_Check {
	/**
	 * @inheritDoc
	 */
	public function run( array $context ) {
		$images       = $context['xpath']->query( '//img' );
		$total        = $images ? $images->length : 0;
		$missing_alts = 0;

		if ( $images ) {
			foreach ( $images as $image ) {
				if ( ! $image->hasAttribute( 'alt' ) ) {
					$missing_alts++;
				}
			}
		}

		if ( 0 === $total ) {
			return array( SCSA_Check_Result::make( 'image_alt', __( 'Image alt attributes', 'searchcounsel-seo-audit' ), 'pass', __( 'No image elements were found on this page.', 'searchcounsel-seo-audit' ), __( 'Add meaningful alt text to future content images; empty alt text is appropriate only for decorative images.', 'searchcounsel-seo-audit' ) ) );
		}

		if ( $missing_alts > 0 ) {
			return array( SCSA_Check_Result::make( 'image_alt', __( 'Image alt attributes', 'searchcounsel-seo-audit' ), 'warning', sprintf( __( '%1$d of %2$d images have no alt attribute.', 'searchcounsel-seo-audit' ), $missing_alts, $total ), __( 'Add an alt attribute to every image. Describe informative images and use alt="" only for decorative ones.', 'searchcounsel-seo-audit' ), array( __( 'Images', 'searchcounsel-seo-audit' ) => $total, __( 'Missing alt', 'searchcounsel-seo-audit' ) => $missing_alts ) ) );
		}

		return array( SCSA_Check_Result::make( 'image_alt', __( 'Image alt attributes', 'searchcounsel-seo-audit' ), 'pass', sprintf( __( 'All %d images include an alt attribute.', 'searchcounsel-seo-audit' ), $total ), __( 'Review alt text periodically to ensure it remains accurate and purposeful.', 'searchcounsel-seo-audit' ), array( __( 'Images', 'searchcounsel-seo-audit' ) => $total ) ) );
	}
}

class SCSA_Word_Count_Check extends SCSA_DOM_Check {
	/**
	 * @inheritDoc
	 */
	public function run( array $context ) {
		$document = clone $context['document'];
		$xpath    = new DOMXPath( $document );
		$ignored  = $xpath->query( '//script|//style|//noscript|//template|//svg' );

		if ( $ignored ) {
			foreach ( $ignored as $node ) {
				$node->parentNode->removeChild( $node );
			}
		}

		$body_nodes = $xpath->query( '//body' );
		$text       = $body_nodes && $body_nodes->length ? $body_nodes->item( 0 )->textContent : $document->textContent;
		$matches    = array();
		$word_count = preg_match_all( "/[\\p{L}\\p{N}]+(?:['’\\-][\\p{L}\\p{N}]+)*/u", $text, $matches );

		if ( false === $word_count ) {
			$word_count = 0;
		}

		if ( $word_count < 300 ) {
			return array( SCSA_Check_Result::make( 'word_count', __( 'Page word count', 'searchcounsel-seo-audit' ), 'warning', sprintf( __( 'About %d visible words were found.', 'searchcounsel-seo-audit' ), $word_count ), __( 'Ensure the page gives searchers enough original, helpful information for its purpose. Short pages can be appropriate for simple intents.', 'searchcounsel-seo-audit' ), array( __( 'Words', 'searchcounsel-seo-audit' ) => $word_count ) ) );
		}

		return array( SCSA_Check_Result::make( 'word_count', __( 'Page word count', 'searchcounsel-seo-audit' ), 'pass', sprintf( __( 'About %d visible words were found.', 'searchcounsel-seo-audit' ), $word_count ), __( 'Prioritize accuracy, clarity, and usefulness over adding words for their own sake.', 'searchcounsel-seo-audit' ), array( __( 'Words', 'searchcounsel-seo-audit' ) => $word_count ) ) );
	}
}
