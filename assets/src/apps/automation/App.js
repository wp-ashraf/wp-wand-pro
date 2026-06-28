/**
 * Automation (Pro) — schedule recurring AI post generation.
 *
 * List of schedules (cadence, source, next run, on/off, run-now/edit/delete) + a create/edit form.
 * Each schedule runs unattended via WP-Cron: it queues titles on the generation engine and the
 * finished posts are created as draft/pending/published automatically. New screen — no legacy
 * equivalent — but styled to match the rest of the React admin (settings/bulk).
 */
/* eslint-disable jsx-a11y/label-has-associated-control */
import { useState, useEffect, useRef, Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '../../shared/apiClient';

const FREQUENCIES = [
	{ value: 'hourly', label: __( 'Every hour', 'wp-wand' ) },
	{ value: 'daily', label: __( 'Every day', 'wp-wand' ) },
	{ value: 'weekly', label: __( 'Every week', 'wp-wand' ) },
];

const STATUSES = [
	{ value: 'draft', label: __( 'Draft', 'wp-wand' ) },
	{ value: 'pending', label: __( 'Pending review', 'wp-wand' ) },
	{ value: 'publish', label: __( 'Publish', 'wp-wand' ) },
];

const TONES = [
	'',
	'friendly',
	'professional',
	'informative',
	'conversational',
	'persuasive',
	'witty',
	'inspirational',
];

// Module-level so the no-alert disable comment can't drift onto the wrong line after formatting.
function confirmDelete() {
	// eslint-disable-next-line no-alert
	return window.confirm( __( 'Delete this schedule?', 'wp-wand' ) );
}

const freqLabel = ( v ) =>
	( FREQUENCIES.find( ( f ) => f.value === v ) || {} ).label || v;
const statusLabel = ( v ) =>
	( STATUSES.find( ( s ) => s.value === v ) || {} ).label || v;

function blankForm() {
	return {
		name: '',
		enabled: true,
		frequency: 'daily',
		mode: 'list',
		topics: '',
		subject: '',
		loop: true,
		count: 1,
		post_status: 'draft',
		tone: '',
		keyword: '',
		word_count: 0,
		language: '',
		toc_include: false,
		faq_include: false,
	};
}

// A stored schedule → form shape (topics array → newline text).
function toForm( s ) {
	return {
		...blankForm(),
		...s,
		topics: Array.isArray( s.topics ) ? s.topics.join( '\n' ) : '',
	};
}

// Form shape → API payload (topics text → array).
function toPayload( f ) {
	return {
		...f,
		count: Number( f.count ) || 1,
		word_count: Number( f.word_count ) || 0,
		topics: f.topics
			.split( '\n' )
			.map( ( t ) => t.trim() )
			.filter( Boolean ),
	};
}

export default function App() {
	const qc = useQueryClient();
	const [ editing, setEditing ] = useState( null ); // null = list, object = form
	const [ error, setError ] = useState( '' );
	const [ notice, setNotice ] = useState( '' );

	const { data, isLoading } = useQuery( {
		queryKey: [ 'automation' ],
		queryFn: () => api( '/automation/schedules' ),
	} );

	const invalidate = () =>
		qc.invalidateQueries( { queryKey: [ 'automation' ] } );

	// Live queue state — drives the heartbeat + the "generating" banner so the page reflects
	// progress (new posts, statuses) without a manual refresh, even after reloading mid-run.
	const queue = data?.meta?.queue || {};
	const running = !! queue.running;

	// Browser heartbeat: while the queue has work, advance it one step at a time and refresh the
	// list so generated posts appear live. Mirrors the bulk page's heartbeat driver.
	const ticking = useRef( false );
	useEffect( () => {
		if ( ! running || ticking.current ) {
			return undefined;
		}
		ticking.current = true;
		let cancelled = false;
		( async () => {
			let busy = true;
			while ( ! cancelled && busy ) {
				// eslint-disable-next-line no-await-in-loop
				const snap = await api( '/automation/schedules/tick', {
					method: 'POST',
				} );
				busy = !! snap.running;
				// eslint-disable-next-line no-await-in-loop
				await invalidate();
			}
			ticking.current = false;
		} )();
		return () => {
			cancelled = true;
			ticking.current = false;
		};
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ running ] );

	const saveMut = useMutation( {
		mutationFn: ( payload ) =>
			api( '/automation/schedules', { method: 'POST', data: payload } ),
		onSuccess: ( res ) => {
			if ( res && res.error ) {
				setError( res.error );
				return;
			}
			setEditing( null );
			setError( '' );
			invalidate();
		},
		onError: ( e ) => setError( e.message || 'Save failed' ),
	} );

	const delMut = useMutation( {
		mutationFn: ( id ) =>
			api( `/automation/schedules/${ id }`, { method: 'DELETE' } ),
		onSuccess: invalidate,
	} );

	// Run = enqueue only; the heartbeat above then advances the queue and the list updates live.
	const runMut = useMutation( {
		mutationFn: ( id ) =>
			api( `/automation/schedules/${ id }/run`, { method: 'POST' } ),
		onSuccess: ( r ) => {
			setNotice(
				r && r.queued === 0
					? __(
							'Nothing to generate — the topic list may be finished.',
							'wp-wand'
					  )
					: ''
			);
			invalidate();
		},
	} );

	const publishMut = useMutation( {
		mutationFn: ( postId ) =>
			api( `/automation/post/${ postId }/publish`, { method: 'POST' } ),
		onSuccess: invalidate,
	} );

	const schedules = ( data && data.schedules ) || [];

	return (
		<div className="wpwa">
			<div className="wpwa__header">
				<div>
					<h1 className="wpwa__title">
						{ __( 'Automation', 'wp-wand' ) }
					</h1>
					<p className="wpwa__sub">
						{ __(
							'Generate posts on a recurring schedule. Posts are created automatically — no need to keep a tab open.',
							'wp-wand'
						) }
					</p>
				</div>
				{ ! editing && (
					<button
						className="wpwa-btn wpwa-btn--primary"
						onClick={ () => {
							setError( '' );
							setEditing( blankForm() );
						} }
					>
						{ __( '+ New schedule', 'wp-wand' ) }
					</button>
				) }
			</div>

			{ running && (
				<div className="wpwa-notice wpwa-notice--live">
					<span className="wpwa-spinner" />
					{ __( 'Generating…', 'wp-wand' ) } { queue.remaining || 0 }{ ' ' }
					{ __( 'post(s) remaining', 'wp-wand' ) }
				</div>
			) }

			{ ! running && notice && (
				<div className="wpwa-notice">
					{ notice }
					<button
						className="wpwa-notice__x"
						onClick={ () => setNotice( '' ) }
						aria-label={ __( 'Dismiss', 'wp-wand' ) }
					>
						×
					</button>
				</div>
			) }

			{ editing ? (
				<ScheduleForm
					form={ editing }
					setForm={ setEditing }
					onCancel={ () => {
						setEditing( null );
						setError( '' );
					} }
					onSave={ () => saveMut.mutate( toPayload( editing ) ) }
					saving={ saveMut.isPending }
					error={ error }
				/>
			) : (
				<ScheduleList
					schedules={ schedules }
					loading={ isLoading }
					onEdit={ ( s ) => {
						setError( '' );
						setEditing( toForm( s ) );
					} }
					onDelete={ ( id ) => delMut.mutate( id ) }
					onRun={ ( id ) => runMut.mutate( id ) }
					runningId={ runMut.isPending ? runMut.variables : null }
					onPublish={ ( pid ) => publishMut.mutate( pid ) }
					publishingId={
						publishMut.isPending ? publishMut.variables : null
					}
				/>
			) }
		</div>
	);
}

function PostList( { posts, onPublish, publishingId } ) {
	if ( ! posts || ! posts.length ) {
		return (
			<div className="wpwa-posts wpwa-posts--empty">
				{ __(
					'No posts generated yet. Use “Run now” or wait for the next scheduled run.',
					'wp-wand'
				) }
			</div>
		);
	}
	return (
		<ul className="wpwa-posts">
			{ posts.map( ( po ) => (
				<li key={ po.id } className="wpwa-posts__item">
					<span className="wpwa-posts__title">{ po.title }</span>
					<span
						className={
							'wpwa-pill ' +
							( po.status === 'publish'
								? 'wpwa-pill--on'
								: 'wpwa-pill--off' )
						}
					>
						{ po.status }
					</span>
					<span className="wpwa-posts__date">{ po.date }</span>
					<span className="wpwa-posts__actions">
						<a
							className="wpwa-link"
							href={ po.edit_url }
							target="_blank"
							rel="noreferrer"
						>
							{ __( 'Edit', 'wp-wand' ) }
						</a>
						{ po.status === 'publish' ? (
							<a
								className="wpwa-link"
								href={ po.view_url }
								target="_blank"
								rel="noreferrer"
							>
								{ __( 'View', 'wp-wand' ) }
							</a>
						) : (
							<button
								className="wpwa-link"
								disabled={ publishingId === po.id }
								onClick={ () => onPublish( po.id ) }
							>
								{ publishingId === po.id
									? __( 'Publishing…', 'wp-wand' )
									: __( 'Publish', 'wp-wand' ) }
							</button>
						) }
					</span>
				</li>
			) ) }
		</ul>
	);
}

function ScheduleList( {
	schedules,
	loading,
	onEdit,
	onDelete,
	onRun,
	runningId,
	onPublish,
	publishingId,
} ) {
	const [ expanded, setExpanded ] = useState( null );

	if ( loading ) {
		return (
			<div className="wpwa-empty">{ __( 'Loading…', 'wp-wand' ) }</div>
		);
	}
	if ( ! schedules.length ) {
		return (
			<div className="wpwa-empty">
				{ __(
					'No schedules yet. Create one to start generating posts automatically.',
					'wp-wand'
				) }
			</div>
		);
	}

	return (
		<table className="wpwa-table">
			<thead>
				<tr>
					<th>{ __( 'Name', 'wp-wand' ) }</th>
					<th>{ __( 'Frequency', 'wp-wand' ) }</th>
					<th>{ __( 'Source', 'wp-wand' ) }</th>
					<th>{ __( 'Per run', 'wp-wand' ) }</th>
					<th>{ __( 'Result', 'wp-wand' ) }</th>
					<th>{ __( 'Next run', 'wp-wand' ) }</th>
					<th>{ __( 'Status', 'wp-wand' ) }</th>
					<th />
				</tr>
			</thead>
			<tbody>
				{ schedules.map( ( s ) => {
					const posts = s.posts || [];
					const isOpen = expanded === s.id;
					return (
						<Fragment key={ s.id }>
							<tr>
								<td className="wpwa-table__name">
									<button
										className="wpwa-expand"
										onClick={ () =>
											setExpanded( isOpen ? null : s.id )
										}
										aria-expanded={ isOpen }
									>
										<span className="wpwa-expand__chev">
											{ isOpen ? '▾' : '▸' }
										</span>
										{ s.name }
										<span className="wpwa-expand__count">
											{ posts.length }{ ' ' }
											{ __( 'posts', 'wp-wand' ) }
										</span>
									</button>
								</td>
								<td>{ freqLabel( s.frequency ) }</td>
								<td>
									{ s.mode === 'prompt'
										? __( 'AI ideas', 'wp-wand' ) +
										  ( s.subject
												? `: ${ s.subject }`
												: '' )
										: ( s.topics || [] ).length +
										  ' ' +
										  __( 'topics', 'wp-wand' ) }
								</td>
								<td>{ s.count }</td>
								<td>{ statusLabel( s.post_status ) }</td>
								<td>
									{ s.enabled
										? s.next_run_h || '—'
										: __( 'Paused', 'wp-wand' ) }
									{ s.runs > 0 && (
										<span className="wpwa-runs">
											{ ' · ' }
											{ s.runs }{ ' ' }
											{ __( 'runs', 'wp-wand' ) }
										</span>
									) }
								</td>
								<td>
									<span
										className={
											'wpwa-pill ' +
											( s.enabled
												? 'wpwa-pill--on'
												: 'wpwa-pill--off' )
										}
									>
										{ s.enabled
											? __( 'Active', 'wp-wand' )
											: __( 'Paused', 'wp-wand' ) }
									</span>
								</td>
								<td className="wpwa-table__actions">
									<button
										className="wpwa-link"
										disabled={ runningId === s.id }
										onClick={ () => onRun( s.id ) }
									>
										{ runningId === s.id
											? __( 'Running…', 'wp-wand' )
											: __( 'Run now', 'wp-wand' ) }
									</button>
									<button
										className="wpwa-link"
										onClick={ () => onEdit( s ) }
									>
										{ __( 'Edit', 'wp-wand' ) }
									</button>
									<button
										className="wpwa-link wpwa-link--danger"
										onClick={ () =>
											confirmDelete() && onDelete( s.id )
										}
									>
										{ __( 'Delete', 'wp-wand' ) }
									</button>
								</td>
							</tr>
							{ isOpen && (
								<tr className="wpwa-detail">
									<td colSpan={ 8 }>
										<PostList
											posts={ posts }
											onPublish={ onPublish }
											publishingId={ publishingId }
										/>
									</td>
								</tr>
							) }
						</Fragment>
					);
				} ) }
			</tbody>
		</table>
	);
}

function ScheduleForm( { form, setForm, onCancel, onSave, saving, error } ) {
	const set = ( key ) => ( e ) => {
		const val =
			e && e.target && e.target.type === 'checkbox'
				? e.target.checked
				: e.target.value;
		setForm( { ...form, [ key ]: val } );
	};

	return (
		<div className="wpwa-card">
			{ error && <div className="wpwa-error">{ error }</div> }

			<Field label={ __( 'Schedule name', 'wp-wand' ) }>
				<input
					className="wpwa-input"
					type="text"
					value={ form.name }
					placeholder={ __(
						'e.g. Weekly gardening tips',
						'wp-wand'
					) }
					onChange={ set( 'name' ) }
				/>
			</Field>

			<Field label={ __( 'How often', 'wp-wand' ) }>
				<select
					className="wpwa-select"
					value={ form.frequency }
					onChange={ set( 'frequency' ) }
				>
					{ FREQUENCIES.map( ( f ) => (
						<option key={ f.value } value={ f.value }>
							{ f.label }
						</option>
					) ) }
				</select>
			</Field>

			<Field label={ __( 'Title source', 'wp-wand' ) }>
				<div className="wpwa-segment">
					<button
						className={ form.mode === 'list' ? 'is-on' : '' }
						onClick={ () => setForm( { ...form, mode: 'list' } ) }
					>
						{ __( 'Topic list', 'wp-wand' ) }
					</button>
					<button
						className={ form.mode === 'prompt' ? 'is-on' : '' }
						onClick={ () => setForm( { ...form, mode: 'prompt' } ) }
					>
						{ __( 'AI ideas', 'wp-wand' ) }
					</button>
				</div>
			</Field>

			{ form.mode === 'list' ? (
				<>
					<Field
						label={ __( 'Topics', 'wp-wand' ) }
						desc={ __(
							'One title/topic per line. Each run takes the next ones in order.',
							'wp-wand'
						) }
					>
						<textarea
							className="wpwa-textarea"
							rows={ 6 }
							value={ form.topics }
							placeholder={
								'10 easy houseplants for beginners\nHow to repot a snake plant\n…'
							}
							onChange={ set( 'topics' ) }
						/>
					</Field>
					<Field label="">
						<label className="wpwa-check">
							<input
								type="checkbox"
								checked={ !! form.loop }
								onChange={ set( 'loop' ) }
							/>
							{ __(
								'Start over from the top when the list is finished',
								'wp-wand'
							) }
						</label>
					</Field>
				</>
			) : (
				<Field
					label={ __( 'Subject', 'wp-wand' ) }
					desc={ __(
						'The AI invents fresh post titles about this subject each run.',
						'wp-wand'
					) }
				>
					<input
						className="wpwa-input"
						type="text"
						value={ form.subject }
						placeholder={ __(
							'e.g. indoor gardening for small apartments',
							'wp-wand'
						) }
						onChange={ set( 'subject' ) }
					/>
				</Field>
			) }

			<div className="wpwa-grid">
				<Field label={ __( 'Posts per run', 'wp-wand' ) }>
					<input
						className="wpwa-input"
						type="number"
						min="1"
						max="10"
						value={ form.count }
						onChange={ set( 'count' ) }
					/>
				</Field>
				<Field label={ __( 'Create as', 'wp-wand' ) }>
					<select
						className="wpwa-select"
						value={ form.post_status }
						onChange={ set( 'post_status' ) }
					>
						{ STATUSES.map( ( s ) => (
							<option key={ s.value } value={ s.value }>
								{ s.label }
							</option>
						) ) }
					</select>
				</Field>
				<Field label={ __( 'Approx. words', 'wp-wand' ) }>
					<input
						className="wpwa-input"
						type="number"
						min="0"
						step="100"
						value={ form.word_count }
						onChange={ set( 'word_count' ) }
					/>
				</Field>
			</div>

			<div className="wpwa-grid">
				<Field label={ __( 'Tone', 'wp-wand' ) }>
					<select
						className="wpwa-select"
						value={ form.tone }
						onChange={ set( 'tone' ) }
					>
						{ TONES.map( ( t ) => (
							<option key={ t } value={ t }>
								{ t
									? t.charAt( 0 ).toUpperCase() + t.slice( 1 )
									: __( 'Default', 'wp-wand' ) }
							</option>
						) ) }
					</select>
				</Field>
				<Field label={ __( 'Focus keyword', 'wp-wand' ) }>
					<input
						className="wpwa-input"
						type="text"
						value={ form.keyword }
						onChange={ set( 'keyword' ) }
					/>
				</Field>
				<Field label={ __( 'Language', 'wp-wand' ) }>
					<input
						className="wpwa-input"
						type="text"
						value={ form.language }
						placeholder={ __( 'Default', 'wp-wand' ) }
						onChange={ set( 'language' ) }
					/>
				</Field>
			</div>

			<Field label="">
				<label className="wpwa-check">
					<input
						type="checkbox"
						checked={ !! form.toc_include }
						onChange={ set( 'toc_include' ) }
					/>
					{ __( 'Add a table of contents', 'wp-wand' ) }
				</label>
				<label className="wpwa-check">
					<input
						type="checkbox"
						checked={ !! form.faq_include }
						onChange={ set( 'faq_include' ) }
					/>
					{ __( 'Add an FAQ section', 'wp-wand' ) }
				</label>
				<label className="wpwa-check">
					<input
						type="checkbox"
						checked={ !! form.enabled }
						onChange={ set( 'enabled' ) }
					/>
					{ __( 'Active (uncheck to pause)', 'wp-wand' ) }
				</label>
			</Field>

			<div className="wpwa-actions">
				<button
					className="wpwa-btn wpwa-btn--primary"
					disabled={ saving }
					onClick={ onSave }
				>
					{ saving
						? __( 'Saving…', 'wp-wand' )
						: __( 'Save schedule', 'wp-wand' ) }
				</button>
				<button
					className="wpwa-btn wpwa-btn--ghost"
					onClick={ onCancel }
				>
					{ __( 'Cancel', 'wp-wand' ) }
				</button>
			</div>
		</div>
	);
}

function Field( { label, desc, children } ) {
	return (
		<div className="wpwa-field">
			{ label !== '' && (
				<label className="wpwa-field__label">{ label }</label>
			) }
			<div className="wpwa-field__control">
				{ children }
				{ desc && <span className="wpwa-field__desc">{ desc }</span> }
			</div>
		</div>
	);
}
