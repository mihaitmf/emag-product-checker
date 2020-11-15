<?php

namespace Notifier\EmagProductChecker;

class EmagProductData
{
    private float $price;
    private string $stockLevel;
    private string $seller;

    public function __construct(float $price, string $stockLevel, string $seller)
    {
        $this->price = $price;
        $this->stockLevel = $stockLevel;
        $this->seller = $seller;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getStockLevel(): string
    {
        return $this->stockLevel;
    }

    public function getSeller(): string
    {
        return $this->seller;
    }
}
