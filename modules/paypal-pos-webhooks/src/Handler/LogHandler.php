<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Webhooks\Handler;

use Syde\Vendor\Zettle\Psr\Log\LoggerInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\API\Webhooks\Entity\Payload;
class LogHandler implements WebhookHandler
{
    private LoggerInterface $logger;
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
    /**
     * @inheritDoc
     */
    public function accepts(Payload $payload): bool
    {
        return \true;
    }
    /**
     * @inheritDoc
     */
    public function handle(Payload $payload): void
    {
        $this->logger->info("Received Webhook: {$payload->eventName()}", $payload->payload());
    }
}
