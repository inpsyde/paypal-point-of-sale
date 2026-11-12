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

        try {
            $webhooks = $this->ownWebhooks();
            $this->assertCount(1, $webhooks);

            $registeredWebhook = $webhooks[0];
            $this->assertEquals($this->email, $registeredWebhook->contactEmail());
            $this->assertEqualsCanonicalizing([
                'ProductDeleted',
                'InventoryBalanceChanged',
                'InventoryTrackingStarted',
                'InventoryTrackingStopped',
                'PurchaseCreated',
            ], $registeredWebhook->eventNames());
        } finally {
            // Delete even when an assertion fails, so a failed run leaves no webhook behind.
            $deleteWebhooks = $this->get('paypal-pos.webhook.delete');
            $this->assertIsCallable($deleteWebhooks);
            $deleteWebhooks();
        }

        $this->assertEmpty($this->ownWebhooks(), 'Should have no webhooks after deletion.');
    }

    /**
     * The CI account is shared, so only webhooks pointing at this run's destination count.
     *
     * @return RegisteredZettleWebhook[]
     */
    private function ownWebhooks(): array
    {
        return array_values(array_filter(
            $this->subscriptions()->list(),
            fn (RegisteredZettleWebhook $webhook): bool => (string) $webhook->destination() === $this->destination
        ));
    }

    private function subscriptions(): Subscriptions
    {
        return $this->get('paypal-pos.sdk.api.webhooks');
    }
}
