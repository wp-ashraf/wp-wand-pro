/**
 * Bulk Posts (Pro) — recreates the legacy flow + design in React:
 *   • List of not-yet-approved generated posts (post_id = 0): checkbox, Title (View modal),
 *     Content, Creation Date, Status, View/Approve/Remove + bulk actions + counter + Create.
 *   • Create = 3-step wizard (Custom | AI headlines → review + settings → confirm).
 * Same backend/table as the legacy page. Recreated from the legacy markup/CSS, not copied.
 */
/* eslint-disable jsx-a11y/label-has-associated-control, jsx-a11y/no-static-element-interactions, jsx-a11y/click-events-have-key-events */
import { useState, useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { api } from '../../shared/apiClient';
import { renderMarkdown } from '../../shared/markdown';
import { useTypewriter } from '../../shared/useTypewriter';

const TONES = [
	'friendly',
	'helpful',
	'informative',
	'aggressive',
	'professional',
	'Formal',
	'Informal',
	'Conversational',
	'Persuasive',
	'Witty',
	'Descriptive',
	'Expository',
	'Humorous',
	'Inspirational',
	'Funny',
	'Poetic',
	'Technical',
	'Argumentative',
	'Instructional',
	'Sarcastic',
	'Urgent',
	'Optimistic',
];

/** Recreates the legacy 8-bar rotating loader (green #21b43e), CSS-animated instead of rAF. */
function Spinner() {
	return (
		<svg
			className="wpwb-spinner"
			viewBox="0 0 48 48"
			width="34"
			height="34"
			aria-hidden="true"
		>
			<g className="wpwb-spinner__g">
				{ Array.from( { length: 8 } ).map( ( _, i ) => (
					<rect
						key={ i }
						x="21"
						y="2"
						width="6"
						height="11"
						rx="3"
						fill="#21b43e"
						opacity={ 0.25 + i * 0.1 }
						transform={ `rotate(${ i * 45 } 24 24)` }
					/>
				) ) }
			</g>
		</svg>
	);
}

const confirmRemove = () =>
	// eslint-disable-next-line no-alert
	window.confirm( __( 'Remove this?', 'wp-wand' ) );

const statusLabel = ( s ) =>
	( {
		done: 'Complete',
		pending: 'Pending',
		publish: 'Complete',
		failed: 'Failed',
	} )[ s ] || s;

function ViewModal( { id, onClose, onRetry } ) {
	// While the post is still generating, poll so the modal shows the content fill in
	// section by section (bulk generates server-side, not token-stream).
	const { data, isLoading } = useQuery( {
		queryKey: [ 'bulk-view', id ],
		queryFn: () => api( `/bulk-posts/${ id }` ),
		refetchInterval: ( query ) => {
			const s = query.state.data?.status;
			return s && s !== 'done' && s !== 'failed' ? 1500 : false;
		},
	} );
	const generating =
		data?.status && data.status !== 'done' && data.status !== 'failed';
	const failed = data?.status === 'failed';
	const content = data?.content || '';
	// While generating, reveal newly-arrived sections as smooth plain text (avoids the whole
	// markdown re-render flashing on every poll). Switch to formatted markdown once done.
	const typed = useTypewriter( generating ? content : '', {
		divisor: 4,
		max: 40,
	} );
	return (
		<div className="wpwb-modal" onClick={ onClose }>
			<div
				className="wpwb-modal__box"
				onClick={ ( e ) => e.stopPropagation() }
			>
				<button className="wpwb-modal__close" onClick={ onClose }>
					×
				</button>
				<h3>{ data?.title }</h3>
				{ generating && (
					<div className="wpwb-viewgen">
						<Spinner />
						{ __( 'Generating… this updates live.', 'wp-wand' ) }
					</div>
				) }
				{ isLoading && <p>{ __( 'Loading…', 'wp-wand' ) }</p> }
				{ ! isLoading && generating && (
					<div className="wpwb-stream-text">
						{ typed || '…' }
						<span className="wpwb-stream-caret" />
					</div>
				) }
				{ ! isLoading && failed && (
					<div className="wpwb-notice wpwb-notice--error">
						<strong>
							{ __( 'Generation failed', 'wp-wand' ) }
						</strong>
						<p>
							{ content ||
								__(
									'This post could not be generated. Please try again.',
									'wp-wand'
								) }
						</p>
						{ onRetry && (
							<button
								className="wpwb-hbtn wpwb-hbtn--approve"
								onClick={ () => onRetry( id ) }
							>
								{ __( 'Retry generation', 'wp-wand' ) }
							</button>
						) }
					</div>
				) }
				{ ! isLoading && ! generating && ! failed && (
					<div
						className="wpwb-md"
						dangerouslySetInnerHTML={ {
							__html: renderMarkdown( content ),
						} }
					/>
				) }
			</div>
		</div>
	);
}

function BulkList( { onCreate } ) {
	const qc = useQueryClient();
	const [ sel, setSel ] = useState( () => new Set() );
	const [ bulkAction, setBulkAction ] = useState( '-1' );
	const [ viewId, setViewId ] = useState( null );

	const { data, isLoading } = useQuery( {
		queryKey: [ 'bulk-list' ],
		queryFn: () => api( '/bulk-posts' ),
		refetchInterval: 8000,
	} );
	const items = data?.items || [];
	const refresh = () => qc.invalidateQueries( { queryKey: [ 'bulk-list' ] } );

	const approve = useMutation( {
		mutationFn: ( id ) =>
			api( `/bulk-posts/${ id }/approve`, { method: 'POST' } ),
		onSuccess: refresh,
	} );
	const remove = useMutation( {
		mutationFn: ( id ) =>
			api( `/bulk-posts/${ id }`, { method: 'DELETE' } ),
		onSuccess: refresh,
	} );
	// Re-queue a failed post so the engine generates it again.
	const retry = useMutation( {
		mutationFn: ( id ) =>
			api( `/bulk-posts/${ id }/retry`, { method: 'POST' } ),
		onSuccess: refresh,
	} );

	const toggle = ( id ) =>
		setSel( ( s ) => {
			const n = new Set( s );
			if ( n.has( id ) ) {
				n.delete( id );
			} else {
				n.add( id );
			}
			return n;
		} );
	const toggleAll = ( on ) =>
		setSel( on ? new Set( items.map( ( i ) => i.id ) ) : new Set() );

	const applyBulk = async () => {
		if ( bulkAction === '-1' || sel.size === 0 ) {
			return;
		}
		for ( const id of [ ...sel ] ) {
			// eslint-disable-next-line no-await-in-loop
			await ( bulkAction === 'approve'
				? approve.mutateAsync( id )
				: remove.mutateAsync( id ) );
		}
		setSel( new Set() );
		setBulkAction( '-1' );
	};

	const generating =
		data?.process_running || items.some( ( i ) => i.status === 'pending' );

	// Browser-heartbeat driver: while this page is open and the step-queue has work, keep
	// POSTing /tick (each call does ONE short step server-side, so nothing times out) and
	// refetch the list for live progress. Resumable: reopening the page picks up pending jobs.
	// The 'action_scheduler' engine runs server-side, so no heartbeat there.
	const ticking = useRef( false );
	useEffect( () => {
		const engine = data?.engine || 'browser';
		if (
			engine === 'action_scheduler' ||
			! data?.process_running ||
			ticking.current
		) {
			return undefined;
		}
		ticking.current = true;
		let cancelled = false;
		( async () => {
			while ( ! cancelled ) {
				let res = null;
				try {
					res = await api( '/bulk-posts/tick', { method: 'POST' } );
				} catch ( e ) {
					res = null;
				}
				await qc.invalidateQueries( { queryKey: [ 'bulk-list' ] } );
				if ( ! res || res.running === false || res.idle ) {
					break;
				}
				// eslint-disable-next-line no-await-in-loop
				await new Promise( ( r ) => setTimeout( r, 400 ) );
			}
			ticking.current = false;
		} )();
		return () => {
			cancelled = true;
			ticking.current = false;
		};
	}, [ data?.process_running, data?.engine, qc ] );

	return (
		<>
			<div className="wpwb-listhead">
				<h1>
					{ __( 'Bulk Posts', 'wp-wand' ) }{ ' ' }
					<small>{ __( 'Beta 1.0.1', 'wp-wand' ) }</small>
				</h1>
				<div className="wpwb-listhead__right">
					<span className="wpwb-counter">
						<strong>
							{ __( 'Bulk Generation Left:', 'wp-wand' ) }
						</strong>{ ' ' }
						{ data?.limit_text ?? '—' }
					</span>
					{ data?.can_create === false ? (
						<a
							className="wpwb-pbtn"
							href="https://wpwand.com/pro-plugin"
							target="_blank"
							rel="noreferrer"
						>
							{ __( 'Upgrade for Unlimited', 'wp-wand' ) }
						</a>
					) : (
						<button className="wpwb-pbtn" onClick={ onCreate }>
							{ __( 'Create Bulk Posts', 'wp-wand' ) }
						</button>
					) }
				</div>
			</div>

			{ generating && (
				<div className="wpwb-genhead">
					<Spinner />
					<div className="wpwb-genhead__text">
						<strong>
							{ __( 'Generating Bulk Post…', 'wp-wand' ) }
						</strong>
						<p>
							{ __(
								'You can leave this page if you want. Posts are generating in the background.',
								'wp-wand'
							) }
						</p>
					</div>
				</div>
			) }

			<div className="wpwb-bulkbar">
				<select
					value={ bulkAction }
					onChange={ ( e ) => setBulkAction( e.target.value ) }
				>
					<option value="-1">
						{ __( 'Bulk actions', 'wp-wand' ) }
					</option>
					<option value="approve">
						{ __( 'Approve', 'wp-wand' ) }
					</option>
					<option value="delete">
						{ __( 'Delete', 'wp-wand' ) }
					</option>
				</select>
				<button
					className="wpwb-hbtn wpwb-hbtn--approve"
					style={ { marginLeft: 0 } }
					onClick={ applyBulk }
				>
					{ __( 'Apply', 'wp-wand' ) }
				</button>
			</div>

			{ isLoading ? (
				<p>{ __( 'Loading…', 'wp-wand' ) }</p>
			) : (
				<table className="wpwb-table">
					<thead>
						<tr>
							<th className="wpwb-cb">
								<input
									type="checkbox"
									checked={
										items.length > 0 &&
										sel.size === items.length
									}
									onChange={ ( e ) =>
										toggleAll( e.target.checked )
									}
								/>
							</th>
							<th>{ __( 'Title', 'wp-wand' ) }</th>
							<th>{ __( 'Content', 'wp-wand' ) }</th>
							<th>{ __( 'Creation Date', 'wp-wand' ) }</th>
							<th>{ __( 'Status', 'wp-wand' ) }</th>
							<th />
						</tr>
					</thead>
					<tbody>
						{ items.length === 0 && (
							<tr>
								<td colSpan="6" className="wpwb-empty">
									{ __(
										'No bulk posts yet. Click “Create Bulk Posts”.',
										'wp-wand'
									) }
								</td>
							</tr>
						) }
						{ items.map( ( it ) => (
							<tr key={ it.id }>
								<td className="wpwb-cb">
									<input
										type="checkbox"
										checked={ sel.has( it.id ) }
										onChange={ () => toggle( it.id ) }
									/>
								</td>
								<td>
									<button
										className="wpwb-linktext"
										onClick={ () => setViewId( it.id ) }
									>
										{ it.title }
									</button>
								</td>
								<td className="wpwb-content">
									{ it.preview }…
								</td>
								<td className="wpwb-date">{ it.date }</td>
								<td>
									<span
										className={ `wpwb-badge wpwb-badge--${ it.status }` }
									>
										{ statusLabel( it.status ) }
									</span>
								</td>
								<td className="wpwb-rowactions">
									<button
										className="wpwb-hbtn"
										onClick={ () => setViewId( it.id ) }
									>
										{ __( 'View', 'wp-wand' ) }
									</button>
									{ it.status === 'failed' ? (
										<button
											className="wpwb-hbtn wpwb-hbtn--approve"
											disabled={ retry.isPending }
											onClick={ () =>
												retry.mutate( it.id )
											}
										>
											{ __( 'Retry', 'wp-wand' ) }
										</button>
									) : (
										<button
											className="wpwb-hbtn wpwb-hbtn--approve"
											disabled={ approve.isPending }
											onClick={ () =>
												approve.mutate( it.id )
											}
										>
											{ __( 'Approve', 'wp-wand' ) }
										</button>
									) }
									<button
										className="wpwb-hbtn wpwb-hbtn--delete"
										onClick={ () =>
											confirmRemove() &&
											remove.mutate( it.id )
										}
									>
										{ __( 'Remove', 'wp-wand' ) }
									</button>
								</td>
							</tr>
						) ) }
					</tbody>
				</table>
			) }

			{ viewId && (
				<ViewModal
					id={ viewId }
					onClose={ () => setViewId( null ) }
					onRetry={ ( id ) => {
						retry.mutate( id );
						setViewId( null );
					} }
				/>
			) }
		</>
	);
}

function Wizard( { onDone } ) {
	const qc = useQueryClient();
	const [ step, setStep ] = useState( 1 );
	const [ tab, setTab ] = useState( 'ai' );
	const [ topic, setTopic ] = useState( '' );
	const [ count, setCount ] = useState( 3 );
	const [ customText, setCustomText ] = useState( '' );
	const [ titles, setTitles ] = useState( [] );
	const [ chosen, setChosen ] = useState( () => new Set() );
	const [ keyword, setKeyword ] = useState( '' );
	const [ tone, setTone ] = useState( 'professional' );
	const [ wordCount, setWordCount ] = useState( 0 );
	const [ toc, setToc ] = useState( true );
	const [ faq, setFaq ] = useState( true );
	const [ error, setError ] = useState( null );

	const gen = useMutation( {
		mutationFn: () =>
			api( '/bulk-posts/titles', {
				method: 'POST',
				data: { topic, count },
			} ),
		onSuccess: ( res ) =>
			res.error ? setError( res.error ) : loadTitles( res.titles || [] ),
		onError: ( e ) => setError( e?.message ),
	} );

	const queue = useMutation( {
		mutationFn: () =>
			api( '/bulk-posts', {
				method: 'POST',
				data: {
					titles: [ ...chosen ],
					tone,
					keyword,
					word_count: wordCount,
					toc_include: toc,
					faq_include: faq,
				},
			} ),
		onSuccess: ( res ) => {
			if ( res.error ) {
				setError( res.error );
				return;
			}
			// Force the list to refetch immediately so the new pending rows + the
			// "Generating Bulk Post…" header show without a manual page refresh.
			qc.invalidateQueries( { queryKey: [ 'bulk-list' ] } );
			onDone();
		},
		onError: ( e ) => setError( e?.message ),
	} );

	const loadTitles = ( list ) => {
		setTitles( list );
		setChosen( new Set( list ) );
		setStep( 2 );
	};

	const nextFromStep1 = () => {
		setError( null );
		if ( tab === 'custom' ) {
			const list = customText
				.split( '\n' )
				.map( ( l ) => l.trim() )
				.filter( Boolean );
			if ( ! list.length ) {
				setError( __( 'Add at least one headline.', 'wp-wand' ) );
				return;
			}
			loadTitles( list );
		} else if ( ! topic.trim() ) {
			setError( __( 'Enter a topic.', 'wp-wand' ) );
		} else {
			gen.mutate();
		}
	};

	const toggleTitle = ( t ) =>
		setChosen( ( s ) => {
			const n = new Set( s );
			if ( n.has( t ) ) {
				n.delete( t );
			} else {
				n.add( t );
			}
			return n;
		} );

	return (
		<>
			<div className="wpwb-createhead">
				<h1>{ __( 'Create Bulk Posts', 'wp-wand' ) }</h1>
				<button className="wpwb-back" onClick={ onDone }>
					{ __( 'Back to list', 'wp-wand' ) }
				</button>
			</div>

			<div className="wpwb-pgs">
				<div className="wpwb-steps">
					{ [
						[
							__( 'Step 1', 'wp-wand' ),
							__( 'Add info', 'wp-wand' ),
						],
						[
							__( 'Step 2', 'wp-wand' ),
							__( 'Review titles', 'wp-wand' ),
						],
						[
							__( 'Step 3', 'wp-wand' ),
							__( 'Confirmation', 'wp-wand' ),
						],
					].map( ( [ a, b ], i ) => (
						<div
							key={ i }
							className={ `wpwb-step${
								step === i + 1 ? ' is-active' : ''
							}` }
						>
							<strong>{ a }</strong>
							<span>{ b }</span>
						</div>
					) ) }
				</div>

				<div className="wpwb-stepbody">
					{ error && <div className="wpwb-notice">{ error }</div> }

					{ step === 1 && (
						<>
							<div className="wpwb-ntabs">
								<button
									className={
										tab === 'custom' ? 'is-active' : ''
									}
									onClick={ () => setTab( 'custom' ) }
								>
									{ __( 'Custom Headlines', 'wp-wand' ) }
								</button>
								<button
									className={
										tab === 'ai' ? 'is-active' : ''
									}
									onClick={ () => setTab( 'ai' ) }
								>
									{ __(
										'AI Generated Headlines',
										'wp-wand'
									) }
								</button>
							</div>

							{ tab === 'custom' ? (
								<div className="wpwb-pgf-row">
									<div className="wpwb-pgf-label">
										<label>
											{ __(
												'Add Your Own Headlines',
												'wp-wand'
											) }
										</label>
										<p>
											{ __(
												'Enter each headline in a single line. You can add as many as you want.',
												'wp-wand'
											) }
										</p>
									</div>
									<div className="wpwb-pgf-field">
										<textarea
											placeholder={ __(
												'Your first headline goes here.',
												'wp-wand'
											) }
											value={ customText }
											onChange={ ( e ) =>
												setCustomText( e.target.value )
											}
										/>
									</div>
								</div>
							) : (
								<>
									<div className="wpwb-pgf-row">
										<div className="wpwb-pgf-label">
											<label>
												{ __( 'Topic', 'wp-wand' ) }
											</label>
											<p>
												{ __(
													'Add a topic of your bulk post',
													'wp-wand'
												) }
											</p>
										</div>
										<div className="wpwb-pgf-field">
											<input
												type="text"
												placeholder={ __(
													'Digital Marketing',
													'wp-wand'
												) }
												value={ topic }
												onChange={ ( e ) =>
													setTopic( e.target.value )
												}
											/>
										</div>
									</div>
									<div className="wpwb-pgf-row">
										<div className="wpwb-pgf-label">
											<label>
												{ __(
													'Number of Posts',
													'wp-wand'
												) }
											</label>
											<p>
												{ __(
													'How many posts do you want to generate at once, maximum 20 at a time.',
													'wp-wand'
												) }
											</p>
										</div>
										<div className="wpwb-pgf-field">
											<input
												type="number"
												min="1"
												max="20"
												value={ count }
												onChange={ ( e ) =>
													setCount(
														Math.max(
															1,
															Math.min(
																20,
																Number(
																	e.target
																		.value
																) || 1
															)
														)
													)
												}
											/>
										</div>
									</div>
								</>
							) }

							<div className="wpwb-formrow">
								<button
									className="wpwb-pbtn"
									disabled={ gen.isPending }
									onClick={ nextFromStep1 }
								>
									{ gen.isPending
										? __( 'Generating…', 'wp-wand' )
										: __( 'Next', 'wp-wand' ) }
								</button>
							</div>
						</>
					) }

					{ step === 2 && (
						<>
							<div className="wpwb-info">
								{ topic && (
									<div>
										<strong>
											{ __( 'Topic:', 'wp-wand' ) }
										</strong>
										<span>{ topic }</span>
									</div>
								) }
								<div>
									<strong>
										{ __( 'Number of Posts:', 'wp-wand' ) }
									</strong>
									<span>{ titles.length }</span>
								</div>
							</div>

							<div className="wpwb-step2">
								<div className="wpwb-titlesbox">
									<h4>
										{ chosen.size }{ ' ' }
										{ __( 'Titles Generated', 'wp-wand' ) }
									</h4>
									<ul className="wpwb-titles">
										{ titles.map( ( t, i ) => (
											<li key={ i }>
												<label>
													<input
														type="checkbox"
														checked={ chosen.has(
															t
														) }
														onChange={ () =>
															toggleTitle( t )
														}
													/>
													{ t }
												</label>
											</li>
										) ) }
									</ul>
								</div>

								<div className="wpwb-settings">
									<div className="wpwb-fld">
										<label>
											{ __(
												'Keywords to Include',
												'wp-wand'
											) }{ ' ' }
											<span className="wpwb-opt">
												({ __( 'Optional', 'wp-wand' ) }
												)
											</span>
										</label>
										<textarea
											placeholder={ __(
												'Write keyword and separate using comma',
												'wp-wand'
											) }
											value={ keyword }
											onChange={ ( e ) =>
												setKeyword( e.target.value )
											}
										/>
									</div>
									<div className="wpwb-fld">
										<label>
											{ __( 'Writing Tone', 'wp-wand' ) }{ ' ' }
											<span className="wpwb-opt">
												({ __( 'Optional', 'wp-wand' ) }
												)
											</span>
										</label>
										<select
											value={ tone }
											onChange={ ( e ) =>
												setTone( e.target.value )
											}
										>
											{ TONES.map( ( t ) => (
												<option key={ t } value={ t }>
													{ t
														.charAt( 0 )
														.toUpperCase() +
														t.slice( 1 ) }
												</option>
											) ) }
										</select>
									</div>
									<div className="wpwb-fld">
										<label>
											{ __(
												'Target Word Count',
												'wp-wand'
											) }{ ' ' }
											<span className="wpwb-opt">
												({ __( 'Optional', 'wp-wand' ) }
												)
											</span>
										</label>
										<input
											type="number"
											min="0"
											max="8000"
											step="100"
											placeholder={ __(
												'e.g. 1500 — long posts are written section by section',
												'wp-wand'
											) }
											value={ wordCount || '' }
											onChange={ ( e ) =>
												setWordCount(
													Math.max(
														0,
														Math.min(
															8000,
															Number(
																e.target.value
															) || 0
														)
													)
												)
											}
										/>
									</div>
									<label className="wpwb-check">
										<input
											type="checkbox"
											checked={ toc }
											onChange={ ( e ) =>
												setToc( e.target.checked )
											}
										/>
										{ __(
											'Include Table of Content (TOC)',
											'wp-wand'
										) }
									</label>
									<label className="wpwb-check">
										<input
											type="checkbox"
											checked={ faq }
											onChange={ ( e ) =>
												setFaq( e.target.checked )
											}
										/>
										{ __(
											'Include FAQ section at bottom',
											'wp-wand'
										) }
									</label>
								</div>
							</div>

							<div className="wpwb-formrow">
								<button
									className="wpwb-back"
									onClick={ () => setStep( 1 ) }
								>
									{ __( 'Back', 'wp-wand' ) }
								</button>
								<button
									className="wpwb-pbtn"
									disabled={ ! chosen.size }
									onClick={ () => setStep( 3 ) }
								>
									{ __( 'Next', 'wp-wand' ) }
								</button>
							</div>
						</>
					) }

					{ step === 3 && (
						<>
							<div className="wpwb-info">
								{ topic && (
									<div>
										<strong>
											{ __( 'Topic:', 'wp-wand' ) }
										</strong>
										<span>{ topic }</span>
									</div>
								) }
								<div>
									<strong>
										{ __(
											'Number of Titles Selected:',
											'wp-wand'
										) }
									</strong>
									<span>{ chosen.size }</span>
								</div>
							</div>
							<p className="wpwb-confirm-text">
								{ __(
									'On your confirmation, your post generation will start in the background.',
									'wp-wand'
								) }
							</p>
							<div className="wpwb-formrow">
								<button
									className="wpwb-back"
									onClick={ () => setStep( 2 ) }
								>
									{ __( 'Back', 'wp-wand' ) }
								</button>
								<button
									className="wpwb-pbtn"
									disabled={ queue.isPending }
									onClick={ () => queue.mutate() }
								>
									{ queue.isPending
										? __( 'Starting…', 'wp-wand' )
										: __(
												'Start Generating Posts',
												'wp-wand'
										  ) }
								</button>
							</div>
						</>
					) }
				</div>
			</div>
		</>
	);
}

export default function App() {
	const [ view, setView ] = useState( 'list' );
	return (
		<div className="wpwand-bulk">
			{ view === 'list' ? (
				<BulkList onCreate={ () => setView( 'create' ) } />
			) : (
				<Wizard onDone={ () => setView( 'list' ) } />
			) }
		</div>
	);
}
