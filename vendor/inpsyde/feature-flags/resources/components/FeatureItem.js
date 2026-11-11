import {__} from '@wordpress/i18n';
import {
    Card,
    CardHeader,
    ToggleControl,
    __experimentalText as Text,
    __experimentalHStack as HStack,
    __experimentalVStack as VStack
} from '@wordpress/components';

/**
 * Displays a single feature flag with its name, description, and status.
 *
 * @param {Object} props
 * @param {string} props.name Feature flag name.
 * @param {string} props.label Display label for the feature flag.
 * @param {string} props.description Feature flag description.
 * @param {boolean} props.enabled Whether the feature flag is enabled.
 * @param {Function} props.onClick Optional click handler.
 * @param {boolean} props.selected Whether this item is selected.
 * @return {JSX.Element}
 */
export function FeatureItem({name, label, description, enabled, onClick, selected = false}) {
    const statusClass = enabled ? 'enabled' : 'disabled';
    const statusText = enabled
        ? __('Enabled', 'feature-flags-block')
        : __('Disabled', 'feature-flags-block');

    return (
        <Card size="small">
            <CardHeader>
                <HStack>
                    <Text>
                        <strong>{label || name}</strong>
                    </Text>
                    <ToggleControl
                        label={statusText}
                        checked={enabled}
                        disabled={true}
                    />
                </HStack>
            </CardHeader>
        </Card>
    );
}
