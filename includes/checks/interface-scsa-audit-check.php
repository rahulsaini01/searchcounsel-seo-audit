<?php
/**
 * Contract implemented by every pluggable audit check.
 *
 * @package SearchCounsel_SEO_Audit
 */

defined( 'ABSPATH' ) || exit;

interface SCSA_Audit_Check {
	/**
	 * Runs this check against the prepared page context.
	 *
	 * @param array<string,mixed> $context Parsed page and request facts.
	 * @return array<int,array<string,mixed>>
	 */
	public function run( array $context );
}
