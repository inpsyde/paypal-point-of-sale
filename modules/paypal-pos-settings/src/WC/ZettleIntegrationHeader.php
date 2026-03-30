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
    public function __construct(callable $accountLinkData, array $shopLinkData, callable $linkData, OrganizationProvider $organizationProvider, array $disconnectAccountData, Countable $productCounter, ?int $firstImportTimestamp, callable $timestampFormatter, bool $priceSyncEnabled, string $title, string $description, string $currentState, callable $authCheck)
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
        // WPCS: xss ok. 
        ?>
                    </div>

                    <?php 
        echo $this->renderDetails();
        // WPCS: xss ok. 
        ?>
                </div>

                <div class="zettle-settings-header-meta">
                    <?php 
        echo $this->renderMeta();
        // WPCS: xss ok. 
        ?>
                </div>
            </div>
        </div>

        <?php 
        return ob_get_clean();
    }
    /**
     * @return string
     */
    protected function renderIcon(): string
    {
        ob_start();
        ?>

        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1203.2667 357.05334">
            <g transform="matrix(1.3333333,0,0,-1.3333333,0,357.05333)">
                <g transform="scale(0.1)">
                    <path
                        d="m 2045.15,1997.54 c 32.67,208.31 -0.23,350.06 -112.89,478.44 -124.05,141.36 -348.16,201.91 -634.88,201.91 H 465.082 c -58.602,0 -108.492,-42.63 -117.625,-100.52 L 0.886719,379.711 C -5.94922,336.34 27.5664,297.129 71.4609,297.129 H 585.277 L 549.813,72.2422 C 543.832,34.3086 573.148,0 611.551,0 h 433.089 c 51.26,0 94.9,37.2891 102.89,87.9414 l 4.25,22.0076 81.59,517.36 5.26,28.589 c 7.99,50.633 51.63,87.942 102.9,87.942 h 64.78 c 419.57,0 748.1,170.461 844.09,663.42 40.12,206 19.38,377.95 -86.7,498.82 -32.08,36.56 -72,66.8 -118.55,91.46 v 0"
                        style="fill:#009cde;fill-opacity:1;fill-rule:nonzero;stroke:none"
                    />
                    <path
                        d="m 2045.15,1997.54 c 32.67,208.31 -0.23,350.06 -112.89,478.44 -124.05,141.36 -348.16,201.91 -634.88,201.91 H 465.082 c -58.602,0 -108.492,-42.63 -117.625,-100.52 L 0.886719,379.711 C -5.94922,336.34 27.5664,297.129 71.4609,297.129 H 585.277 l 129.071,818.441 -4.004,-25.67 c 9.133,57.88 58.605,100.52 117.203,100.52 h 244.203 c 479.61,0 855.16,194.86 964.89,758.38 3.25,16.68 6.02,32.85 8.51,48.74"
                        style="fill:#012169;fill-opacity:1;fill-rule:nonzero;stroke:none"
                    />
                    <path
                        d="m 853,1994.77 c 5.488,34.82 27.836,63.33 57.926,77.75 13.672,6.55 28.949,10.19 44.969,10.19 H 1608.3 c 77.29,0 149.35,-5.05 215.24,-15.66 18.83,-3.04 37.15,-6.52 54.95,-10.47 17.8,-3.95 35.07,-8.37 51.8,-13.26 8.36,-2.44 16.59,-5.01 24.67,-7.69 32.35,-10.74 62.47,-23.41 90.19,-38.09 32.67,208.31 -0.23,350.06 -112.89,478.44 -124.05,141.36 -348.16,201.91 -634.88,201.91 H 465.086 c -58.606,0 -108.496,-42.63 -117.629,-100.52 L 0.886719,379.699 C -5.94922,336.34 27.5664,297.129 71.4609,297.129 H 585.281 L 714.348,1115.57 853,1994.77"
                        style="fill:#003087;fill-opacity:1;fill-rule:nonzero;stroke:none"
                    />
                    <path
                        d="m 8564.75,1311.71 c 172.03,0 221.2,-112.31 221.2,-214.21 0,-24.51 0,-63.14 -7.13,-94.78 h -575.65 c 59.58,179.15 203.52,308.99 361.58,308.99 z m 431.71,-473.89 c 17.66,94.782 28.07,168.46 28.07,235.17 0,245.71 -129.84,445.81 -435.26,445.81 -372.13,0 -652.91,-322.96 -652.91,-705.64 0,-312.41 203.54,-540.578 547.58,-540.578 140.39,0 270.37,24.508 386.24,87.789 l 31.5,221.051 C 8771.84,514.73 8659.53,493.77 8543.65,493.77 c -214.2,0 -365.13,119.3 -365.13,319.39 v 24.66 z m -4200.79,369.25 c 66.7,65.33 149.28,104.64 236.26,104.64 172.02,0 221.19,-112.31 221.19,-214.21 0,-24.51 0,-63.14 -7.12,-94.78 h -575.66 c 26.85,80.68 70.68,151.2 125.33,204.35 z m 668.1,-369.25 c 17.53,94.782 28.08,168.46 28.08,235.17 0,245.71 -129.98,445.81 -435.41,445.81 -93.68,0 -181.61,-20.54 -260.77,-57.25 -235.03,-109.3 -392.13,-362.13 -392.13,-648.39 0,-108.058 24.38,-205.98 70.4,-287.89 87.24,-155.051 252.15,-252.688 477.18,-252.688 140.52,0 270.37,24.508 386.24,87.789 L 5369,581.422 C 5239.02,514.73 5126.7,493.77 5010.83,493.77 c -82.86,0 -156.27,17.941 -215.16,51.492 -93.27,53.008 -149.98,145.32 -149.98,267.898 v 24.66 z M 7697.23,2080.49 7416.32,297.09 h 259.82 l 287.9,1822.02 z m -777.95,-796.86 h 414.17 l 70.26,203.67 h -452.8 l 52.6,322.96 -270.36,-38.62 -45.62,-284.34 h -572.22 l 52.73,322.96 -270.37,-38.62 -45.61,-284.34 h -217.77 l -31.5,-203.67 h 217.63 l -98.34,-600.298 c -3.43,-31.641 -6.98,-59.723 -6.98,-87.801 0,-231.742 172.02,-322.949 379.11,-322.949 91.36,0 214.21,24.508 322.96,73.68 l 35.2,224.75 c -122.86,-59.711 -217.63,-73.813 -287.89,-73.813 -108.89,0 -196.55,49.172 -179.02,161.473 l 98.2,624.958 h 572.24 l -98.2,-600.298 c -3.57,-31.641 -7.13,-59.723 -7.13,-87.801 0,-231.742 172.03,-322.949 379.26,-322.949 119.28,0 242.15,24.508 351.03,73.68 l 35.06,224.75 c -122.85,-59.711 -217.64,-73.813 -315.98,-73.813 -105.32,0 -196.53,49.172 -179,161.473 z M 2851.06,525.27 2814.63,297.09 h 1467.42 l 35.07,228.18 z m 279.41,1555.22 -35.06,-231.61 h 1390.31 l 35.06,231.61 H 3130.47"
                        style="fill:#003087;fill-opacity:1;fill-rule:nonzero;stroke:none"
                    />
                    <path
                        d="M 4485.72,1848.88 3187.51,525.27 h -336.45 l 1298.13,1323.61 h 336.53"
                        style="fill:#ae9ff3;fill-opacity:1;fill-rule:nonzero;stroke:none"
                    />
                </g>
            </g>
        </svg>

        <?php 
        return ob_get_clean();
    }
    /**
     * @return string
     */
    protected function renderDetails(): string
    {
        ob_start();
        $authenticated = ($this->authCheck)();
        $zettleLink = ($this->linkData)($authenticated);
        ?>

        <h2><?php 
        echo esc_html($this->title);
        ?></h2>

        <div class="zettle-settings-header-details-links">
            <?php 
        echo $this->renderLink($zettleLink['url'], $zettleLink['title'], $zettleLink['icon']);
        // WPCS: xss ok. 
        ?>

            <span class="separator">
                <?php 
        echo esc_html__(' | ', 'paypal-point-of-sale');
        ?>
            </span>

            <?php 
        echo $this->renderLink($this->shopLinkData['url'], $this->shopLinkData['title'], $this->shopLinkData['icon']);
        // WPCS: xss ok. 
        ?>
        </div>

        <p>
            <?php 
        echo wp_kses_post($this->description);
        ?>
        </p>

        <?php 
        return ob_get_clean();
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
        // WPCS: xss ok.
        if ($this->currentState === S::WELCOME || $this->currentState === S::API_CREDENTIALS) {
            echo $this->renderLink($accountLinkData['url'], $accountLinkData['title'], $accountLinkData['icon'], 'btn btn-secondary', 'btn', '_blank', $accountLinkData['popup'] ?? \false);
            // WPCS: xss ok.
        }
        if ($this->currentState === S::WELCOME) {
            echo $this->renderButton(__('Connect', 'paypal-point-of-sale'), 'save', __('Save changes', 'woocommerce'), \false, 'btn btn-primary', 'btn', 'submit');
            // WPCS: xss ok.
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
            echo $this->renderButton($this->disconnectAccountData['title'], $this->disconnectAccountData['name'], $this->disconnectAccountData['value'], $this->disconnectAccountData['icon'], $this->disconnectAccountData['class'], '', 'button', ['data-micromodal-trigger' => $this->disconnectAccountData['dialog']['id']]);
            // WPCS: xss ok.
            add_action('admin_footer', function () {
                echo $this->renderModal($this->disconnectAccountData['dialog']['id'], $this->disconnectAccountData['dialog']['title'], $this->disconnectAccountData['dialog']['content'], $this->disconnectAccountData['dialog']['buttons']);
                // WPCS: xss ok.
            });
        }
        return ob_get_clean();
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
        // WPCS: xss ok. 
        ?>
        </a>

        <?php 
        return ob_get_clean();
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
        // WPCS: xss ok. 
        ?>
        </button>

        <?php 
        return ob_get_clean();
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
            return ob_get_clean();
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
        // WPCS: xss ok. 
        ?>
        </span>

        <?php 
        return ob_get_clean();
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
        return ob_get_clean();
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
        return ob_get_clean();
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
