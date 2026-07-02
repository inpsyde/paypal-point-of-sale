<?php

declare(strict_types=1);

use Syde\PayPal\PointOfSale\PhpSdk\API\Products\Products;
use Syde\PayPal\PointOfSale\Test\AuthenticatedRestRequestTestCase;
use Syde\PayPal\PointOfSale\Test\EnvMapCredentialsContainer;
use Mockery\MockInterface;
use Psr\Container\ContainerInterface;
use function Brain\Monkey\Functions\when;

class CredentialsTest extends AuthenticatedRestRequestTestCase
{

    protected function setUp(): void
    {
        parent::setUp();

        when('get_transient')->justReturn(false);
        when('set_transient')->justReturn(true);
    }

    /**
     * Test if the auth plugin fetches the credentials from the settings container,
     * and auth is successful
     */
    public function testCredentialsContainer()
    {
        $this->injectExtension(
            'paypal-pos.oauth.credentials.parent',
            static function (?ContainerInterface $previous, ContainerInterface $container) {
                return Mockery::spy(new EnvMapCredentialsContainer());
            }
        );
        $this->setupModuleContainer();

        $settings = $this->get('paypal-pos.oauth.credentials.parent');
        $this->assertInstanceOf(ContainerInterface::class, $settings);
        assert($settings instanceof MockInterface);

        $productClient = $this->get('paypal-pos.sdk.api.products');
        assert($productClient instanceof Products);

        $productClient->list();

        $settings->shouldHaveReceived('get')->with('api_key');
    }
}
