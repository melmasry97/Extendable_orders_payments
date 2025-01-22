<?php

namespace Tests\Unit\Enums;

use App\Enums\OrderStatus;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class OrderStatusTest extends TestCase
{
    #[Test]
    public function test_has_expected_cases(): void
    {
        $expectedCases = [
            'PENDING',
            'CONFIRMED',
            'CANCELLED'
        ];

        $actualCases = array_column(OrderStatus::cases(), 'name');

        $this->assertEquals($expectedCases, $actualCases);
    }

    #[Test]
    public function test_returns_correct_values(): void
    {
        $this->assertEquals('pending', OrderStatus::PENDING->value);
        $this->assertEquals('confirmed', OrderStatus::CONFIRMED->value);
        $this->assertEquals('cancelled', OrderStatus::CANCELLED->value);
    }

    #[Test]
    public function test_can_be_created_from_value(): void
    {
        $this->assertEquals(OrderStatus::PENDING, OrderStatus::from('pending'));
        $this->assertEquals(OrderStatus::CONFIRMED, OrderStatus::from('confirmed'));
        $this->assertEquals(OrderStatus::CANCELLED, OrderStatus::from('cancelled'));
    }

    #[Test]
    public function test_throws_exception_for_invalid_value(): void
    {
        $this->expectException(\ValueError::class);
        OrderStatus::from('invalid_status');
    }

}
