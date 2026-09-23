<?php
/**
 * Narrow, database-backed advisory locks for concurrent public requests.
 *
 * @package SearchCounsel_SEO_Audit
 */

defined( 'ABSPATH' ) || exit;

class SCSA_Database_Lock {
	/**
	 * Acquired MySQL lock name.
	 *
	 * @var string
	 */
	private $name;

	/**
	 * Whether this instance still owns the lock.
	 *
	 * @var bool
	 */
	private $acquired = true;

	/**
	 * @param string $name Acquired lock name.
	 */
	private function __construct( $name ) {
		$this->name = $name;
	}

	/**
	 * Acquires a site-scoped MySQL advisory lock with a bounded wait.
	 *
	 * MySQL advisory locks are connection-owned, so the database also releases
	 * the lock if PHP terminates before the explicit finally cleanup can run.
	 *
	 * @param string $namespace Short lock purpose.
	 * @param string $identity  Exact resource identity to serialize.
	 * @param int    $wait      Maximum acquisition wait in seconds.
	 * @return self|WP_Error
	 */
	public static function acquire( $namespace, $identity, $wait ) {
		global $wpdb;

		$namespace = substr( sanitize_key( $namespace ), 0, 12 );
		$wait      = max( 0, min( 10, absint( $wait ) ) );
		$site_id   = function_exists( 'get_current_blog_id' ) ? get_current_blog_id() : 1;
		$name      = 'scsa_' . $namespace . '_' . substr( hash( 'sha256', $site_id . '|' . (string) $identity ), 0, 40 );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$acquired = $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, %d)', $name, $wait ) );

		if ( '1' !== (string) $acquired ) {
			return new WP_Error( 'scsa_lock_unavailable', __( 'The request could not be synchronized safely. Please try again.', 'searchcounsel-seo-audit' ) );
		}

		return new self( $name );
	}

	/**
	 * Releases an acquired advisory lock once.
	 *
	 * @return void
	 */
	public function release() {
		global $wpdb;

		if ( ! $this->acquired ) {
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $this->name ) );
		$this->acquired = false;
	}
}
