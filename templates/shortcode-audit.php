<?php
/**
 * Public audit tool shortcode template.
 *
 * @package SearchCounsel_SEO_Audit
 */

defined( 'ABSPATH' ) || exit;
?>
<section class="scsa-audit-tool" aria-label="<?php esc_attr_e( 'SearchCounselco SEO audit tool', 'searchcounsel-seo-audit' ); ?>">
	<div class="scsa-landing">
		<div class="scsa-brand-lockup">
			<span class="scsa-brand-mark" aria-hidden="true">S</span>
			<span><?php esc_html_e( 'SearchCounselco', 'searchcounsel-seo-audit' ); ?></span>
		</div>
		<p class="scsa-kicker"><?php esc_html_e( 'FREE WEBSITE ANALYSIS', 'searchcounsel-seo-audit' ); ?></p>
		<h2><?php esc_html_e( 'See what’s holding your website back in search.', 'searchcounsel-seo-audit' ); ?></h2>
		<p class="scsa-intro"><?php esc_html_e( 'Get an instant, actionable SEO review of your page—from search metadata to technical foundations.', 'searchcounsel-seo-audit' ); ?></p>

		<form class="scsa-audit-form" novalidate>
			<label class="screen-reader-text" for="scsa-audit-url"><?php esc_html_e( 'Website URL', 'searchcounsel-seo-audit' ); ?></label>
			<div class="scsa-url-row">
				<span class="scsa-url-icon" aria-hidden="true">⌁</span>
				<input id="scsa-audit-url" name="url" type="url" inputmode="url" autocomplete="url" placeholder="https://yourwebsite.com" required />
				<button class="scsa-analyze-button" type="submit"><span><?php esc_html_e( 'Analyze Website', 'searchcounsel-seo-audit' ); ?></span><span aria-hidden="true">→</span></button>
			</div>
			<p class="scsa-form-note"><?php esc_html_e( 'No signup required. We only audit publicly accessible web pages.', 'searchcounsel-seo-audit' ); ?></p>
		</form>

		<div class="scsa-trust-row" aria-label="<?php esc_attr_e( 'Audit coverage', 'searchcounsel-seo-audit' ); ?>">
			<span>✓ <?php esc_html_e( 'Technical SEO', 'searchcounsel-seo-audit' ); ?></span>
			<span>✓ <?php esc_html_e( 'On-page signals', 'searchcounsel-seo-audit' ); ?></span>
			<span>✓ <?php esc_html_e( 'Action plan', 'searchcounsel-seo-audit' ); ?></span>
		</div>
	</div>

	<div class="scsa-progress" hidden aria-live="polite">
		<p class="scsa-progress-title"><?php esc_html_e( 'Preparing your SEO report', 'searchcounsel-seo-audit' ); ?></p>
		<div class="scsa-progress-track"><span></span></div>
		<ol class="scsa-progress-steps">
			<li data-step="1"><?php esc_html_e( 'Checking website access', 'searchcounsel-seo-audit' ); ?></li>
			<li data-step="2"><?php esc_html_e( 'Reviewing on-page SEO', 'searchcounsel-seo-audit' ); ?></li>
			<li data-step="3"><?php esc_html_e( 'Prioritizing recommendations', 'searchcounsel-seo-audit' ); ?></li>
		</ol>
	</div>

	<div class="scsa-message" role="status" aria-live="polite" hidden></div>

	<div class="scsa-report" hidden>
		<header class="scsa-report-header">
			<div>
				<p class="scsa-kicker"><?php esc_html_e( 'YOUR SEO SNAPSHOT', 'searchcounsel-seo-audit' ); ?></p>
				<h2><?php esc_html_e( 'Your website audit report', 'searchcounsel-seo-audit' ); ?></h2>
				<p class="scsa-audited-url"></p>
			</div>
			<div class="scsa-score-card">
				<div class="scsa-score-gauge" style="--score: 0" role="img" aria-label="<?php esc_attr_e( 'SEO score', 'searchcounsel-seo-audit' ); ?>">
					<span class="scsa-score-value">0</span><small>/100</small>
				</div>
				<p class="scsa-score-grade"></p>
			</div>
		</header>

		<div class="scsa-summary-cards" aria-label="<?php esc_attr_e( 'Audit summary', 'searchcounsel-seo-audit' ); ?>">
			<div class="scsa-summary-card scsa-summary-pass"><span class="scsa-summary-count">0</span><span><?php esc_html_e( 'Passed', 'searchcounsel-seo-audit' ); ?></span></div>
			<div class="scsa-summary-card scsa-summary-warning"><span class="scsa-summary-count">0</span><span><?php esc_html_e( 'Warnings', 'searchcounsel-seo-audit' ); ?></span></div>
			<div class="scsa-summary-card scsa-summary-critical"><span class="scsa-summary-count">0</span><span><?php esc_html_e( 'Critical issues', 'searchcounsel-seo-audit' ); ?></span></div>
		</div>

		<section class="scsa-recommendations-section">
			<div class="scsa-section-heading"><div><p class="scsa-kicker"><?php esc_html_e( 'PRIORITY ACTIONS', 'searchcounsel-seo-audit' ); ?></p><h3><?php esc_html_e( 'What to improve first', 'searchcounsel-seo-audit' ); ?></h3></div><span class="scsa-impact-note"><?php esc_html_e( 'Ranked by impact', 'searchcounsel-seo-audit' ); ?></span></div>
			<div class="scsa-recommendations"></div>
		</section>

		<section class="scsa-checks-section">
			<div class="scsa-section-heading"><div><p class="scsa-kicker"><?php esc_html_e( 'FULL REVIEW', 'searchcounsel-seo-audit' ); ?></p><h3><?php esc_html_e( 'Detailed SEO checks', 'searchcounsel-seo-audit' ); ?></h3></div></div>
			<div class="scsa-checks"></div>
		</section>

		<section class="scsa-cta-panel">
			<div><p class="scsa-kicker"><?php esc_html_e( 'READY FOR THE NEXT STEP?', 'searchcounsel-seo-audit' ); ?></p><h3><?php esc_html_e( 'Turn this audit into measurable search growth.', 'searchcounsel-seo-audit' ); ?></h3><p><?php esc_html_e( 'Get a strategist’s view of your highest-value opportunities and a practical path forward.', 'searchcounsel-seo-audit' ); ?></p></div>
			<div class="scsa-cta-actions"><a class="scsa-button-light scsa-contact-link" href="<?php echo esc_url( $settings['consultation_url'] ); ?>"><?php esc_html_e( 'Contact SearchCounselco', 'searchcounsel-seo-audit' ); ?></a><a class="scsa-button-outline scsa-consultation-jump" href="#scsa-consultation"><?php esc_html_e( 'Get Full Report', 'searchcounsel-seo-audit' ); ?></a></div>
		</section>

		<section class="scsa-consultation" id="scsa-consultation">
			<div class="scsa-consultation-copy"><p class="scsa-kicker"><?php esc_html_e( 'FREE SEO CONSULTATION', 'searchcounsel-seo-audit' ); ?></p><h3><?php esc_html_e( 'Want expert help interpreting your report?', 'searchcounsel-seo-audit' ); ?></h3><p><?php esc_html_e( 'Tell us where you want to grow. A SearchCounselco specialist will follow up with tailored next steps.', 'searchcounsel-seo-audit' ); ?></p></div>
			<form class="scsa-consultation-form" novalidate>
				<label><span><?php esc_html_e( 'Name', 'searchcounsel-seo-audit' ); ?></span><input type="text" name="name" autocomplete="name" required /></label>
				<label><span><?php esc_html_e( 'Work email', 'searchcounsel-seo-audit' ); ?></span><input type="email" name="email" autocomplete="email" required /></label>
				<label><span><?php esc_html_e( 'What would you like to improve?', 'searchcounsel-seo-audit' ); ?></span><textarea name="message" rows="3" placeholder="Rankings, leads, technical SEO…"></textarea></label>
				<input type="hidden" name="website" value="" />
				<button class="scsa-submit-consultation" type="submit"><?php esc_html_e( 'Request free consultation', 'searchcounsel-seo-audit' ); ?></button>
				<p class="scsa-consultation-message" role="status" aria-live="polite"></p>
			</form>
		</section>
	</div>
</section>
