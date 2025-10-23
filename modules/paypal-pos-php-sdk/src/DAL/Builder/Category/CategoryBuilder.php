<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Category;

use DateTime;
use Exception;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\AbstractBuilder;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Category\Category;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Category\CategoryFactory;
use Syde\Vendor\Zettle\Symfony\Component\Uid\Uuid;
class CategoryBuilder extends AbstractBuilder implements CategoryBuilderInterface
{
    /**
     * @var CategoryFactory
     */
    private $categoryFactory;
    /**
     * CategoryBuilder constructor.
     *
     * @param CategoryFactory $categoryFactory
     */
    public function __construct(CategoryFactory $categoryFactory)
    {
        $this->categoryFactory = $categoryFactory;
    }
    /**
     * @inheritDoc
     */
    public function buildFromArray(array $data): Category
    {
        return $this->build($data);
    }
    /**
     * @inheritDoc
     */
    public function createDataArray(Category $category): array
    {
        return ['uuid' => $category->uuid(), 'name' => $category->name()];
    }
    /**
     * @param string $categoryName
     *
     * @return Category
     */
    public function buildFromString(string $categoryName): Category
    {
        return $this->buildWithName($categoryName);
    }
    /**
     * @param Category $category
     *
     * @return string
     */
    public function createDataString(Category $category): string
    {
        return $category->name();
    }
    /**
     * @param array $data
     * @return Category
     *
     * @throws Exception
     */
    private function build(array $data): Category
    {
        $createdAt = $data['created'] ? new DateTime($data['created']) : null;
        $updatedAt = $data['updated'] ? new DateTime($data['updated']) : null;
        return $this->categoryFactory->create($data['uuid'], $data['name'], $this->getDataFromKey('etag', $data), $createdAt, $updatedAt, $data['updatedBy'] ?? null);
    }
    /**
     * @param string $name
     *
     * @return Category
     */
    private function buildWithName(string $name): Category
    {
        return $this->categoryFactory->create($name, (string) Uuid::v1());
    }
}
