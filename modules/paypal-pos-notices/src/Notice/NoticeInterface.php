<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Notices\Notice;

interface NoticeInterface
{
    /**
     * @param string $currentState
     *
     * @return bool
     */
    public function accepts(string $currentState): bool;
    /**
     * @return string
     */
    public function render(): string;
}
