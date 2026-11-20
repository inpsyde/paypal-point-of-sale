<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\FeatureFlags\Project;

final class ProjectRegistry
{
    /**
     * @var array<string, Project>
     */
    private array $projects = [];
    /**
     * Register a new project
     */
    public function registerProject(Project $project): void
    {
        $this->projects[$project->getName()] = $project;
    }
    /**
     * Get a single project by slug
     */
    public function getProject(string $name): Project
    {
        if (!isset($this->projects[$name])) {
            throw new \RuntimeException("Feature Flags project {$name} not found");
        }
        return $this->projects[$name];
    }
    /**
     * Get all registered projects
     *
     * @return array<string, Project>
     */
    public function getAllProjects(): array
    {
        return $this->projects;
    }
    /**
     * Check if a project exists
     */
    public function hasProject(string $name): bool
    {
        return isset($this->projects[$name]);
    }
}
