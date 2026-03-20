<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Onboarding\Event;

use Syde\Vendor\Zettle\Inpsyde\StateMachine\Event\GenericStateChange;
use Throwable;
class UnhandledError extends GenericStateChange
{
    /**
     * @var Throwable
     */
    private $exception;
    public function __construct(Throwable $exception)
    {
        $this->exception = $exception;
    }
    public function getException(): Throwable
    {
        return $this->exception;
    }
}
