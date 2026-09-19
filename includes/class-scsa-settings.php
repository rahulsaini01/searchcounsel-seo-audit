<?php
/**
 * Settings schema and fields for SearchCounselco lead routing.
 *
 * @package SearchCounsel_SEO_Audit
 */

defined( 'ABSPATH' ) || exit;

class SCSA_Settings {
	/**
	 * Registers settings hooks.
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'register' ) );
	}

	/**
	 * Returns merged plugin settings.
	 *
	 * @return array<string,mixed>
	 */
	public static function get() {
		$stored = get_option( 'scsa_settings', array() );

		return wp_parse_args( is_array( $stored ) ? $stored : array(), self::defaults() );
	}

	/**
	 * Registers the settings fields used by the admin template.
	 *
	 * @return void
	 */
	public function register() {
		register_setting(
			'scsa_settings_group',
			'scsa_settings',
			array( $this, 'sanitize' )
		);

		add_settings_section(
			'scsa_lead_settings',
			__( 'Lead generation settings', 'searchcounsel-seo-audit' ),
			array( $this, 'render_section' ),
			'scsa-settings'
		);

		add_settings_field( 'consultation_email', __( 'Consultation recipient', 'searchcounsel-seo-audit' ), array( $this, 'render_email_field' ), 'scsa-settings', 'scsa_lead_settings' );
		add_settings_field( 'consultation_url', __( 'Contact CTA URL', 'searchcounsel-seo-audit' ), array( $this, 'render_url_field' ), 'scsa-settings', 'scsa_lead_settings' );
		add_settings_field( 'audit_rate_limit', __( 'Audits per IP per hour', 'searchcounsel-seo-audit' ), array( $this, 'render_rate_field' ), 'scsa-settings', 'scsa_lead_settings' );
	}

	/**
	 * Sanitizes the full settings payload.
	 *
	 * @param mixed $input Submitted value.
	 * @return array<string,mixed>
	 */
	public function sanitize( $input ) {
		$input    = is_array( $input ) ? $input : array();
		$defaults = self::defaults();
		$email    = isset( $input['consultation_email'] ) ? sanitize_email( $input['consultation_email'] ) : $defaults['consultation_email'];
		$url      = isset( $input['consultation_url'] ) ? esc_url_raw( trim( $input['consultation_url'] ) ) : $defaults['consultation_url'];
		$limit    = isset( $input['audit_rate_limit'] ) ? absint( $input['audit_rate_limit'] ) : $defaults['audit_rate_limit'];

		if ( ! is_email( $email ) ) {
			add_settings_error( 'scsa_settings', 'scsa_invalid_email', __( 'Enter a valid consultation recipient email address.', 'searchcounsel-seo-audit' ) );
			$email = $defaults['consultation_email'];
		}

		if ( '' !== $url && ! wp_http_validate_url( $url ) ) {
			add_settings_error( 'scsa_settings', 'scsa_invalid_cta_url', __( 'Enter a valid absolute Contact CTA URL.', 'searchcounsel-seo-audit' ) );
			$url = $defaults['consultation_url'];
		}

		return array(
			'consultation_email' => $email,
			'consultation_url'   => $url,
			'audit_rate_limit'   => max( 1, min( 30, $limit ) ),
		);
	}

	/**
	 * Renders the section description.
	 *
	 * @return void
	 */
	public function render_section() {
		echo '<p>' . esc_html__( 'Route free-consultation requests and set a sensible public audit limit. Limits use a short-lived, hashed network identifier only.', 'searchcounsel-seo-audit' ) . '</p>';
	}

	/**
	 * Renders the recipient field.
	 *
	 * @return void
	 */
	public function render_email_field() {
		$settings = self::get();
		printf( '<input class="regular-text" type="email" name="scsa_settings[consultation_email]" value="%s" />', esc_attr( $settings['consultation_email'] ) );
	}

	/**
	 * Renders the CTA link field.
	 *
	 * @return void
	 */
	public function render_url_field() {
		$settings = self::get();
		printf( '<input class="regular-text code" type="url" name="scsa_settings[consultation_url]" value="%s" placeholder="https://searchcounsel.co/contact/" />', esc_attr( $settings['consultation_url'] ) );
	}

	/**
	 * Renders the audit rate setting.
	 *
	 * @return void
	 */
	public function render_rate_field() {
		$settings = self::get();
		printf( '<input class="small-text" type="number" min="1" max="30" name="scsa_settings[audit_rate_limit]" value="%d" />', absint( $settings['audit_rate_limit'] ) );
	}

	/**
	 * Provides defaults without persisting anything at activation time.
	 *
	 * @return array<string,mixed>
	 */
	private static function defaults() {
		return array(
			'consultation_email' => get_option( 'admin_email' ),
			'consultation_url'   => home_url( '/contact/' ),
			'audit_rate_limit'   => 6,
		);
	}
}
