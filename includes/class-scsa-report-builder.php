<?php
/**
 * Builds a public-friendly report from low-level audit output.
 *
 * @package SearchCounsel_SEO_Audit
 */

defined( 'ABSPATH' ) || exit;

class SCSA_Report_Builder {
	/**
	 * Score calculator.
	 *
	 * @var SCSA_Score_Engine
	 */
	private $scorer;

	/**
	 * @param SCSA_Score_Engine $scorer Score calculator.
	 */
	public function __construct( SCSA_Score_Engine $scorer ) {
		$this->scorer = $scorer;
	}

	/**
	 * Appends score and impact-ranked recommendations to an audit.
	 *
	 * @param array<string,mixed> $audit Audit output.
	 * @return array<string,mixed>
	 */
	public function build( array $audit ) {
		$results         = isset( $audit['results'] ) && is_array( $audit['results'] ) ? $audit['results'] : array();
		$score_data      = $this->scorer->calculate( $results );
		$recommendations = array();

		foreach ( $results as $result ) {
			if ( empty( $result['status'] ) || 'pass' === $result['status'] ) {
				continue;
			}

			$recommendations[] = array(
				'key'            => isset( $result['key'] ) ? $result['key'] : '',
				'label'          => isset( $result['label'] ) ? $result['label'] : '',
				'status'         => $result['status'],
				'priority'       => $this->priority( $result ),
				'recommendation' => isset( $result['recommendation'] ) ? $result['recommendation'] : '',
			);
		}

		usort( $recommendations, array( $this, 'sort_recommendations' ) );

		$audit['score']           = $score_data['score'];
		$audit['grade']           = $score_data['grade'];
		$audit['recommendations'] = $recommendations;

		return $audit;
	}

	/**
	 * Classifies recommendation impact.
	 *
	 * @param array<string,mixed> $result Check result.
	 * @return string
	 */
	private function priority( array $result ) {
		if ( 'critical' === $result['status'] ) {
			return 'high';
		}

		$high_impact_warnings = array( 'title', 'meta_description', 'h1', 'canonical', 'robots_meta', 'https', 'http_status', 'viewport', 'page_size', 'core_web_vitals' );

		return in_array( $result['key'], $high_impact_warnings, true ) ? 'high' : 'medium';
	}

	/**
	 * Sorts high-impact recommendations first, then alphabetically.
	 *
	 * @param array<string,mixed> $first  First recommendation.
	 * @param array<string,mixed> $second Second recommendation.
	 * @return int
	 */
	private function sort_recommendations( array $first, array $second ) {
		$order = array( 'high' => 0, 'medium' => 1, 'low' => 2 );
		$first_weight  = isset( $order[ $first['priority'] ] ) ? $order[ $first['priority'] ] : 3;
		$second_weight = isset( $order[ $second['priority'] ] ) ? $order[ $second['priority'] ] : 3;

		if ( $first_weight === $second_weight ) {
			return strcmp( (string) $first['label'], (string) $second['label'] );
		}

		return $first_weight - $second_weight;
	}
}
