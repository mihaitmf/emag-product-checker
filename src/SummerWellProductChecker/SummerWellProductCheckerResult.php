<?php

namespace ProductChecker\SummerWellProductChecker;

class SummerWellProductCheckerResult
{
    private bool $isAvailable;
    private SummerWellProductData $productData;

    public function __construct(bool $isAvailable, SummerWellProductData $productData)
    {
        $this->isAvailable = $isAvailable;
        $this->productData = $productData;
    }

    public function isAvailable(): bool
    {
        return $this->isAvailable;
    }

    public function getProductData(): SummerWellProductData
    {
        return $this->productData;
    }
}
