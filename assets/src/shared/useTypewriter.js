/**
 * Smoothly reveal a growing string.
 *
 * Network streaming arrives in bursts (a few big chunks), and bulk generation lands a whole
 * ~4000-char section at once — both make text "pop" in and the layout jump. This decouples how
 * fast text is *received* from how fast it's *shown*: a requestAnimationFrame loop reveals a few
 * characters per frame, easing faster when the backlog is large so it always catches up. The
 * result reads like steady typing regardless of how the data actually arrives.
 *
 * @param {string} target       Full text received so far (grows over time).
 * @param {Object} opts
 * @param {number} opts.divisor Backlog divider — lower = faster reveal.
 * @param {number} opts.max     Max characters revealed per frame.
 * @param {number} opts.min     Min characters revealed per frame.
 * @return {string} The portion currently revealed.
 */
import { useEffect, useRef, useState } from '@wordpress/element';

export function useTypewriter(
	target,
	{ divisor = 6, max = 12, min = 2 } = {}
) {
	const [ shown, setShown ] = useState( '' );
	const targetRef = useRef( '' );
	const lenRef = useRef( 0 );

	targetRef.current = typeof target === 'string' ? target : '';

	useEffect( () => {
		let raf = 0;
		const loop = () => {
			const t = targetRef.current;
			// New generation / cleared target → restart from the beginning.
			if ( lenRef.current > t.length ) {
				lenRef.current = 0;
			}
			if ( lenRef.current < t.length ) {
				const backlog = t.length - lenRef.current;
				const step = Math.min(
					max,
					Math.max( min, Math.ceil( backlog / divisor ) )
				);
				lenRef.current = Math.min( t.length, lenRef.current + step );
				setShown( t.slice( 0, lenRef.current ) );
			}
			raf = window.requestAnimationFrame( loop );
		};
		raf = window.requestAnimationFrame( loop );
		return () => window.cancelAnimationFrame( raf );
	}, [ divisor, max, min ] );

	return shown;
}
