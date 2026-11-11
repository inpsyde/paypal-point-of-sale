import {__} from '@wordpress/i18n';

/**
 * Displays details about the selected project and feature flag.
 *
 * @param {Object} props
 * @param {Object} props.project Selected project details.
 * @param {string} props.project.label Project label.
 * @param {string} props.project.description Project description.
 * @param {Object} props.flag Selected feature flag details.
 * @param {string} props.flag.label Flag label.
 * @param {string} props.flag.description Flag description.
 * @param {boolean} props.flag.enabled Whether the flag is enabled.
 * @return {JSX.Element|null}
 */
export function SelectionDetails({project, flag}) {
    if (!project && !flag) {
        return null;
    }

    return (
        <div className="selection-details">
            <h4>{__('Selection Details', 'feature-flags-block')}</h4>

            {project && (
                <div className="selection-details__project">
                    <p>
                        <strong>{__('Project:', 'feature-flags-block')}</strong> {project.label}
                    </p>
                    {project.description && (
                        <p>
                            <strong>{__('Description:', 'feature-flags-block')}</strong> {project.description}
                        </p>
                    )}
                </div>
            )}

            {flag && (
                <div className="selection-details__flag">
                    <p>
                        <strong>{__('Flag:', 'feature-flags-block')}</strong> {flag.label}
                    </p>
                    <p>
                        <strong>{__('Status:', 'feature-flags-block')}</strong>{' '}
                        {flag.enabled
                            ? __('Enabled', 'feature-flags-block')
                            : __('Disabled', 'feature-flags-block')}
                    </p>
                    {flag.description && (
                        <p>
                            <strong>{__('Description:', 'feature-flags-block')}</strong> {flag.description}
                        </p>
                    )}
                </div>
            )}
        </div>
    );
}
