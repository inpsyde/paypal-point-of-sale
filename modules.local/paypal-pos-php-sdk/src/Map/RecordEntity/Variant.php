<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\Map\RecordEntity;

class Variant
{
    public const TYPE = 'variant';

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $uuid;

    /**
     * @var int
     */
    private $localId;

    /**
     * @var string
     */
    private $type;

    /**
     * @var int
     */
    private $siteId;

    /**
     * @var array
     */
    private $meta;

    /**
     * Variant constructor.
     *
     * @param int $id
     * @param string $uuid
     * @param int $localId
     * @param string $type
     * @param int $siteId
     * @param array $meta
     */
    public function __construct(
        int $id,
        string $uuid,
        int $localId,
        int $siteId,
        string $type = self::TYPE,
        array $meta = []
    ) {

        $this->id = $id;
        $this->uuid = $uuid;
        $this->localId = $localId;
        $this->siteId = $siteId;
        $this->type = $type;
        $this->meta = $meta;
    }

    /**
     * @return int
     */
    public function id(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function uuid(): string
    {
        return $this->uuid;
    }

    /**
     * @return int
     */
    public function localId(): int
    {
        return $this->localId;
    }

    /**
     * @return int
     */
    public function siteId(): int
    {
        return $this->siteId;
    }

    /**
     * @return string
     */
    public function type(): string
    {
        return $this->type;
    }

    /**
     * @return array
     */
    public function meta(): array
    {
        return $this->meta;
    }

    /**
     * @param array $data
     *
     * @return static
     */
    public static function fromArray(array $data): self
    {
        return new self(
            !empty($data['ID']) ? (int) $data['ID'] : 0,
            !empty($data['remote_id']) ? (string) $data['remote_id'] : '',
            !empty($data['local_id']) ? (int) $data['local_id'] : 0,
            !empty($data['site_id']) ? (int) $data['site_id'] : 1,
            !empty($data['type']) && $data['type'] === self::TYPE ? $data['type'] : self::TYPE,
            !empty($data['meta']) ? json_decode($data['meta'], true) : []
        );
    }
}
