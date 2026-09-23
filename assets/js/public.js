/* global SCSA_Public */
( function () {
	'use strict';

	if ( ! window.SCSA_Public ) {
		return;
	}

	var config = window.SCSA_Public;
	var categoryCopy = config.categories || {};
	var statusNames = {
		pass: config.i18n.passed,
		warning: config.i18n.warning,
		critical: config.i18n.critical
	};
	var statusIcons = { pass: '✓', warning: '!', critical: '×' };
	var statusRank = { pass: 0, warning: 1, critical: 2 };
	var pageSpeedInstance = 0;
	var categoryDefinitions = [
		{
			key: 'technical',
			name: categoryCopy.technical ? categoryCopy.technical.name : '',
			description: categoryCopy.technical ? categoryCopy.technical.description : '',
			keys: [ 'https', 'http_status', 'content_type', 'viewport', 'html_lang', 'robots_txt', 'sitemap' ]
		},
		{
			key: 'on_page',
			name: categoryCopy.onPage ? categoryCopy.onPage.name : '',
			description: categoryCopy.onPage ? categoryCopy.onPage.description : '',
			keys: [ 'title', 'meta_description', 'h1', 'h2', 'h3', 'canonical', 'robots_meta' ]
		},
		{
			key: 'content',
			name: categoryCopy.content ? categoryCopy.content.name : '',
			description: categoryCopy.content ? categoryCopy.content.description : '',
			keys: [ 'word_count', 'image_alt' ]
		},
		{
			key: 'performance',
			name: categoryCopy.performance ? categoryCopy.performance.name : '',
			description: categoryCopy.performance ? categoryCopy.performance.description : '',
			keys: [ 'page_size', 'core_web_vitals' ]
		},
		{
			key: 'links',
			name: categoryCopy.links ? categoryCopy.links.name : '',
			description: categoryCopy.links ? categoryCopy.links.description : '',
			keys: [ 'internal_links', 'external_links', 'broken_links' ]
		},
		{
			key: 'social_schema',
			name: categoryCopy.socialSchema ? categoryCopy.socialSchema.name : '',
			description: categoryCopy.socialSchema ? categoryCopy.socialSchema.description : '',
			keys: [ 'open_graph', 'twitter_card', 'structured_data' ]
		}
	];

	function element( name, className, text ) {
		var node = document.createElement( name );
		if ( className ) {
			node.className = className;
		}
		if ( typeof text !== 'undefined' ) {
			node.textContent = String( text );
		}
		return node;
	}

	function safeStatus( status ) {
		return Object.prototype.hasOwnProperty.call( statusNames, status ) ? status : 'warning';
	}

	function formatText( template, values ) {
		var output = String( template || '' );
		values.forEach( function ( value, index ) {
			output = output.replace( new RegExp( '%' + ( index + 1 ) + '\\$[sd]', 'g' ), String( value ) );
		} );
		if ( values.length === 1 ) {
			output = output.replace( /%[sd]/g, String( values[ 0 ] ) );
		}
		return output;
	}

	function countText( singular, plural, count ) {
		return formatText( count === 1 ? singular : plural, [ count ] );
	}

	function scrollBehavior() {
		return window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ? 'auto' : 'smooth';
	}

	function scrollToElement( node, block ) {
		node.scrollIntoView( { behavior: scrollBehavior(), block: block } );
	}

	function normaliseResults( results ) {
		return Array.isArray( results ) ? results.filter( function ( result ) {
			return result && typeof result === 'object' && result.key;
		} ) : [];
	}

	function message( tool, text, type ) {
		var box = tool.querySelector( '.scsa-message' );
		box.hidden = ! text;
		box.className = 'scsa-message' + ( type ? ' is-' + type : '' );
		box.replaceChildren();
		if ( text ) {
			box.appendChild( element( 'span', 'scsa-message-icon', type === 'error' ? '!' : '✓' ) );
			box.appendChild( element( 'span', '', text ) );
		}
	}

	function startLoading( tool ) {
		var panel = tool.querySelector( '.scsa-loading' );
		var stages = Array.prototype.slice.call( panel.querySelectorAll( '[data-stage]' ) );
		var currentCopy = panel.querySelector( '.scsa-current-stage' );
		var activeIndex = 0;

		panel.hidden = false;
		stages.forEach( function ( stage ) { stage.classList.remove( 'is-active' ); } );
		if ( stages.length ) {
			stages[ 0 ].classList.add( 'is-active' );
			currentCopy.textContent = stages[ 0 ].dataset.stage;
		}

		var interval = window.setInterval( function () {
			stages[ activeIndex ].classList.remove( 'is-active' );
			activeIndex = ( activeIndex + 1 ) % stages.length;
			stages[ activeIndex ].classList.add( 'is-active' );
			currentCopy.textContent = stages[ activeIndex ].dataset.stage;
		}, 1450 );

		return function () {
			window.clearInterval( interval );
			panel.hidden = true;
			stages.forEach( function ( stage ) { stage.classList.remove( 'is-active' ); } );
		};
	}

	function parseWebsite( url ) {
		try {
			return new URL( url );
		} catch ( error ) {
			return null;
		}
	}

	function validateAuditUrl( value ) {
		var candidate = String( value || '' ).trim();
		if ( candidate && ! /^[a-z][a-z\d+.-]*:\/\//i.test( candidate ) ) {
			candidate = 'https://' + candidate;
		}
		var parsed = parseWebsite( candidate );
		if ( ! parsed || ! [ 'http:', 'https:' ].includes( parsed.protocol ) ) {
			return null;
		}
		return parsed.href;
	}

	function healthCopy( score ) {
		if ( score >= 90 ) {
			return config.i18n.healthExcellent;
		}
		if ( score >= 75 ) {
			return config.i18n.healthStrong;
		}
		if ( score >= 55 ) {
			return config.i18n.healthFair;
		}
		return config.i18n.healthCritical;
	}

	function updateReportHero( tool, report ) {
		var score = Math.max( 0, Math.min( 100, Number( report.score ) || 0 ) );
		var summary = report.summary || {};
		var passed = Number( summary.pass ) || 0;
		var warnings = Number( summary.warning ) || 0;
		var critical = Number( summary.critical ) || 0;
		var total = Math.max( 1, passed + warnings + critical );
		var parsed = parseWebsite( report.audited_url );
		var gauge = tool.querySelector( '.scsa-score-gauge' );

		gauge.style.setProperty( '--scsa-score', score );
		gauge.classList.remove( 'is-excellent', 'is-strong', 'is-fair', 'is-critical' );
		gauge.classList.add( score >= 90 ? 'is-excellent' : score >= 75 ? 'is-strong' : score >= 55 ? 'is-fair' : 'is-critical' );
		gauge.setAttribute( 'aria-label', formatText( config.i18n.scoreAria, [ score, 100 ] ) );
		tool.querySelector( '.scsa-score-value' ).textContent = score;
		tool.querySelector( '.scsa-score-grade' ).textContent = report.grade || '';
		tool.querySelector( '.scsa-health-message' ).textContent = healthCopy( score );
		tool.querySelector( '.scsa-report-hostname' ).textContent = parsed ? parsed.hostname : report.audited_url || '';
		tool.querySelector( '.scsa-report-time' ).textContent = report.generated_at ? config.i18n.audited + ' ' + new Date( report.generated_at ).toLocaleString() : '';
		tool.querySelector( '.scsa-stat-pass strong' ).textContent = passed;
		tool.querySelector( '.scsa-stat-warning strong' ).textContent = warnings;
		tool.querySelector( '.scsa-stat-critical strong' ).textContent = critical;
		tool.querySelector( '.scsa-distribution-pass' ).style.flexGrow = passed / total;
		tool.querySelector( '.scsa-distribution-warning' ).style.flexGrow = warnings / total;
		tool.querySelector( '.scsa-distribution-critical' ).style.flexGrow = critical / total;
	}

	function categoryForResult( result ) {
		for ( var index = 0; index < categoryDefinitions.length; index++ ) {
			if ( categoryDefinitions[ index ].keys.includes( result.key ) ) {
				return categoryDefinitions[ index ];
			}
		}
		return { key: 'other', name: categoryCopy.other ? categoryCopy.other.name : '', description: categoryCopy.other ? categoryCopy.other.description : '', keys: [] };
	}

	function groupByCategory( results ) {
		var groups = {};
		results.forEach( function ( result ) {
			var category = categoryForResult( result );
			if ( ! groups[ category.key ] ) {
				groups[ category.key ] = { definition: category, results: [] };
			}
			groups[ category.key ].results.push( result );
		} );
		var ordered = categoryDefinitions.map( function ( definition ) {
			return groups[ definition.key ];
		} ).filter( Boolean );
		if ( groups.other ) {
			ordered.push( groups.other );
		}
		return ordered;
	}

	function categoryCounts( results ) {
		return results.reduce( function ( counts, result ) {
			counts[ safeStatus( result.status ) ]++;
			return counts;
		}, { pass: 0, warning: 0, critical: 0 } );
	}

	function categorySeverity( results ) {
		return results.reduce( function ( maximum, result ) {
			return Math.max( maximum, statusRank[ safeStatus( result.status ) ] );
		}, 0 );
	}

	function renderCategorySummary( tool, groups ) {
		var container = tool.querySelector( '.scsa-category-grid' );
		container.replaceChildren();

		groups.forEach( function ( group ) {
			var counts = categoryCounts( group.results );
			var total = group.results.length;
			var passRate = total ? Math.round( ( counts.pass / total ) * 100 ) : 0;
			var issueCount = counts.warning + counts.critical;
			var button = element( 'button', 'scsa-category-card' );
			var top = element( 'span', 'scsa-category-card-top' );
			var name = element( 'strong', '', group.definition.name );
			var score = element( 'span', 'scsa-category-rate', passRate + '%' );
			var track = element( 'span', 'scsa-category-track' );
			var bar = element( 'span', 'scsa-category-bar' );
			var checkCount = countText( config.i18n.oneCheck, config.i18n.manyChecks, total );
			var issueSummary = issueCount ? countText( config.i18n.oneIssue, config.i18n.manyIssues, issueCount ) : config.i18n.allClear;
			var bottom = element( 'span', 'scsa-category-card-bottom', checkCount + ' · ' + issueSummary );

			button.type = 'button';
			button.dataset.categoryTarget = group.definition.key;
			button.setAttribute( 'aria-label', formatText( config.i18n.viewCategory, [ group.definition.name ] ) );
			button.classList.add( categorySeverity( group.results ) === 2 ? 'has-critical' : categorySeverity( group.results ) === 1 ? 'has-warning' : 'is-healthy' );
			bar.style.width = passRate + '%';
			track.appendChild( bar );
			top.appendChild( name );
			top.appendChild( score );
			button.appendChild( top );
			button.appendChild( track );
			button.appendChild( bottom );
			container.appendChild( button );
		} );
	}

	function recommendationTier( recommendation ) {
		if ( safeStatus( recommendation.status ) === 'critical' ) {
			return { key: 'critical', label: config.i18n.criticalLabel };
		}
		if ( recommendation.priority === 'high' ) {
			return { key: 'high', label: config.i18n.high };
		}
		if ( recommendation.priority === 'low' ) {
			return { key: 'low', label: config.i18n.low };
		}
		return { key: 'medium', label: config.i18n.medium };
	}

	function sortRecommendations( recommendations ) {
		var statusOrder = { critical: 0, warning: 1, pass: 2 };
		var priorityOrder = { high: 0, medium: 1, low: 2 };

		return recommendations.map( function ( recommendation, index ) {
			return { item: recommendation, index: index };
		} ).sort( function ( first, second ) {
			var firstStatus = statusOrder[ safeStatus( first.item.status ) ];
			var secondStatus = statusOrder[ safeStatus( second.item.status ) ];
			var firstPriority = Object.prototype.hasOwnProperty.call( priorityOrder, first.item.priority ) ? priorityOrder[ first.item.priority ] : 3;
			var secondPriority = Object.prototype.hasOwnProperty.call( priorityOrder, second.item.priority ) ? priorityOrder[ second.item.priority ] : 3;

			if ( firstStatus !== secondStatus ) {
				return firstStatus - secondStatus;
			}
			if ( firstPriority !== secondPriority ) {
				return firstPriority - secondPriority;
			}
			return first.index - second.index;
		} ).map( function ( entry ) {
			return entry.item;
		} );
	}

	function renderRecommendations( tool, recommendations, results ) {
		var container = tool.querySelector( '.scsa-recommendations' );
		var resultMap = {};
		container.replaceChildren();
		results.forEach( function ( result ) { resultMap[ result.key ] = result; } );

		if ( ! Array.isArray( recommendations ) || ! recommendations.length ) {
			var success = element( 'div', 'scsa-success-state' );
			success.appendChild( element( 'span', 'scsa-success-icon', '✓' ) );
			var successCopy = element( 'div', '' );
			successCopy.appendChild( element( 'strong', '', config.i18n.healthyTitle ) );
			successCopy.appendChild( element( 'p', '', config.i18n.healthyCopy ) );
			success.appendChild( successCopy );
			container.appendChild( success );
			return;
		}

		sortRecommendations( recommendations ).slice( 0, 6 ).forEach( function ( recommendation, index ) {
			var source = resultMap[ recommendation.key ] || {};
			var category = categoryForResult( source );
			var tier = recommendationTier( recommendation );
			var card = element( 'article', 'scsa-action-card scsa-action-' + tier.key );
			var number = element( 'span', 'scsa-action-number', String( index + 1 ).padStart( 2, '0' ) );
			var content = element( 'div', 'scsa-action-content' );
			var meta = element( 'div', 'scsa-action-meta' );

			meta.appendChild( element( 'span', 'scsa-action-tier', tier.label ) );
			meta.appendChild( element( 'span', 'scsa-action-category', category.name ) );
			content.appendChild( meta );
			content.appendChild( element( 'h4', '', recommendation.label || source.label || '' ) );
			if ( source.summary ) {
				content.appendChild( element( 'p', 'scsa-action-why', source.summary ) );
			}
			content.appendChild( element( 'p', 'scsa-action-fix', recommendation.recommendation || '' ) );
			card.appendChild( number );
			card.appendChild( content );
			container.appendChild( card );
		} );
	}

	function firstDetail( result ) {
		if ( ! result || ! result.details || typeof result.details !== 'object' ) {
			return '';
		}
		var values = Object.keys( result.details ).map( function ( key ) { return result.details[ key ]; } );
		return values.length ? String( values[ 0 ] ) : '';
	}

	function renderSerpPreview( tool, results, url ) {
		var titleResult = results.find( function ( result ) { return result.key === 'title'; } );
		var descriptionResult = results.find( function ( result ) { return result.key === 'meta_description'; } );
		var title = firstDetail( titleResult );
		var description = firstDetail( descriptionResult );
		var section = tool.querySelector( '.scsa-serp-section' );
		var parsed = parseWebsite( url );

		if ( ! title && ! description ) {
			section.hidden = true;
			return;
		}

		tool.querySelector( '.scsa-serp-site' ).textContent = parsed ? parsed.hostname : '';
		tool.querySelector( '.scsa-serp-url' ).textContent = url || '';
		tool.querySelector( '.scsa-serp-title' ).textContent = title;
		tool.querySelector( '.scsa-serp-title' ).hidden = ! title;
		tool.querySelector( '.scsa-serp-description' ).textContent = description;
		tool.querySelector( '.scsa-serp-description' ).hidden = ! description;
		section.hidden = false;
	}

	function detailsList( details ) {
		var list = element( 'dl', 'scsa-technical-list' );
		Object.keys( details || {} ).forEach( function ( key ) {
			list.appendChild( element( 'dt', '', key ) );
			list.appendChild( element( 'dd', '', details[ key ] ) );
		} );
		return list;
	}

	function createCheckCard( result ) {
		var status = safeStatus( result.status );
		var card = element( 'details', 'scsa-check-card scsa-status-' + status );
		var header = element( 'summary', 'scsa-check-summary' );
		var icon = element( 'span', 'scsa-check-status-icon', statusIcons[ status ] );
		var headline = element( 'span', 'scsa-check-headline' );
		var meta = element( 'span', 'scsa-check-meta' );
		var body = element( 'div', 'scsa-check-body' );

		card.dataset.status = status;
		headline.appendChild( element( 'strong', '', result.label || '' ) );
		headline.appendChild( element( 'small', '', result.summary || '' ) );
		meta.appendChild( element( 'span', 'scsa-status-badge', statusNames[ status ] ) );
		meta.appendChild( element( 'span', 'scsa-check-chevron', '⌄' ) );
		header.appendChild( icon );
		header.appendChild( headline );
		header.appendChild( meta );

		if ( result.recommendation ) {
			var recommendation = element( 'div', 'scsa-check-recommendation' );
			recommendation.appendChild( element( 'span', '', config.i18n.recommendation ) );
			recommendation.appendChild( element( 'p', '', result.recommendation ) );
			body.appendChild( recommendation );
		}
		if ( result.details && Object.keys( result.details ).length ) {
			var technical = element( 'div', 'scsa-technical-details' );
			technical.appendChild( element( 'span', '', config.i18n.technicalDetails ) );
			technical.appendChild( detailsList( result.details ) );
			body.appendChild( technical );
		}

		card.appendChild( header );
		card.appendChild( body );
		return card;
	}

	function pageSpeedStatusName( status ) {
		var names = {
			good: config.i18n.good,
			needs_improvement: config.i18n.needsImprovement,
			poor: config.i18n.poor
		};

		return names[ status ] || '';
	}

	function pageSpeedMetricDefinition( key ) {
		var definitions = {
			fcp: { name: config.i18n.fcp, help: config.i18n.fcpHelp },
			lcp: { name: config.i18n.lcp, help: config.i18n.lcpHelp },
			inp: { name: config.i18n.inp, help: config.i18n.inpHelp },
			tbt: { name: config.i18n.tbt, help: config.i18n.tbtHelp },
			cls: { name: config.i18n.cls, help: config.i18n.clsHelp },
			speed_index: { name: config.i18n.speedIndex, help: config.i18n.speedIndexHelp },
			ttfb: { name: config.i18n.ttfb, help: config.i18n.ttfbHelp }
		};

		return definitions[ key ];
	}

	function pageSpeedMetricValue( key, metric ) {
		if ( ! metric || ! metric.available ) {
			return config.i18n.unavailable;
		}
		if ( metric.display ) {
			return metric.display;
		}
		if ( typeof metric.value !== 'number' || ! Number.isFinite( metric.value ) ) {
			return config.i18n.unavailable;
		}
		if ( key === 'cls' || metric.unit === 'unitless' ) {
			return metric.value.toFixed( 2 );
		}
		if ( metric.unit === 'millisecond' ) {
			return metric.value >= 1000 ? ( metric.value / 1000 ).toFixed( 1 ) + ' s' : Math.round( metric.value ) + ' ms';
		}

		return String( metric.value );
	}

	function createPageSpeedMetric( key, metric ) {
		var definition = pageSpeedMetricDefinition( key );
		var status = metric && metric.available ? pageSpeedStatusName( metric.status ) : '';
		var card = element( 'article', 'scsa-pagespeed-metric' );
		var heading = element( 'div', 'scsa-pagespeed-metric-heading' );

		if ( metric && metric.status ) {
			card.classList.add( 'is-' + metric.status.replace( '_', '-' ) );
		}
		heading.appendChild( element( 'strong', '', definition.name ) );
		if ( status ) {
			heading.appendChild( element( 'span', 'scsa-pagespeed-status', status ) );
		}
		card.appendChild( heading );
		card.appendChild( element( 'span', 'scsa-pagespeed-metric-value', pageSpeedMetricValue( key, metric ) ) );
		card.appendChild( element( 'p', '', definition.help ) );

		return card;
	}

	function appendPageSpeedMetrics( parent, metrics, keys ) {
		var grid = element( 'div', 'scsa-pagespeed-metrics' );

		keys.forEach( function ( key ) {
			grid.appendChild( createPageSpeedMetric( key, metrics && metrics[ key ] ? metrics[ key ] : { available: false } ) );
		} );
		parent.appendChild( grid );
	}

	function createPageSpeedScore( result ) {
		var hasScore = result.score !== null && result.score !== '' && Number.isFinite( Number( result.score ) );
		var score = hasScore ? Number( result.score ) : null;
		var row = element( 'div', 'scsa-pagespeed-performance' );
		var copy = element( 'div', '' );
		var value = element( 'strong', '', hasScore ? score : config.i18n.unavailable );

		copy.appendChild( element( 'span', '', config.i18n.performance ) );
		if ( hasScore ) {
			value.classList.add( score >= 90 ? 'is-good' : score >= 50 ? 'is-needs-improvement' : 'is-poor' );
		}
		row.appendChild( copy );
		row.appendChild( value );
		if ( hasScore ) {
			row.appendChild( element( 'small', '', '/ 100' ) );
		}

		return row;
	}

	function createPageSpeedItems( className, items ) {
		var list = element( 'ul', className );

		items.forEach( function ( item ) {
			var row = element( 'li', '' );
			var itemValue = item.value || '';
			if ( ! itemValue && typeof item.savings_ms === 'number' && Number.isFinite( item.savings_ms ) ) {
				itemValue = Math.round( item.savings_ms ) + ' ms';
			} else if ( ! itemValue && typeof item.savings_bytes === 'number' && Number.isFinite( item.savings_bytes ) ) {
				itemValue = Math.round( item.savings_bytes / 1024 ) + ' KB';
			}
			row.appendChild( element( 'span', '', item.title || '' ) );
			if ( itemValue ) {
				row.appendChild( element( 'strong', '', itemValue ) );
			}
			list.appendChild( row );
		} );

		return list;
	}

	function renderPageSpeedPanel( module, strategy ) {
		var data = module.scsaPageSpeedData || {};
		var result = data[ strategy ] || { available: false };
		var state = module.scsaPageSpeedStates && module.scsaPageSpeedStates[ strategy ] ? module.scsaPageSpeedStates[ strategy ] : 'complete';
		var panel = module.querySelector( '.scsa-pagespeed-panel' );
		var activeTab = module.querySelector( '[data-scsa-pagespeed-tab="' + strategy + '"]' );

		panel.replaceChildren();
		panel.setAttribute( 'aria-labelledby', activeTab ? activeTab.id : '' );
		if ( state === 'loading' ) {
			var loading = element( 'div', 'scsa-pagespeed-loading' );
			loading.setAttribute( 'role', 'status' );
			loading.appendChild( element( 'span', 'scsa-pagespeed-loading-indicator' ) );
			loading.appendChild( element( 'span', '', config.i18n.analyzingPerformance ) );
			panel.appendChild( loading );
			return;
		}
		if ( ! result.available ) {
			panel.appendChild( element( 'p', 'scsa-pagespeed-unavailable', config.i18n.performanceUnavailable ) );
			return;
		}

		panel.appendChild( createPageSpeedScore( result ) );

		var lab = element( 'section', 'scsa-pagespeed-data-section' );
		lab.appendChild( element( 'h5', '', config.i18n.labData ) );
		lab.appendChild( element( 'p', 'scsa-pagespeed-section-copy', config.i18n.labDescription ) );
		lab.appendChild( element( 'h6', '', config.i18n.coreWebVitals ) );
		appendPageSpeedMetrics( lab, result.lab && result.lab.metrics, [ 'lcp', 'inp', 'cls' ] );
		lab.appendChild( element( 'h6', '', config.i18n.performanceMetrics ) );
		appendPageSpeedMetrics( lab, result.lab && result.lab.metrics, [ 'fcp', 'tbt', 'speed_index', 'ttfb' ] );
		panel.appendChild( lab );

		var field = element( 'section', 'scsa-pagespeed-data-section' );
		field.appendChild( element( 'h5', '', config.i18n.fieldData ) );
		field.appendChild( element( 'p', 'scsa-pagespeed-section-copy', config.i18n.fieldDescription ) );
		if ( result.field && result.field.available ) {
			field.appendChild( element( 'span', 'scsa-pagespeed-field-scope', result.field.scope === 'origin' ? config.i18n.originFieldData : config.i18n.urlFieldData ) );
			appendPageSpeedMetrics( field, result.field.metrics, [ 'lcp', 'inp', 'cls', 'fcp', 'ttfb' ] );
		} else {
			field.appendChild( element( 'p', 'scsa-pagespeed-unavailable', config.i18n.fieldUnavailable ) );
		}
		panel.appendChild( field );

		if ( Array.isArray( result.opportunities ) && result.opportunities.length ) {
			var opportunities = element( 'section', 'scsa-pagespeed-data-section' );
			opportunities.appendChild( element( 'h5', '', config.i18n.opportunities ) );
			opportunities.appendChild( createPageSpeedItems( 'scsa-pagespeed-item-list', result.opportunities ) );
			panel.appendChild( opportunities );
		}

		if ( Array.isArray( result.diagnostics ) && result.diagnostics.length ) {
			var diagnostics = element( 'details', 'scsa-pagespeed-diagnostics' );
			diagnostics.appendChild( element( 'summary', '', config.i18n.diagnostics ) );
			diagnostics.appendChild( createPageSpeedItems( 'scsa-pagespeed-item-list', result.diagnostics ) );
			panel.appendChild( diagnostics );
		}
	}

	function selectPageSpeedStrategy( module, strategy, moveFocus ) {
		module.querySelectorAll( '[data-scsa-pagespeed-tab]' ).forEach( function ( button ) {
			var selected = button.dataset.scsaPagespeedTab === strategy;
			button.setAttribute( 'aria-selected', selected ? 'true' : 'false' );
			button.tabIndex = selected ? 0 : -1;
			if ( selected && moveFocus ) {
				button.focus();
			}
		} );
		renderPageSpeedPanel( module, strategy );
	}

	function updatePageSpeedTab( module, strategy ) {
		var button = module.querySelector( '[data-scsa-pagespeed-tab="' + strategy + '"]' );
		var result = module.scsaPageSpeedData[ strategy ] || { available: false };
		var state = module.scsaPageSpeedStates[ strategy ];
		var label = strategy === 'desktop' ? config.i18n.desktop : config.i18n.mobile;
		var hasScore = result.available && result.score !== null && result.score !== '' && Number.isFinite( Number( result.score ) );
		var value = state === 'loading' ? config.i18n.analyzingShort : hasScore ? Number( result.score ) : config.i18n.unavailable;

		button.querySelector( 'strong' ).textContent = value;
		button.classList.toggle( 'is-loading', state === 'loading' );
		if ( state === 'loading' ) {
			button.setAttribute( 'aria-label', formatText( config.i18n.pageSpeedAnalyzingScore, [ label ] ) );
		} else {
			button.setAttribute( 'aria-label', formatText( hasScore ? config.i18n.pageSpeedScore : config.i18n.pageSpeedUnavailableScore, hasScore ? [ label, value ] : [ label ] ) );
		}
	}

	function updatePageSpeedStrategy( module, strategy, result ) {
		module.scsaPageSpeedData[ strategy ] = result && typeof result === 'object' ? result : { available: false };
		module.scsaPageSpeedStates[ strategy ] = 'complete';
		updatePageSpeedTab( module, strategy );
		if ( module.scsaPageSpeedStates.desktop === 'complete' && module.scsaPageSpeedStates.mobile === 'complete' ) {
			module.querySelector( '.scsa-pagespeed-summary-copy' ).textContent = config.i18n.labDescription;
		}

		var selected = module.querySelector( '[data-scsa-pagespeed-tab="' + strategy + '"][aria-selected="true"]' );
		if ( selected ) {
			renderPageSpeedPanel( module, strategy );
		}
	}

	function createPageSpeedModule( performance, loading ) {
		pageSpeedInstance++;
		var module = element( 'section', 'scsa-pagespeed' );
		var header = element( 'div', 'scsa-pagespeed-header' );
		var title = element( 'div', '' );
		var tabs = element( 'div', 'scsa-pagespeed-tabs' );
		var details = element( 'details', 'scsa-pagespeed-details' );
		var panel = element( 'div', 'scsa-pagespeed-panel' );
		var panelId = 'scsa-pagespeed-panel-' + pageSpeedInstance;
		var data = performance && typeof performance === 'object' ? performance : {};

		title.appendChild( element( 'span', 'scsa-pagespeed-kicker', config.i18n.pageSpeedTitle ) );
		title.appendChild( element( 'p', 'scsa-pagespeed-summary-copy', loading ? config.i18n.analyzingPerformance : config.i18n.labDescription ) );
		header.appendChild( title );
		module.appendChild( header );
		tabs.setAttribute( 'role', 'tablist' );
		tabs.setAttribute( 'aria-label', config.i18n.pageSpeedTitle );

		[ 'desktop', 'mobile' ].forEach( function ( strategy, index ) {
			var label = strategy === 'desktop' ? config.i18n.desktop : config.i18n.mobile;
			var button = element( 'button', 'scsa-pagespeed-tab' );

			button.type = 'button';
			button.id = 'scsa-pagespeed-tab-' + pageSpeedInstance + '-' + strategy;
			button.dataset.scsaPagespeedTab = strategy;
			button.setAttribute( 'role', 'tab' );
			button.setAttribute( 'aria-controls', panelId );
			button.setAttribute( 'aria-selected', index === 0 ? 'true' : 'false' );
			button.tabIndex = index === 0 ? 0 : -1;
			button.appendChild( element( 'span', '', label ) );
			button.appendChild( element( 'strong', '', '' ) );
			tabs.appendChild( button );
		} );

		module.appendChild( tabs );
		details.appendChild( element( 'summary', 'scsa-pagespeed-details-summary', config.i18n.viewDetails ) );
		panel.id = panelId;
		panel.setAttribute( 'role', 'tabpanel' );
		panel.setAttribute( 'aria-live', 'polite' );
		panel.tabIndex = 0;
		details.appendChild( panel );
		module.appendChild( details );
		module.scsaPageSpeedData = data;
		module.scsaPageSpeedStates = {
			desktop: loading ? 'loading' : 'complete',
			mobile: loading ? 'loading' : 'complete'
		};
		updatePageSpeedTab( module, 'desktop' );
		updatePageSpeedTab( module, 'mobile' );
		selectPageSpeedStrategy( module, 'desktop', false );

		return module;
	}

	function createCategoryAccordion( group, open, performance, pageSpeedLoading ) {
		var counts = categoryCounts( group.results );
		var accordion = element( 'details', 'scsa-audit-category' );
		var summary = element( 'summary', 'scsa-audit-category-summary' );
		var heading = element( 'span', 'scsa-audit-category-heading' );
		var countsRow = element( 'span', 'scsa-audit-category-counts' );
		var cards = element( 'div', 'scsa-check-list' );

		accordion.dataset.category = group.definition.key;
		accordion.open = open;
		heading.appendChild( element( 'strong', '', group.definition.name ) );
		heading.appendChild( element( 'small', '', group.definition.description ) );
		countsRow.appendChild( element( 'span', 'is-total', countText( config.i18n.oneCheck, config.i18n.manyChecks, group.results.length ) ) );
		if ( counts.critical ) {
			countsRow.appendChild( element( 'span', 'is-critical', formatText( config.i18n.criticalCount, [ counts.critical ] ) ) );
		}
		if ( counts.warning ) {
			countsRow.appendChild( element( 'span', 'is-warning', countText( config.i18n.warningCount, config.i18n.warningsCount, counts.warning ) ) );
		}
		countsRow.appendChild( element( 'span', 'is-pass', formatText( config.i18n.passedCount, [ counts.pass ] ) ) );
		countsRow.appendChild( element( 'span', 'scsa-category-chevron', '⌄' ) );
		summary.appendChild( heading );
		summary.appendChild( countsRow );
		group.results.slice().sort( function ( first, second ) {
			return statusRank[ safeStatus( second.status ) ] - statusRank[ safeStatus( first.status ) ];
		} ).forEach( function ( result ) {
			cards.appendChild( createCheckCard( result ) );
		} );
		if ( group.definition.key === 'technical' ) {
			cards.appendChild( createPageSpeedModule( performance, pageSpeedLoading ) );
		}
		accordion.appendChild( summary );
		accordion.appendChild( cards );
		return accordion;
	}

	function renderDetailedChecks( tool, groups, performance, pageSpeedLoading ) {
		var container = tool.querySelector( '.scsa-checks' );
		var highestSeverity = groups.reduce( function ( highest, group ) {
			return Math.max( highest, categorySeverity( group.results ) );
		}, 0 );
		var opened = false;
		container.replaceChildren();

		groups.forEach( function ( group ) {
			var shouldOpen = ! opened && categorySeverity( group.results ) === highestSeverity;
			container.appendChild( createCategoryAccordion( group, shouldOpen, performance, pageSpeedLoading ) );
			opened = opened || shouldOpen;
		} );
	}

	function applyCheckFilter( tool, filter ) {
		var categories = tool.querySelectorAll( '.scsa-audit-category' );
		categories.forEach( function ( category ) {
			var visible = 0;
			category.querySelectorAll( '.scsa-check-card' ).forEach( function ( card ) {
				var show = filter === 'all' || filter === card.dataset.status || ( filter === 'issues' && card.dataset.status !== 'pass' );
				card.hidden = ! show;
				visible += show ? 1 : 0;
			} );
			category.hidden = visible === 0;
			if ( filter !== 'all' && visible > 0 ) {
				category.open = true;
			}
		} );
	}

	function setCheckFilter( tool, filter ) {
		tool.querySelectorAll( '[data-scsa-filter]' ).forEach( function ( button ) {
			var active = button.dataset.scsaFilter === filter;
			button.classList.toggle( 'is-active', active );
			button.setAttribute( 'aria-pressed', active ? 'true' : 'false' );
		} );
		applyCheckFilter( tool, filter );
	}

	function renderReport( tool, report ) {
		var results = normaliseResults( report.results );
		if ( ! results.length ) {
			message( tool, config.i18n.emptyResult, 'error' );
			return false;
		}
		var groups = groupByCategory( results );
		var reportPanel = tool.querySelector( '.scsa-report' );

		updateReportHero( tool, report );
		renderCategorySummary( tool, groups );
		renderRecommendations( tool, report.recommendations, results );
		renderSerpPreview( tool, results, report.audited_url );
		var pageSpeedLoading = Boolean( report.pagespeed_request && report.pagespeed_request.enabled );
		renderDetailedChecks( tool, groups, report.performance, pageSpeedLoading );
		tool.querySelector( '.scsa-consultation-form [name="website"]' ).value = report.audited_url || '';
		setCheckFilter( tool, 'all' );
		reportPanel.hidden = false;
		window.requestAnimationFrame( function () { reportPanel.classList.add( 'is-visible' ); } );
		scrollToElement( reportPanel, 'start' );
		return true;
	}

	function requestPageSpeed( tool, report, auditRun ) {
		var request = report.pagespeed_request || {};
		var module = tool.querySelector( '.scsa-pagespeed' );

		if ( ! request.enabled || ! request.nonce || ! report.audited_url || ! module ) {
			return;
		}

		[ 'desktop', 'mobile' ].forEach( function ( strategy ) {
			send( {
				action: 'scsa_run_pagespeed',
				nonce: request.nonce,
				url: report.audited_url,
				strategy: strategy
			} ).then( function ( response ) {
				if ( ! response.success || ! response.data || response.data.strategy !== strategy ) {
					throw new Error( config.i18n.performanceUnavailable );
				}
				if ( tool.scsaAuditRun !== auditRun || ! module.isConnected ) {
					return;
				}
				updatePageSpeedStrategy( module, strategy, response.data.result );
			} ).catch( function () {
				if ( tool.scsaAuditRun === auditRun && module.isConnected ) {
					updatePageSpeedStrategy( module, strategy, { available: false } );
				}
			} );
		} );
	}

	function send( values ) {
		return window.fetch( config.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: new URLSearchParams( values ).toString()
		} ).then( function ( response ) {
			return response.json().catch( function () {
				return { success: false, data: { message: config.i18n.failed } };
			} );
		} );
	}

	function focusAuditForm( tool ) {
		var input = tool.querySelector( '.scsa-audit-form [name="url"]' );
		scrollToElement( tool.querySelector( '.scsa-hero' ), 'start' );
		window.setTimeout( function () { input.focus(); input.select(); }, 400 );
	}

	function clearFieldError( field ) {
		var wrapper = field.closest( '.scsa-form-field' );
		if ( ! wrapper ) {
			return;
		}
		field.removeAttribute( 'aria-invalid' );
		wrapper.classList.remove( 'has-error' );
		wrapper.querySelector( '.scsa-field-error' ).textContent = '';
	}

	function setFieldError( field, text ) {
		var wrapper = field.closest( '.scsa-form-field' );
		field.setAttribute( 'aria-invalid', 'true' );
		wrapper.classList.add( 'has-error' );
		wrapper.querySelector( '.scsa-field-error' ).textContent = text;
	}

	function validateConsultation( form ) {
		var firstInvalid = null;
		var name = form.querySelector( '[name="name"]' );
		var email = form.querySelector( '[name="email"]' );
		var website = form.querySelector( '[name="website"]' );

		[ name, email, website ].forEach( clearFieldError );
		if ( ! name.value.trim() ) {
			setFieldError( name, config.i18n.nameRequired );
			firstInvalid = firstInvalid || name;
		}
		if ( ! email.value.trim() || ! email.validity.valid ) {
			setFieldError( email, config.i18n.emailRequired );
			firstInvalid = firstInvalid || email;
		}
		if ( ! validateAuditUrl( website.value ) ) {
			setFieldError( website, config.i18n.websiteRequired );
			firstInvalid = firstInvalid || website;
		}
		if ( firstInvalid ) {
			firstInvalid.focus();
			return false;
		}
		website.value = validateAuditUrl( website.value );
		return true;
	}

	function initializeTool( tool ) {
		var auditForm = tool.querySelector( '.scsa-audit-form' );
		var auditButton = auditForm.querySelector( '.scsa-analyze-button' );
		var auditButtonLabel = auditButton.querySelector( 'span' );
		var originalButtonLabel = auditButtonLabel.textContent;
		var consultationForm = tool.querySelector( '.scsa-consultation-form' );
		tool.scsaAuditRun = 0;

		auditForm.addEventListener( 'submit', function ( event ) {
			event.preventDefault();
			var input = auditForm.querySelector( '[name="url"]' );
			var url = validateAuditUrl( input.value );

			if ( ! url ) {
				message( tool, config.i18n.invalidUrl, 'error' );
				input.setAttribute( 'aria-invalid', 'true' );
				input.focus();
				return;
			}

			input.value = url;
			tool.scsaAuditRun++;
			var auditRun = tool.scsaAuditRun;
			input.removeAttribute( 'aria-invalid' );
			message( tool, '', '' );
			tool.querySelector( '.scsa-report' ).hidden = true;
			tool.querySelector( '.scsa-report' ).classList.remove( 'is-visible' );
			auditButton.disabled = true;
			auditButtonLabel.textContent = config.i18n.analyzing;
			var stopLoading = startLoading( tool );

			send( { action: 'scsa_run_public_audit', nonce: config.auditNonce, url: url } ).then( function ( response ) {
				if ( ! response.success ) {
					throw new Error( response.data && response.data.message ? response.data.message : config.i18n.failed );
				}
				if ( renderReport( tool, response.data ) ) {
					requestPageSpeed( tool, response.data, auditRun );
				}
			} ).catch( function ( error ) {
				message( tool, error.message || config.i18n.failed, 'error' );
			} ).finally( function () {
				stopLoading();
				auditButton.disabled = false;
				auditButtonLabel.textContent = originalButtonLabel;
			} );
		} );

		tool.addEventListener( 'click', function ( event ) {
			var filter = event.target.closest( '[data-scsa-filter]' );
			var categoryButton = event.target.closest( '[data-category-target]' );
			var pageSpeedTab = event.target.closest( '[data-scsa-pagespeed-tab]' );
			var runAgain = event.target.closest( '.scsa-audit-another, .scsa-run-again' );

			if ( filter ) {
				setCheckFilter( tool, filter.dataset.scsaFilter );
			}
			if ( categoryButton ) {
				var category = tool.querySelector( '.scsa-audit-category[data-category="' + categoryButton.dataset.categoryTarget + '"]' );
				if ( category ) {
					if ( category.hidden ) {
						setCheckFilter( tool, 'all' );
					}
					category.open = true;
					scrollToElement( category, 'center' );
				}
			}
			if ( pageSpeedTab ) {
				var pageSpeedModule = pageSpeedTab.closest( '.scsa-pagespeed' );
				if ( pageSpeedModule ) {
					selectPageSpeedStrategy( pageSpeedModule, pageSpeedTab.dataset.scsaPagespeedTab, false );
				}
			}
			if ( runAgain ) {
				focusAuditForm( tool );
			}
		} );

		tool.addEventListener( 'keydown', function ( event ) {
			var activeTab = event.target.closest( '[data-scsa-pagespeed-tab]' );
			if ( ! activeTab || ! [ 'ArrowLeft', 'ArrowRight', 'Home', 'End' ].includes( event.key ) ) {
				return;
			}

			var pageSpeedModule = activeTab.closest( '.scsa-pagespeed' );
			var tabs = Array.prototype.slice.call( pageSpeedModule.querySelectorAll( '[data-scsa-pagespeed-tab]' ) );
			var index = tabs.indexOf( activeTab );

			if ( event.key === 'Home' ) {
				index = 0;
			} else if ( event.key === 'End' ) {
				index = tabs.length - 1;
			} else {
				index = ( index + ( event.key === 'ArrowRight' ? 1 : -1 ) + tabs.length ) % tabs.length;
			}

			event.preventDefault();
			selectPageSpeedStrategy( pageSpeedModule, tabs[ index ].dataset.scsaPagespeedTab, true );
		} );

		consultationForm.querySelectorAll( 'input, textarea' ).forEach( function ( field ) {
			field.addEventListener( 'input', function () { clearFieldError( field ); } );
		} );

		consultationForm.addEventListener( 'submit', function ( event ) {
			event.preventDefault();
			if ( ! validateConsultation( consultationForm ) ) {
				return;
			}

			var formButton = consultationForm.querySelector( 'button' );
			var note = consultationForm.querySelector( '.scsa-consultation-message' );
			var data = new FormData( consultationForm );
			var payload = {
				action: 'scsa_submit_consultation',
				nonce: config.consultationNonce,
				name: data.get( 'name' ) || '',
				email: data.get( 'email' ) || '',
				website: data.get( 'website' ) || '',
				message: data.get( 'message' ) || ''
			};

			formButton.disabled = true;
			note.className = 'scsa-consultation-message';
			note.textContent = '';
			send( payload ).then( function ( response ) {
				if ( ! response.success ) {
					throw new Error( response.data && response.data.message ? response.data.message : config.i18n.contactFailed );
				}
				note.className = 'scsa-consultation-message is-success';
				note.textContent = response.data.message || config.i18n.contactSuccess;
				consultationForm.querySelector( '[name="name"]' ).value = '';
				consultationForm.querySelector( '[name="email"]' ).value = '';
				consultationForm.querySelector( '[name="message"]' ).value = '';
			} ).catch( function ( error ) {
				note.className = 'scsa-consultation-message is-error';
				note.textContent = error.message || config.i18n.contactFailed;
			} ).finally( function () {
				formButton.disabled = false;
			} );
		} );
	}

	document.querySelectorAll( '[data-scsa-audit-tool]' ).forEach( initializeTool );
}() );
