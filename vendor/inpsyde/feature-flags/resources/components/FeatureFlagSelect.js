import {__} from '@wordpress/i18n';
import {useState, useEffect} from '@wordpress/element';
import {ComboboxControl, Spinner, Notice} from '@wordpress/components';

import {useFeatureFlags} from '../hooks';

/**
 * Combobox for selecting a feature flag with search/filter support.
 *
 * @param {Object} props
 * @param {string} props.project Project identifier to fetch flags for.
 * @param {string} props.value Currently selected flag value.
 * @param {Function} props.onChange Callback when selection changes.
 * @return {JSX.Element}
 */
export function FeatureFlagSelect({project, value, onChange}) {
    const {flags, isLoading, error} = useFeatureFlags(project);
    const [filteredFlags, setFilteredFlags] = useState([]);

    useEffect(() => {
        setFilteredFlags(flags);
    }, [flags]);

    const handleFilterChange = (inputValue) => {
        if (!inputValue) {
            setFilteredFlags(flags);
            return;
        }

        const filtered = flags.filter(option =>
            option.label.toLowerCase().includes(inputValue.toLowerCase()) ||
            option.value.toLowerCase().includes(inputValue.toLowerCase()) ||
            (option.description && option.description.toLowerCase().includes(inputValue.toLowerCase()))
        );
        setFilteredFlags(filtered);
    };

    const selectedDetails = flags.find(flag => flag.value === value);

    if (!project) {
        return (
            <Notice status="info" isDismissible={false}>
                {__('Please select a project first to load available feature flags.', 'feature-flags-block')}
            </Notice>
        );
    }

    if (isLoading) {
        return (
            <div className="feature-flag-select__loading">
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
                {__('No feature flags found in the selected project.', 'feature-flags-block')}
            </Notice>
        );
    }

    return (
        <ComboboxControl
            label={__('Select Feature Flag', 'feature-flags-block')}
            value={value}
            onChange={onChange}
            onFilterValueChange={handleFilterChange}
            options={filteredFlags}
            help={selectedDetails?.description || __('Choose which feature flag to check against', 'feature-flags-block')}
            placeholder={__('Search for a feature flag...', 'feature-flags-block')}
        />
    );
}
