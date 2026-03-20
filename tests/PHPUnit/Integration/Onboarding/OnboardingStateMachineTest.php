<?php
declare(strict_types=1);

use Inpsyde\StateMachine\Event\PostTransition;
use Inpsyde\StateMachine\Exceptions\DenyTransitionException;
use Inpsyde\StateMachine\StateMachineInterface;
use Syde\PayPal\PointOfSale\Auth\OAuth\CredentialValidator;
use Syde\PayPal\PointOfSale\Onboarding\Event\AuthCheck;
use Syde\PayPal\PointOfSale\Onboarding\Event\BackButtonPressed;
use Syde\PayPal\PointOfSale\Onboarding\Event\ProceedButtonPressed;
use Syde\PayPal\PointOfSale\Onboarding\Listener\UnhandledErrorListener;
use Syde\PayPal\PointOfSale\Onboarding\OnboardingState as S;
use Syde\PayPal\PointOfSale\Onboarding\OnboardingTransition as T;
use Syde\PayPal\PointOfSale\Onboarding\SyncCollisionStrategy;
use Syde\PayPal\PointOfSale\Test\ModuleContainerAwareTestCase;
use Psr\Log\NullLogger;
use function Brain\Monkey\Functions\expect;

class OnboardingStateMachineTest extends ModuleContainerAwareTestCase
{

    private const STATE_OPTION_NAME = 'onboarding.current-state';

    /**
     * @var \Syde\PayPal\PointOfSale\Container\WritableContainerInterface
     */
    private $optionsContainer;

    public function setUp(): void
    {
        $this->delayModuleContainerSetup();

        $this->injectFactory(
            'paypal-pos.logger.woocommerce',
            function () {
                return new NullLogger();
            }
        );

        $this->injectFactory(
            'paypal-pos.http.page-reloader',
            function () {
                return new PageReloader();
            }
        );
        $this->optionsContainer = new \Syde\PayPal\PointOfSale\Container\ArrayContainer([]);
        $this->injectFactory(
            'paypal-pos.settings',
            function () {
                return $this->optionsContainer;
            }
        );

        parent::setUp();

        expect('esc_html__')->andReturnUsing(
            function (string $html): string {
                return $html;
            }
        );
    }

    /**
     * @dataProvider defaultTestData
     */
    public function testOnboardingSteps(
        string $initialState,
        string $expectedTransition,
        string $expectedState,
        string $eventClass,
        array $data = []
    ) {
        $this->optionsContainer->set(self::STATE_OPTION_NAME, $initialState);

        // mock API auth check, we don't want to make actual HTTP requests here
        $authCheckValid = ($expectedTransition === T::TO_SYNC_PARAM_PRODUCTS);
        $credentialsValidator = Mockery::mock(CredentialValidator::class);
        $credentialsValidator->shouldReceive('validateApiToken')->andReturn($authCheckValid);
        $this->injectFactory(
            'paypal-pos.oauth.credential-validator',
            function () use ($credentialsValidator): CredentialValidator {
                return $credentialsValidator;
            }
        );

        // mock all post transition events,
        // they are complex and use too much WP stuff, should be tested separately
        $firedPostTransitionEvents = [];
        $this->injectFactory(
            'paypal-pos.onboarding.state-machine.transition-events',
            function () use (&$firedPostTransitionEvents): array {
                $listeners = [];
                $allTransitions = (new ReflectionClass(T::class))->getConstants();
                foreach ($allTransitions as $transition) {
                    $listeners[$transition] = function (PostTransition $event) use (&$firedPostTransitionEvents): void {
                        $firedPostTransitionEvents[] = $event;
                    };
                }

                return $listeners;
            }
        );

        $this->setupModuleContainer();

        $machine = $this->stateMachine();
        $currentState = $machine->currentState()->name();

        $this->assertSame(
            $initialState,
            $currentState,
            "State machine should have been initialized with state {$initialState}"
        );

        $transitions = $machine->availableTransitions();

        $this->assertContains(
            $expectedTransition,
            array_keys($transitions),
            "Transition {$expectedTransition} should be available for state {$currentState}"
        );

        $machine->handle(new $eventClass($data));

        $this->assertSame(
            $expectedState,
            $machine->currentState()->name(),
            "Expected State should have been reached by the state machine"
        );

        $this->assertCount(
            1,
            $firedPostTransitionEvents,
            'Expected one post transition event'
        );
        $postTransitionEvent = $firedPostTransitionEvents[0];
        assert($postTransitionEvent instanceof PostTransition);
        $this->assertEquals($initialState, $postTransitionEvent->fromState());
        $this->assertEquals($expectedTransition, $postTransitionEvent->transition()->name());
    }

