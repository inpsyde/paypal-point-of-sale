import apiFetch from '@wordpress/api-fetch';

/**
 * The abilities namespace Abilities::register() uses unless it is passed another.
 */
export const DEFAULT_ABILITIES_NAMESPACE = 'syde';

/**
 * Fetches available projects from the abilities API.
 *
 * @param {string} [namespace] The namespace passed to Abilities::register().
 * @return {Promise<Array<{value: string, label: string, description: string}>>} Array of project options.
 */
export async function fetchProjects(namespace = DEFAULT_ABILITIES_NAMESPACE) {
    const response = await apiFetch({
        path: `/wp-abilities/v1/abilities/${namespace}/get-projects/run`,
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({})
    });

    return response.map(project => ({
        value: project.name || project.id,
        label: project.label || project.name || project.id,
        description: project.description || ''
    }));
}

/**
 * Fetches feature flags for a specific project from the abilities API.
 *
 * @param {string} project     The project identifier.
 * @param {string} [namespace] The namespace passed to Abilities::register().
 * @return {Promise<Array<{value: string, label: string, description: string, enabled: boolean}>>} Array of feature flag options.
 */
export async function fetchFeatureFlags(project, namespace = DEFAULT_ABILITIES_NAMESPACE) {
    const response = await apiFetch({
        path: `/wp-abilities/v1/abilities/${namespace}/get-flags/run`,
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            input: {
                project
            }
        })
    });

    return response.map(flag => ({
        value: flag.name || flag.id,
        label: flag.label || flag.name || flag.id,
        description: flag.description || '',
        enabled: flag.enabled || false
    }));
}

/**
 * Gets the state of a specific feature flag.
 *
 * @param {string} project     The project identifier.
 * @param {string} flag        The feature flag name.
 * @param {string} [namespace] The namespace passed to Abilities::register().
 * @return {Promise<boolean>} Whether the flag is enabled.
 */
export async function getFlagState(project, flag, namespace = DEFAULT_ABILITIES_NAMESPACE) {
    const flags = await fetchFeatureFlags(project, namespace);
    const found = flags.find(f => f.value === flag);
    return found?.enabled ?? false;
}

