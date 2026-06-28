/**
 * Thin REST client for the wpwand/v1 API.
 *
 * Wraps @wordpress/api-fetch so every call carries the logged-in user's cookie + REST
 * nonce automatically. The nonce middleware is configured here from data localized by
 * PHP (wpwandApi.nonce / wpwandApi.root).
 */
import apiFetch from '@wordpress/api-fetch';

const config =
	typeof window !== 'undefined' && window.wpwandApi ? window.wpwandApi : {};

if ( config.nonce ) {
	apiFetch.use( apiFetch.createNonceMiddleware( config.nonce ) );
}
if ( config.root ) {
	apiFetch.use( apiFetch.createRootURLMiddleware( config.root ) );
}

/**
 * Call a wpwand/v1 endpoint.
 *
 * @param {string} path    Path relative to the namespace, e.g. '/settings'.
 * @param {Object} options apiFetch options (method, data, …).
 * @return {Promise<any>} Parsed JSON response.
 */
export function api( path, options = {} ) {
	return apiFetch( { path: `/wpwand/v1${ path }`, ...options } );
}
