import {useCallback} from '@wordpress/element';

import {fetchProjects} from '../utils/api';
import {useAsync} from './useAsync';

/**
 * Hook for fetching and managing projects.
 *
 * @return {{projects: Array, isLoading: boolean, error: string|null, refetch: Function}}
 */
export function useProjects() {
    const fetchFn = useCallback(() => fetchProjects(), []);
    const {data: projects, isLoading, error, refetch} = useAsync(fetchFn, []);

    return {projects, isLoading, error, refetch};
}
