<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Onboarding\Job;

use Exception;
use Inpsyde\Queue\Queue\Job\ContextInterface;
use Inpsyde\Queue\Queue\Job\Job;
use Inpsyde\Queue\Queue\Job\JobRepository;
use Psr\Log\LoggerInterface;
use Syde\PayPal\PointOfSale\Auth\OAuth\TokenPersistorInterface;
use Syde\PayPal\PointOfSale\Container\ClearableContainerInterface;
use Throwable;
use wpdb;

class ResetOnboardingJob implements Job
{
    public const TYPE = 'reset-onboarding';

    private wpdb $database;

    private ClearableContainerInterface $optionContainer;

    private ClearableContainerInterface $setupInfoContainer;

    private TokenPersistorInterface $tokenStorage;

    private array $tables;

    /**
     * @var string[]
     */
    private array $transients;

    /**
     * @var string[]
     */
    private array $options;

    /**
     * @var callable
     */
    private $deleteWebhooks;

    public function __construct(
        wpdb $database,
        ClearableContainerInterface $optionContainer,
        ClearableContainerInterface $setupInfoContainer,
        TokenPersistorInterface $tokenStorage,
        array $tables,
        array $transients,
        array $options,
        callable $deleteWebhooks
    ) {

        $this->database = $database;
        $this->optionContainer = $optionContainer;
        $this->setupInfoContainer = $setupInfoContainer;
        $this->tokenStorage = $tokenStorage;
        $this->tables = $tables;
        $this->transients = $transients;
        $this->options = $options;
        $this->deleteWebhooks = $deleteWebhooks;
    }

    /**
     * @inheritDoc
     */
    public function isUnique(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function type(): string
    {
        return self::TYPE;
    }

    /**
     * @inheritDoc
     */
    public function execute(
        ContextInterface $context,
        JobRepository $repository,
        LoggerInterface $logger
    ): bool {

        try {
            $this->resetOnboarding(
                $logger,
                $this->database->get_blog_prefix()
            );

            return true;
        } catch (Exception $exception) {
            $logger->error("ResetOnboarding failed because of: {$exception->getMessage()}");
        }

        return false;
    }

    /**
     * @param LoggerInterface $logger
     *
     * @param string $prefix
     * @return bool
     */
    private function resetOnboarding(LoggerInterface $logger, string $prefix): bool
    {
        $logger->info("Start reset the Onboarding");

        try {
            ($this->deleteWebhooks)();

            $logger->info('Removed Webhooks');
        } catch (Throwable $exception) {
            $logger->warning("Failed to remove Webhooks: " . (string) $exception);
        }

        $this->tokenStorage->clear();
        $logger->info("Flush Tokens");
        $this->optionContainer->clear();
        $this->setupInfoContainer->clear();
        $logger->info('Removed PayPal Point of Sale options');

        foreach ($this->tables as $table) {
            $this->database->query("TRUNCATE TABLE {$prefix}{$table}");
            $logger->info("Cleared table: '{$prefix}{$table}'");
        }

        foreach ($this->transients as $transient) {
            delete_transient($transient);
            $logger->info("Cleared Transient: '{$transient}'");
        }

        foreach ($this->options as $option) {
            delete_option($option);
            $logger->info("Cleared Option: '{$option}'");
        }

        $logger->info("Cleanup finished");

        return true;
    }
}
