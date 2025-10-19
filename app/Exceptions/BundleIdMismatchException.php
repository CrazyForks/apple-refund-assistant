<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class BundleIdMismatchException extends Exception
{
    public function __construct(string $expected, string $actual)
    {
        parent::__construct(
            "Bundle ID mismatch: expected '{$expected}', got '{$actual}'"
        );
    }
}
