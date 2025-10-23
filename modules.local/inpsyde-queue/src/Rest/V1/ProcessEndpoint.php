<?php

declare(strict_types=1);

# -*- coding: utf-8 -*-

namespace Inpsyde\Queue\Rest\V1;

use Inpsyde\Queue\Exception\QueueLockedException;
use Inpsyde\Queue\ExceptionLoggingTrait;
use Inpsyde\Queue\Log\ArrayLogger;
use Inpsyde\Queue\Logger\LoggerProviderInterface;
use Inpsyde\Queue\Processor\ProcessorBuilder;
use Inpsyde\Queue\Processor\QueueProcessor;
use Inpsyde\Queue\Queue\Job\JobRepository;
use Inpsyde\Queue\Queue\Locker;
use Inpsyde\Queue\Queue\TimeStopper;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Throwable;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class ProcessEndpoint implements EndpointInterface
{
    use ExceptionLoggingTrait;

    public const METHODS = WP_REST_Server::READABLE;
    public const VERSION = 'v1';
    public const ROUTE = '/process';
    public const DEFAULT_EXECUTION_TIME = 10;

    /**
     * @var ProcessorBuilder
     */
    private $builder;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var callable
     */
    private $metaCallback;

    /**
     * @var Locker
     */
    private $locker;

    /**
     * @var JobRepository
     */
    private $repository;

    /**
     * @var bool
     */
    private $isMultisite;

    /**
     * @var int
     */
    private $maxRetriesCount;
    public function __construct(
        ProcessorBuilder $processorBuilder,
        JobRepository $repository,
        Locker $locker,
        LoggerInterface $logger,
        callable $metaCallback,
        bool $isMultisite,
        int $maxRetriesCount
    ) {

        $this->builder = $processorBuilder;
        $this->logger = $logger;
        $this->metaCallback = $metaCallback;
        $this->locker = $locker;
        $this->repository = $repository;
        $this->isMultisite = $isMultisite;
        $this->maxRetriesCount = $maxRetriesCount;
    }

    /** @inheritDoc */
    public function methods(): string
    {
        return self::METHODS;
    }

    /** @inheritDoc */
    public function version(): string
    {
        return self::VERSION;
    }

    /** @inheritDoc */
    public function route(): string
    {
        return self::ROUTE;
    }

    /** @inheritDoc */
    public function permissionCallback(): bool
    {
        return current_user_can('manage_woocommerce');
    }

    /** @inheritDoc */
    public function args(): array
    {
        // phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
        // phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType
        return [
            'types' => [
                'types' => 'array',
                'default' => [],
                'validate_callback' => static function ($value): bool {
                    return is_array($value);
                },
                'sanitize_callback' => static function ($value) {
                    return (array) $value;
                },
            ],
            'executionTime' => [
                'type' => 'integer',
                'default' => self::DEFAULT_EXECUTION_TIME,
                'minimum' => 0,
                'maximum' => 30,
                'validate_callback' => static function ($value): bool {
                    return is_numeric($value);
                },
                'sanitize_callback' => static function ($value) {
                    return (int) sanitize_text_field($value);
                },
            ],
            'meta' => [
                'type' => 'array',
                'default' => [],
                'validate_callback' => static function ($value): bool {
                    return is_array($value);
                },
                'sanitize_callback' => static function ($value) {
                    return $value;
                },
            ],
        ];
        // phpcs:enable
    }

    /**
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     */
    public function handleRequest(WP_REST_Request $request): WP_REST_Response
    {
        $types = (array) $request->get_param('types');
        $executionTime = (int) $request->get_param('executionTime');
        $meta = (array) $request->get_param('meta');

        $queueProcessor = $this->builder
            ->withRepository($this->repository)
            ->withLogger($this->logger)
            ->withJobTypes($types)
            ->withStopper(new TimeStopper((float) $executionTime))
            ->withLocker($this->locker)
            ->withNetworkSupport($this->isMultisite)
            ->withMaxRetriesCount($this->maxRetriesCount)
            ->build();

        $responseStatus = 200;
        try {
            $processedJobs = $queueProcessor->process();
        } catch (QueueLockedException $exception) {
            $responseStatus = 204; // No Content
        } catch (Throwable $exception) {
            $responseStatus = 500;
            $this->logException($exception, $this->logger);
        }

        if ($queueProcessor instanceof LoggerProviderInterface) {
            $logs = $this->getLogs($queueProcessor->logger());
        }

        $responseData = [
            'logs' => $logs ?? [],
            'completed' => $processedJobs ?? 0,
            'remaining' => $queueProcessor->repository()->count($types),
            'meta' => ($this->metaCallback)($meta, $types) ?? [],
        ];

        return new WP_REST_Response($responseData, $responseStatus);
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return array
     */
    private function getLogs(LoggerInterface $logger): array
    {
        if ($logger instanceof ArrayLogger) {
            return $logger->logs();
        }

        return [];
    }
}
