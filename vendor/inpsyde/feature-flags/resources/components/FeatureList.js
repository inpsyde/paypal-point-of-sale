import {__} from '@wordpress/i18n';
import {useState, useEffect} from '@wordpress/element';
import {
    Spinner,
    Notice,
    __experimentalText as Text,
    __experimentalHStack as HStack,
    __experimentalVStack as VStack, __experimentalSpacer as Spacer,
} from '@wordpress/components';

import {fetchFeatureFlags} from '../utils/api';
import {FeatureItem} from './FeatureItem';

/**
 * Displays a list of feature flags for a given project.
 *
 * @param {Object} props
 * @param {string} props.project Project identifier to fetch flags for.
 * @param {string} props.selectedFlag Currently selected flag value.
 * @param {Function} props.onSelect Callback when a flag is selected.
 * @return {JSX.Element}
 */
export function FeatureList({project, selectedFlag, onSelect}) {
    const [flags, setFlags] = useState([]);
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState(null);
    const enabledCount = flags.filter(flag => flag.enabled).length;
    const disabledCount = flags.length - enabledCount;

    useEffect(() => {
        if (!project) {
            setFlags([]);
            return;
        }

        const loadFlags = async () => {
            try {
                setIsLoading(true);
                setError(null);
                const flagOptions = await fetchFeatureFlags(project);
                setFlags(flagOptions);
            } catch (err) {
                console.error('Error fetching feature flags:', err);
                setError(err.message);
                setFlags([]);
            } finally {
                setIsLoading(false);
            }
        };

        loadFlags();
    }, [project]);

    if (!project) {
        return (
            <Notice status="info" isDismissible={false}>
                {__('Please select a project to view feature flags.', 'feature-flags-block')}
            </Notice>
        );
    }

    if (isLoading) {
        return (
            <div className="feature-list__loading">
                <Spinner />
                <p>{__('Loading feature flags...', 'feature-flags-block')}</p>
            </div>
        );
    }

    if (error) {
        return (
            <Notice status="error" isDismissible={false}>
                {__('Error loading feature flags: ', 'feature-flags-block')} {error}
            </Notice>
        );
    }

    if (flags.length === 0) {
        return (
            <Notice status="warning" isDismissible={false}>
                {__('No feature flags found in this project.', 'feature-flags-block')}
            </Notice>
        );
    }
    return (
        <>
        <HStack>
            <Text>
                <strong>{flags.length}</strong> {__('total flags', 'feature-flags-block')}
            </Text>
            <Text className="feature-flags-admin__count--enabled">
                <strong>{enabledCount}</strong> {__('enabled', 'feature-flags-block')}
            </Text>
            <Text className="feature-flags-admin__count--disabled">
                <strong>{disabledCount}</strong> {__('disabled', 'feature-flags-block')}
            </Text>
        </HStack>
            <Spacer/>
        <VStack>
            {flags.map((flag) => (
                <FeatureItem
                    key={flag.value}
                    name={flag.value}
                    label={flag.label}
                    description={flag.description}
                    enabled={flag.enabled}
                    selected={selectedFlag === flag.value}
                    onClick={onSelect ? () => onSelect(flag.value) : undefined}
                />
            ))}
        </VStack>
        </>
    );
}
