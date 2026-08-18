/**
 * License (Pro) — React rebuild of the legacy TALA activation modal.
 *
 * Shows the current activation status + plan tier, and lets an admin activate a key or deactivate.
 * Talks to the wpwand/v1/license endpoints, which wrap the existing TALA mechanism (same options,
 * same remote server) — so activation state is fully compatible with the legacy modal.
 */
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '../../shared/apiClient';
import LoadingSkeleton from '../../shared/LoadingSkeleton';

const TIER_LABELS = {
	agency: __( 'Agency', 'wp-wand' ),
	growth: __( 'Growth', 'wp-wand' ),
	solo: __( 'Solo', 'wp-wand' ),
	pro: __( 'Pro', 'wp-wand' ),
	none: __( 'Not activated', 'wp-wand' ),
};

/**
 * Plugin update box — shows the installed version, lets the admin re-check against the server,
 * and surfaces an "update available" link to the WordPress Plugins screen (where the update,
 * injected by UpdateChecker into site_transient_update_plugins, can be applied).
 */
function UpdateBox() {
	const qc = useQueryClient();
	const { data, isLoading } = useQuery( {
		queryKey: [ 'license-update' ],
		queryFn: () => api( '/license/update' ),
	} );

	const checkMut = useMutation( {
		mutationFn: () => api( '/license/update', { method: 'POST' } ),
		onSuccess: ( res ) => qc.setQueryData( [ 'license-update' ], res ),
	} );

	const available = data && data.update_available;

	return (
		<div className="wpwl-card wpwl-update">
			<div className="wpwl-update__row">
				<div>
					<div className="wpwl-update__title">
						{ __( 'Plugin updates', 'wp-wand' ) }
					</div>
					<div className="wpwl-update__sub">
						{ isLoading
							? __( 'Checking…', 'wp-wand' )
							: __( 'Installed version:', 'wp-wand' ) +
							  ' ' +
							  ( data?.current || '—' ) +
							  ( available
									? ' · ' +
									  __( 'Latest:', 'wp-wand' ) +
									  ' ' +
									  data.latest
									: '' ) }
					</div>
				</div>
				<button
					className="wpwl-btn wpwl-btn--ghost"
					disabled={ checkMut.isPending }
					onClick={ () => checkMut.mutate() }
				>
					{ checkMut.isPending
						? __( 'Checking…', 'wp-wand' )
						: __( 'Check for updates', 'wp-wand' ) }
				</button>
			</div>

			{ available ? (
				<div className="wpwl-update__avail">
					<span>
						{ __( 'A new version', 'wp-wand' ) } ({ data.latest }){ ' ' }
						{ __( 'is available.', 'wp-wand' ) }
					</span>
					<a
						className="wpwl-btn wpwl-btn--primary"
						href={ data.updates_url }
					>
						{ __( 'Go to updates', 'wp-wand' ) }
					</a>
				</div>
			) : (
				! isLoading &&
				! checkMut.isPending && (
					<div className="wpwl-update__ok">
						{ __( 'You’re on the latest version.', 'wp-wand' ) }
					</div>
				)
			) }
		</div>
	);
}

export default function App() {
	const qc = useQueryClient();
	const [ key, setKey ] = useState( '' );
	const [ error, setError ] = useState( '' );

	const { data: status, isLoading } = useQuery( {
		queryKey: [ 'license' ],
		queryFn: () => api( '/license' ),
	} );

	const refresh = ( fresh ) => {
		qc.setQueryData( [ 'license' ], fresh );
		qc.invalidateQueries( { queryKey: [ 'license' ] } );
	};

	const activateMut = useMutation( {
		mutationFn: () =>
			api( '/license/activate', { method: 'POST', data: { key } } ),
		onSuccess: ( res ) => {
			if ( res && res.error ) {
				setError( res.error );
				return;
			}
			// Activated, but the Pro templates didn't come down. Not a failure — say so plainly
			// rather than showing a clean success over a half-finished state.
			setError( res && res.warning ? res.warning : '' );
			setKey( '' );
			refresh( res );
		},
		onError: ( e ) =>
			setError(
				e?.error || e?.message || __( 'Activation failed.', 'wp-wand' )
			),
	} );

	const deactivateMut = useMutation( {
		mutationFn: () => api( '/license/deactivate', { method: 'POST' } ),
		onSuccess: ( res ) => {
			setError( '' );
			refresh( res );
		},
	} );

	// Initial page-load: continue the server-painted skeleton until license status arrives.
	if ( isLoading ) {
		return (
			<LoadingSkeleton
				title={ __( 'WP Wand License', 'wp-wand' ) }
				rows={ 3 }
			/>
		);
	}

	const active = status && status.active;
	const tier = status ? status.tier : 'none';

	return (
		<div className="wpwl">
			<div className="wpwl__header">
				<span className="wpwl__logo" aria-hidden="true">
					✦
				</span>
				<h1 className="wpwl__title">
					{ __( 'WP Wand License', 'wp-wand' ) }
				</h1>
			</div>
			{ /* Anchor so WP relocates admin notices below the header, not into the flex row. */ }
			<hr className="wp-header-end" />

			<div className="wpwl-card">
				<>
						<div className="wpwl-status">
							<span
								className={
									'wpwl-dot ' +
									( active ? 'is-on' : 'is-off' )
								}
							/>
							<div>
								<div className="wpwl-status__line">
									{ active
										? __(
												'Your license is active',
												'wp-wand'
										  )
										: __( 'No active license', 'wp-wand' ) }
								</div>
								<div className="wpwl-status__sub">
									{ active
										? __( 'Plan:', 'wp-wand' ) +
										  ' ' +
										  ( TIER_LABELS[ tier ] || tier )
										: __(
												'Enter your purchase key to unlock the Pro features.',
												'wp-wand'
										  ) }
								</div>
							</div>
						</div>

						{ error && <div className="wpwl-error">{ error }</div> }

						{ active ? (
							<div className="wpwl-active">
								<div className="wpwl-keyrow">
									<span className="wpwl-keymask">
										{ status.key_masked || '••••••••' }
									</span>
								</div>
								<button
									className="wpwl-btn wpwl-btn--ghost"
									disabled={ deactivateMut.isPending }
									onClick={ () => deactivateMut.mutate() }
								>
									{ deactivateMut.isPending
										? __( 'Deactivating…', 'wp-wand' )
										: __(
												'Deactivate license',
												'wp-wand'
										  ) }
								</button>
							</div>
						) : (
							<form
								className="wpwl-form"
								onSubmit={ ( e ) => {
									e.preventDefault();
									activateMut.mutate();
								} }
							>
								<input
									className="wpwl-input"
									type="text"
									value={ key }
									placeholder={ __(
										'Paste your license key',
										'wp-wand'
									) }
									onChange={ ( e ) =>
										setKey( e.target.value )
									}
								/>
								<button
									className="wpwl-btn wpwl-btn--primary"
									type="submit"
									disabled={
										activateMut.isPending ||
										key.trim() === ''
									}
								>
									{ activateMut.isPending
										? __( 'Activating…', 'wp-wand' )
										: __( 'Activate', 'wp-wand' ) }
								</button>
							</form>
						) }
				</>
			</div>

			{ active && <UpdateBox /> }

			<p className="wpwl-help">
				{ __(
					'Your license key was emailed to you after purchase. Need help?',
					'wp-wand'
				) }{ ' ' }
				<a
					href="https://wpwand.com/contact"
					target="_blank"
					rel="noreferrer"
				>
					{ __( 'Contact support', 'wp-wand' ) }
				</a>
			</p>
		</div>
	);
}
