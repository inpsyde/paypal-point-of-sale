<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Presentation;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Image\ImageInterface;
class Presentation
{
    private ImageInterface $image;
    private ?string $backgroundColor;
    private ?string $textColor;
    public function __construct(ImageInterface $image, ?string $backgroundColor = null, ?string $textColor = null)
    {
        $this->image = $image;
        $this->backgroundColor = $backgroundColor;
        $this->textColor = $textColor;
    }
    public function image(): ImageInterface
    {
        return $this->image;
    }
    public function setImage(ImageInterface $image): Presentation
    {
        $this->image = $image;
        return $this;
    }
    public function backgroundColor(): ?string
    {
        return $this->backgroundColor;
    }
    public function setBackgroundColor(string $backgroundColor): Presentation
    {
        $this->backgroundColor = $backgroundColor;
        return $this;
    }
    public function textColor(): ?string
    {
        return $this->textColor;
    }
    public function setTextColor(string $textColor): Presentation
    {
        $this->textColor = $textColor;
        return $this;
    }
}
