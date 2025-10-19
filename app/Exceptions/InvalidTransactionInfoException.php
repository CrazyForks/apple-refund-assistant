<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class InvalidTransactionInfoException extends Exception
{
    public function __construct(string $message = 'invalid transaction info')
    {
        parent::__construct($message);
    }
}

