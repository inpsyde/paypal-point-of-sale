<?php

namespace Inpsyde\StateMachine\Test;

use Inpsyde\StateMachine\Transition\Transition;
use PHPUnit\Framework\TestCase;

class TransitionTest extends TestCase
{

    public function testMethods()
    {
        $name = 'foo';
        $transition = new Transition($name, ['created'], 'processed');
        $this->assertSame($name, $transition->name());
    }
}