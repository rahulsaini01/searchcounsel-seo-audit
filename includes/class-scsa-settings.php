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
	 * Returns the server-side PageSpeed API key.
	 *
	 * A wp-config.php constant takes precedence so production sites can keep the
	 * secret outside the WordPress database. The value is never localized or
	 * rendered into public markup.
	 *
	 * @return string
	 */
	public static function get_pagespeed_api_key() {
		if ( defined( 'SCSA_PAGESPEED_API_KEY' ) && is_string( SCSA_PAGESPEED_API_KEY ) ) {
			return self::sanitize_api_key( SCSA_PAGESPEED_API_KEY );
		}

		$settings = self::get();

		return self::sanitize_api_key( $settings['pagespeed_api_key'] );
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

		add_settings_section(
			'scsa_pagespeed_settings',
			__( 'PageSpeed Insights', 'searchcounsel-seo-audit' ),
			array( $this, 'render_pagespeed_section' ),
			'scsa-settings'
		);

		add_settings_field( 'pagespeed_enabled', __( 'Enable PageSpeed analysis', 'searchcounsel-seo-audit' ), array( $this, 'render_pagespeed_enabled_field' ), 'scsa-settings', 'scsa_pagespeed_settings' );
		add_settings_field( 'pagespeed_api_key', __( 'Google PageSpeed Insights API Key', 'searchcounsel-seo-audit' ), array( $this, 'render_pagespeed_api_key_field' ), 'scsa-settings', 'scsa_pagespeed_settings' );
		add_settings_field( 'pagespeed_cache_duration', __( 'Cache duration', 'searchcounsel-seo-audit' ), array( $this, 'render_pagespeed_cache_field' ), 'scsa-settings', 'scsa_pagespeed_settings' );
	}

	/**
	 * Sanitizes the full settings payload.
	 *
	 * @param mixed $input Submitted value.
	 * @return array<string,mixed>
	 */
	public function sanitize( $input ) {
		$input       = is_array( $input ) ? $input : array();
		$defaults    = self::defaults();
		$current     = get_option( 'scsa_settings', array() );
		$current     = is_array( $current ) ? wp_parse_args( $current, $defaults ) : $defaults;
		$email       = isset( $input['consultation_email'] ) && is_string( $input['consultation_email'] ) ? sanitize_email( $input['consultation_email'] ) : $defaults['consultation_email'];
		$url         = isset( $input['consultation_url'] ) && is_string( $input['consultation_url'] ) ? esc_url_raw( trim( $input['consultation_url'] ) ) : $defaults['consultation_url'];
		$limit       = isset( $input['audit_rate_limit'] ) && is_scalar( $input['audit_rate_limit'] ) ? absint( $input['audit_rate_limit'] ) : $defaults['audit_rate_limit'];
		$enabled     = isset( $input['pagespeed_enabled'] ) && is_scalar( $input['pagespeed_enabled'] ) && '1' === (string) $input['pagespeed_enabled'] ? 1 : 0;
		$cache       = isset( $input['pagespeed_cache_duration'] ) && is_scalar( $input['pagespeed_cache_duration'] ) ? absint( $input['pagespeed_cache_duration'] ) : $defaults['pagespeed_cache_duration'];
		$api_key     = isset( $current['pagespeed_api_key'] ) ? self::sanitize_api_key( $current['pagespeed_api_key'] ) : '';
		$new_api_key = isset( $input['pagespeed_api_key'] ) && is_string( $input['pagespeed_api_key'] ) ? trim( sanitize_text_field( $input['pagespeed_api_key'] ) ) : '';

		if ( isset( $input['pagespeed_clear_api_key'] ) && is_scalar( $input['pagespeed_clear_api_key'] ) && '1' === (string) $input['pagespeed_clear_api_key'] ) {
			$api_key = '';
		} elseif ( '' !== $new_api_key ) {
			$sanitized_key = self::sanitize_api_key( $new_api_key );

			if ( '' === $sanitized_key || $sanitized_key !== $new_api_key ) {
				add_settings_error( 'scsa_settings', 'scsa_invalid_pagespeed_key', __( 'The PageSpeed API key contains unsupported characters and was not changed.', 'searchcounsel-seo-audit' ) );
			} else {
				$api_key = $sanitized_key;
			}
		}

		if ( ! is_email( $email ) ) {
			add_settings_error( 'scsa_settings', 'scsa_invalid_email', __( 'Enter a valid consultation recipient email address.', 'searchcounsel-seo-audit' ) );
			$email = $defaults['consultation_email'];
		}

		if ( '' !== $url && ! wp_http_validate_url( $url ) ) {
			add_settings_error( 'scsa_settings', 'scsa_invalid_cta_url', __( 'Enter a valid absolute Contact CTA URL.', 'searchcounsel-seo-audit' ) );
			$url = $defaults['consultation_url'];
		}

		return array(
			'consultation_email'      => $email,
			'consultation_url'        => $url,
			'audit_rate_limit'        => max( 1, min( 30, $limit ) ),
			'pagespeed_enabled'       => $enabled,
			'pagespeed_api_key'       => $api_key,
			'pagespeed_cache_duration' => max( 5, min( 1440, $cache ) ),
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
	 * Renders the PageSpeed section description.
	 *
	 * @return void
	 */
	public function render_pagespeed_section() {
		echo '<p>' . esc_html__( 'Add Google PageSpeed desktop and mobile performance data to the existing public SEO report. The API key remains server-side.', 'searchcounsel-seo-audit' ) . '</p>';
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
	 * Renders the PageSpeed enable toggle.
	 *
	 * @return void
	 */
	public function render_pagespeed_enabled_field() {
		$settings = self::get();
		printf(
			'<input type="hidden" name="scsa_settings[pagespeed_enabled]" value="0" /><label><input type="checkbox" name="scsa_settings[pagespeed_enabled]" value="1" %s /> %s</label>',
			checked( 1, absint( $settings['pagespeed_enabled'] ), false ),
			esc_html__( 'Request PageSpeed data during each public audit when no valid cache entry exists.', 'searchcounsel-seo-audit' )
		);
	}

	/**
	 * Renders a write-only, masked API key field.
	 *
	 * @return void
	 */
	public function render_pagespeed_api_key_field() {
		$settings      = self::get();
		$constant_key  = defined( 'SCSA_PAGESPEED_API_KEY' ) && '' !== self::sanitize_api_key( SCSA_PAGESPEED_API_KEY );
		$stored_key    = ! empty( $settings['pagespeed_api_key'] );
		$placeholder   = $constant_key || $stored_key ? __( 'API key configured', 'searchcounsel-seo-audit' ) : __( 'Enter API key', 'searchcounsel-seo-audit' );

		printf(
			'<input class="regular-text code" type="password" name="scsa_settings[pagespeed_api_key]" value="" placeholder="%s" autocomplete="new-password" %s />',
			esc_attr( $placeholder ),
			$constant_key ? 'disabled="disabled"' : ''
		);

		if ( $constant_key ) {
			echo '<p class="description">' . esc_html__( 'PageSpeed API key is configured via wp-config.php. The key is not displayed here.', 'searchcounsel-seo-audit' ) . '</p>';

			if ( $stored_key ) {
				echo '<p><label><input type="checkbox" name="scsa_settings[pagespeed_clear_api_key]" value="1" /> ' . esc_html__( 'Remove stored database key', 'searchcounsel-seo-audit' ) . '</label></p>';
				echo '<p class="description">' . esc_html__( 'This older key would become active if the wp-config.php constant were removed. Save changes to delete it permanently; the wp-config.php key will continue to be used.', 'searchcounsel-seo-audit' ) . '</p>';
			}
		} else {
			echo '<p class="description">' . esc_html__( 'Saved keys are never rendered back into the field. Leave this blank to keep the current key.', 'searchcounsel-seo-audit' ) . '</p>';

			if ( $stored_key ) {
				echo '<label><input type="checkbox" name="scsa_settings[pagespeed_clear_api_key]" value="1" /> ' . esc_html__( 'Remove the saved API key', 'searchcounsel-seo-audit' ) . '</label>';
			}
		}
	}

	/**
	 * Renders the PageSpeed transient duration setting.
	 *
	 * @return void
	 */
	public function render_pagespeed_cache_field() {
		$settings = self::get();
		printf(
			'<input class="small-text" type="number" min="5" max="1440" name="scsa_settings[pagespeed_cache_duration]" value="%d" /> %s',
			absint( $settings['pagespeed_cache_duration'] ),
			esc_html__( 'minutes', 'searchcounsel-seo-audit' )
		);
	}

	/**
	 * Provides defaults without persisting anything at activation time.
	 *
	 * @return array<string,mixed>
	 */
	private static function defaults() {
		return array(
			'consultation_email'      => get_option( 'admin_email' ),
			'consultation_url'        => home_url( '/contact/' ),
			'audit_rate_limit'        => 6,
			'pagespeed_enabled'       => 0,
			'pagespeed_api_key'       => '',
			'pagespeed_cache_duration' => 30,
		);
	}

	/**
	 * Restricts API keys to Google's documented URL-safe key characters.
	 *
	 * @param mixed $value Candidate API key.
	 * @return string
	 */
	private static function sanitize_api_key( $value ) {
		$value = is_string( $value ) ? trim( $value ) : '';

		return substr( (string) preg_replace( '/[^A-Za-z0-9_-]/', '', $value ), 0, 255 );
	}
}
