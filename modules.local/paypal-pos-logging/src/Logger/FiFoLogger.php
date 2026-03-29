<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Logging\Logger;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use SplQueue;

class FiFoLogger implements LoggerInterface
{
    use LoggerTrait;

    private $pool;

    private string $optionKey;

    private int $maxItems;

    /**
     * @var callable
     */
    private $flush;

    public function __construct(
        array $current,
        int $maxItems,
        callable $flush
    ) {

        $this->maxItems = $maxItems;
        $this->pool = new SplQueue();
        foreach ($current as $item) {
            $this->pool->enqueue($item);
        }
        $this->flush = $flush;
    }

    /**
     * @inheritDoc
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
     */
    public function log($level, $message, array $context = [])
    {
        while ($this->pool->count() >= $this->maxItems) {
            $this->pool->dequeue();
        }
        $this->pool->enqueue(
            [
                'LEVEL' => $level,
                'MESSAGE' => $message,
                'CONTEXT' => $context,
            ]
        );
    }

    public function __destruct()
    {
        ($this->flush)(iterator_to_array($this->pool));
    }
}
