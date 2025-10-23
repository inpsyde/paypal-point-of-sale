<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Webhooks\Handler;

use Syde\PayPal\PointOfSale\PhpSdk\API\Webhooks\Entity\Payload;
use Psr\Log\LoggerInterface;

class LogHandler implements WebhookHandler
{

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function accepts(Payload $payload): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function handle(Payload $payload)
    {
        $this->logger->info("Received Webhook: {$payload->eventName()}", $payload->payload());
    }
}
