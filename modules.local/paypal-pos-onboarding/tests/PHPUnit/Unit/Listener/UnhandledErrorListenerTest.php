<?php
declare(strict_types=1);

use Inpsyde\StateMachine\State\State;
use Inpsyde\StateMachine\StateMachineInterface;
use Syde\PayPal\PointOfSale\Auth\Exception\AuthenticationException;
use Syde\PayPal\PointOfSale\Http\PageReloaderInterface;
use Syde\PayPal\PointOfSale\Onboarding\Event\AuthFailed;
use Syde\PayPal\PointOfSale\Onboarding\Event\UnhandledError;
use Syde\PayPal\PointOfSale\Onboarding\Listener\UnhandledErrorListener;
use Syde\PayPal\PointOfSale\Onboarding\OnboardingState as S;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\ZettleRestException;
use MonkeryTestCase\BrainMonkeyWpTestCase;

class UnhandledErrorListenerTest extends BrainMonkeyWpTestCase
{
    private const AUTH_FAILED_ACTION = 'inpsyde.zettle.onboarding.auth-failed';

    private const INITIAL_STATE = S::SYNC_PARAM_VAT;

    private $stateMachine;
    private $pageReloader;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stateMachine = Mockery::mock(StateMachineInterface::class);

        $this->pageReloader = Mockery::mock(PageReloaderInterface::class);
    }

    function tearDown(): void
    {
        // fix no assertions warning for mockery-only tests
        $mockeryContainer = Mockery::getContainer();
        if ($mockeryContainer) {
            $this->addToAssertionCount($mockeryContainer->mockery_getExpectationCount());
        }

        parent::tearDown();
    }

    /**
     * @dataProvider authExceptions
     */
    public function testAuthError(Throwable $exception)
    {
        $sut = $this->createSut();

        $this->stateMachine
            ->shouldReceive('currentState')
            ->andReturn(new State(self::INITIAL_STATE))
            ->once();

        $this->stateMachine
            ->shouldReceive('handle')
            ->with(Mockery::type(AuthFailed::class))
            ->once();

        $this->stateMachine
            ->shouldReceive('currentState')
            ->andReturn(new State(S::INVALID_CREDENTIALS))
            ->once();

        $this->pageReloader
            ->shouldReceive('reload')
            ->once();

        $sut($exception);
    }

    /**
     * @dataProvider authExceptions
     */
    public function testAuthErrorNotAllowedStates(Throwable $exception)
    {
        $sut = $this->createSut();

        $this->stateMachine
            ->shouldReceive('currentState')
            ->andReturn(new State(self::INITIAL_STATE));

        $this->stateMachine
            ->shouldReceive('handle')
            ->with(Mockery::type(AuthFailed::class))
            ->once();

        $this->stateMachine
            ->shouldReceive('handle')
            ->once()
            ->with(Mockery::type(UnhandledError::class));

        $sut($exception);
    }

    /**
     * @dataProvider otherExceptions
     */
    public function testOtherExceptions(Throwable $exception)
    {
        $sut = $this->createSut();

        $this->stateMachine
            ->shouldReceive('currentState')
            ->andReturn(new State(self::INITIAL_STATE))
            ->once();

        $this->stateMachine
            ->shouldReceive('handle')
            ->with(Mockery::type(UnhandledError::class))
            ->once();

        $this->stateMachine
            ->shouldReceive('currentState')
            ->andReturn(new State(S::UNHANDLED_ERROR))
            ->once();

        $this->pageReloader
            ->shouldReceive('reload')
            ->once();

        $sut($exception);
    }

    /**
     * @dataProvider otherExceptions
     */
    public function testOtherExceptionsNotAllowedState(Throwable $exception)
    {
        $sut = $this->createSut();

        $this->stateMachine
            ->shouldReceive('currentState')
            ->andReturn(new State(self::INITIAL_STATE));

        $this->stateMachine
            ->shouldReceive('handle')
            ->with(Mockery::type(UnhandledError::class))
            ->once();

        $sut($exception);
    }

    public function authExceptions()
    {
        yield 'AuthenticationException' => [new AuthenticationException('Auth error')];
        yield 'Inner AuthenticationException' => [new ZettleRestException('BOOM!', 0, [], [], new AuthenticationException('Auth error'))];
        yield 'ZettleRestException auth error type' => [new ZettleRestException('BOOM!', 0, ['errorType' => ZettleRestException::TYPE_UNAUTHENTICATED])];
    }

    public function otherExceptions()
    {
        yield 'Error' => [new Error('BOOM!')];
        yield 'ZettleRestException error type' => [new ZettleRestException('BOOM!', 0, ['errorType' => ZettleRestException::TYPE_ENTITY_NOT_FOUND])];
    }

    private function createSut(): UnhandledErrorListener {
        return new UnhandledErrorListener(
            $this->stateMachine,
            $this->pageReloader,
            false
        );
    }
}
