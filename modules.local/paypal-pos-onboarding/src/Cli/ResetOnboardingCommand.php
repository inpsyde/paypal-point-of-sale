<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Onboarding\Cli;

use Inpsyde\Queue\Queue\Job\Context;
use Inpsyde\Queue\Queue\Job\EphemeralJobRepository;
use Inpsyde\Queue\Queue\Job\Job;
use Psr\Log\LoggerInterface;

class ResetOnboardingCommand
{
    private Job $resetOnboardingJob;

    private bool $isMultisite;

    private int $currentSiteId;

    private LoggerInterface $logger;

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
    public function complete(array $args, array $assocArgs): void
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
    public function site(array $args, array $assocArgs): void
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