    /**
     * Tests that auth failure can be triggered on some steps.
     *
     * @dataProvider authCheckStates
     */
    public function testAuthFailureChecks(string $initialState)
    {
        $this->optionsContainer->set(self::STATE_OPTION_NAME, $initialState);

        $this->injectFactory(
            'paypal-pos.sdk.api.auth-check',
            function () {
                return function () {
                    return false;
                };
            }
        );

        $this->setupModuleContainer();

        $machine = $this->stateMachine();

        $machine->handle(new AuthCheck());

        $this->assertSame(
            S::INVALID_CREDENTIALS,
            $machine->currentState()->name(),
            'Auth failure state should have been reached by the state machine'
        );
    }

    /**
     * Tests that auth failure cannot be triggered on other steps.
     *
     * @dataProvider authFailureNotAllowedStates
     */
    public function testAuthFailureNotAllowedStates(string $initialState)
    {
        $this->optionsContainer->set(self::STATE_OPTION_NAME, $initialState);

        $this->injectFactory(
            'paypal-pos.sdk.api.auth-check',
            function () {
                return function () {
                    $this->fail('Should not call auth check');
                };
            }
        );

        $this->setupModuleContainer();

        $machine = $this->stateMachine();

        $machine->handle(new AuthCheck());

        $this->assertSame(
            $initialState,
            $machine->currentState()->name(),
            'State should not change'
        );

        if ($initialState === S::API_CREDENTIALS) {
            return;
        }

        $this->expectException(DenyTransitionException::class);

        $machine->apply(T::TO_INVALID_CREDENTIALS);
    }

    /**
     * Tests that unhandled error state can be triggered on some steps.
     *
     * @dataProvider unhandledErrorStates
     */
    public function testUnhandledError(string $initialState)
    {
        $this->optionsContainer->set(self::STATE_OPTION_NAME, $initialState);

        $this->injectFactory(
            'paypal-pos.throw-unhandled-errors',
            function () {
                return false;
            }
        );

        $this->setupModuleContainer();

        $this->unhandledErrorListener()(new LogicException('BOOM!'));

        $machine = $this->stateMachine();

        $this->assertSame(
            S::UNHANDLED_ERROR,
            $machine->currentState()->name(),
            'Unhandled error state should have been reached by the state machine'
        );
    }

    /**
     * Tests that unhandled error step cannot be triggered on other steps.
     *
     * @dataProvider unhandledErrorNotAllowedStates
     */
    public function testUnhandledErrorNotAllowedStates(string $initialState)
    {
        $this->optionsContainer->set(self::STATE_OPTION_NAME, $initialState);

        $this->injectFactory(
            'paypal-pos.throw-unhandled-errors',
            function () {
                return false;
            }
        );

        $this->setupModuleContainer();

        $this->unhandledErrorListener()(new LogicException('BOOM!'));

        $machine = $this->stateMachine();

        $this->assertSame(
            $initialState,
            $machine->currentState()->name(),
            'State should not change'
        );

        $this->expectException(DenyTransitionException::class);

        $machine->apply(T::TO_UNHANDLED_ERROR);
    }

    private function stateMachine(): StateMachineInterface
    {
        return $this->get('inpsyde.state-machine');
    }

    private function unhandledErrorListener(): UnhandledErrorListener
    {
        return $this->get('paypal-pos.onboarding.failure.listener');
    }

