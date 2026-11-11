<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\FeatureFlags;

use Syde\Vendor\Zettle\Inpsyde\FeatureFlags\Project\Project;
use Syde\Vendor\Zettle\Inpsyde\FeatureFlags\Project\ProjectRegistry;
final class FeatureFlags
{
    /**
     * Global across scoped copies of this library, so plugins can check each other's flags.
     */
    public const HOOK_BASE = 'syde.feature-flags';
    private static ?ProjectRegistry $registry = null;
    private static bool $isSetUp = \false;
    private static function getRegistry(): ProjectRegistry
    {
        return self::$registry ??= new ProjectRegistry();
    }
    /**
     * Registers the flag filters. Idempotent, and called by registerProject().
     *
     * The abilities are separate and opt-in, see Abilities::register().
     */
    public static function setup(): void
    {
        if (self::$isSetUp) {
            return;
        }
        self::$isSetUp = \true;
        add_filter(self::HOOK_BASE, [self::class, 'handleFlagForProject'], 10, 3);
    }
    public static function registerProject(Project $project): void
    {
        self::setup();
        $hookBase = self::HOOK_BASE;
        $project = apply_filters("{$hookBase}.registered-project", $project);
        $projectName = $project->getName();
        self::getRegistry()->registerProject($project);
        add_filter("{$hookBase}.{$projectName}", static fn(bool $assumed, string $flagName): bool => self::handleFlagForProject($assumed, $flagName, $projectName), 10, 2);
    }
    public static function handleFlagForProject(bool $assumed, string $flagName, string $project): bool
    {
        $registry = self::getRegistry();
        if (!$registry->hasProject($project)) {
            return $assumed;
        }
        return $registry->getProject($project)->isEnabled($flagName, $assumed);
    }
    public static function hasProject(string $name): bool
    {
        return self::getRegistry()->hasProject($name);
    }
    public static function getProject(string $name): Project
    {
        return self::getRegistry()->getProject($name);
    }
    /**
     * @return array<string, Project> Registered projects by name.
     */
    public static function getProjects(): array
    {
        return self::getRegistry()->getAllProjects();
    }
}
