import {__} from '@wordpress/i18n';
import {useState, useEffect} from '@wordpress/element';
import {ComboboxControl, Spinner, Notice} from '@wordpress/components';

import {useProjects} from '../hooks';

/**
 * Combobox for selecting a project with search/filter support.
 *
 * @param {Object} props
 * @param {string} props.value Currently selected project value.
 * @param {Function} props.onChange Callback when selection changes.
 * @return {JSX.Element}
 */
export function ProjectSelect({value, onChange}) {
    const {projects, isLoading, error} = useProjects();
    const [filteredProjects, setFilteredProjects] = useState([]);

    useEffect(() => {
        setFilteredProjects(projects);
    }, [projects]);

    const handleFilterChange = (inputValue) => {
        if (!inputValue) {
            setFilteredProjects(projects);
            return;
        }

        const filtered = projects.filter(option =>
            option.label.toLowerCase().includes(inputValue.toLowerCase()) ||
            option.value.toLowerCase().includes(inputValue.toLowerCase()) ||
            (option.description && option.description.toLowerCase().includes(inputValue.toLowerCase()))
        );
        setFilteredProjects(filtered);
    };

    const selectedDetails = projects.find(project => project.value === value);

    if (isLoading) {
        return (
            <div className="project-select__loading">
                <Spinner />
                <p>{__('Loading projects...', 'feature-flags-block')}</p>
            </div>
        );
    }

    if (error) {
        return (
            <Notice status="error" isDismissible={false}>
                {__('Error loading projects: ', 'feature-flags-block')} {error}
            </Notice>
        );
    }

    return (
        <ComboboxControl
            label={__('Select Project', 'feature-flags-block')}
            value={value}
            onChange={onChange}
            onFilterValueChange={handleFilterChange}
            options={filteredProjects}
            help={selectedDetails?.description || __('Choose which project to load feature flags from', 'feature-flags-block')}
            placeholder={__('Search for a project...', 'feature-flags-block')}
        />
    );
}