    public function defaultTestData()
    {
        // initial state
        // expected transition
        // expected state
        // event that triggers state machine
        // (optional) event data
        yield 'Can begin onboarding' => [
            S::WELCOME,
            T::TO_API_CREDENTIALS,
            S::API_CREDENTIALS,
            ProceedButtonPressed::class,
        ];
        yield 'Can go back to welcome screen' => [
            S::API_CREDENTIALS,
            T::TO_WELCOME,
            S::WELCOME,
            BackButtonPressed::class,
        ];
        yield 'Can go back to api credentials after success' => [
            S::SYNC_PARAM_PRODUCTS,
            T::TO_API_CREDENTIALS,
            S::API_CREDENTIALS,
            BackButtonPressed::class,
        ];
        yield 'Credentials test can fail' => [
            S::API_CREDENTIALS,
            T::TO_INVALID_CREDENTIALS,
            S::INVALID_CREDENTIALS,
            ProceedButtonPressed::class,
        ];
        yield 'Failed credentials can start over' => [
            S::INVALID_CREDENTIALS,
            T::TO_API_CREDENTIALS,
            S::API_CREDENTIALS,
            ProceedButtonPressed::class,
        ];
        yield 'Credentials can be valid' => [
            S::API_CREDENTIALS,
            T::TO_SYNC_PARAM_PRODUCTS,
            S::SYNC_PARAM_PRODUCTS,
            ProceedButtonPressed::class,
            ['woocommerce_zettle_api_key' => getenv('IZETTLE_API_KEY')],
        ];
        yield 'VAT sync params can be reached' => [
            S::SYNC_PARAM_PRODUCTS,
            T::TO_SYNC_PARAM_VAT,
            S::SYNC_PARAM_VAT,
            ProceedButtonPressed::class,
            ['woocommerce_zettle_sync_collision_strategy' => SyncCollisionStrategy::MERGE],
        ];
        yield 'Can go back from VAT params' => [
            S::SYNC_PARAM_VAT,
            T::TO_SYNC_PARAM_PRODUCTS,
            S::SYNC_PARAM_PRODUCTS,
            BackButtonPressed::class,
        ];
        yield 'Sync Finished page can be reached' => [
            S::SYNC_PROGRESS,
            T::TO_SYNC_FINISHED,
            S::SYNC_FINISHED,
            ProceedButtonPressed::class,
        ];
        yield 'Onboarding can be completed' => [
            S::SYNC_FINISHED,
            T::TO_ONBOARDING_COMPLETED,
            S::ONBOARDING_COMPLETED,
            ProceedButtonPressed::class,
        ];
    }

    public function authCheckStates()
    {
        $states = [
            S::SYNC_PARAM_PRODUCTS,
            S::SYNC_PARAM_VAT,
            S::SYNC_PROGRESS,
        ];

        foreach ($states as $state) {
            yield "Auth can fail on {$state}" => [$state];
        }
    }

    public function authFailureNotAllowedStates()
    {
        $states = [
            S::WELCOME,
            S::API_CREDENTIALS,
            S::INVALID_CREDENTIALS,
            S::SYNC_FINISHED,
            S::ONBOARDING_COMPLETED,
            S::UNHANDLED_ERROR,
        ];

        foreach ($states as $state) {
            yield "Auth cannot fail on {$state}" => [$state];
        }
    }

    public function unhandledErrorStates()
    {
        $states = [
            S::WELCOME,
            S::API_CREDENTIALS,
            S::INVALID_CREDENTIALS,
            S::SYNC_PARAM_PRODUCTS,
            S::SYNC_PARAM_VAT,
            S::SYNC_PROGRESS,
            S::SYNC_FINISHED,
        ];

        foreach ($states as $state) {
            yield "Can go to Unhandled error state from {$state}" => [$state];
        }
    }

    public function unhandledErrorNotAllowedStates()
    {
        $states = [
            S::ONBOARDING_COMPLETED,
            S::UNHANDLED_ERROR,
        ];

        foreach ($states as $state) {
            yield "Cannot go to Unhandled error state from {$state}" => [$state];
        }
    }
}
