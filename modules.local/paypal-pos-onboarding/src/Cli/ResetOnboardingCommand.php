<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Onboarding\Cli;

use Inpsyde\Queue\Queue\Job\Context;
use Inpsyde\Queue\Queue\Job\EphemeralJobRepository;
use Inpsyde\Queue\Queue\Job\Job;
use Inpsyde\Queue\Queue\QueueProcessor;
use Psr\Log\LoggerInterface;

class ResetOnboardingCommand
{
    /**
     * @var Job
     */
    private $resetOnboardingJob;

    /**
     * @var bool
     */
    private $isMultisite;

    /**
     * @var int
     */
    private $currentSiteId;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Job $resetOnboardingJob
     * @param bool $isMultisite
     * @param int $currentSiteId
     * @param LoggerInterface $logger
     */
    public function __construct(
        Job $resetOnboardingJob,
        bool $isMultisite,
        int $currentSiteId,
        LoggerInterface $logger
    ) {
        $this->resetOnboardingJob = $resetOnboardingJob;
        $this->isMultisite = $isMultisite;
        $this->currentSiteId = $currentSiteId;
        $this->logger = $logger;
    }

    /**
     * Reset the Zettle WooCommerce Configuration (Credentials & Parameters)
     * & connected Products at WooCommerce
     *
     * ## EXAMPLES
     *
     *     wp zettle reset onboarding complete
     *
     * @when after_wp_load
     *
     * @param array $args
     * @param array $assocArgs
     */
    public function complete(array $args, array $assocArgs)
    {
        $this->resetOnboardingJob->execute(
            Context::fromArray([]),
            new EphemeralJobRepository(),
            $this->logger
        );
    }

    /**
     * Reset the Zettle WooCommerce Configuration (Credentials & Parameters)
     * & connected Products at WooCommerce
     *
     * ## EXAMPLES
     *
     *     wp zettle reset onboarding site --url=<site-url>
     *
     * @when after_wp_load
     *
     * @param array $args
     * @param array $assocArgs
     */
    public function site(array $args, array $assocArgs)
    {
        if (!$this->isMultisite) {
            $this->logger->error("This Command is only available for Multisite Setups");

            return;
        }

        $this->resetOnboardingJob->execute(
            Context::fromArray([], $this->currentSiteId),
            new EphemeralJobRepository(),
            $this->logger
        );
    }
}
