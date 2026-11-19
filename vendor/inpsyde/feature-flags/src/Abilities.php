<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\FeatureFlags;

use Syde\Vendor\Zettle\Inpsyde\FeatureFlags\Feature\Feature;
use Syde\Vendor\Zettle\Inpsyde\FeatureFlags\Project\Project;
/**
 * Exposes the registered projects and their flags through the WordPress Abilities API
 * (WordPress 6.9+), e.g. for an admin UI. Opt-in: nothing is registered unless a consumer
 * calls register().
 */
final class Abilities
{
    public const DEFAULT_NAMESPACE = 'syde';
    public const DEFAULT_CAPABILITY = 'manage_options';
    // Shared by all copies of this library, so their abilities are listed together.
    private const CATEGORY = 'feature-flags';
    /**
     * Capability per namespace registered by this copy of the library.
     *
     * @var array<string, string>
     */
    private static array $registered = [];
    /**
     * Registers the get-projects and get-flags abilities. Call it before the Abilities API
     * initialises, e.g. on plugins_loaded or init, not from wp_abilities_api_init: the ability
     * categories are registered before that hook fires. Further calls with the same namespace
     * have no effect. Scoped copies of this library each need their own namespace, because
     * ability names are global in WordPress.
     *
     * @param string $namespace Namespace of the ability names, e.g. "syde/get-flags".
     * @param string $capability Capability required to run the abilities.
     * @throws \InvalidArgumentException If the namespace is not a valid ability namespace.
     */
    public static function register(string $namespace = self::DEFAULT_NAMESPACE, string $capability = self::DEFAULT_CAPABILITY): void
    {
        if (!preg_match('/^[a-z0-9-]+$/', $namespace)) {
            throw new \InvalidArgumentException("Invalid abilities namespace \"{$namespace}\": " . 'use lowercase letters, digits and dashes.');
        }
        /** @var lowercase-string&non-empty-string $namespace */
        if (!self::canRegister($namespace, $capability)) {
            return;
        }
        self::$registered[$namespace] = $capability;
        add_action('wp_abilities_api_categories_init', [self::class, 'registerCategory']);
        // A callback added to a running hook at the same priority doesn't run, and
        // registerCategory() skips a category that is already registered.
        if (doing_action('wp_abilities_api_categories_init')) {
            self::registerCategory();
        }
        add_action('wp_abilities_api_init', static function () use ($namespace, $capability): void {
            $permissionCallback = static fn(): bool => current_user_can($capability);
            wp_register_ability("{$namespace}/get-projects", self::getProjectsAbility($permissionCallback));
            wp_register_ability("{$namespace}/get-flags", self::getFlagsAbility($permissionCallback));
        });
    }
    /**
     * Whether register() can still register the namespace's abilities, with a "doing it
     * wrong" notice where it can't and the caller should know.
     */
    private static function canRegister(string $namespace, string $capability): bool
    {
        if (isset(self::$registered[$namespace])) {
            if (self::$registered[$namespace] !== $capability) {
                _doing_it_wrong(__CLASS__ . '::register', sprintf('The feature flag abilities of namespace "%1$s" are already registered ' . 'with the capability "%2$s", which stays.', esc_html($namespace), esc_html(self::$registered[$namespace])), '2.0.0');
            }
            return \false;
        }
        // did_action() is already true while the hook runs.
        if (!did_action('wp_abilities_api_init')) {
            return \true;
        }
        _doing_it_wrong(__CLASS__ . '::register', doing_action('wp_abilities_api_init') ? 'Called from wp_abilities_api_init, after the ability categories are ' . 'registered, so the feature flag abilities are not registered. ' . 'Call register() on plugins_loaded or init.' : 'The Abilities API is already initialised, so the feature flag ' . 'abilities are not registered. Call register() earlier, e.g. on ' . 'plugins_loaded.', '2.0.0');
        return \false;
    }
    /**
     * @internal Hooked to wp_abilities_api_categories_init by register().
     */
    public static function registerCategory(): void
    {
        if (wp_has_ability_category(self::CATEGORY)) {
            return;
        }
        wp_register_ability_category(self::CATEGORY, ['label' => 'Get Feature Flag information', 'description' => 'Query the status of defined feature toggles ' . 'across various projects']);
    }
    /**
     * @param callable(): bool $permissionCallback
     * @return array<string, mixed>
     */
    private static function getProjectsAbility(callable $permissionCallback): array
    {
        return ['label' => 'Get feature flag projects', 'description' => 'Get all projects tracking feature flags', 'category' => self::CATEGORY, 'output_schema' => ['type' => 'array', 'description' => 'All registered feature flag projects.', 'items' => ['type' => 'object', 'properties' => ['name' => ['type' => 'string', 'description' => 'Project name']]]], 'execute_callback' => static fn(): array => array_values(array_map(static fn(Project $project): array => ['name' => $project->getName()], FeatureFlags::getProjects())), 'permission_callback' => $permissionCallback, 'meta' => ['show_in_rest' => \true]];
    }
    /**
     * @param callable(): bool $permissionCallback
     * @return array<string, mixed>
     */
    private static function getFlagsAbility(callable $permissionCallback): array
    {
        return ['label' => 'Get feature flags for project', 'description' => 'Get all feature flags for a specific project', 'category' => self::CATEGORY, 'input_schema' => ['type' => 'object', 'properties' => ['project' => ['type' => 'string', 'description' => 'Project name to get flags for']], 'required' => ['project'], 'additionalProperties' => \false], 'output_schema' => ['type' => 'array', 'description' => 'All feature flags for the specified project.', 'items' => ['type' => 'object', 'properties' => ['name' => ['type' => 'string', 'description' => 'Feature flag name'], 'label' => ['type' => 'string', 'description' => 'Feature flag label'], 'description' => ['type' => 'string', 'description' => 'Feature flag description'], 'enabled' => ['type' => 'boolean', 'description' => 'Whether the feature flag is currently enabled']]]], 'execute_callback' => [self::class, 'getFlags'], 'permission_callback' => $permissionCallback, 'meta' => ['show_in_rest' => \true]];
    }
    /**
     * @internal The get-flags ability's execute callback.
     *
     * @param array{project: string} $input
     * @return list<array{name: string, label: string, description: string, enabled: bool}>
     *     |\WP_Error
     */
    public static function getFlags(array $input): array|\WP_Error
    {
        $projectName = $input['project'];
        if (!FeatureFlags::hasProject($projectName)) {
            return new \WP_Error('feature_flags_unknown_project', "Feature Flags project {$projectName} not found", ['status' => 404]);
        }
        $project = FeatureFlags::getProject($projectName);
        return array_values(array_map(static fn(Feature $feature): array => ['name' => $feature->getName(), 'label' => $feature->getLabel(), 'description' => $feature->getDescription(), 'enabled' => $project->isEnabled($feature->getName())], $project->getFeatures()));
    }
}
