<?php

use Syde\PayPal\PointOfSale\PhpSdk\API\Webhooks\Entity\RegisteredZettleWebhook;
use Syde\PayPal\PointOfSale\PhpSdk\API\Webhooks\Subscriptions;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\ZettleRestException;
use Syde\PayPal\PointOfSale\Test\AuthenticatedRestRequestTestCase;

/**
 *  @group sync
 */
class WebhookSubscriptionsTest extends AuthenticatedRestRequestTestCase
{
    private $destination;

    private $email = 'admin@zettle-acceptance.woo';

    protected function setUp(): void
    {
        $destination = getenv('WEBHOOK_DESTINATION_URL');
        if ($destination === false || $destination === '') {
            $this->markTestSkipped('WEBHOOK_DESTINATION_URL not set; no reachable webhook endpoint.');
        }
        $this->destination = $destination;

        $this->injectFactory(
            'paypal-pos.webhook.config.email',
            function (): string {
                return $this->email;
            }
        );
        $this->injectFactory(
            'paypal-pos.webhook.listener.url',
            function (): string {
                return $this->destination;
            }
        );
        $this->injectFactory(
            'paypal-pos.webhook.can-register',
            function (): callable {
                return function (): bool {
                    return true;
                };
            }
        );

        parent::setUp();
    }


    public function testRegisterWebhook()
    {
        $registerWebhooks = $this->get('paypal-pos.webhook.register');
        $this->assertIsCallable($registerWebhooks);

        try {
            $registerWebhooks();
        } catch (ZettleRestException $exc) {
            throw new Exception($exc->getMessage() . ' ' . json_encode($exc->json()), 0, $exc);
        }

        $webhooks = $this->subscriptions()->list();
        $this->assertCount(1, $webhooks);

        $registeredWebhook = $webhooks[0];
        assert($registeredWebhook instanceof RegisteredZettleWebhook, 'Webhook must be registered.');
        $this->assertEquals($this->destination, $registeredWebhook->destination());
        $this->assertEquals($this->email, $registeredWebhook->contactEmail());
        $this->assertEqualsCanonicalizing([
            'ProductDeleted',
            'InventoryBalanceChanged',
            'InventoryTrackingStarted',
            'InventoryTrackingStopped',
        ], $registeredWebhook->eventNames());

        // delete webhooks

        $deleteWebhooks = $this->get('paypal-pos.webhook.delete');
        $this->assertIsCallable($deleteWebhooks);

        $deleteWebhooks();

        $this->assertEmpty($this->subscriptions()->list(), 'Should have no webhooks after deletion.');
    }

    private function subscriptions(): Subscriptions
    {
        return $this->get('paypal-pos.sdk.api.webhooks');
    }
}
