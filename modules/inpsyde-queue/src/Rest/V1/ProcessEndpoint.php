<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\Queue\Rest\V1;

use Syde\Vendor\Zettle\Inpsyde\Queue\Exception\QueueLockedException;
use Syde\Vendor\Zettle\Inpsyde\Queue\ExceptionLoggingTrait;
use Syde\Vendor\Zettle\Inpsyde\Queue\Log\ArrayLogger;
use Syde\Vendor\Zettle\Inpsyde\Queue\Logger\LoggerProviderInterface;
use Syde\Vendor\Zettle\Inpsyde\Queue\Processor\ProcessorBuilder;
use Syde\Vendor\Zettle\Inpsyde\Queue\Queue\Job\JobRepository;
use Syde\Vendor\Zettle\Inpsyde\Queue\Queue\Locker;
use Syde\Vendor\Zettle\Inpsyde\Queue\Queue\TimeStopper;
use Syde\Vendor\Zettle\Psr\Log\LoggerInterface;
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
    private ProcessorBuilder $builder;
    private LoggerInterface $logger;
    /**
     * @var callable
     */
    private $metaCallback;
    private Locker $locker;
    private JobRepository $repository;
    private bool $isMultisite;
    private int $maxRetriesCount;
    public function __construct(ProcessorBuilder $processorBuilder, JobRepository $repository, Locker $locker, LoggerInterface $logger, callable $metaCallback, bool $isMultisite, int $maxRetriesCount)
    {
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
        // phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
        // phpcs:disable Syde.Functions.ReturnTypeDeclaration.NoReturnType
        return ['types' => ['types' => 'array', 'default' => [], 'validate_callback' => static function (mixed $value): bool {
            return is_array($value);
        }, 'sanitize_callback' => static function (mixed $value) {
            return (array) $value;
        }], 'executionTime' => ['type' => 'integer', 'default' => self::DEFAULT_EXECUTION_TIME, 'minimum' => 0, 'maximum' => 30, 'validate_callback' => static function (mixed $value): bool {
            return is_numeric($value);
        }, 'sanitize_callback' => static function (mixed $value) {
            return (int) sanitize_text_field($value);
        }], 'meta' => ['type' => 'array', 'default' => [], 'validate_callback' => static function (mixed $value): bool {
            return is_array($value);
        }, 'sanitize_callback' => static function (mixed $value) {
            return $value;
        }]];
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
        $queueProcessor = $this->builder->withRepository($this->repository)->withLogger($this->logger)->withJobTypes($types)->withStopper(new TimeStopper((float) $executionTime))->withLocker($this->locker)->withNetworkSupport($this->isMultisite)->withMaxRetriesCount($this->maxRetriesCount)->build();
        $responseStatus = 200;
        try {
            $processedJobs = $queueProcessor->process();
        } catch (QueueLockedException $exception) {
            $responseStatus = 204;
            // No Content
        } catch (Throwable $exception) {
            $responseStatus = 500;
            $this->logException($exception, $this->logger);
        }
        if ($queueProcessor instanceof LoggerProviderInterface) {
            $logs = $this->getLogs($queueProcessor->logger());
        }
        $responseData = ['logs' => $logs ?? [], 'completed' => $processedJobs ?? 0, 'remaining' => $queueProcessor->repository()->count($types), 'meta' => ($this->metaCallback)($meta, $types) ?? []];
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
