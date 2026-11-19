/**
 * @inpsyde/feature-flags
 *
 * Main entry point for the feature flags JS library.
 * Exports all public components, hooks, and utilities.
 */

// Components
export {
    ProjectList,
    ProjectSelect,
    FeatureList,
    FeatureItem,
    FeatureFlagSelect,
    FeatureFlagIndicator,
    SelectionDetails,
} from './components';

// Hooks
export {
    useAsync,
    useProjects,
    useFeatureFlags,
} from './hooks';

// API utilities
export {
    DEFAULT_ABILITIES_NAMESPACE,
    fetchProjects,
    fetchFeatureFlags,
} from './utils/api';
