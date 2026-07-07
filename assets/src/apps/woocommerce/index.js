/**
 * WP Wand WooCommerce — a "Generate with AI" panel on the product editor that writes a
 * product title + description from a short brief and inserts them. Plain JS; defensive.
 * Replaces the legacy woocommerce.js.
 */
import { renderMarkdown } from '../../shared/markdown';

( function () {
	const cfg = window.wpwandWc || {};

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

	function insertTitle( title ) {
		const el = document.querySelector( '#title' );
		if ( el ) {
			setNativeValue( el, title );
			const prompt = document.querySelector( '#title-prompt-text' );
			if ( prompt ) {
				prompt.classList.add( 'screen-reader-text' );
			}
		}
	}

	function insertContent( text ) {
		// The AI returns markdown; convert it to HTML so the product description shows formatted
		// text (headings, lists, bold) instead of raw ## / - / ** markers.
		const html = renderMarkdown( text );
		const tmce = window.tinyMCE && window.tinyMCE.get( 'content' );
		if ( tmce && ! tmce.isHidden() ) {
			tmce.setContent( html );
		} else {
			const ta = document.querySelector( '#content' );
			if ( ta ) {
				setNativeValue( ta, html );
			}
		}
	}

	function el( tag, css, text ) {
		const e = document.createElement( tag );
		if ( css ) {
			e.style.cssText = css;
		}
		if ( text ) {
			e.textContent = text;
		}
		return e;
	}

	function buildPanel() {
		const panel = el(
			'div',
			'position:fixed;right:20px;bottom:20px;width:360px;max-width:92vw;z-index:99999;' +
				'background:#fff;border:1px solid #e4e4e7;border-radius:12px;box-shadow:0 10px 34px rgba(0,0,0,.16);' +
				'font-family:Inter,sans-serif;display:none;overflow:hidden;'
		);

		const head = el(
			'div',
			'background:#3767fb;color:#fff;padding:10px 14px;font-weight:600;font-size:13px;display:flex;justify-content:space-between;align-items:center;'
		);
		head.appendChild( el( 'span', '', 'Generate Product Content' ) );
		const close = el( 'span', 'cursor:pointer;', '✕' );
		close.addEventListener(
			'click',
			() => ( panel.style.display = 'none' )
		);
		head.appendChild( close );

		const body = el( 'div', 'padding:14px;' );
		const ta = el(
			'textarea',
			'width:100%;min-height:80px;border:1px solid #e4e4e7;border-radius:8px;padding:10px;font-size:13px;'
		);
		ta.placeholder = 'Short brief about the product…';

		const gen = el(
			'button',
			'margin-top:10px;width:100%;height:38px;background:#3767fb;color:#fff;border:0;border-radius:8px;font-weight:600;cursor:pointer;',
			'Generate Content'
		);
		const out = el( 'div', 'margin-top:12px;' );

		gen.addEventListener( 'click', () => {
			if ( ! ta.value.trim() ) {
				return;
			}
			gen.disabled = true;
			gen.textContent = 'Generating…';
			out.innerHTML = '';
			fetch(
				( cfg.root || '/wp-json/' ) + 'wpwand/v1/woocommerce/product',
				{
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': cfg.nonce || '',
					},
					credentials: 'same-origin',
					body: JSON.stringify( { prompt: ta.value.trim() } ),
				}
			)
				.then( ( r ) => r.json() )
				.then( ( res ) => {
					if ( res.error ) {
						out.appendChild(
							el(
								'p',
								'color:#b32d2e;font-size:13px;',
								res.error
							)
						);
						return;
					}
					out.appendChild(
						resultBlock( 'Title', res.title, () =>
							insertTitle( res.title )
						)
					);
					out.appendChild(
						resultBlock( 'Description', res.description, () =>
							insertContent( res.description )
						)
					);
				} )
				.catch( ( e ) => window.alert( e.message || 'Error' ) )
				.finally( () => {
					gen.disabled = false;
					gen.textContent = 'Generate Content';
				} );
		} );

		body.appendChild( ta );
		body.appendChild( gen );
		body.appendChild( out );
		panel.appendChild( head );
		panel.appendChild( body );
		document.body.appendChild( panel );
		return panel;
	}

	function resultBlock( label, text, onInsert ) {
		const wrap = el(
			'div',
			'border:1px solid #eef0f3;border-radius:8px;padding:10px;margin-bottom:10px;'
		);
		const top = el(
			'div',
			'display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;'
		);
		top.appendChild(
			el( 'strong', 'font-size:12px;color:#454f5c;', label )
		);
		const ins = el(
			'button',
			'background:#fff;border:1px solid #e4e4e7;border-radius:6px;padding:4px 10px;font-size:12px;cursor:pointer;',
			'Insert'
		);
		ins.addEventListener( 'click', onInsert );
		top.appendChild( ins );
		wrap.appendChild( top );
		wrap.appendChild(
			el(
				'div',
				'font-size:13px;line-height:1.5;color:#080e13;white-space:pre-wrap;',
				text
			)
		);
		return wrap;
	}

	function init() {
		const titleWrap = document.querySelector( '#titlediv' );
		if ( ! titleWrap || document.querySelector( '#wpwand-wc-trigger' ) ) {
			return;
		}
		const panel = buildPanel();
		const btn = el(
			'button',
			'margin:8px 0;display:inline-flex;align-items:center;gap:6px;background:#3767fb;color:#fff;' +
				'border:0;border-radius:6px;padding:7px 14px;font-size:13px;font-weight:600;cursor:pointer;',
			'✨ Generate with AI'
		);
		btn.id = 'wpwand-wc-trigger';
		btn.type = 'button';
		btn.addEventListener( 'click', () => {
			panel.style.display =
				panel.style.display === 'none' ? 'block' : 'none';
		} );
		titleWrap.appendChild( btn );
	}

	let ticks = 0;
	const timer = setInterval( () => {
		init();
		if ( document.querySelector( '#wpwand-wc-trigger' ) || ++ticks > 30 ) {
			clearInterval( timer );
		}
	}, 600 );
	document.addEventListener( 'DOMContentLoaded', init );
} )();
