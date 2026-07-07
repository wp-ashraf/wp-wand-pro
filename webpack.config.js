/**
 * Pro plugin webpack config — mirrors the free plugin's setup but only builds the Pro feature apps.
 *
 * Each Pro screen is a separate entry; @wordpress/scripts externalizes wp.* and emits a *.asset.php
 * per entry. The PHP pages enqueue these from WPWANDPRO_NEW_URL . 'build/'.
 */
const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

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
};
