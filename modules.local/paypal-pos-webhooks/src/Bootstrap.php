<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Webhooks;

use Syde\PayPal\PointOfSale\PhpSdk\Exception\WebhookException;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\ZettleRestException;
use Syde\PayPal\PointOfSale\Webhooks\Job\WebhookRegistrationJob;

class Bootstrap
{
    /**
     * @var callable
     */
    private $createJob;

    /**
     * @var callable
     */
    private $webhookDeletion;

    /**
     * @var callable(): bool
     */
    private $isOnboarded;

    public function __construct(
        callable $createJob,
        callable $webhookDeletion,
        callable $isOnboarded
    ) {

        $this->createJob = $createJob;
        $this->webhookDeletion = $webhookDeletion;
        $this->isOnboarded = $isOnboarded;
    }

    /**
     * Before onboarding is completed there is nothing to register yet: onboarding registers the
     * webhook itself, and a queued registration would replace it right afterwards.
     */
    public function activate(): void
    {
        if (($this->isOnboarded)()) {
            ($this->createJob)(WebhookRegistrationJob::TYPE);
        }
    }

    public function deactivate(): void
    {
        try {
            ($this->webhookDeletion)();
        } catch (ZettleRestException | WebhookException $exc) {
            // looks like it is already logged inside, so no need to duplicate logging
            // but probably should not throw here to not abort deactivation
        }
    }
}
