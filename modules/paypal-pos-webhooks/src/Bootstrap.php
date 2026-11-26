<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Webhooks;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\WebhookException;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\ZettleRestException;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Webhooks\Job\WebhookRegistrationJob;
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
    public function __construct(callable $createJob, callable $webhookDeletion, callable $isOnboarded)
    {
        $this->createJob = $createJob;
        $this->webhookDeletion = $webhookDeletion;
        $this->isOnboarded = $isOnboarded;
    }
    /**
     * Onboarding registers the webhook itself; a registration queued before it completes would
     * replace that webhook again.
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
        } catch (ZettleRestException|WebhookException $exc) {
            // looks like it is already logged inside, so no need to duplicate logging
            // but probably should not throw here to not abort deactivation
        }
    }
}
