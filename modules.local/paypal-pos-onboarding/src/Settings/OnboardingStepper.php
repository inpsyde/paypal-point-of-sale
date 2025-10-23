<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Onboarding\Settings;

use Inpsyde\StateMachine\State\State;

class OnboardingStepper
{
    /**
     * @var array
     */
    private $states;

    /**
     * @var string
     */
    private $currentStep;

    /**
     * @var array
     */
    private $exclude;

    /**
     * @var string
     */
    private $stepLabel;

    /**
     * @var string
     */
    private $stepsTemplate;

    /**
     * @var array
     */
    private $steppableMap;

    /**
     * @var int
     */
    private $currentPosition;

    /**
     * OnboardingStepper constructor.
     *
     * @param array $states
     * @param string $currentStep
     * @param array $exclude
     * @param string $stepLabel
     * @param string $stepsTemplate
     */
    public function __construct(
        array $states,
        string $currentStep,
        array $exclude,
        string $stepLabel,
        string $stepsTemplate = '%1$d/%2$d'
    ) {
        $this->states = $states;
        $this->currentStep = $currentStep;
        $this->exclude = $exclude;
        $this->stepLabel = $stepLabel;
        $this->stepsTemplate = $stepsTemplate;

        $this->initialize();
    }

    /**
     * @return bool
     */
    public function canRender(): bool
    {
        return in_array($this->currentStep, $this->steppableMap, true);
    }

    /**
     * @return string
     */
    public function render(): string
    {
        ob_start(); ?>
            <div class="zettle-settings-onboarding-stepper">
                <?php echo esc_html($this->generateStepper()); ?>
            </div>
        <?php return ob_get_clean();
    }

    /**
     * @return int
     */
    public function currentPosition(): int
    {
        return $this->currentPosition;
    }

    /**
     * Initialize the Stepper
     */
    protected function initialize(): void
    {
        $this->steppableMap = $this->generateSteppableMap($this->states, $this->exclude);
        $this->currentPosition = $this->walkSteppableMap($this->steppableMap);
    }

    /**
     * @param array $states
     *
     * @param array $exclude
     * @return array
     */
    protected function generateSteppableMap(array $states, array $exclude = []): array
    {
        $map = [];

        foreach ($states as $state) {
            assert($state instanceof State);

            if (in_array($state->name(), $exclude, true)) {
                continue;
            }

            $map[] = $state->name();
        }

        return $map;
    }

    /**
     * @param array $steppableMap
     *
     * @return int
     */
    protected function walkSteppableMap(array $steppableMap): int
    {
        $position = 1;

        foreach ($steppableMap as $index => $step) {
            if ($this->currentStep === $step) {
                $position = $index + 1;
            }

            continue;
        }

        return $position;
    }

    /**
     * @return string
     */
    private function generateStepper(): string
    {
        return sprintf(
            '%s %s',
            $this->stepLabel,
            sprintf(
                $this->stepsTemplate,
                $this->currentPosition(),
                count($this->steppableMap) + 1
            )
        );
    }
}
