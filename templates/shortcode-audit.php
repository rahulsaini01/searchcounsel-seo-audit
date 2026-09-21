<?php
/**
 * Public audit tool shortcode template.
 *
 * @package SearchCounsel_SEO_Audit
 */

defined( 'ABSPATH' ) || exit;

$url_input_id    = $instance_id . '-url';
$consultation_id = $instance_id . '-consultation';
?>
<section id="<?php echo esc_attr( $instance_id ); ?>" class="scsa-audit-tool" data-scsa-audit-tool aria-label="<?php esc_attr_e( 'SearchCounsel SEO Audit', 'searchcounsel-seo-audit' ); ?>">
	<header class="scsa-hero">
		<div class="scsa-hero-glow" aria-hidden="true"></div>
		<div class="scsa-brand-lockup">
			<span class="scsa-brand-mark" aria-hidden="true">S</span>
			<span><?php esc_html_e( 'SearchCounsel', 'searchcounsel-seo-audit' ); ?></span>
		</div>
		<div class="scsa-hero-content">
			<p class="scsa-eyebrow"><?php esc_html_e( 'SearchCounsel SEO Audit', 'searchcounsel-seo-audit' ); ?></p>
			<h2><?php esc_html_e( 'Find What’s Holding Your Website Back', 'searchcounsel-seo-audit' ); ?></h2>
			<p class="scsa-hero-copy"><?php esc_html_e( 'Run a fast SEO health check and uncover technical, on-page, performance, and search visibility issues.', 'searchcounsel-seo-audit' ); ?></p>
		</div>

		<form class="scsa-audit-form" novalidate>
			<label class="scsa-input-label" for="<?php echo esc_attr( $url_input_id ); ?>"><?php esc_html_e( 'Enter your website URL', 'searchcounsel-seo-audit' ); ?></label>
			<div class="scsa-url-control">
				<span class="scsa-url-icon" aria-hidden="true">
					<svg viewBox="0 0 24 24" width="20" height="20" focusable="false"><path d="M10.6 13.4a1.5 1.5 0 0 0 2.1 0l3.5-3.5a3 3 0 0 0-4.2-4.2l-2 2m3.4 2.9a1.5 1.5 0 0 0-2.1 0l-3.5 3.5A3 3 0 0 0 12 18.3l2-2" fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="1.8"/></svg>
				</span>
				<input id="<?php echo esc_attr( $url_input_id ); ?>" name="url" type="url" inputmode="url" autocomplete="url" placeholder="<?php esc_attr_e( 'https://yourwebsite.com', 'searchcounsel-seo-audit' ); ?>" aria-describedby="<?php echo esc_attr( $url_input_id ); ?>-help" required />
				<button class="scsa-analyze-button" type="submit">
					<span><?php esc_html_e( 'Analyze Website', 'searchcounsel-seo-audit' ); ?></span>
					<svg viewBox="0 0 20 20" width="18" height="18" aria-hidden="true"><path d="m7 4 6 6-6 6" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/></svg>
				</button>
			</div>
			<p id="<?php echo esc_attr( $url_input_id ); ?>-help" class="scsa-field-help"><?php esc_html_e( 'Use a complete public URL, including https://', 'searchcounsel-seo-audit' ); ?></p>
		</form>

		<ul class="scsa-value-list" aria-label="<?php esc_attr_e( 'Audit benefits', 'searchcounsel-seo-audit' ); ?>">
			<li><span aria-hidden="true">✓</span><?php esc_html_e( 'Fast analysis', 'searchcounsel-seo-audit' ); ?></li>
			<li><span aria-hidden="true">✓</span><?php esc_html_e( 'Actionable recommendations', 'searchcounsel-seo-audit' ); ?></li>
			<li><span aria-hidden="true">✓</span><?php esc_html_e( 'No signup required', 'searchcounsel-seo-audit' ); ?></li>
		</ul>
	</header>

	<section class="scsa-loading" hidden aria-live="polite" aria-busy="true">
		<div class="scsa-loading-visual" aria-hidden="true"><span></span><span></span><span></span></div>
		<div class="scsa-loading-copy">
			<p class="scsa-eyebrow"><?php esc_html_e( 'Audit in progress', 'searchcounsel-seo-audit' ); ?></p>
			<h3><?php esc_html_e( 'Reviewing your website', 'searchcounsel-seo-audit' ); ?></h3>
			<p class="scsa-current-stage"><?php esc_html_e( 'Fetching website…', 'searchcounsel-seo-audit' ); ?></p>
		</div>
		<div class="scsa-loading-track" role="progressbar" aria-label="<?php esc_attr_e( 'SEO audit in progress', 'searchcounsel-seo-audit' ); ?>"><span></span></div>
		<ol class="scsa-loading-stages">
			<li data-stage="<?php esc_attr_e( 'Fetching website…', 'searchcounsel-seo-audit' ); ?>"><?php esc_html_e( 'Fetching website', 'searchcounsel-seo-audit' ); ?></li>
			<li data-stage="<?php esc_attr_e( 'Checking technical SEO…', 'searchcounsel-seo-audit' ); ?>"><?php esc_html_e( 'Technical SEO', 'searchcounsel-seo-audit' ); ?></li>
			<li data-stage="<?php esc_attr_e( 'Analyzing content structure…', 'searchcounsel-seo-audit' ); ?>"><?php esc_html_e( 'Content structure', 'searchcounsel-seo-audit' ); ?></li>
			<li data-stage="<?php esc_attr_e( 'Checking links and metadata…', 'searchcounsel-seo-audit' ); ?>"><?php esc_html_e( 'Links and metadata', 'searchcounsel-seo-audit' ); ?></li>
			<li data-stage="<?php esc_attr_e( 'Checking page performance…', 'searchcounsel-seo-audit' ); ?>"><?php esc_html_e( 'Page performance', 'searchcounsel-seo-audit' ); ?></li>
			<li data-stage="<?php esc_attr_e( 'Preparing your report…', 'searchcounsel-seo-audit' ); ?>"><?php esc_html_e( 'Preparing report', 'searchcounsel-seo-audit' ); ?></li>
		</ol>
		<p class="scsa-loading-note"><?php esc_html_e( 'This usually takes less than a minute. Complex pages may take a little longer.', 'searchcounsel-seo-audit' ); ?></p>
	</section>

	<div class="scsa-message" role="alert" aria-live="assertive" hidden></div>

	<div class="scsa-report" hidden>
		<section class="scsa-report-hero" aria-labelledby="<?php echo esc_attr( $instance_id ); ?>-report-title">
			<div class="scsa-report-main">
				<div class="scsa-report-identity">
					<span class="scsa-live-dot" aria-hidden="true"></span>
					<div><p class="scsa-report-hostname"></p><p class="scsa-report-time"></p></div>
				</div>
				<p class="scsa-eyebrow"><?php esc_html_e( 'SEO health', 'searchcounsel-seo-audit' ); ?></p>
				<h2 id="<?php echo esc_attr( $instance_id ); ?>-report-title" class="scsa-health-title"><?php esc_html_e( 'Your website audit report', 'searchcounsel-seo-audit' ); ?></h2>
				<p class="scsa-health-message"></p>
				<button class="scsa-audit-another" type="button">
					<svg viewBox="0 0 20 20" width="16" height="16" aria-hidden="true"><path d="M16 10a6 6 0 1 1-1.8-4.3M16 3v4h-4" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"/></svg>
					<?php esc_html_e( 'Audit another URL', 'searchcounsel-seo-audit' ); ?>
				</button>
			</div>
			<div class="scsa-score-panel">
				<div class="scsa-score-gauge" role="img" aria-label="<?php esc_attr_e( 'SEO score', 'searchcounsel-seo-audit' ); ?>">
					<div class="scsa-score-inner"><span class="scsa-score-value">0</span><small>/100</small></div>
				</div>
				<p class="scsa-score-grade" data-label="<?php esc_attr_e( 'Health rating', 'searchcounsel-seo-audit' ); ?>"></p>
			</div>
			<div class="scsa-report-stats" aria-label="<?php esc_attr_e( 'Audit result counts', 'searchcounsel-seo-audit' ); ?>">
				<div class="scsa-stat scsa-stat-pass"><span class="scsa-stat-icon" aria-hidden="true">✓</span><strong>0</strong><small><?php esc_html_e( 'Passed', 'searchcounsel-seo-audit' ); ?></small></div>
				<div class="scsa-stat scsa-stat-warning"><span class="scsa-stat-icon" aria-hidden="true">!</span><strong>0</strong><small><?php esc_html_e( 'Warnings', 'searchcounsel-seo-audit' ); ?></small></div>
				<div class="scsa-stat scsa-stat-critical"><span class="scsa-stat-icon" aria-hidden="true">×</span><strong>0</strong><small><?php esc_html_e( 'Critical', 'searchcounsel-seo-audit' ); ?></small></div>
			</div>
			<div class="scsa-distribution" aria-label="<?php esc_attr_e( 'Issue distribution', 'searchcounsel-seo-audit' ); ?>"><span class="scsa-distribution-pass"></span><span class="scsa-distribution-warning"></span><span class="scsa-distribution-critical"></span></div>
		</section>

		<section class="scsa-report-section scsa-category-section" aria-labelledby="<?php echo esc_attr( $instance_id ); ?>-category-title">
			<div class="scsa-section-heading">
				<div><p class="scsa-eyebrow"><?php esc_html_e( 'Score breakdown', 'searchcounsel-seo-audit' ); ?></p><h3 id="<?php echo esc_attr( $instance_id ); ?>-category-title"><?php esc_html_e( 'SEO health by category', 'searchcounsel-seo-audit' ); ?></h3></div>
				<p><?php esc_html_e( 'Pass rates use the checks completed in this audit.', 'searchcounsel-seo-audit' ); ?></p>
			</div>
			<div class="scsa-category-grid"></div>
		</section>

		<section class="scsa-report-section scsa-action-section" aria-labelledby="<?php echo esc_attr( $instance_id ); ?>-actions-title">
			<div class="scsa-section-heading">
				<div><p class="scsa-eyebrow"><?php esc_html_e( 'SEO action plan', 'searchcounsel-seo-audit' ); ?></p><h3 id="<?php echo esc_attr( $instance_id ); ?>-actions-title"><?php esc_html_e( 'What to Fix First', 'searchcounsel-seo-audit' ); ?></h3></div>
				<span class="scsa-section-chip"><?php esc_html_e( 'Prioritized by impact', 'searchcounsel-seo-audit' ); ?></span>
			</div>
			<div class="scsa-recommendations"></div>
		</section>

		<section class="scsa-report-section scsa-serp-section" hidden aria-labelledby="<?php echo esc_attr( $instance_id ); ?>-serp-title">
			<div class="scsa-section-heading">
				<div><p class="scsa-eyebrow"><?php esc_html_e( 'Search appearance', 'searchcounsel-seo-audit' ); ?></p><h3 id="<?php echo esc_attr( $instance_id ); ?>-serp-title"><?php esc_html_e( 'Google-style preview', 'searchcounsel-seo-audit' ); ?></h3></div>
			</div>
			<div class="scsa-serp-preview"><p class="scsa-serp-site"></p><p class="scsa-serp-url"></p><p class="scsa-serp-title"></p><p class="scsa-serp-description"></p></div>
			<p class="scsa-serp-note"><?php esc_html_e( 'This is a preview, not a guaranteed representation of how Google will display your page.', 'searchcounsel-seo-audit' ); ?></p>
		</section>

		<section class="scsa-report-section scsa-audit-details" aria-labelledby="<?php echo esc_attr( $instance_id ); ?>-details-title">
			<div class="scsa-section-heading scsa-details-heading">
				<div><p class="scsa-eyebrow"><?php esc_html_e( 'Detailed audit', 'searchcounsel-seo-audit' ); ?></p><h3 id="<?php echo esc_attr( $instance_id ); ?>-details-title"><?php esc_html_e( 'Explore every check', 'searchcounsel-seo-audit' ); ?></h3></div>
				<p><?php esc_html_e( 'Open a category, then expand a check for its recommendation and technical details.', 'searchcounsel-seo-audit' ); ?></p>
			</div>
			<div class="scsa-filter-bar" role="group" aria-label="<?php esc_attr_e( 'Filter audit checks', 'searchcounsel-seo-audit' ); ?>">
				<button type="button" class="is-active" data-scsa-filter="all" aria-pressed="true"><?php esc_html_e( 'All', 'searchcounsel-seo-audit' ); ?></button>
				<button type="button" data-scsa-filter="issues" aria-pressed="false"><?php esc_html_e( 'Issues only', 'searchcounsel-seo-audit' ); ?></button>
				<button type="button" data-scsa-filter="critical" aria-pressed="false"><?php esc_html_e( 'Critical', 'searchcounsel-seo-audit' ); ?></button>
				<button type="button" data-scsa-filter="warning" aria-pressed="false"><?php esc_html_e( 'Warnings', 'searchcounsel-seo-audit' ); ?></button>
				<button type="button" data-scsa-filter="pass" aria-pressed="false"><?php esc_html_e( 'Passed', 'searchcounsel-seo-audit' ); ?></button>
			</div>
			<div class="scsa-checks"></div>
		</section>

		<section class="scsa-growth-cta">
			<div class="scsa-growth-copy">
				<p class="scsa-eyebrow"><?php esc_html_e( 'Work with SearchCounsel', 'searchcounsel-seo-audit' ); ?></p>
				<h3><?php esc_html_e( 'Turn Your Audit Into SEO Growth', 'searchcounsel-seo-audit' ); ?></h3>
				<p><?php esc_html_e( 'Get a personalized SEO roadmap based on your website’s biggest opportunities.', 'searchcounsel-seo-audit' ); ?></p>
				<ul><li><?php esc_html_e( 'Free SEO consultation', 'searchcounsel-seo-audit' ); ?></li><li><?php esc_html_e( 'Technical SEO review', 'searchcounsel-seo-audit' ); ?></li><li><?php esc_html_e( 'Prioritized action plan', 'searchcounsel-seo-audit' ); ?></li></ul>
			</div>
			<div class="scsa-growth-actions">
				<a class="scsa-primary-link" href="#<?php echo esc_attr( $consultation_id ); ?>"><?php esc_html_e( 'Book a Free SEO Consultation', 'searchcounsel-seo-audit' ); ?></a>
				<button class="scsa-run-again" type="button"><?php esc_html_e( 'Run Another Audit', 'searchcounsel-seo-audit' ); ?></button>
			</div>
		</section>

		<section id="<?php echo esc_attr( $consultation_id ); ?>" class="scsa-consultation" aria-labelledby="<?php echo esc_attr( $instance_id ); ?>-consultation-title">
			<div class="scsa-consultation-copy">
				<p class="scsa-eyebrow"><?php esc_html_e( 'Free SEO consultation', 'searchcounsel-seo-audit' ); ?></p>
				<h3 id="<?php echo esc_attr( $instance_id ); ?>-consultation-title"><?php esc_html_e( 'Get a clear plan for what comes next.', 'searchcounsel-seo-audit' ); ?></h3>
				<p><?php esc_html_e( 'Share your goals and a SearchCounsel specialist will follow up with practical next steps for your website.', 'searchcounsel-seo-audit' ); ?></p>
				<a class="scsa-text-link" href="<?php echo esc_url( $settings['consultation_url'] ); ?>"><?php esc_html_e( 'Prefer to contact us directly?', 'searchcounsel-seo-audit' ); ?> <span aria-hidden="true">→</span></a>
			</div>
			<form class="scsa-consultation-form" novalidate>
				<div class="scsa-form-field"><label for="<?php echo esc_attr( $instance_id ); ?>-name"><?php esc_html_e( 'Name', 'searchcounsel-seo-audit' ); ?></label><input id="<?php echo esc_attr( $instance_id ); ?>-name" type="text" name="name" autocomplete="name" placeholder="<?php esc_attr_e( 'Your name', 'searchcounsel-seo-audit' ); ?>" aria-describedby="<?php echo esc_attr( $instance_id ); ?>-name-error" required /><span id="<?php echo esc_attr( $instance_id ); ?>-name-error" class="scsa-field-error"></span></div>
				<div class="scsa-form-field"><label for="<?php echo esc_attr( $instance_id ); ?>-email"><?php esc_html_e( 'Work email', 'searchcounsel-seo-audit' ); ?></label><input id="<?php echo esc_attr( $instance_id ); ?>-email" type="email" name="email" autocomplete="email" placeholder="<?php esc_attr_e( 'you@company.com', 'searchcounsel-seo-audit' ); ?>" aria-describedby="<?php echo esc_attr( $instance_id ); ?>-email-error" required /><span id="<?php echo esc_attr( $instance_id ); ?>-email-error" class="scsa-field-error"></span></div>
				<div class="scsa-form-field scsa-form-field-wide"><label for="<?php echo esc_attr( $instance_id ); ?>-website"><?php esc_html_e( 'Website', 'searchcounsel-seo-audit' ); ?></label><input id="<?php echo esc_attr( $instance_id ); ?>-website" type="url" name="website" inputmode="url" autocomplete="url" placeholder="<?php esc_attr_e( 'https://yourwebsite.com', 'searchcounsel-seo-audit' ); ?>" aria-describedby="<?php echo esc_attr( $instance_id ); ?>-website-error" required /><span id="<?php echo esc_attr( $instance_id ); ?>-website-error" class="scsa-field-error"></span></div>
				<div class="scsa-form-field scsa-form-field-wide"><label for="<?php echo esc_attr( $instance_id ); ?>-message"><?php esc_html_e( 'What would you like help with?', 'searchcounsel-seo-audit' ); ?></label><textarea id="<?php echo esc_attr( $instance_id ); ?>-message" name="message" rows="4" placeholder="<?php esc_attr_e( 'Tell us about your SEO goals or challenges', 'searchcounsel-seo-audit' ); ?>"></textarea></div>
				<div class="scsa-form-field-wide"><button class="scsa-submit-consultation" type="submit"><?php esc_html_e( 'Request My Free Consultation', 'searchcounsel-seo-audit' ); ?></button><p class="scsa-consultation-message" role="status" aria-live="polite"></p></div>
			</form>
		</section>

		<footer class="scsa-trust-footer"><span><?php esc_html_e( 'Built by SearchCounsel', 'searchcounsel-seo-audit' ); ?></span><span><?php esc_html_e( 'Your audit remains available without registration.', 'searchcounsel-seo-audit' ); ?></span></footer>
	</div>
</section>
