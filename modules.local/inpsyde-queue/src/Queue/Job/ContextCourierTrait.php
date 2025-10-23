<?php

declare(strict_types=1);

namespace Inpsyde\Queue\Queue\Job;

use DateTime;
use stdClass;

trait ContextCourierTrait
{
    /**
     * @var ContextInterface
     */
    protected $context;
    /**
     * @inheritDoc
     */
    public function args(): stdClass
    {
        return $this->context->args();
    }

    /**
     * @inheritDoc
     */
    public function id(): int
    {
        return $this->context->id();
    }

    /**
     * @inheritDoc
     */
    public function forSite(): int
    {
        return $this->context->forSite();
    }

    /**
     * @inheritDoc
     */
    public function created(): DateTime
    {
        return $this->context->created();
    }
}
