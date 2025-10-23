<?php
declare(strict_types=1);

use Syde\PayPal\PointOfSale\Http\PageReloaderInterface;

class PageReloader implements PageReloaderInterface
{
    /**
     * @var bool
     */
    private $executed = false;

    public function reload(): void
    {
        $this->executed = true;
    }

    public function isExecuted(): bool
    {
        return $this->executed;
    }
}
