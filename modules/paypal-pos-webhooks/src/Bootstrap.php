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
    public function __construct(callable $createJob, callable $webhookDeletion)
    {
        $this->createJob = $createJob;
        $this->webhookDeletion = $webhookDeletion;
    }
    public function activate(): void
    {
        ($this->createJob)(WebhookRegistrationJob::TYPE);
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
