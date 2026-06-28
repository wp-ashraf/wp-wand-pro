/**
 * Markdown → safe HTML for rendering generated results.
 *
 * Uses `marked`, then a light DOM sanitizer (admin context, own-provider content): drops
 * dangerous elements and strips event-handler and javascript: attributes. Display only —
 * editor insertion goes through wp.blocks.pasteHandler, which is markdown-aware itself.
 */
import { marked } from 'marked';

marked.setOptions( { breaks: true, gfm: true } );

const BAD_TAGS = 'script,style,iframe,object,embed,link,meta,form,base';

function sanitize( html ) {
	const tpl = document.createElement( 'template' );
	tpl.innerHTML = html;

	tpl.content.querySelectorAll( BAD_TAGS ).forEach( ( n ) => n.remove() );

	tpl.content.querySelectorAll( '*' ).forEach( ( el ) => {
		[ ...el.attributes ].forEach( ( attr ) => {
			const name = attr.name.toLowerCase();
			const value = ( attr.value || '' ).trim();
			if (
				name.startsWith( 'on' ) ||
				( [ 'href', 'src', 'xlink:href' ].includes( name ) &&
					/^(javascript|vbscript|data):/i.test( value ) )
			) {
				el.removeAttribute( attr.name );
			}
		} );
	} );

	return tpl.innerHTML;
}

export function renderMarkdown( text ) {
	return sanitize( marked.parse( text || '' ) );
}
