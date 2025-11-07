<?php

declare (strict_types=1);
namespace Inpsyde\Assets;

use Inpsyde\Assets\Handler\AssetHandler;
/**
 * @internal use BaseAsset to start your custom implementation.
 */
interface Asset
{
    // Location types
    public const FRONTEND = 2;
    public const BACKEND = 4;
    public const CUSTOMIZER = 8;
    public const LOGIN = 16;
    public const BLOCK_EDITOR_ASSETS = 32;
    public const BLOCK_ASSETS = 64;
    public const CUSTOMIZER_PREVIEW = 128;
    public const ACTIVATE = 256;
    // Hooks
    public const HOOK_FRONTEND = 'wp_enqueue_scripts';
    public const HOOK_BACKEND = 'admin_enqueue_scripts';
    public const HOOK_LOGIN = 'login_enqueue_scripts';
    public const HOOK_CUSTOMIZER = 'customize_controls_enqueue_scripts';
    public const HOOK_CUSTOMIZER_PREVIEW = 'customize_preview_init';
    public const HOOK_BLOCK_ASSETS = 'enqueue_block_assets';
    public const HOOK_BLOCK_EDITOR_ASSETS = 'enqueue_block_editor_assets';
    public const HOOK_ACTIVATE = 'activate_wp_head';
    /**
     * Hooks to Locations map
     * @var array<string,int>
     */
    public const HOOK_TO_LOCATION = [\Inpsyde\Assets\Asset::HOOK_FRONTEND => \Inpsyde\Assets\Asset::FRONTEND, \Inpsyde\Assets\Asset::HOOK_BACKEND => \Inpsyde\Assets\Asset::BACKEND, \Inpsyde\Assets\Asset::HOOK_LOGIN => \Inpsyde\Assets\Asset::LOGIN, \Inpsyde\Assets\Asset::HOOK_CUSTOMIZER => \Inpsyde\Assets\Asset::CUSTOMIZER, \Inpsyde\Assets\Asset::HOOK_CUSTOMIZER_PREVIEW => \Inpsyde\Assets\Asset::CUSTOMIZER_PREVIEW, \Inpsyde\Assets\Asset::HOOK_BLOCK_ASSETS => \Inpsyde\Assets\Asset::BLOCK_ASSETS, \Inpsyde\Assets\Asset::HOOK_BLOCK_EDITOR_ASSETS => \Inpsyde\Assets\Asset::BLOCK_EDITOR_ASSETS, \Inpsyde\Assets\Asset::HOOK_ACTIVATE => \Inpsyde\Assets\Asset::ACTIVATE];
    /**
     * Contains the full url to file.
     *
     * @return string
     */
    public function url(): string;
    /**
     * Returns the full file path to the asset.
     *
     * @return string
     */
    public function filePath(): string;
    /**
     * Define the full filePath to the Asset.
     *
     * @param string $filePath
     *
     * @return static
     */
    public function withFilePath(string $filePath): \Inpsyde\Assets\Asset;
    /**
     * Name of the given asset.
     *
     * @return string
     */
    public function handle(): string;
    /**
     * A list of handle-dependencies.
     *
     * @return string[]
     */
    public function dependencies(): array;
    /**
     * @param string ...$dependencies
     *
     * @return static
     */
    public function withDependencies(string ...$dependencies): \Inpsyde\Assets\Asset;
    /**
     * The current version of the asset.
     *
     * @return string|null
     */
    public function version(): ?string;
    /**
     * @param string $version
     *
     * @return static
     */
    public function withVersion(string $version): \Inpsyde\Assets\Asset;
    /**
     * @return bool
     */
    public function enqueue(): bool;
    /**
     * @param bool|callable $enqueue
     *
     * @return static
     *
     *  phpcs:disable Syde.Functions.ArgumentTypeDeclaration.NoArgumentType
     */
    public function canEnqueue($enqueue): \Inpsyde\Assets\Asset;
    /**
     * Location where the asset is enqueued.
     *
     * @return int
     *
     * @example Asset::FRONTEND | Asset::BACKEND
     * @example Asset::FRONTEND
     */
    public function location(): int;
    /**
     * Define a location based on Asset location types.
     *
     * @param int $location
     *
     * @return static
     */
    public function forLocation(int $location): \Inpsyde\Assets\Asset;
    /**
     * Name of the handler class to register and enqueue the asset.
     *
     * @return class-string<AssetHandler>
     */
    public function handler(): string;
    /**
     * @param class-string<AssetHandler> $handler
     *
     * @return static
     */
    public function useHandler(string $handler): \Inpsyde\Assets\Asset;
}
