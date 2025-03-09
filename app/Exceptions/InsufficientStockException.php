<?php

namespace App\Exceptions;

use Exception;
use App\Models\Product;

class InsufficientStockException extends Exception
{
    private Product $product;

    public function __construct(string $message, Product $product)
    {
        parent::__construct($message);
        $this->product = $product;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }
}
