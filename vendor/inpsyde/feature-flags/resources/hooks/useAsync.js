import {useState, useEffect, useCallback} from '@wordpress/element';

/**
 * Generic hook for handling async operations with loading and error states.
 *
 * @param {Function} asyncFn The async function to execute.
 * @param {Array} deps Dependencies array that triggers re-fetch when changed.
 * @param {Object} options Configuration options.
 * @param {boolean} options.immediate Whether to execute immediately (default: true).
 * @param {*} options.initialData Initial data value (default: []).
 * @return {{data: *, isLoading: boolean, error: string|null, refetch: Function}}
 */
export function useAsync(asyncFn, deps = [], {immediate = true, initialData = []} = {}) {
    const [data, setData] = useState(initialData);
    const [isLoading, setIsLoading] = useState(immediate);
    const [error, setError] = useState(null);

    const execute = useCallback(async () => {
        try {
            setIsLoading(true);
            setError(null);
            const result = await asyncFn();
            setData(result);
            return result;
        } catch (err) {
            console.error('useAsync error:', err);
            setError(err.message);
            setData(initialData);
            return initialData;
        } finally {
            setIsLoading(false);
        }
    }, [asyncFn, initialData]);

    useEffect(() => {
        if (immediate) {
            execute();
        }
    }, [...deps, immediate]);

    return {data, isLoading, error, refetch: execute};
}
