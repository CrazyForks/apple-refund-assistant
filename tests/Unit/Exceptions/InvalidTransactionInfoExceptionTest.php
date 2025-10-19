<?php

namespace Tests\Unit\Exceptions;

use App\Exceptions\InvalidTransactionInfoException;
use PHPUnit\Framework\TestCase;

class InvalidTransactionInfoExceptionTest extends TestCase
{
    public function test_exception_with_default_message(): void
    {
        $exception = new InvalidTransactionInfoException();
        
        $this->assertInstanceOf(InvalidTransactionInfoException::class, $exception);
        $this->assertEquals('invalid transaction info', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
    }

    public function test_exception_with_custom_message(): void
    {
        $customMessage = 'Custom error message';
        $exception = new InvalidTransactionInfoException($customMessage);
        
        $this->assertEquals($customMessage, $exception->getMessage());
    }
}

