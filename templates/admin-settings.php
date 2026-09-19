<?php
/**
 * Plugin settings admin template.
 *
 * @package SearchCounsel_SEO_Audit
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap">
	<h1><?php esc_html_e( 'SearchCounsel SEO Audit Settings', 'searchcounsel-seo-audit' ); ?></h1>
	<form action="options.php" method="post">
		<?php
		settings_fields( 'scsa_settings_group' );
		do_settings_sections( 'scsa-settings' );
		submit_button();
		?>
	</form>
</div>
