<?php
/**
 * Converts individual audit results into a transparent 0–100 score.
 *
 * @package SearchCounsel_SEO_Audit
 */

defined( 'ABSPATH' ) || exit;

class SCSA_Score_Engine {
	/**
	 * Calculates a score and grade from checks.
	 *
	 * @param array<int,array<string,mixed>> $results Audit checks.
	 * @return array<string,mixed>
	 */
	public function calculate( array $results ) {
		$score    = 100;
		$penalty  = 0;
		$weights  = $this->weights();

		foreach ( $results as $result ) {
			$status = isset( $result['status'] ) ? $result['status'] : 'warning';
			$key    = isset( $result['key'] ) ? $result['key'] : '';
			$weight = isset( $weights[ $key ] ) ? $weights[ $key ] : 4;

			if ( 'critical' === $status ) {
				$penalty += $weight;
			} elseif ( 'warning' === $status ) {
				$penalty += max( 2, (int) ceil( $weight * 0.45 ) );
			}
		}

		$score = max( 0, min( 100, $score - $penalty ) );

		return array(
			'score' => $score,
			'grade' => $this->grade( $score ),
		);
	}

	/**
	 * Returns score weights for higher-impact SEO failures.
	 *
	 * @return array<string,int>
	 */
	private function weights() {
		return array(
			'title'            => 12,
			'meta_description' => 10,
			'h1'               => 9,
			'canonical'        => 9,
			'robots_meta'      => 16,
			'https'            => 14,
			'http_status'      => 16,
			'robots_txt'       => 6,
			'sitemap'          => 6,
			'broken_links'     => 8,
			'page_size'        => 6,
			'core_web_vitals'  => 8,
			'viewport'         => 7,
			'image_alt'        => 5,
		);
	}

	/**
	 * Provides a plain-language band for the score.
	 *
	 * @param int $score Audit score.
	 * @return string
	 */
	private function grade( $score ) {
		if ( $score >= 90 ) {
			return __( 'Excellent', 'searchcounsel-seo-audit' );
		}
		if ( $score >= 75 ) {
			return __( 'Strong', 'searchcounsel-seo-audit' );
		}
		if ( $score >= 55 ) {
			return __( 'Needs attention', 'searchcounsel-seo-audit' );
		}

		return __( 'Critical improvements needed', 'searchcounsel-seo-audit' );
	}
}
