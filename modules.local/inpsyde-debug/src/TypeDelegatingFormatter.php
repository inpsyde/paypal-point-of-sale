<?php

declare(strict_types=1);

namespace Inpsyde\Debug;

use Throwable;

class TypeDelegatingFormatter implements ExceptionFormatter
{

    /**
     * @var ExceptionFormatter
     */
    private $fallback;

    /**
     * @var array
     */
    private $formatters;

    public function __construct(ExceptionFormatter $fallback, array $formatters)
    {
        $this->fallback = $fallback;
        $this->formatters = $formatters;
    }

    public function format(Throwable $exception): string
    {
        $formatter = $this->findFormatterByType($exception) ?? $this->fallback;
        $result = $formatter->format($exception);
        $previous = $exception->getPrevious();
        if ($previous) {
            $result .= PHP_EOL . '# Previous:' . PHP_EOL . $this->format($previous);
        }

        return $result;
    }

    private function findFormatterByType(Throwable $exception): ?ExceptionFormatter
    {
        /**
         * Check actual type of exception
         */
        $type = get_class($exception);
        if (isset($this->formatters[$type])) {
            return $this->formatters[$type];
        }
        /**
         * Check parent types
         */
        foreach (class_parents($exception) as $classParent) {
            if (isset($this->formatters[$classParent])) {
                return $this->formatters[$classParent];
            }
        }
        /**
         * Check interfaces
         */
        foreach (class_implements($exception) as $classParent) {
            if (isset($this->formatters[$classParent])) {
                return $this->formatters[$classParent];
            }
        }

        return null;
    }
}
