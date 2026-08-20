/**
 * Local stand-in for `react/jsx-runtime`, built on @wordpress/element.
 *
 * Our own JSX compiles in classic mode (see babel.config.js), but dependencies do not.
 * The `@tanstack/react-query` package ships pre-compiled and imports `react/jsx-runtime`. Left
 * alone, the `@wordpress/dependency-extraction-webpack-plugin` turns that into a `react-jsx-runtime`
 * script dependency, and WordPress only registers that handle from 6.6 — so the bundle never loads
 * on anything older and the screen renders blank with no error the site owner can act on.
 *
 * webpack.config.js aliases `react/jsx-runtime` here instead. Routing through `wp-element` (shipped
 * since WordPress 5.0) rather than bundling React's own runtime also avoids pairing React 18's
 * runtime with the React 16/17 that older cores put on `window.React`.
 *
 * `createElement` already does the work: it lifts `key` and `ref` out of the props object and keeps
 * `props.children` when no child arguments are passed, which is exactly the shape the automatic
 * runtime hands over. `jsxs` differs from `jsx` only in that `children` is an array — and the
 * compiler has already assigned those keys — so one implementation covers both.
 */
import { createElement, Fragment } from '@wordpress/element';

export function jsx( type, config, maybeKey ) {
	return createElement(
		type,
		maybeKey === undefined ? config : { ...config, key: maybeKey }
	);
}

export const jsxs = jsx;

// React calls this one in development builds; it carries extra debug arguments we do not need.
export const jsxDEV = jsx;

export { Fragment };
