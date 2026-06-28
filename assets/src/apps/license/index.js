/**
 * License app entry. Mounts onto #wpwand-license-root.
 */
import { createRoot } from '@wordpress/element';
import { QueryClientProvider } from '@tanstack/react-query';
import { queryClient } from '../../shared/queryClient';
import App from './App';
import './style.scss';

const mount = document.getElementById( 'wpwand-license-root' );

if ( mount ) {
	createRoot( mount ).render(
		<QueryClientProvider client={ queryClient }>
			<App />
		</QueryClientProvider>
	);
}
