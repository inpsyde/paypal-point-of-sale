<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Presentation;

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Image\ImageInterface;

class Presentation
{
    private ImageInterface $image;

    private string $backgroundColor;

    private string $textColor;

    /**
     * Presentation constructor.
     *
     * @param ImageInterface $image
     * @param string|null $backgroundColor
     * @param string|null $textColor
     */
    public function __construct(
        ImageInterface $image,
        ?string $backgroundColor = null,
        ?string $textColor = null
    ) {

        $this->image = $image;
        $this->backgroundColor = $backgroundColor;
        $this->textColor = $textColor;
    }

    /**
     * @return ImageInterface
     */
    public function image(): ImageInterface
    {
        return $this->image;
    }

    /**
     * @param string $image
     *
     * @return Presentation
     */
    public function setImage(ImageInterface $image): Presentation
    {
        $this->image = $image;

        return $this;
    }

    /**
     * @return string|null
     */
    public function backgroundColor(): ?string
    {
        return $this->backgroundColor;
    }

    /**
     * @param string $backgroundColor
     *
     * @return Presentation
     */
    public function setBackgroundColor(string $backgroundColor): Presentation
    {
        $this->backgroundColor = $backgroundColor;

        return $this;
    }

    /**
     * @return string|null
     */
    public function textColor(): ?string
    {
        return $this->textColor;
    }

    /**
     * @param string $textColor
     *
     * @return Presentation
     */
    public function setTextColor(string $textColor): Presentation
    {
        $this->textColor = $textColor;

        return $this;
    }
}
