import {__} from '@wordpress/i18n';

/**
 * Displays the current project and feature flag selection status in the block editor.
 *
 * @param {Object} props
 * @param {string} props.project Selected project name.
 * @param {string} props.flag Selected feature flag name.
 * @param {boolean} props.enabled Whether the selected flag is enabled.
 * @return {JSX.Element}
 */
export function FeatureFlagIndicator({project, flag, enabled}) {
    if (!project) {
        return (
            <div className="feature-flag-indicator">
                <span className="feature-flag-indicator__no-project">
                    {__('No project selected', 'feature-flags-block')}
                </span>
            </div>
        );
    }

    return (
        <div className="feature-flag-indicator">
            <div className="feature-flag-indicator__info">
                <span className="feature-flag-indicator__project">
                    {__('Project:', 'feature-flags-block')} <strong>{project}</strong>
                </span>
                {flag ? (
                    <span className={`feature-flag-indicator__flag ${enabled ? 'enabled' : 'disabled'}`}>
                        {__('Flag:', 'feature-flags-block')} <strong>{flag}</strong>
                        <span className="feature-flag-indicator__status">
                            ({enabled ? __('Enabled', 'feature-flags-block') : __('Disabled', 'feature-flags-block')})
                        </span>
                    </span>
                ) : (
                    <span className="feature-flag-indicator__no-flag">
                        {__('No feature flag selected', 'feature-flags-block')}
                    </span>
                )}
            </div>
        </div>
    );
}
