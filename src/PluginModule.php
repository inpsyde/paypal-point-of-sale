<?php

declare(strict_types=1);

// phpcs:disable Squiz.Functions.MultiLineFunctionDeclaration.SpaceAfterFunction

namespace Syde\PayPal\PointOfSale;

use Dhii\Container\ServiceProvider;
use Dhii\Modular\Module\ModuleInterface;
use Inpsyde\Assets\Asset;
use Inpsyde\Assets\AssetManager;
use Interop\Container\ServiceProviderInterface;
use MetaboxOrchestra\Bootstrap;
use MetaboxOrchestra\Boxes;
use MetaboxOrchestra\Metabox;
use Psr\Container\ContainerInterface as C;

class PluginModule implements ModuleInterface
{

    /**
     * @inheritDoc
     */
    public function setup(): ServiceProviderInterface
    {
        return new ServiceProvider(
            require __DIR__ . '/services.php',
            require __DIR__ . '/extensions.php'
        );
    }

    /**
     * @inheritDoc
     */
    public function run(C $container): void
    {
        add_action(
            'init',
            function () use ($container) {
                $this->registerAssets($container);
                $this->registerMetaboxOrchestration($container);

                /**
                 * Fire an action when the plugin is fully set up and ready to go.
                 * This includes a completed onboarding and working API authentication
                 */
                if ($container->get('paypal-pos.init-possible')) {
                    do_action('paypal-point-of-sale.init'); // Do we want to pass the container here maybe?
                }
            },
            PHP_INT_MAX
        );

        add_action('paypal-point-of-sale.migrate', $container->get('paypal-pos.clear-cache'));
    }

    /**
     * @param C $container
     *
     * phpcs:disable Generic.Metrics.NestingLevel.TooHigh
     */
    public function registerAssets(C $container): void
    {
        add_action(
            AssetManager::ACTION_SETUP,
            static function (AssetManager $assetManager) use ($container): void {
                $assets = $container->get('inpsyde.assets.registry');

                if (empty($assets)) {
                    return;
                }

                foreach ($assets as $asset) {
                    if (!($asset instanceof Asset)) {
                        continue;
                    }

                    $assetManager->register($asset);
                }
            }
        );
    }

    /**
     * @param C $container
     *
     * phpcs:disable Generic.Metrics.NestingLevel.TooHigh
     */
    public function registerMetaboxOrchestration(C $container): void
    {
        Bootstrap::bootstrap();

        add_action(
            Boxes::REGISTER_BOXES,
            static function (Boxes $boxes) use ($container): void {
                $metaboxes = $container->get('inpsyde.metabox.registry');

                foreach ($metaboxes as $metabox) {
                    if (!$metabox instanceof Metabox) {
                        continue;
                    }

                    $boxes->add_box($metabox);
                }
            }
        );
    }
}
