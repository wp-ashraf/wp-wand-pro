/**
 * Shared react-query client.
 *
 * react-query owns all async/background server state in the new UI — generation
 * requests, history, and especially the bulk-post progress polling (via
 * `refetchInterval`). @wordpress/data is used separately, only for editor stores.
 * See REBUILD-PLAN.md §3.2.
 */
import { QueryClient } from '@tanstack/react-query';

export const queryClient = new QueryClient( {
	defaultOptions: {
		queries: {
			refetchOnWindowFocus: false,
			retry: 1,
			staleTime: 30 * 1000,
		},
	},
} );
