<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Settings\WC;

use Countable;
use Exception;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Onboarding\OnboardingState as S;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Onboarding\Settings\View\ButtonRendererTrait;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Provider\Organization\OrganizationProvider;
class ZettleIntegrationHeader implements ZettleIntegrationTemplate
{
    use ButtonRendererTrait;
    /**
     * @var callable
     */
    private $accountLinkData;
    private array $shopLinkData;
    /**
     * @var callable
     */
    private $linkData;
    private OrganizationProvider $organizationProvider;
    private array $disconnectAccountData;
    private Countable $productCounter;
    private ?int $firstImportTimestamp = null;
    /**
     * @var callable(int):string
     */
    private $timestampFormatter;
    private bool $priceSyncEnabled;
    private string $title;
    private string $description;
    private string $currentState;
    /**
     * @var callable
     */
    private $authCheck;
    private string $imgResourcesUrl;
    public function __construct(callable $accountLinkData, array $shopLinkData, callable $linkData, OrganizationProvider $organizationProvider, array $disconnectAccountData, Countable $productCounter, ?int $firstImportTimestamp, callable $timestampFormatter, bool $priceSyncEnabled, string $title, string $description, string $currentState, callable $authCheck, string $imgResourcesUrl)
    {
        $this->accountLinkData = $accountLinkData;
        $this->shopLinkData = $shopLinkData;
        $this->linkData = $linkData;
        $this->organizationProvider = $organizationProvider;
        $this->disconnectAccountData = $disconnectAccountData;
        $this->productCounter = $productCounter;
        $this->firstImportTimestamp = $firstImportTimestamp;
        $this->timestampFormatter = $timestampFormatter;
        $this->priceSyncEnabled = $priceSyncEnabled;
        $this->title = $title;
        $this->description = $description;
        $this->currentState = $currentState;
        $this->authCheck = $authCheck;
        $this->imgResourcesUrl = $imgResourcesUrl;
    }
    /**
     * @inheritDoc
     */
    public function render(): string
    {
        ob_start();
        ?>

        <div class="zettle-settings-header">
            <div class="zettle-settings-header-container">
                <div class="zettle-settings-header-details">
                    <div class="zettle-settings-header-logo">
                        <?php 
        echo $this->renderIcon();
        // phpcs:ignore WordPress.Security.EscapeOutput 
        ?>
                    </div>

                    <?php 
        echo $this->renderDetails();
        // phpcs:ignore WordPress.Security.EscapeOutput 
        ?>
                </div>

                <div class="zettle-settings-header-meta">
                    <?php 
        echo $this->renderMeta();
        // phpcs:ignore WordPress.Security.EscapeOutput 
        ?>
                </div>
            </div>
        </div>

        <?php 
        return (string) ob_get_clean();
    }
    /**
     * @return string
     */
    protected function renderIcon(): string
    {
        ob_start();
        ?>

        <img src="<?php 
        echo esc_url_raw("{$this->imgResourcesUrl}/logo.png");
        ?>"
            alt="<?php 
        echo esc_attr($this->title);
        ?>">

        <?php 
        return (string) ob_get_clean();
    }
    /**
     * @return string
     */
    protected function renderDetails(): string
    {
        ob_start();
        $authenticated = ($this->authCheck)();
        $zettleLink = ($this->linkData)($authenticated);
        // phpcs:disable WordPress.Security.EscapeOutput
        ?>

        <h2><?php 
        echo esc_html($this->title);
        ?></h2>

        <div class="zettle-settings-header-details-links">
            <?php 
        echo $this->renderLink($zettleLink['url'], $zettleLink['title'], $zettleLink['icon']);
        ?>

            <span class="separator">
                <?php 
        echo esc_html__(' | ', 'paypal-point-of-sale');
        ?>
            </span>

            <?php 
        echo $this->renderLink($this->shopLinkData['url'], $this->shopLinkData['title'], $this->shopLinkData['icon']);
        ?>
        </div>

        <p>
            <?php 
        echo wp_kses_post($this->description);
        ?>
        </p>

        <?php 
        return (string) ob_get_clean();
        // phpcs:enable
    }
    // phpcs:ignore Syde.Functions.FunctionLength.TooLong
    protected function renderMeta(): string
    {
        $authenticated = ($this->authCheck)();
        $accountLinkData = ($this->accountLinkData)($authenticated);
        ob_start();
        if ($this->currentState === S::WELCOME || $this->currentState === S::ONBOARDING_COMPLETED) {
            ?>
            <input type="hidden" name="zettle_onboarding_state"
                    value="<?php 
            echo esc_attr($this->currentState);
            ?>">
        <?php 
        }
        if ($this->currentState === S::WELCOME || $this->currentState === S::API_CREDENTIALS) {
            // phpcs:disable WordPress.Security.EscapeOutput
            echo $this->renderLink($accountLinkData['url'], $accountLinkData['title'], $accountLinkData['icon'], 'btn btn-secondary', 'btn', '_blank', $accountLinkData['popup'] ?? \false);
            // phpcs:enable
        }
        if ($this->currentState === S::WELCOME) {
            // phpcs:disable WordPress.Security.EscapeOutput
            echo $this->renderButton(__('Connect', 'paypal-point-of-sale'), 'save', __('Save changes', 'woocommerce'), \false, 'btn btn-primary', 'btn', 'submit');
            // phpcs:enable
        }
        if ($this->currentState === S::ONBOARDING_COMPLETED) {
            ?>
            <div class="zettle-settings-header-merchant-email">
                    <p><?php 
            echo esc_html((string) $this->email());
            ?></p>
            </div>
            <?php 
            if ($this->firstImportTimestamp) {
                ?>
                <div>
                        <p>
                            <?php 
                echo esc_html__('First import: ', 'paypal-point-of-sale');
                ?>
                            <?php 
                echo esc_html(($this->timestampFormatter)($this->firstImportTimestamp));
                ?>
                        </p>
                </div>
                <?php 
            }
            ?>
            <div>
                    <p>
                        <?php 
            echo esc_html__('Number of products syncing: ', 'paypal-point-of-sale');
            ?>
                        <?php 
            echo esc_html((string) $this->productCounter->count());
            ?>
                    </p>
            </div>
            <div>
                    <p>
                        <?php 
            echo esc_html__('Prices syncing: ', 'paypal-point-of-sale');
            ?>
                        <?php 
            echo esc_html($this->priceSyncEnabled ? __('Yes', 'paypal-point-of-sale') : __('No', 'paypal-point-of-sale'));
            ?>
                    </p>
            </div>
        <?php 
        }
        if ($this->currentState === S::ONBOARDING_COMPLETED) {
            // phpcs:disable WordPress.Security.EscapeOutput
            echo $this->renderButton($this->disconnectAccountData['title'], $this->disconnectAccountData['name'], $this->disconnectAccountData['value'], $this->disconnectAccountData['icon'], $this->disconnectAccountData['class'], '', 'button', ['data-micromodal-trigger' => $this->disconnectAccountData['dialog']['id']]);
            add_action('admin_footer', function () {
                echo $this->renderModal($this->disconnectAccountData['dialog']['id'], $this->disconnectAccountData['dialog']['title'], $this->disconnectAccountData['dialog']['content'], $this->disconnectAccountData['dialog']['buttons']);
            });
            // phpcs:enable
        }
        return (string) ob_get_clean();
    }
    /**
     * @param string $url
     * @param string $label
     * @param bool $withIcon
     * @param string $class
     * @param string $labelClass
     * @param string $target
     * @param bool $popup
     * @return string
     */
    private function renderLink(string $url, string $label, bool $withIcon = \false, string $class = 'link', string $labelClass = 'link', string $target = '_blank', bool $popup = \false): string
    {
        ob_start();
        ?>

        <a href="<?php 
        echo esc_url_raw($url);
        ?>"
            class="<?php 
        echo esc_attr($class);
        ?>"
            rel="noopener noreferrer"
            target="<?php 
        echo esc_attr($target);
        ?>" <?php 
        echo $popup ? 'data-popup="true"' : '';
        ?>>
            <?php 
        echo $this->renderLabel($label, esc_html($labelClass), $withIcon);
        // phpcs:ignore WordPress.Security.EscapeOutput 
        ?>
        </a>

        <?php 
        return (string) ob_get_clean();
    }
    /**
     * @param string $label
     * @param string $name
     * @param string $value
     * @param bool $withIcon
     * @param string $class
     * @param string $labelClass
     * @param string $type
     * @param array<string, string> $otherAttributes
     * @return string
     */
    private function renderButton(string $label, string $name, string $value, bool $withIcon = \false, string $class = 'btn btn-primary', string $labelClass = 'btn', string $type = 'submit', array $otherAttributes = []): string
    {
        ob_start();
        ?>

        <button name="<?php 
        echo esc_attr($name);
        ?>" class="<?php 
        echo esc_attr($class);
        ?>"
                type="<?php 
        echo esc_attr($type);
        ?>" value="<?php 
        echo esc_attr($value);
        ?>"
                <?php 
        // phpcs:ignore WordPress.Security.EscapeOutput
        echo implode(' ', array_map(static function (string $key) use ($otherAttributes): string {
            return sprintf('%1$s="%2$s"', esc_html($key), esc_attr($otherAttributes[$key]));
        }, array_keys($otherAttributes)));
        ?>
                >
            <?php 
        echo $this->renderLabel($label, esc_html($labelClass), $withIcon);
        // phpcs:ignore WordPress.Security.EscapeOutput 
        ?>
        </button>

        <?php 
        return (string) ob_get_clean();
    }
    /**
     * @param string $label
     * @param string $class
     * @param bool $withIcon
     *
     * @return string
     */
    private function renderLabel(string $label, string $class, bool $withIcon = \false): string
    {
        ob_start();
        if (!$withIcon) {
            echo esc_html($label);
            return (string) ob_get_clean();
        }
        ?>

        <span class="<?php 
        echo esc_attr("{$class}-label");
        ?>">
            <?php 
        echo esc_html($label);
        ?>
        </span>

        <span class="<?php 
        echo esc_attr("{$class}-icon");
        ?>">
            <?php 
        echo $this->renderIconExternalLink();
        // phpcs:ignore WordPress.Security.EscapeOutput 
        ?>
        </span>

        <?php 
        return (string) ob_get_clean();
    }
    /**
     * @return string
     */
    private function renderIconExternalLink(): string
    {
        ob_start();
        ?>

        <svg viewBox="2 2 22 22" xmlns="http://www.w3.org/2000/svg"
            xmlns:xlink="http://www.w3.org/1999/xlink">
            <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                <path d="M19.5692298,21.4615374 C20.6538451,21.4615374 21.4615374,20.6538451 21.4615374,19.5692298
                    L21.4615374,12.2307687 L20.0769221,12.2307687 L20.0769221,19.6153836 C20.0769221,19.8692298
                    19.8692298,20.0769221 19.6153836,20.0769221 L4.84615374,20.0769221 C4.5923076,20.0769221
                    4.3846153,19.8692298 4.3846153,19.6153836 L4.3846153,4.84615374 C4.3846153,4.5923076
                    4.5923076,4.3846153 4.84615374,4.3846153 L12.2307687,4.3846153 L12.2307687,3
                    L4.89230758,3 C3.80769226,3 3,3.80769226 3,4.89230758 L3,19.5692298 C3,20.6538451
                    3.80769226,21.4615374 4.89230758,21.4615374 L19.5692298,21.4615374 Z M10.3846149,15
                    L20.0769221,5.30769217 L20.0769221,9.46153808 L21.4615381,9.46153808 L21.4615381,3.46153843
                    C21.4615381,3.2076923 21.2538451,3 20.9999989,3 L14.9999993,3 L14.9999993,4.3846153
                    L19.1538452,4.3846153 L9.46153808,14.0769224 L10.3846149,15 Z" fill="#000000" fill-rule="nonzero">
                </path>
            </g>
        </svg>

        <?php 
        return (string) ob_get_clean();
    }
    /**
     * @param array<array{action: string, label: string, params?: array}> $buttons
     * Definitions of the dialog buttons.
     * 'action' - one of ButtonAction constants.
     * 'label' - button text.
     * 'params' - see ButtonRendererTrait.
     */
    private function renderModal(string $id, string $title, string $content, array $buttons): string
    {
        $buttonsHtml = implode('', array_map(function (array $btn): string {
            $params = $btn['params'] ?? [];
            $params = array_merge(['type' => 'button', 'value' => '', 'attributes' => array_merge(['data-micromodal-close' => ''], $params['attributes'] ?? [])], $params);
            return $this->renderActionButton($btn['action'], $btn['label'], $params);
        }, $buttons));
        ob_start();
        ?>

        <div class="zettle-settings zettle-modal">
            <div class="micromodal-slide" id="<?php 
        echo esc_attr($id);
        ?>" aria-hidden="true">
                <div class="zettle-modal-overlay" tabindex="-1" data-micromodal-close>
                    <div class="zettle-modal-container" role="dialog" aria-modal="true">
                        <header>
                            <h2><?php 
        echo esc_html($title);
        ?></h2>
                        </header>
                        <main>
                            <?php 
        echo $content;
        ?>
                        </main>
                        <footer class="zettle-settings-onboarding-actions">
                            <?php 
        echo $buttonsHtml;
        ?>
                        </footer>
                    </div>
                </div>
            </div>
        </div>

        <?php 
        return (string) ob_get_clean();
    }
    private function email(): ?string
    {
        try {
            return $this->organizationProvider->provide()->contactEmail();
        } catch (Exception $exception) {
            return null;
        }
    }
}
