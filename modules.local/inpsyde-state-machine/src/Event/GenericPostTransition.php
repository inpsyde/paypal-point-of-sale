<?php

declare(strict_types=1);

namespace Inpsyde\StateMachine\Event;

class GenericPostTransition implements PostTransition
{
    use TransitionEventTrait;
}
