<?php

namespace Notifier\EmagProductChecker;

class ParsedProductData
{
    /** @var float */
    private $price;

    /** @var bool */
    private $isInStock;

    /** @var bool */
    private $isSoldByEmag;

    /**
     * @param float $price
     * @param bool $isInStock
     * @param bool $isSoldByEmag
     */
    public function __construct($price, $isInStock, $isSoldByEmag)
    {
        $this->price = $price;
        $this->isInStock = $isInStock;
        $this->isSoldByEmag = $isSoldByEmag;
    }

    /**
     * @return float
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @return bool
     */
    public function isInStock()
    {
        return $this->isInStock;
    }

    /**
     * @return bool
     */
    public function isSoldByEmag()
    {
        return $this->isSoldByEmag;
    }
}
