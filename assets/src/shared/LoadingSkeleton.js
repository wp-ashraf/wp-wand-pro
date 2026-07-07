/**
 * Shared loading skeleton for the React admin screens.
 *
 * Mirrors the PHP pre-JS paint (\WPWand\Admin\Skeleton::panel) so the hand-off from the server-
 * rendered skeleton to the app's own isLoading state is one continuous loader — no blank second
 * spinner. Header + tabs stay crisp; only the card body blurs behind a centered spinner.
 *
 * Self-contained: it injects its own scoped CSS (wpwand-skel-* prefix) so any app can use it
 * without touching its stylesheet. Duplicate keyframes across apps are harmless.
 *
 * @param {Object}   props
 * @param {string}   props.title Crisp heading (page/brand name).
 * @param {string[]} [props.tabs] Optional crisp tab labels; first is active.
 * @param {number}   [props.rows] Blurred placeholder rows (default 4).
 * @param {string}   [props.label] Spinner caption (default "Loading…").
 */
import { __ } from '@wordpress/i18n';

const CSS = `
.wpwand-skel-panel{position:relative;min-height:320px}
.wpwand-skel-blur{filter:blur(3px);opacity:.6;pointer-events:none;user-select:none}
.wpwand-skel-row{display:flex;flex-direction:column;gap:8px;padding:14px 0;border-bottom:1px solid #f1f2f4}
.wpwand-skel-line{display:block;height:12px;width:180px;border-radius:5px;background:#eef0f2}
.wpwand-skel-input{display:block;height:38px;max-width:420px;border:1px solid #e5e7eb;border-radius:6px;background:linear-gradient(90deg,#f3f4f6 25%,#fafafa 37%,#f3f4f6 63%);background-size:400% 100%;animation:wpwand-skel-shine 1.4s ease infinite}
.wpwand-skel-spin{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:14px;color:#6b7280;font-size:13px;font-family:Inter,-apple-system,sans-serif;z-index:2}
.wpwand-skel-spin i{width:26px;height:26px;border:3px solid #e5e7eb;border-top-color:#2563eb;border-radius:50%;animation:wpwand-skel-spin .8s linear infinite;display:block}
@keyframes wpwand-skel-shine{0%{background-position:100% 0}100%{background-position:-100% 0}}
@keyframes wpwand-skel-spin{to{transform:rotate(360deg)}}
`;

export default function LoadingSkeleton( { title, tabs = [], rows = 4, label } ) {
	return (
		<div className="wpwand-app" role="status" aria-live="polite">
			<style>{ CSS }</style>
			<div className="wpwand-app__header">
				<h1 className="wpwand-app__title">{ title }</h1>
			</div>
			<div className="wpws-card">
				{ tabs.length > 0 && (
					<div className="wpws-tabs">
						{ tabs.map( ( t, i ) => (
							<button
								type="button"
								key={ t }
								className={ `wpws-tab${ i === 0 ? ' is-active' : '' }` }
							>
								{ t }
							</button>
						) ) }
					</div>
				) }
				<div className="wpwand-skel-panel">
					<div className="wpws-panel wpwand-skel-blur">
						{ Array.from( { length: Math.max( 1, rows ) } ).map(
							( _, i ) => (
								<div className="wpwand-skel-row" key={ i }>
									<span className="wpwand-skel-line" />
									<span className="wpwand-skel-input" />
								</div>
							)
						) }
					</div>
					<div className="wpwand-skel-spin">
						<i />
						<span>{ label || __( 'Loading…', 'wp-wand' ) }</span>
					</div>
				</div>
			</div>
		</div>
	);
}
