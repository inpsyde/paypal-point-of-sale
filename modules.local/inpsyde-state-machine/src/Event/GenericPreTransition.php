<?php

declare(strict_types=1);

namespace Inpsyde\StateMachine\Event;

class GenericPreTransition implements PreTransition
{
    use TransitionEventTrait;
}
