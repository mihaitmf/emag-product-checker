<?php

namespace Notifier\EmagProductChecker;

class EmagProductData
{
    /** @var float */
    private $price;

    /** @var string */
    private $stockLevel;

    /** @var string */
    private $seller;

    /**
     * @param float $price
     * @param string $stockLevel
     * @param string $seller
     */
    public function __construct($price, $stockLevel, $seller)
    {
        $this->price = $price;
        $this->stockLevel = $stockLevel;
        $this->seller = $seller;
    }

    /**
     * @return float
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @return string
     */
    public function getStockLevel()
    {
        return $this->stockLevel;
    }

    /**
     * @return string
     */
    public function getSeller()
    {
        return $this->seller;
    }
}
