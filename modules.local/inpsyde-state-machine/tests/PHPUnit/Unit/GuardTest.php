<?php
declare(strict_types=1);

namespace Inpsyde\StateMachine\Test;

use Inpsyde\StateMachine\Guard\Guard;
use MonkeryTestCase\BrainMonkeyWpTestCase;

class GuardTest extends BrainMonkeyWpTestCase
{

    /**
     * @dataProvider defaultData
     */
    public function testGuardHandles(
        string $guardedTransition,
        string $transition,
        string $fromState,
        bool $expectedResult,
        ?string $guardFromState
    ) {
        $guard = new Guard($guardedTransition, $guardFromState);
        $result = $guard->handles($transition, $fromState);
        $this->assertSame($expectedResult, $result);
    }

    public function defaultData()
    {
        yield [
            // $guardedTransition
            'foo',
            // $transition
            'foo',
            // $fromState
            'bar',
            // $expectedResult
            true,
            // $guardFromState
            null,
        ];

        yield [
            // $guardedTransition
            'foo',
            // $transition
            'foo',
            // $fromState
            'bar',
            // $expectedResult
            true,
            // $guardFromState
            'bar',
        ];

        yield [
            // $guardedTransition
            'foo',
            // $transition
            'baz',
            // $fromState
            'bar',
            // $expectedResult
            false,
            // $guardFromState
            'bar',
        ];
    }
}
