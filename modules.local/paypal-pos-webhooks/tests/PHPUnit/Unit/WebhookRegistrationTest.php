<?php
declare(strict_types=1);

use Syde\PayPal\PointOfSale\PhpSdk\API\Webhooks\Entity\Webhook;
use Syde\PayPal\PointOfSale\PhpSdk\API\Webhooks\Subscriptions;
use Syde\PayPal\PointOfSale\Webhooks\WebhookDeletion;
use Syde\PayPal\PointOfSale\Webhooks\WebhookRegistration;
use MonkeryTestCase\BrainMonkeyWpTestCase;
use Psr\Log\LoggerInterface;

class WebhookRegistrationTest extends BrainMonkeyWpTestCase
{
    private $localWebhook;
    private $createdWebhook;

    private $subscriptions;

    private $webhookDeletion;

    private $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->localWebhook = Mockery::mock(Webhook::class);
        $this->createdWebhook = Mockery::mock(Webhook::class);

        $this->subscriptions = Mockery::mock(Subscriptions::class);

        $this->webhookDeletion = Mockery::mock(WebhookDeletion::class);

        $this->logger = Mockery::mock(LoggerInterface::class);
    }

    public function testSuccess()
    {
        $this->webhookDeletion->shouldReceive('execute')->once();

        $this->subscriptions
            ->shouldReceive('create')
            ->once()
            ->with($this->localWebhook)
            ->andReturn($this->createdWebhook);

        $testee = new WebhookRegistration(
            $this->localWebhook,
            $this->subscriptions,
            $this->webhookDeletion,
            $this->logger
        );

        $result = $testee->execute();

        $this->assertEquals($this->createdWebhook, $result, 'Created webhook should match expected webhook');
    }

    public function testError()
    {
        $this->webhookDeletion->shouldReceive('execute')->once();

        $this->subscriptions
            ->shouldReceive('create')
            ->once()
            ->with($this->localWebhook)
            ->andThrow(new Exception('BOOM'));

        $testee = new WebhookRegistration(
            $this->localWebhook,
            $this->subscriptions,
            $this->webhookDeletion,
            $this->logger
        );

        $this->expectException(Exception::class);

        $testee->execute();
    }
}
