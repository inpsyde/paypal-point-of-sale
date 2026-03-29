<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\ProductSettings\Components;

// phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType

use WP_Error;

class TermManager
{
    private string $name;

    private string $slug;

    private string $taxonomy;

    /**
     * ExcludeFromSync constructor.
     *
     * @param string $name
     * @param string $slug
     * @param string $taxonomy
     */
    public function __construct(string $name, string $slug, string $taxonomy)
    {
        $this->name = $name;
        $this->slug = $slug;
        $this->taxonomy = $taxonomy;
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
    public function slug(): string
    {
        return $this->slug;
    }

    /**
     * @return string
     */
    public function taxonomy(): string
    {
        return $this->taxonomy;
    }

    /**
     * @return int
     */
    public function id(): int
    {
        $term = $this->ensureTerm();

        if (is_wp_error($term)) {
            return 0;
        }

        if (!isset($term['term_id'])) {
            return 0;
        }

        return (int) $term['term_id'];
    }

    /**
     * Appends the specified taxonomy term to the incoming post object. If
     * the term doesn't already exist in the database, it will be created.
     *
     * @param int $postId The post to which we're adding the taxonomy term.
     * @param array|null $args
     *
     * @return void
     */
    public function setTerm(int $postId, ?array $args = []): void
    {
        if (is_wp_error($this->ensureTerm($args))) {
            return;
        }

        wp_set_object_terms($postId, $this->slug(), $this->taxonomy(), true);
    }

    /**
     * @param int $postId
     */
    public function removeTerm(int $postId)
    {
        wp_remove_object_terms($postId, [$this->slug()], $this->taxonomy());
    }

    /**
     * @param int $postId
     *
     * @return bool
     */
    public function hasTerm(int $postId): bool
    {
        $hasTerm = is_object_in_term($postId, $this->taxonomy(), $this->slug());

        if (is_wp_error($hasTerm)) {
            return false;
        }

        /**
         * @psalm-suppress RedundantCastGivenDocblockType
         */
        return (bool) $hasTerm;
    }

    /**
     * @param array|null $args
     *
     * @return int|int[]|mixed|WP_Error
     */
    private function ensureTerm(?array $args = [])
    {
        $term = term_exists($this->slug(), $this->taxonomy());

        if ($term === 0 || $term === null) {
            $term = $this->createTerm($args);
        }

        return $term;
    }

    /**
     * @param array|null $args
     *
     * @psalm-suppress InvalidReturnType
     * @return int[]|WP_Error
     */
    private function createTerm(?array $args = [])
    {
        /**
         * @psalm-suppress InvalidReturnStatement
         */
        return wp_insert_term(
            $this->name(),
            $this->taxonomy(),
            array_merge(
                $args,
                [
                    'slug' => $this->slug(),
                ]
            )
        );
    }
}
