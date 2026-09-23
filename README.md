# SearchCounsel SEO Audit

SearchCounsel SEO Audit is a standalone WordPress plugin that turns a page into a public, SEOptimer-style SEO audit and lead-generation experience. Visitors enter a public website URL, receive an instant score-based report, and can request a free consultation from SearchCounselco.

The core SEO audit runs locally in WordPress. An optional server-side Google PageSpeed Insights integration adds desktop and mobile Lighthouse/CrUX data when an administrator supplies an API key.

## Installation

1. From this repository, create a ZIP whose top level contains `searchcounsel-seo-audit.php`, `includes/`, `assets/`, and `templates/`.
2. In WordPress, go to **Plugins → Add New → Upload Plugin** and upload that ZIP.
3. Activate **SearchCounsel SEO Audit**.
4. Open **SEO Audit → Settings** and set the consultation-recipient email, contact CTA URL, and public audit limit. To include PageSpeed data, enable PageSpeed analysis and enter a Google PageSpeed Insights API key.
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
- A Page Speed Insights module inside the existing **Technical SEO** accordion, with separately reported desktop/mobile Performance scores, lab metrics, available CrUX field data, Google-returned opportunities, and collapsed diagnostics.

The existing Core Web Vitals guidance check remains a lightweight server-response/HTML signal. When PageSpeed is configured, its separate module labels controlled Lighthouse lab measurements and available CrUX real-user field data independently. PageSpeed does not alter the SearchCounsel SEO score or SEO check counts.

The public interface is rendered from the audit response already produced by the plugin. It does not invent category scores, issue counts, recommendations, or search-preview content.

### Frontend markup compatibility

Version 1.2.0 introduced a new public report structure and replaced several presentation-only selectors from earlier versions. Third-party theme CSS or JavaScript that targets legacy selectors such as `.scsa-progress`, `.scsa-summary-card`, or `[data-step]` may need to be updated. Version 1.3.0 added namespaced `.scsa-pagespeed-*` components inside the existing Technical SEO accordion. Version 1.4.0 moves Google analysis into bounded asynchronous requests after the core report renders. The supported shortcode remains `[searchcounsel_audit]`.

## Architecture

```text
searchcounsel-seo-audit.php          Plugin bootstrap and activation hook
includes/
  class-scsa-public.php              Shortcode and public asset loading
  class-scsa-audit-controller.php    Audit, asynchronous PageSpeed, and consultation AJAX endpoints
  class-scsa-audit-service.php       Validation → fetch → analysis → report workflow
  class-scsa-url-validator.php       Public URL / DNS / private-network protection
  class-scsa-http-client.php         WordPress HTTP API wrapper with safe redirects
  class-scsa-database-lock.php       Bounded MySQL advisory locks for concurrency safety
  class-scsa-pagespeed-insights.php  Server-side PageSpeed API, normalization, and cache
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

## PageSpeed configuration

PageSpeed is disabled by default. In **SEO Audit → Settings → PageSpeed Insights**:

1. Enter a Google PageSpeed Insights API key.
2. Enable PageSpeed analysis.
3. Keep the default 30-minute cache duration or choose 5–1440 minutes.

The saved key is a write-only field: WordPress never renders the stored value back into the admin page. For stronger secret management, define `SCSA_PAGESPEED_API_KEY` in `wp-config.php`; that server constant takes precedence over the saved option and disables the admin key field. If an older database key exists, administrators can remove it securely from the same Settings API form while the constant remains active. Server requests transmit the key in Google's `X-Goog-Api-Key` header rather than placing it in the request URL.

The normal SEO audit completes and renders before PageSpeed begins. The audit response contains a short-lived WordPress nonce bound to the validated final URL only when PageSpeed is configured; it never contains the API key. The frontend then starts separate Desktop and Mobile requests in parallel, allowing each strategy to finish or fail independently without delaying the SEO report. Switching tabs only changes the rendered strategy and never starts another request.

Successful desktop and mobile responses are cached independently by a SHA-256-derived key based on the validated final URL and strategy. Each asynchronous request checks its strategy cache before calling Google, and failed or malformed responses are not cached. A narrow database advisory lock prevents simultaneous requests for the same URL and strategy from duplicating an uncached Google call; the cache is checked again after the lock is acquired. Uncached strategies have a 25-second server-side Google request cap.

Public counters use short-lived, site-scoped database advisory locks so concurrent requests cannot bypass the transient-backed limit, even without Redis or another persistent object cache. PageSpeed uses separate Desktop and Mobile scopes, each with the configured `audit_rate_limit`. With the default value of 6, one network address may make up to six Desktop and six Mobile PageSpeed endpoint requests per hour. A normal audit launches one request in each scope. Lock acquisition is bounded and fails closed when synchronization is unavailable.

## Security and data handling

- All AJAX actions use WordPress nonces; settings use the WordPress Settings API nonce flow.
- All user inputs are unslashed, validated, sanitized, and escaped on output. Browser rendering uses `textContent`, never server-provided HTML.
- Public audits permit only `http`/`https` destinations on ports 80/443. URLs with credentials, local hostnames, private/reserved IP ranges, and unsafe redirect destinations are rejected.
- Every request and redirect goes through the URL validator and WordPress's safe HTTP API. Downloads, timeouts, redirects, sitemap checks, and link probes are bounded.
- PageSpeed receives only the already-validated final audit URL. The API key is used only in the server-to-server Google request and is never localized to JavaScript, included in public markup, returned in AJAX data, or written to audit history.
- The browser receives a normalized PageSpeed subset rather than Google's raw response. API errors, quota failures, timeouts, and malformed responses become a generic unavailable state and never prevent the normal SEO report from rendering.
- Safe PageSpeed diagnostics are retained privately for 24 hours in hashed transient keys. They contain strategy, timing, HTTP/WP error status, bounded sanitized Google error text, runtime-error code, and response-shape flags only—never the API key, headers, request URL, or raw response.
- Link validation samples at most 10 unique public links by default (filterable up to 20); it is not a full-site crawler.
- Public audit and consultation endpoints are rate-limited with short-lived transient keys based on a site-salted hash of the direct client IP. No raw visitor IP is saved.
- Audit history stores the audited URL, score, compact summary, report data, and timestamp. It never stores downloaded page HTML or visitor IPs. Uninstall intentionally preserves business records and settings so site owners control retention.

## Development and verification

The plugin requires WordPress 6.0+ and PHP 7.4+. Before production use, test on a staging WordPress installation:

1. Activate the plugin and confirm the **SEO Audit** admin menu appears.
2. Save Settings and verify the consultation recipient and CTA URL.
3. If PageSpeed is enabled, verify the masked key state, desktop/mobile results, cache reuse, and a graceful unavailable state with an invalid key.
4. Add `[searchcounsel_audit]` to a page and test a public HTTPS URL.
5. Confirm reports save under **SEO Audit → Audit History**.
6. Test invalid, localhost, private-IP, credentialed, and non-standard-port URLs. Each should be rejected.
7. Configure outbound WordPress mail (or an SMTP plugin) and submit a consultation form.

The plugin does not deploy itself or send website changes to SearchCounselco. Installation and activation remain a deliberate WordPress-admin action.
