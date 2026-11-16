<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Webhooks\Job;

use Syde\Vendor\Zettle\Inpsyde\Queue\Queue\Job\ContextInterface;
use Syde\Vendor\Zettle\Inpsyde\Queue\Queue\Job\Job;
use Syde\Vendor\Zettle\Inpsyde\Queue\Queue\Job\JobRepository;
use Syde\Vendor\Zettle\Psr\Log\LoggerInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\OrderSync\Backfill\PurchaseBackfill;
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
    private PurchaseBackfill $backfill;
    public function __construct(callable $webhookRegistration, callable $canRegisterWebhooks, PurchaseBackfill $backfill)
    {
        $this->webhookRegistration = $webhookRegistration;
        $this->canRegisterWebhooks = $canRegisterWebhooks;
        $this->backfill = $backfill;
    }
    /**
     * @inheritDoc
     */
    public function execute(ContextInterface $context, JobRepository $repository, LoggerInterface $logger): bool
    {
        if (!($this->canRegisterWebhooks)()) {
            return \true;
        }
        try {
            ($this->webhookRegistration)();
        } catch (Throwable $throwable) {
            // Registration deletes the remote webhook first, so a failure leaves none behind.
            // Returning false lets the queue retry; a throw would drop the job.
            $logger->error(sprintf('Webhook registration failed: %s', $throwable->getMessage()));
            $this->backfill->rememberWebhookGap();
            return \false;
        }
        $this->backfill->recoverWebhookGap();
        return \true;
    }
    public function isUnique(): bool
    {
        return \true;
    }
    public function type(): string
    {
        return self::TYPE;
    }
}
