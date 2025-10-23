<?php
declare(strict_types=1);

use Syde\PayPal\PointOfSale\PhpSdk\API\Webhooks\Entity\Webhook;
use Syde\PayPal\PointOfSale\PhpSdk\API\Webhooks\Subscriptions;
use Syde\PayPal\PointOfSale\Webhooks\WebhookDeletion;
use MonkeryTestCase\BrainMonkeyWpTestCase;
use Nyholm\Psr7\Uri;
use Psr\Log\LoggerInterface;

class WebhookDeletionTest extends BrainMonkeyWpTestCase
{
    private $defaultDestination;

    private $localWebhook;
    private $createdWebhook;

    private $subscriptions;

    private $logger;

    private $canManageWebhooks;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultDestination = new Uri('https://example.local');

        $this->localWebhook = $this->mockHook();
        $this->createdWebhook = $this->mockHook();

        $this->subscriptions = Mockery::mock(Subscriptions::class);

        $this->logger = Mockery::mock(LoggerInterface::class);

        $this->canManageWebhooks = function (): bool {
            return true;
        };
    }

    /**
     * @dataProvider defaultTestData
     */
    public function testExecute(array $existing)
    {
        if (!empty($existing)) {
            $this->localWebhook
                ->shouldReceive('destination')
                ->atLeast(1)
                ->andReturn($this->defaultDestination);
        }

        $this->subscriptions
            ->shouldReceive('list')
            ->once()
            ->andReturn($existing);

        /**
         * Every existing webhook is deleted.
         */
        foreach ($existing as $existingHook) {
            $id = uniqid();
            $existingHook->shouldReceive('uuid')->andReturn($id);
            $existingHook->shouldReceive('destination')->andReturn($this->defaultDestination);
            $this->subscriptions->shouldReceive('delete')->once()->with($id);
        }

        $testee = new WebhookDeletion(
            $this->localWebhook,
            $this->subscriptions,
            $this->logger,
            $this->canManageWebhooks
        );

        $testee->execute();

        $this->assertTrue(true); // fix no assertions warning
    }

    public function defaultTestData()
    {
        yield 'no existing' => [
            [],
        ];
        yield 'with existing' => [
            [
                $this->mockHook(),
                $this->mockHook(),
                $this->mockHook(),
                $this->mockHook(),
            ],
        ];
    }

    private function mockHook()
    {
        return Mockery::mock(Webhook::class);
    }
}
