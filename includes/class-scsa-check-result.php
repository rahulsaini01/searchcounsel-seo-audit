<?php
/**
 * Value object factory for audit checks.
 *
 * @package SearchCounsel_SEO_Audit
 */

defined( 'ABSPATH' ) || exit;

class SCSA_Check_Result {
	/**
	 * Builds a consistent, JSON-serializable audit result.
	 *
	 * @param string               $key            Stable programmatic key.
	 * @param string               $label          Human-readable label.
	 * @param string               $status         pass, warning, or critical.
	 * @param string               $summary        What was found.
	 * @param string               $recommendation Suggested next action.
	 * @param array<string,mixed>  $details        Small, display-safe facts.
	 * @return array<string,mixed>
	 */
	public static function make( $key, $label, $status, $summary, $recommendation, $details = array() ) {
		$allowed_statuses = array( 'pass', 'warning', 'critical' );

		if ( ! in_array( $status, $allowed_statuses, true ) ) {
			$status = 'warning';
		}

		return array(
			'key'            => sanitize_key( $key ),
			'label'          => (string) $label,
			'status'         => $status,
			'summary'        => (string) $summary,
			'recommendation' => (string) $recommendation,
			'details'        => is_array( $details ) ? $details : array(),
		);
	}
}
