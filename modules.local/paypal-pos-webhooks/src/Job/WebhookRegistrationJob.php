<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Webhooks\Job;

use Inpsyde\Queue\Queue\Job\ContextInterface;
use Inpsyde\Queue\Queue\Job\Job;
use Inpsyde\Queue\Queue\Job\JobRepository;
use Psr\Log\LoggerInterface;
use Throwable;

class WebhookRegistrationJob implements Job
{
    public const TYPE = 'webhook-registration';

    /**
     * @var callable
     */
    private $webhookRegistration;

    /**
     * @var callable
     */
    private $canRegisterWebhooks;

    public function __construct(callable $webhookRegistration, callable $canRegisterWebhooks)
    {
        $this->webhookRegistration = $webhookRegistration;
        $this->canRegisterWebhooks = $canRegisterWebhooks;
    }

    /**
     * @inheritDoc
     */
    public function execute(
        ContextInterface $context,
        JobRepository $repository,
        LoggerInterface $logger
    ): bool {

        if (!($this->canRegisterWebhooks)()) {
            return true;
        }

        try {
            ($this->webhookRegistration)();
        } catch (Throwable $throwable) {
            // Registration deletes the remote webhook first, so a failure leaves none behind.
            // Returning false lets the queue retry; a throw would drop the job.
            $logger->error(sprintf('Webhook registration failed: %s', $throwable->getMessage()));

            return false;
        }

        return true;
    }

    public function isUnique(): bool
    {
        return true;
    }

    public function type(): string
    {
        return self::TYPE;
    }
}
