<?php

namespace Notifier\EmagProductChecker;

class EmagProductCheckerResult
{
    /** @var bool */
    private $isAvailable;

    /** @var EmagProductData */
    private $productData;

    /**
     * @param bool $isAvailable
     * @param EmagProductData $productData
     */
    public function __construct($isAvailable, EmagProductData $productData)
    {
        $this->isAvailable = $isAvailable;
        $this->productData = $productData;
    }

    /**
     * @return bool
     */
    public function isAvailable()
    {
        return $this->isAvailable;
    }

    /**
     * @return EmagProductData
     */
    public function getProductData()
    {
        return $this->productData;
    }
}
