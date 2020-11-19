<?php

namespace ProductChecker\EmagProductChecker;

class EmagProductData
{
    private string $stockLevel;
    private float $price;
    private string $seller;

    public function __construct(string $stockLevel, float $price, string $seller)
    {
        $this->stockLevel = $stockLevel;
        $this->price = $price;
        $this->seller = $seller;
    }

    public function getStockLevel(): string
    {
        return $this->stockLevel;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getSeller(): string
    {
        return $this->seller;
    }
}
