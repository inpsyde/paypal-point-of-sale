<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Webhooks\Job;

use Inpsyde\Queue\Queue\Job\ContextInterface;
use Inpsyde\Queue\Queue\Job\Job;
use Inpsyde\Queue\Queue\Job\JobRepository;
use Psr\Log\LoggerInterface;

class WebhookRegistrationJob implements Job
{
    const TYPE = 'webhook-registration';

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

        ($this->webhookRegistration)();
        // looks like errors are already logged inside, so no need to duplicate logging

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
