/**
 * WP Wand SEO button — injects "Generate with AI" next to the Yoast / RankMath meta
 * description field and fills it from REST /seo. Plain JS; defensive (fields load async,
 * may be React-controlled). Replaces the legacy seo.js.
 */
( function () {
	const cfg = window.wpwandSeo || {};

	// Fillable meta-description fields across SEO plugins / editors.
	const SELECTORS = [
		'#rank-math-editor-description', // RankMath (block)
		'textarea.rank-math-editor-description',
		'#rank_math_description', // RankMath (classic)
		'#yoast_wpseo_metadesc', // Yoast (classic)
	];

	function getTitle() {
		try {
			const t = window.wp?.data
				?.select?.( 'core/editor' )
				?.getEditedPostAttribute?.( 'title' );
			if ( t ) {
				return t;
			}
		} catch ( e ) {}
		return document.querySelector( '#title' )?.value || '';
	}

	function getPostId() {
		try {
			const id = window.wp?.data
				?.select?.( 'core/editor' )
				?.getCurrentPostId?.();
			if ( id ) {
				return id;
			}
		} catch ( e ) {}
		return document.querySelector( '#post_ID' )?.value || 0;
	}

	function setNativeValue( el, value ) {
		const proto =
			el.tagName === 'TEXTAREA'
				? window.HTMLTextAreaElement.prototype
				: window.HTMLInputElement.prototype;
		const setter = Object.getOwnPropertyDescriptor( proto, 'value' ).set;
		setter.call( el, value );
		el.dispatchEvent( new Event( 'input', { bubbles: true } ) );
		el.dispatchEvent( new Event( 'change', { bubbles: true } ) );
	}

	function generate( field, btn ) {
		btn.disabled = true;
		const label = btn.textContent;
		btn.textContent = 'Generating…';
		fetch( ( cfg.root || '/wp-json/' ) + 'wpwand/v1/seo', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': cfg.nonce || '',
			},
			credentials: 'same-origin',
			body: JSON.stringify( {
				title: getTitle(),
				post_id: getPostId(),
			} ),
		} )
			.then( ( r ) => r.json() )
			.then( ( res ) => {
				if ( res && res.text ) {
					setNativeValue( field, res.text );
				} else if ( res && res.error ) {
					window.alert( res.error );
				}
			} )
			.catch( ( e ) => window.alert( e.message || 'Error' ) )
			.finally( () => {
				btn.disabled = false;
				btn.textContent = label;
			} );
	}

	function inject( field ) {
		if ( field.dataset.wpwandSeo ) {
			return;
		}
		field.dataset.wpwandSeo = '1';

		const btn = document.createElement( 'button' );
		btn.type = 'button';
		btn.textContent = '✨ Generate with AI';
		btn.style.cssText =
			'margin:6px 0;display:inline-flex;align-items:center;gap:6px;' +
			'background:#3767fb;color:#fff;border:0;border-radius:6px;' +
			'padding:6px 12px;font-size:12px;font-weight:600;cursor:pointer;';
		btn.addEventListener( 'click', () => generate( field, btn ) );
		field.parentNode.insertBefore( btn, field );
	}

	function scan() {
		SELECTORS.forEach( ( sel ) => {
			document.querySelectorAll( sel ).forEach( inject );
		} );
	}

	// SEO panels mount asynchronously — scan periodically (then ease off).
	let ticks = 0;
	const timer = setInterval( () => {
		scan();
		if ( ++ticks > 40 ) {
			clearInterval( timer );
		}
	}, 800 );
	document.addEventListener( 'DOMContentLoaded', scan );
} )();
