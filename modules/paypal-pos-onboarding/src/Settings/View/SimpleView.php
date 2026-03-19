<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Onboarding\Settings\View;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Onboarding\Settings\ButtonAction;
class SimpleView implements OnboardingView
{
    use ButtonRendererTrait;
    /**
     * @var string
     */
    private $title;
    /**
     * @var string
     */
    private $content;
    /**
     * @var string
     */
    private $proceedButtonHtml = '';
    /**
     * @var string
     */
    private $backButtonHtml = '';
    /**
     * SimpleView constructor.
     *
     * @param string $title
     * @param string $content
     */
    public function __construct(string $title, string $content)
    {
        $this->title = $title;
        $this->content = $content;
    }
    /**
     * @param string|null $label The button text, or null to use default text.
     * @return SimpleView
     */
    public function withProceedButton(?string $label = null): SimpleView
    {
        $this->proceedButtonHtml = $this->renderActionButton(ButtonAction::PROCEED, $label);
        return $this;
    }
    /**
     * @param string|null $label The button text, or null to use default text.
     * @return SimpleView
     */
    public function withBackButton(?string $label = null): SimpleView
    {
        $this->backButtonHtml = $this->renderActionButton(ButtonAction::BACK, $label);
        return $this;
    }
    public function renderHeader(): string
    {
        ob_start();
        ?>

        <h2><?php 
        echo esc_html($this->title);
        ?></h2>

        <?php 
        return ob_get_clean();
    }
    public function renderContent(): string
    {
        ob_start();
        ?>

        <p>
            <?php 
        echo esc_html($this->content);
        ?>
        </p>

        <?php 
        return ob_get_clean();
    }
    public function renderProceedButton(): string
    {
        return $this->proceedButtonHtml;
    }
    public function renderBackButton(): string
    {
        return $this->backButtonHtml;
    }
}
