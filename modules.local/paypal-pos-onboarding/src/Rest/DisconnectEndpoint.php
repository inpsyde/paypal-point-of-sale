<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Onboarding\Rest;

use Inpsyde\Queue\Queue\Job\Context;
use Inpsyde\Queue\Queue\Job\EphemeralJobRepository;
use Inpsyde\Queue\Queue\Job\Job;
use Psr\Log\LoggerInterface;
use Throwable;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class DisconnectEndpoint implements EndpointInterface
{
    /**
     * @var Job
     */
    protected $onboardingResetJob;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(Job $onboardingResetJob, LoggerInterface $logger)
    {
        $this->onboardingResetJob = $onboardingResetJob;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function methods(): string
    {
        return WP_REST_Server::CREATABLE;
    }

    /**
     * @inheritDoc
     */
    public function version(): string
    {
        return 'v1';
    }

    /**
     * @inheritDoc
     */
    public function route(): string
    {
        return '/disconnect';
    }

    /**
     * @inheritDoc
     */
    public function permissionCallback(): bool
    {
        return current_user_can('manage_woocommerce');
    }

    /**
     * @inheritDoc
     */
    public function args(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function handleRequest(WP_REST_Request $request): WP_REST_Response
    {
        try {
            $this->onboardingResetJob->execute(
                Context::fromArray([]),
                new EphemeralJobRepository(),
                $this->logger
            );
        } catch (Throwable $exception) {
            $this->logger->error(sprintf(
                'Error during reset job. %1$s',
                $exception->getMessage()
            ));

            return new WP_REST_Response(['result' =>
                [
                    'success' => false,
                    'error' => $exception->getMessage(),
                ],
            ], 500);
        }

        return new WP_REST_Response(['result' => ['success' => true]], 200);
    }
}
