<?php

namespace App\Exceptions;

use Exception;

class CategoryException extends Exception
{
    public static function notFound(string $identifier): self
    {
        return new self("Category not found: {$identifier}");
    }

    public static function invalidStructure(): self
    {
        return new self("Invalid category structure");
    }
} 