<?php

declare(strict_types=1);

namespace Inpsyde\Queue\Queue;

use Syde\PayPal\PointOfSale\Operator\Option\OptionOperatorInterface;

/**
 * Class SiteOptionLocker
 *
 * @package Inpsyde\Queue\Queue
 */
class SiteOptionLocker implements Locker
{
    /**
     * @var OptionOperatorInterface
     */
    private $optionOperator;

    /**
     * @var int
     */
    private $timeout;

    /**
     * @var string
     */
    private $optionKey;

    /**
     * SiteOptionLocker constructor.
     *
     * @param OptionOperatorInterface $optionOperator
     * @param int $timeout
     * @param string $optionKey
     */
    public function __construct(
        OptionOperatorInterface $optionOperator,
        int $timeout,
        string $optionKey
    ) {
        $this->optionOperator = $optionOperator;
        $this->timeout = $timeout;
        $this->optionKey = $optionKey;
    }

    /**
     * @return bool
     */
    public function lock(): bool
    {
        return $this->optionOperator->update($this->optionKey, time());
    }

    /**
     * @return bool
     */
    public function unlock(): bool
    {
        return $this->optionOperator->update($this->optionKey, 0);
    }

    /**
     * @return bool
     */
    public function isLocked(): bool
    {
        $value = $this->optionOperator->get($this->optionKey, 0);

        if (!$value) {
            return false;
        }

        $expiration = time() - $this->timeout;

        return $value > $expiration;
    }
}
