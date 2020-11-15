<?php

namespace Notifier\EmagProductChecker;

class EmagProductCheckerResult
{
    private bool $isAvailable;
    private EmagProductData $productData;

    public function __construct(bool $isAvailable, EmagProductData $productData)
    {
        $this->isAvailable = $isAvailable;
        $this->productData = $productData;
    }

    public function isAvailable(): bool
    {
        return $this->isAvailable;
    }

    public function getProductData(): EmagProductData
    {
        return $this->productData;
    }
}
