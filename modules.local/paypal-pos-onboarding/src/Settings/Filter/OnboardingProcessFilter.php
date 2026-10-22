<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Onboarding\Settings\Filter;

use Syde\PayPal\PointOfSale\Onboarding\OnboardingState as S;

/**
 * Filters out settings fields based on the onboarding state
 */
class OnboardingProcessFilter implements SettingsFilter
{
    /** @var array<string> */
    private array $knownStates = [
        S::WELCOME,
        S::API_CREDENTIALS,
        S::INVALID_CREDENTIALS,
        S::SYNC_PARAM_PRODUCTS,
        S::SYNC_PARAM_VAT,
        S::SYNC_PROGRESS,
        S::SYNC_FINISHED,
        S::ONBOARDING_COMPLETED,
        S::UNHANDLED_ERROR,
    ];

    /** @var array<string, array<string>> */
    private array $disabled = [
        S::API_CREDENTIALS => [
            'api_key',
        ],
        S::INVALID_CREDENTIALS => [],
        S::SYNC_PARAM_PRODUCTS => [
            'sync_collision_strategy',
        ],
        S::SYNC_PARAM_VAT => [
            'sync_price_strategy',
        ],
    ];

    /**
     * Needed for IZET-273 fix (old settings were re-submitted)
     * @var string[]
     */
    private array $settingsResetStates = [
        S::WELCOME,
    ];

    /** @var array<string> */
    private array $authFieldKeys = [
        'authentication',
        'api_key',
    ];

    private string $currentState;

    /**
     * OnboardingProcessFilter constructor.
     *
     * @param string $currentState
     */
    public function __construct(string $currentState)
    {
        $this->currentState = $currentState;
    }

    public function filter(array $settings): array
    {
        if (!in_array($this->currentState, $this->knownStates, true)) {
            return $settings;
        }
        $whitelist = $this->getWhitelist();
        $disabled = $this->getDisabled();

        foreach ($this->getRemovedFields() as $key) {
            $settings[$key]['zettle_remove'] = true;
        }

        foreach ($settings as $key => $config) {
            // If the Field has no custom attributes just add it
            if (!isset($settings[$key]['custom_attributes'])) {
                $settings[$key]['custom_attributes'] = [];
            }
            if (in_array($key, $whitelist, true) || $this->isVisibleAfterOnboarding($config)) {
                continue;
            }
            $settings[$key]['zettle_hide'] = true;

            // Fields kept for after onboarding may be asked for by a step's own input; the
            // standard input stays disabled so it doesn't post a second value.
            if (
                in_array($key, $disabled, true)
                    || in_array($this->currentState, $this->settingsResetStates, true)
                    || !empty($config['zettle_visible_after_onboarding'])
            ) {
                $settings[$key]['custom_attributes']['readonly'] = '';
                $settings[$key]['custom_attributes']['disabled'] = '';
            }
        }

        return $settings;
    }

    private function getWhitelist(): array
    {
        if ($this->isSettingsReviewPage()) {
            return [
                'authentication',
                'api_key',
                'sync_params',
                'sync_price_strategy',
            ];
        }

        return [];
    }

    private function getDisabled(): array
    {
        if (!isset($this->disabled[$this->currentState])) {
            return [];
        }

        return $this->disabled[$this->currentState];
    }

    private function getRemovedFields(): array
    {
        if ($this->currentState === S::API_CREDENTIALS || $this->isSettingsReviewPage()) {
            return [];
        }

        return $this->authFieldKeys;
    }

    /**
     * Fields set `zettle_visible_after_onboarding` to stay editable once onboarding is complete.
     *
     * @param mixed $config
     */
    private function isVisibleAfterOnboarding($config): bool
    {
        return $this->currentState === S::ONBOARDING_COMPLETED
            && is_array($config)
            && !empty($config['zettle_visible_after_onboarding']);
    }

    private function isSettingsReviewPage(): bool
    {
        $expanded = filter_input(INPUT_GET, 'review', FILTER_VALIDATE_BOOL);
        return $this->currentState === S::ONBOARDING_COMPLETED && $expanded;
    }
}
