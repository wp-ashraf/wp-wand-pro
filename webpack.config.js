/**
 * Pro plugin webpack config — mirrors the free plugin's setup but only builds the Pro feature apps.
 *
 * Each Pro screen is a separate entry; @wordpress/scripts externalizes wp.* and emits a *.asset.php
 * per entry. The PHP pages enqueue these from WPWANDPRO_NEW_URL . 'build/'.
 */
const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const DependencyExtractionWebpackPlugin = require( '@wordpress/dependency-extraction-webpack-plugin' );

module.exports = {
	...defaultConfig,
	entry: {
		license: path.resolve( __dirname, 'assets/src/apps/license/index.js' ),
		seo: path.resolve( __dirname, 'assets/src/apps/seo/index.js' ),
		woocommerce: path.resolve(
			__dirname,
			'assets/src/apps/woocommerce/index.js'
		),
	},
	output: {
		...defaultConfig.output,
		path: path.resolve( __dirname, 'build' ),
	},
	// Same reasoning as the free plugin: the default mapping sends `react/jsx-runtime` to the
	// `react-jsx-runtime` script handle, which WordPress only registers from 6.6, and a bundle
	// carrying it renders a blank screen on anything older. Keep the two configs in step.
	plugins: [
		...defaultConfig.plugins.filter(
			( plugin ) =>
				plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
		),
		new DependencyExtractionWebpackPlugin( {
			injectPolyfill: false,
			requestToExternal( request ) {
				if (
					request === 'react/jsx-runtime' ||
					request === 'react/jsx-dev-runtime'
				) {
					return false;
				}
				return undefined;
			},
		} ),
	],
	resolve: {
		...defaultConfig.resolve,
		alias: {
			...( defaultConfig.resolve && defaultConfig.resolve.alias ),
			'react/jsx-runtime': path.resolve(
				__dirname,
				'assets/src/shared/jsx-runtime.js'
			),
			'react/jsx-dev-runtime': path.resolve(
				__dirname,
				'assets/src/shared/jsx-runtime.js'
			),
		},
	},
};
