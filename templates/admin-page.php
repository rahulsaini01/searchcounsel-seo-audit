<?php
/**
 * Plugin overview admin template.
 *
 * @package SearchCounsel_SEO_Audit
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap">
	<h1><?php esc_html_e( 'SearchCounsel SEO Audit', 'searchcounsel-seo-audit' ); ?></h1>
	<p class="description"><?php esc_html_e( 'A public SEO audit and lead-generation experience for SearchCounselco.', 'searchcounsel-seo-audit' ); ?></p>

	<div class="card" style="max-width: 760px; margin-top: 20px; padding: 20px;">
		<h2><?php esc_html_e( 'Add the audit tool to a page', 'searchcounsel-seo-audit' ); ?></h2>
		<p><?php esc_html_e( 'Create or edit a WordPress page, then add this shortcode:', 'searchcounsel-seo-audit' ); ?></p>
		<p><code>[searchcounsel_audit]</code></p>
		<p><?php esc_html_e( 'The page will render a responsive landing experience, score-based report, calls to action, and a free-consultation form. No theme files need to be changed.', 'searchcounsel-seo-audit' ); ?></p>
		<p><a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=scsa-settings' ) ); ?>"><?php esc_html_e( 'Configure lead settings', 'searchcounsel-seo-audit' ); ?></a></p>
	</div>

	<div class="card" style="max-width: 760px; margin-top: 20px; padding: 20px;">
		<h2><?php esc_html_e( 'Security model', 'searchcounsel-seo-audit' ); ?></h2>
		<p><?php esc_html_e( 'The public form accepts only public HTTP(S) URLs on ports 80 or 443. The plugin validates DNS/IP destinations before every request and redirect, caps downloads, samples link checks, and rate-limits visitors.', 'searchcounsel-seo-audit' ); ?></p>
	</div>
</div>
