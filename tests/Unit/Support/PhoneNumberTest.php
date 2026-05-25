<?php

namespace Tests\Unit\Support;

use App\Support\PhoneNumber;
use PHPUnit\Framework\TestCase;

class PhoneNumberTest extends TestCase
{
    public function test_normalize_indonesian_formats(): void
    {
        $this->assertSame('628123456789', PhoneNumber::normalize('08123456789'));
        $this->assertSame('628123456789', PhoneNumber::normalize('+628123456789'));
        $this->assertSame('628123456789', PhoneNumber::normalize('628123456789'));
    }

    public function test_rejects_invalid_numbers(): void
    {
        $this->assertNull(PhoneNumber::normalize('12345'));
        $this->assertFalse(PhoneNumber::isValid('abcde'));
        $this->assertFalse(PhoneNumber::isValid('12345'));
    }
}
