<?php

namespace ProductChecker\SummerWellProductChecker;

class SummerWellProductData
{
    private string $stockLevel;
    private float $price;

    public function __construct(string $stockLevel, float $price)
    {
        $this->stockLevel = $stockLevel;
        $this->price = $price;
    }

    public function getStockLevel(): string
    {
        return $this->stockLevel;
    }

    public function getPrice(): float
    {
        return $this->price;
    }
}
