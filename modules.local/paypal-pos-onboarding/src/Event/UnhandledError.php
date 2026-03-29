<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Onboarding\Event;

use Inpsyde\StateMachine\Event\GenericStateChange;
use Throwable;

class UnhandledError extends GenericStateChange
{
    private Throwable $exception;

    public function __construct(Throwable $exception)
    {
        $this->exception = $exception;
    }

    public function getException(): Throwable
    {
        return $this->exception;
    }
}
