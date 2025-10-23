<?php

declare(strict_types=1);

namespace Inpsyde\Queue\Queue\Job;

use DateTime;

class Context implements ContextInterface
{

    /**
     * @var object
     */
    private $args;

    /**
     * @var DateTime
     */
    private $created;

    /**
     * @var int
     */
    private $siteId;

    /**
     * @var int
     */
    private $id;

    /**
     * @var int
     */
    private $retryCount;

    public function __construct(
        object $args,
        DateTime $created,
        int $siteId,
        int $retryCount = 0,
        int $id = 0
    ) {

        $this->args = $args;
        $this->created = $created;
        $this->siteId = $siteId;
        $this->retryCount = $retryCount;
        $this->id = $id;
    }

    public function id(): int
    {
        return $this->id;
    }

    public function forSite(): int
    {
        return $this->siteId;
    }

    public function args(): object
    {
        return $this->args;
    }

    public function created(): DateTime
    {
        return $this->created;
    }

    public function retryCount(): int
    {
        return $this->retryCount;
    }

    public static function fromArray(
        array $data,
        ?int $siteId = null,
        int $retryCount = 0,
        ?DateTime $created = null,
        int $id = 0
    ): ContextInterface {

        return new self(
            (object) $data,
            $created ?? new DateTime(),
            $siteId ?? get_current_blog_id(),
            $retryCount,
            $id
        );
    }

    public function withIncrementedRetryCount(): ContextInterface
    {
        $clone = clone $this;
        $clone->retryCount++;

        return $clone;
    }

    /**
     * For consistency, we update the creation date if the object is cloned
     * - for example using the wither above
     */
    public function __clone()
    {
        $this->created = new DateTime();
    }
}
