<?php

declare(strict_types=1);

namespace Inpsyde\Queue\Queue;

class FileBasedLocker implements Locker
{
    /**
     * @var int
     */
    private $timeout;

    /**
     * @var string
     */
    private $file;

    /**
     * FileBasedLocker constructor.
     *
     * @param int $timeout
     * @param string $file
     */
    public function __construct(int $timeout, string $file)
    {
        $this->timeout = $timeout;
        $this->file = $file;
    }

    /**
     * @return bool
     */
    public function lock(): bool
    {
        return (bool) file_put_contents($this->file, (string) time());
    }

    /**
     * @return bool
     */
    public function unlock(): bool
    {
        if (!file_exists($this->file)) {
            return true;
        }

        return unlink($this->file);
    }

    /**
     * @return bool
     */
    public function isLocked(): bool
    {
        $file = $this->file;

        if (!file_exists($file)) {
            return false;
        }

        $value = filemtime($file);
        $expiration = time() - $this->timeout;

        return $value > $expiration;
    }
}
