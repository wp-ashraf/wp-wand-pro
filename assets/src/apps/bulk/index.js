/**
 * Bulk Posts app entry. Mounts onto #wpwand-bulk-root.
 */
import { createRoot } from '@wordpress/element';
import { QueryClientProvider } from '@tanstack/react-query';
import { queryClient } from '../../shared/queryClient';
import App from './App';
import './style.scss';

const mount = document.getElementById( 'wpwand-bulk-root' );

if ( mount ) {
	createRoot( mount ).render(
		<QueryClientProvider client={ queryClient }>
			<App />
		</QueryClientProvider>
	);
}
