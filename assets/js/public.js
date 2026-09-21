/* global SCSA_Public */
( function () {
	'use strict';

	if ( ! window.SCSA_Public ) {
		return;
	}

	var config = window.SCSA_Public;
	var statusNames = {
		pass: config.i18n.passed,
		warning: config.i18n.warning,
		critical: config.i18n.critical
	};

	function safeStatus( status ) {
		return Object.prototype.hasOwnProperty.call( statusNames, status ) ? status : 'warning';
	}

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

	function message( tool, text, type ) {
		var box = tool.querySelector( '.scsa-message' );
		box.hidden = ! text;
		box.className = 'scsa-message' + ( type ? ' is-' + type : '' );
		box.textContent = text || '';
	}

	function progress( tool, active ) {
		var panel = tool.querySelector( '.scsa-progress' );
		var steps = panel.querySelectorAll( '[data-step]' );
		panel.hidden = ! active;
		steps.forEach( function ( step ) {
			step.classList.remove( 'is-current', 'is-complete' );
		} );

		if ( ! active ) {
			return function () {};
		}

		steps[ 0 ].classList.add( 'is-current' );
		var timeouts = [
			window.setTimeout( function () {
				steps[ 0 ].classList.remove( 'is-current' );
				steps[ 0 ].classList.add( 'is-complete' );
				steps[ 1 ].classList.add( 'is-current' );
			}, 1100 ),
			window.setTimeout( function () {
				steps[ 1 ].classList.remove( 'is-current' );
				steps[ 1 ].classList.add( 'is-complete' );
				steps[ 2 ].classList.add( 'is-current' );
			}, 2600 )
		];

		return function () {
			timeouts.forEach( window.clearTimeout );
			steps.forEach( function ( step ) {
				step.classList.remove( 'is-current' );
				step.classList.add( 'is-complete' );
			} );
		};
	}

	function updateScore( tool, report ) {
		var score = Math.max( 0, Math.min( 100, Number( report.score ) || 0 ) );
		var gauge = tool.querySelector( '.scsa-score-gauge' );
		gauge.style.setProperty( '--score', score );
		gauge.setAttribute( 'aria-label', 'SEO score: ' + score + ' out of 100' );
		tool.querySelector( '.scsa-score-value' ).textContent = score;
		tool.querySelector( '.scsa-score-grade' ).textContent = report.grade || '';
	}

	function renderSummary( tool, report ) {
		var summary = report.summary || {};
		tool.querySelector( '.scsa-summary-pass .scsa-summary-count' ).textContent = Number( summary.pass ) || 0;
		tool.querySelector( '.scsa-summary-warning .scsa-summary-count' ).textContent = Number( summary.warning ) || 0;
		tool.querySelector( '.scsa-summary-critical .scsa-summary-count' ).textContent = Number( summary.critical ) || 0;
	}

	function renderRecommendations( tool, recommendations ) {
		var container = tool.querySelector( '.scsa-recommendations' );
		container.replaceChildren();

		if ( ! recommendations || ! recommendations.length ) {
			var success = element( 'div', 'scsa-empty-state' );
			success.appendChild( element( 'span', 'scsa-empty-icon', '✓' ) );
			var successCopy = element( 'div', '' );
			successCopy.appendChild( element( 'strong', '', 'Your highest-impact SEO foundations look healthy.' ) );
			successCopy.appendChild( element( 'p', '', 'Keep monitoring your content, links, and performance as the site evolves.' ) );
			success.appendChild( successCopy );
			container.appendChild( success );
			return;
		}

		recommendations.forEach( function ( item, index ) {
			var status = safeStatus( item.status );
			var card = element( 'article', 'scsa-recommendation scsa-status-' + status );
			var number = element( 'span', 'scsa-rec-number', String( index + 1 ).padStart( 2, '0' ) );
			var content = element( 'div', 'scsa-rec-content' );
			var title = element( 'h4', '', item.label || '' );
			var copy = element( 'p', '', item.recommendation || '' );
			var impact = element( 'span', 'scsa-impact scsa-impact-' + ( item.priority === 'high' ? 'high' : 'medium' ), item.priority === 'high' ? config.i18n.high : config.i18n.medium );
			content.appendChild( title );
			content.appendChild( copy );
			card.appendChild( number );
			card.appendChild( content );
			card.appendChild( impact );
			container.appendChild( card );
		} );
	}

	function detailsList( details ) {
		var list = element( 'dl', 'scsa-check-details' );
		Object.keys( details || {} ).forEach( function ( key ) {
			var term = element( 'dt', '', key );
			var description = element( 'dd', '', details[ key ] );
			list.appendChild( term );
			list.appendChild( description );
		} );
		return list;
	}

	function createCheckCard( result, isExpanded ) {
			var status = safeStatus( result.status );
			var card = element( 'details', 'scsa-check scsa-status-' + status );
			var header = element( 'summary', 'scsa-check-header' );
			var heading = element( 'div', 'scsa-check-heading' );
			var title = element( 'h4', '', result.label || '' );
			var summary = element( 'p', 'scsa-check-summary', result.summary || '' );
			var meta = element( 'div', 'scsa-check-meta' );
			var pill = element( 'span', 'scsa-status-pill', statusNames[ status ] );
			var chevron = element( 'span', 'scsa-check-chevron', '⌄' );
			var body = element( 'div', 'scsa-check-body' );
			var fix = element( 'p', 'scsa-check-fix', result.recommendation || '' );
			var fixLabel = element( 'strong', '', 'Recommendation: ' );
			fix.prepend( fixLabel );
			card.open = Boolean( isExpanded );
			heading.appendChild( title );
			heading.appendChild( summary );
			meta.appendChild( pill );
			meta.appendChild( chevron );
		header.appendChild( heading );
		header.appendChild( meta );
		card.appendChild( header );
			body.appendChild( fix );

			if ( result.details && Object.keys( result.details ).length ) {
				body.appendChild( detailsList( result.details ) );
			}

			card.appendChild( body );

			return card;
	}

	function createCheckGroup( title, description, status, results, expanded ) {
		var group = element( 'section', 'scsa-check-group scsa-status-' + status );
		var header = element( 'div', 'scsa-check-group-header' );
		var copy = element( 'div', '' );
		copy.appendChild( element( 'h4', '', title ) );
		copy.appendChild( element( 'p', '', description ) );
		header.appendChild( copy );
		header.appendChild( element( 'span', 'scsa-group-count', String( results.length ) ) );
		group.appendChild( header );
		var cards = element( 'div', 'scsa-check-group-cards' );
		results.forEach( function ( result ) {
			cards.appendChild( createCheckCard( result, expanded ) );
		} );
		group.appendChild( cards );
		return group;
	}

	function createPassedGroup( results ) {
		var group = element( 'details', 'scsa-passed-group' );
		var summary = element( 'summary', '' );
		var badge = element( 'span', 'scsa-passed-badge', '✓' );
		var copy = element( 'span', 'scsa-passed-copy' );
		copy.appendChild( element( 'strong', '', String( results.length ) + ' checks passed' ) );
		copy.appendChild( element( 'small', '', 'Your core SEO signals are in good shape.' ) );
		summary.appendChild( badge );
		summary.appendChild( copy );
		summary.appendChild( element( 'span', 'scsa-passed-toggle', 'View details' ) );
		group.appendChild( summary );
		var cards = element( 'div', 'scsa-check-group-cards scsa-passed-cards' );
		results.forEach( function ( result ) {
			cards.appendChild( createCheckCard( result, false ) );
		} );
		group.appendChild( cards );
		return group;
	}

	function renderChecks( tool, results ) {
		var container = tool.querySelector( '.scsa-checks' );
		var groups = { critical: [], warning: [], pass: [] };
		container.replaceChildren();

		( results || [] ).forEach( function ( result ) {
			groups[ safeStatus( result.status ) ].push( result );
		} );

		if ( groups.critical.length ) {
			container.appendChild( createCheckGroup( 'Critical issues', 'Resolve these first—they can directly limit visibility or user trust.', 'critical', groups.critical, true ) );
		}
		if ( groups.warning.length ) {
			container.appendChild( createCheckGroup( 'Opportunities to improve', 'These refinements can strengthen search appearance and page quality.', 'warning', groups.warning, true ) );
		}
		if ( groups.pass.length ) {
			container.appendChild( createPassedGroup( groups.pass ) );
		}
	}

	function renderReport( tool, report ) {
		var reportPanel = tool.querySelector( '.scsa-report' );
		tool.querySelector( '.scsa-audited-url' ).textContent = report.audited_url || '';
		updateScore( tool, report );
		renderSummary( tool, report );
		renderRecommendations( tool, report.recommendations );
		renderChecks( tool, report.results );
		tool.querySelector( '.scsa-consultation-form [name="website"]' ).value = report.audited_url || '';
		reportPanel.hidden = false;
		reportPanel.scrollIntoView( { behavior: 'smooth', block: 'start' } );
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

	function initializeTool( tool ) {
		var auditForm = tool.querySelector( '.scsa-audit-form' );
		var auditButton = auditForm.querySelector( 'button' );
		var originalButton = auditButton.textContent;
		var consultationForm = tool.querySelector( '.scsa-consultation-form' );

		auditForm.addEventListener( 'submit', function ( event ) {
			event.preventDefault();
			var input = auditForm.querySelector( '[name="url"]' );
			var url = input.value.trim();

			if ( ! url ) {
				message( tool, 'Enter a website URL to begin.', 'error' );
				input.focus();
				return;
			}

			message( tool, '', '' );
			tool.querySelector( '.scsa-report' ).hidden = true;
			auditButton.disabled = true;
			auditButton.textContent = config.i18n.analyzing;
			var completeProgress = progress( tool, true );

			send( { action: 'scsa_run_public_audit', nonce: config.auditNonce, url: url } ).then( function ( response ) {
				if ( ! response.success ) {
					throw new Error( response.data && response.data.message ? response.data.message : config.i18n.failed );
				}
				renderReport( tool, response.data );
			} ).catch( function ( error ) {
				message( tool, error.message || config.i18n.failed, 'error' );
			} ).finally( function () {
				completeProgress();
				window.setTimeout( function () { progress( tool, false ); }, 350 );
				auditButton.disabled = false;
				auditButton.textContent = originalButton;
			} );
		} );

		consultationForm.addEventListener( 'submit', function ( event ) {
			event.preventDefault();
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
			note.textContent = '';
			send( payload ).then( function ( response ) {
				if ( ! response.success ) {
					throw new Error( response.data && response.data.message ? response.data.message : config.i18n.contactFailed );
				}
				note.className = 'scsa-consultation-message is-success';
				note.textContent = response.data.message || config.i18n.contactSuccess;
				consultationForm.reset();
			} ).catch( function ( error ) {
				note.className = 'scsa-consultation-message is-error';
				note.textContent = error.message || config.i18n.contactFailed;
			} ).finally( function () {
				formButton.disabled = false;
			} );
		} );
	}

	document.querySelectorAll( '.scsa-audit-tool' ).forEach( initializeTool );
}() );
