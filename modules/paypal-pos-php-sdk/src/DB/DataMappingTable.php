<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DB;

class DataMappingTable implements Table
{
    private string $name;
    /**
     * DataMappingTable constructor.
     *
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }
    /**
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }
    /**
     * @return string
     */
    public function schema(): string
    {
        /** @lang SQL */
        return '
            `ID` BIGINT(20) PRIMARY KEY NOT NULL AUTO_INCREMENT,
            `remote_id` VARCHAR(255) NOT NULL,
            `local_id` BIGINT(20) NOT NULL,
            `type` VARCHAR(255) NOT NULL,
            `site_id` BIGINT(20) NOT NULL,
            `meta` LONGTEXT NOT NULL
        ';
    }
}
