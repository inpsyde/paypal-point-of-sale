import {useState, useEffect, useCallback} from '@wordpress/element';

import {fetchFeatureFlags} from '../utils/api';

/**
 * Hook for fetching and managing feature flags for a project.
 *
 * @param {string} project Project identifier.
 * @return {{flags: Array, isLoading: boolean, error: string|null, refetch: Function}}
 */
export function useFeatureFlags(project) {
    const [flags, setFlags] = useState([]);
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState(null);

    const fetchFlags = useCallback(async () => {
        if (!project) {
            setFlags([]);
            return [];
        }

        try {
            setIsLoading(true);
            setError(null);
            const result = await fetchFeatureFlags(project);
            setFlags(result);
            return result;
        } catch (err) {
            console.error('useFeatureFlags error:', err);
            setError(err.message);
            setFlags([]);
            return [];
        } finally {
            setIsLoading(false);
        }
    }, [project]);

    useEffect(() => {
        fetchFlags();
    }, [fetchFlags]);

    return {flags, isLoading, error, refetch: fetchFlags};
}
