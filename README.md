# SearchCounsel SEO Audit

SearchCounsel SEO Audit is a standalone WordPress plugin that turns a page into a public, SEOptimer-style SEO audit and lead-generation experience. Visitors enter a public website URL, receive an instant score-based report, and can request a free consultation from SearchCounselco.

It is fully local to WordPress and uses no paid APIs.

## Installation

1. From this repository, create a ZIP whose top level contains `searchcounsel-seo-audit.php`, `includes/`, `assets/`, and `templates/`.
2. In WordPress, go to **Plugins → Add New → Upload Plugin** and upload that ZIP.
3. Activate **SearchCounsel SEO Audit**.
4. Open **SEO Audit → Settings** and set the consultation-recipient email, contact CTA URL, and public audit limit.
5. Create or edit the public landing page where the tool belongs and add:

   ```text
   [searchcounsel_audit]
   ```

The plugin does not change WordPress core or the active theme. The shortcode loads the interface only on pages where it is used.

## What visitors receive

- A premium, mobile-first landing screen with a focused URL field and responsive layout from 320px through large desktop widths.
- A staged, indeterminate progress experience that describes the work being performed without displaying a fabricated completion percentage.
- A dominant 0–100 SEO health score, real issue distribution, and category pass rates calculated from completed audit checks.
- A professional report dashboard with **Passed**, **Warning**, and **Critical issue** states, category accordions, and filters for faster scanning.
- An impact-ranked SEO action plan, a Google-style result preview when title or description data is available, and clear empty/error states.
- A focused SearchCounselco growth CTA and an accessible free-consultation form with inline validation.
- Checks for title, description, heading structure (H1–H3), canonical URL, HTTPS, robots meta, `robots.txt`, sitemap, Open Graph, Twitter Cards, structured data, image alt attributes, internal/external links, a limited broken-link sample, word count, page-size signals, viewport, language, HTTP status, and Core Web Vitals guidance.

Core Web Vitals (LCP, INP, CLS) require browser or real-user measurement. This plugin offers server-response and HTML-payload guidance rather than claiming to provide field metrics.

The public interface is rendered from the audit response already produced by the plugin. It does not invent category scores, issue counts, recommendations, or search-preview content.

### Frontend markup compatibility

Version 1.2.0 introduces a new public report structure and replaces several presentation-only selectors from earlier versions. Third-party theme CSS or JavaScript that targets legacy selectors such as `.scsa-progress`, `.scsa-summary-card`, or `[data-step]` may need to be updated. The supported shortcode remains `[searchcounsel_audit]`, and the audit and consultation request contracts are unchanged.

## Architecture

```text
searchcounsel-seo-audit.php          Plugin bootstrap and activation hook
includes/
  class-scsa-public.php              Shortcode and public asset loading
  class-scsa-audit-controller.php    Public AJAX API and consultation endpoint
  class-scsa-audit-service.php       Validation → fetch → analysis → report workflow
  class-scsa-url-validator.php       Public URL / DNS / private-network protection
  class-scsa-http-client.php         WordPress HTTP API wrapper with safe redirects
  class-scsa-page-analyzer.php       DOM parsing and check orchestration
  class-scsa-score-engine.php        Transparent weighted 0–100 scoring
  class-scsa-report-builder.php      Priority-ranked public recommendations
  class-scsa-history-repository.php  Custom-table audit history
  class-scsa-rate-limiter.php        Short-lived hashed-IP throttling
  class-scsa-settings.php            Settings API schema and sanitization
  checks/                            Independent, extensible SEO checks
assets/
  css/public.css                     Responsive SaaS-style visual system
  js/public.js                       Safe DOM rendering and AJAX behavior
templates/
  shortcode-audit.php                Public tool and report markup
  admin-*.php                        Protected settings and audit-history screens
```

Each check implements `SCSA_Audit_Check`. Additional checks can be injected with the `scsa_audit_checks` filter. The `SCSA_Score_Engine` assigns transparent weights to higher-impact failures; the report builder converts non-passing results into high- or medium-impact actions.

## Security and data handling

- All AJAX actions use WordPress nonces; settings use the WordPress Settings API nonce flow.
- All user inputs are unslashed, validated, sanitized, and escaped on output. Browser rendering uses `textContent`, never server-provided HTML.
- Public audits permit only `http`/`https` destinations on ports 80/443. URLs with credentials, local hostnames, private/reserved IP ranges, and unsafe redirect destinations are rejected.
- Every request and redirect goes through the URL validator and WordPress's safe HTTP API. Downloads, timeouts, redirects, sitemap checks, and link probes are bounded.
- Link validation samples at most 10 unique public links by default (filterable up to 20); it is not a full-site crawler.
- Public audit and consultation endpoints are rate-limited with short-lived transient keys based on a site-salted hash of the direct client IP. No raw visitor IP is saved.
- Audit history stores the audited URL, score, compact summary, report data, and timestamp. It never stores downloaded page HTML or visitor IPs. Uninstall intentionally preserves business records and settings so site owners control retention.

## Development and verification

The plugin requires WordPress 6.0+ and PHP 7.4+. Before production use, test on a staging WordPress installation:

1. Activate the plugin and confirm the **SEO Audit** admin menu appears.
2. Save Settings and verify the consultation recipient and CTA URL.
3. Add `[searchcounsel_audit]` to a page and test a public HTTPS URL.
4. Confirm reports save under **SEO Audit → Audit History**.
5. Test invalid, localhost, private-IP, credentialed, and non-standard-port URLs. Each should be rejected.
6. Configure outbound WordPress mail (or an SMTP plugin) and submit a consultation form.

The plugin does not deploy itself or send website changes to SearchCounselco. Installation and activation remain a deliberate WordPress-admin action.
