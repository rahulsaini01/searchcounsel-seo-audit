<?php
/**
 * Local persistence for completed audit reports.
 *
 * @package SearchCounsel_SEO_Audit
 */

defined( 'ABSPATH' ) || exit;

class SCSA_History_Repository {
	/**
	 * Returns the plugin table name.
	 *
	 * @return string
	 */
	public static function table_name() {
		global $wpdb;

		return $wpdb->prefix . 'scsa_audits';
	}

	/**
	 * Creates or updates the history table using WordPress's schema helper.
	 *
	 * @return void
	 */
	public static function install() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table_name      = self::table_name();
		$charset_collate = $wpdb->get_charset_collate();
		$sql             = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			url varchar(2083) NOT NULL,
			score smallint(5) unsigned NOT NULL,
			summary longtext NOT NULL,
			report longtext NOT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY created_at (created_at)
		) {$charset_collate};";

		dbDelta( $sql );
	}

	/**
	 * Saves a generated audit report without keeping the retrieved page HTML.
	 *
	 * @param array<string,mixed> $report Completed public report.
	 * @return bool
	 */
	public function save( array $report ) {
		global $wpdb;

		$url     = isset( $report['audited_url'] ) ? substr( (string) $report['audited_url'], 0, 2083 ) : '';
		$score   = isset( $report['score'] ) ? max( 0, min( 100, absint( $report['score'] ) ) ) : 0;
		$summary = wp_json_encode(
			array(
				'grade'   => isset( $report['grade'] ) ? sanitize_text_field( $report['grade'] ) : '',
				'summary' => isset( $report['summary'] ) && is_array( $report['summary'] ) ? $report['summary'] : array(),
			)
		);
		$payload = wp_json_encode( $report );

		if ( empty( $url ) || false === $summary || false === $payload ) {
			return false;
		}

		return false !== $wpdb->insert(
			self::table_name(),
			array(
				'url'        => $url,
				'score'      => $score,
				'summary'    => $summary,
				'report'     => $payload,
				'created_at' => current_time( 'mysql', true ),
			),
			array( '%s', '%d', '%s', '%s', '%s' )
		);
	}

	/**
	 * Retrieves compact rows for the protected admin history page.
	 *
	 * @param int $limit Maximum number of rows.
	 * @return array<int,object>
	 */
	public function recent( $limit = 50 ) {
		global $wpdb;

		$limit = max( 1, min( 100, absint( $limit ) ) );
		$query = $wpdb->prepare(
			'SELECT id, url, score, summary, created_at FROM ' . self::table_name() . ' ORDER BY id DESC LIMIT %d',
			$limit
		);

		return $wpdb->get_results( $query );
	}
}
