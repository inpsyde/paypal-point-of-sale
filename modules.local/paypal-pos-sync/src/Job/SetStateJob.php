<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Sync\Job;

use Inpsyde\Queue\Queue\Job\ContextInterface;
use Inpsyde\Queue\Queue\Job\Job;
use Inpsyde\Queue\Queue\Job\JobRepository;
use Psr\Log\LoggerInterface;

class SetStateJob implements Job
{
    const TYPE = 'set-state';

    /**
     * @var callable
     */
    private $setState;

    public function __construct(callable $setState)
    {
        $this->setState = $setState;
    }

    /**
     * @inheritDoc
     * phpcs:disable NeutronStandard.Functions.DisallowCallUserFunc.CallUserFunc
     */
    public function execute(
        ContextInterface $context,
        JobRepository $repository,
        LoggerInterface $logger
    ): bool {

        $state = (string) $context->args()->state;
        ($this->setState)($state);

        return true;
    }

    /**
     * @inheritDoc
     */
    public function isUnique(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function type(): string
    {
        return self::TYPE;
    }
}
