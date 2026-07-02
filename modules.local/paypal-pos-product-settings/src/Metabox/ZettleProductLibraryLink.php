<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\ProductSettings\Metabox;

use MetaboxOrchestra\BoxAction;
use MetaboxOrchestra\BoxInfo;
use MetaboxOrchestra\BoxView;
use MetaboxOrchestra\Entity;
use MetaboxOrchestra\PostMetabox;
use Syde\PayPal\PointOfSale\PhpSdk\Repository\Zettle\Product\ProductRepositoryInterface;
use WP_Post;

// phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps

class ZettleProductLibraryLink implements PostMetabox
{
    public const ID = 'zettle-product-library-link';

    private ProductRepositoryInterface $repository;

    private BoxView $view;

    private BoxAction $action;

    private string $title;

    public function __construct(
        ProductRepositoryInterface $repository,
        ZettleProductLibraryLinkView $view,
        BoxAction $action,
        string $title
    ) {

        $this->repository = $repository;
        $this->view = $view;
        $this->action = $action;
        $this->title = $title;
    }

    /**
     * @inheritDoc
     */
    public function create_info(string $showOrSave, Entity $entity): BoxInfo
    {
        $boxInfo = new BoxInfo(
            $this->title,
            self::ID,
            BoxInfo::CONTEXT_SIDE,
            BoxInfo::PRIORITY_SORTED
        );

        $boxInfo['uuid'] = (string) $this->repository->findById($entity->id());

        return $boxInfo;
    }

    /**
     * @inheritDoc
     */
    public function accept_post(WP_Post $post, string $saveOrShow): bool
    {
        return $this->repository->findById((int) $post->ID) !== null;
    }

    /**
     * @inheritDoc
     */
    public function view_for_post(WP_Post $post): BoxView
    {
        return $this->view;
    }

    /**
     * @inheritDoc
     */
    public function action_for_post(WP_Post $post): BoxAction
    {
        return $this->action;
    }
}
