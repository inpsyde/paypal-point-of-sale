import {__} from '@wordpress/i18n';
import {useState, useEffect} from '@wordpress/element';
import {
    Card,
    CardHeader,
    CardBody,
    Spinner,
    Notice,
    __experimentalSpacer as Spacer,
    __experimentalHeading as Heading,
    __experimentalText as Text,
    __experimentalVStack as VStack,
} from '@wordpress/components';

import {FeatureList} from './FeatureList';
import {fetchProjects} from '../utils/api';

/**
 * Displays a single project item.
 *
 * @param {Object} props
 * @param {string} props.name Project name/value.
 * @param {string} props.label Display label for the project.
 * @param {string} props.description Project description.
 * @param {Function} props.onClick Optional click handler.
 * @param {boolean} props.selected Whether this item is selected.
 * @return {JSX.Element}
 */
function ProjectItem({name, label, description, onClick, selected = false}) {
    return (
        <Card>
            <CardHeader>
                <VStack spacing={1}>
                    <Heading level={3}>{label || name}</Heading>
                    {description && (
                        <Text variant="muted">{description}</Text>
                    )}
                </VStack>
            </CardHeader>
            <CardBody>
                <FeatureList project={name}></FeatureList>
            </CardBody>
        </Card>
    );
}

/**
 * Displays a list of available projects.
 *
 * @param {Object} props
 * @param {string} props.selectedProject Currently selected project value.
 * @param {Function} props.onSelect Callback when a project is selected.
 * @return {JSX.Element}
 */
export function ProjectList({selectedProject, onSelect}) {
    const [projects, setProjects] = useState([]);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        const loadProjects = async () => {
            try {
                setIsLoading(true);
                setError(null);
                const projectOptions = await fetchProjects();
                setProjects(projectOptions);
            } catch (err) {
                console.error('Error fetching projects:', err);
                setError(err.message);
                setProjects([]);
            } finally {
                setIsLoading(false);
            }
        };

        loadProjects();
    }, []);

    if (isLoading) {
        return (
            <div className="project-list__loading">
                <Spinner/>
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

    if (projects.length === 0) {
        return (
            <Notice status="warning" isDismissible={false}>
                {__('No projects found.', 'feature-flags-block')}
            </Notice>
        );
    }

    return projects.map((project) => (
            <div key={project.value}>
                <ProjectItem
                    name={project.value}
                    label={project.label}
                    description={project.description}
                    selected={selectedProject === project.value}
                    onClick={onSelect ? () => onSelect(project.value) : undefined}
                />
                <Spacer/>
            </div>
        )
    );
}
