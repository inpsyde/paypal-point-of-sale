<?php

declare(strict_types=1);

namespace Inpsyde\Queue;

class NetworkState
{

    /**
     * @var int
     */
    private $siteId;

    /**
     * @var int[]
     */
    private $stack;

    private function __construct()
    {
    }

    /**
     * Returns a new instance for the global site ID and switched stack.
     *
     * @return static
     */
    public static function create(): NetworkState
    {
        $state = new static();
        $state->siteId = get_current_blog_id();
        $state->stack = (array) ($GLOBALS['_wp_switched_stack'] ?? []);

        return $state;
    }

    /**
     * Restores the stored site state.
     *
     * @return int
     */
    public function restore(): int
    {
        switch_to_blog($this->siteId);
        $GLOBALS['_wp_switched_stack'] = $this->stack;
        $GLOBALS['switched'] = (bool) $this->stack;

        return get_current_blog_id();
    }
}
