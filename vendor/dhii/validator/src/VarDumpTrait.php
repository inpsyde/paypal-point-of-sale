<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Dhii\Validator;

use RuntimeException;
/**
 * Allows dumping of a variable's content.
 */
trait VarDumpTrait
{
    /**
     * Retrieves the dump of a variable as a string.
     *
     * @see var_dump()
     * @param mixed $value The value to get the dump of.
     * @return string The dump.
     * @throws RuntimeException If problem retrieving.
     */
    protected function varDump($value): string
    {
        ob_start();
        /** @psalm-suppress ForbiddenCode */
        var_dump($value);
        $dump = ob_get_clean();
        if ($dump === \false) {
            throw new RuntimeException('Output buffering not started');
        }
        $dump = $this->cleanVarDump($dump);
        return $dump;
    }
    /**
     * Cleans up the {@see var_dump()} output.
     *
     * @param string $dump The output produced by {@see var_dump()}.
     * @return string The cleaned up dump.
     */
    protected function cleanVarDump(string $dump): string
    {
        $matches = [];
        // In case xDebug added a file path path to the beginning
        preg_match_all('!^(.+\:\d+\:\n)?((.|\n)+)$!', $dump, $matches, \PREG_SET_ORDER, 0);
        if (!isset($matches[0]) || !isset($matches[0][2])) {
            throw new RuntimeException('Not a valid dump');
        }
        return trim($matches[0][2]);
    }
}
