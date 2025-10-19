<?php

namespace Tests\Unit\Casts;

use App\Casts\SafeEnumCast;
use App\Enums\BoolEnum;
use Tests\TestCase;

class SafeEnumCastTest extends TestCase
{
    public function test_cast_returns_correct_format(): void
    {
        $result = SafeEnumCast::cast(BoolEnum::class);

        $this->assertStringContainsString(SafeEnumCast::class, $result);
        $this->assertStringContainsString(BoolEnum::class, $result);
        $this->assertStringContainsString(':', $result);
    }

    public function test_get_returns_enum_when_value_is_valid(): void
    {
        $cast = new SafeEnumCast(BoolEnum::class);
        $model = new \stdClass;

        $result = $cast->get($model, 'field', 1, []);

        $this->assertInstanceOf(BoolEnum::class, $result);
        $this->assertEquals(BoolEnum::YES, $result);
    }

    public function test_get_returns_original_value_when_enum_conversion_fails(): void
    {
        $cast = new SafeEnumCast(BoolEnum::class);
        $model = new \stdClass;

        $result = $cast->get($model, 'field', 999, []);

        $this->assertEquals(999, $result);
    }

    public function test_get_returns_original_value_when_value_is_null(): void
    {
        $cast = new SafeEnumCast(BoolEnum::class);
        $model = new \stdClass;

        $result = $cast->get($model, 'field', null, []);

        // When value is null, SafeEnumCast returns null to preserve nullable behavior
        $this->assertNull($result);
    }

    public function test_get_returns_original_value_for_invalid_integer(): void
    {
        $cast = new SafeEnumCast(BoolEnum::class);
        $model = new \stdClass;

        $result = $cast->get($model, 'field', 999, []);

        $this->assertEquals(999, $result);
    }

    public function test_set_returns_enum_value_when_given_enum_instance(): void
    {
        $cast = new SafeEnumCast(BoolEnum::class);
        $model = new \stdClass;

        $result = $cast->set($model, 'field', BoolEnum::YES, []);

        $this->assertEquals(1, $result);
    }

    public function test_set_returns_original_value_when_not_enum_instance(): void
    {
        $cast = new SafeEnumCast(BoolEnum::class);
        $model = new \stdClass;

        $result = $cast->set($model, 'field', 42, []);

        $this->assertEquals(42, $result);
    }

    public function test_set_returns_string_value_when_given_string(): void
    {
        $cast = new SafeEnumCast(BoolEnum::class);
        $model = new \stdClass;

        $result = $cast->set($model, 'field', 'test', []);

        $this->assertEquals('test', $result);
    }

    public function test_set_returns_null_when_given_null(): void
    {
        $cast = new SafeEnumCast(BoolEnum::class);
        $model = new \stdClass;

        $result = $cast->set($model, 'field', null, []);

        $this->assertNull($result);
    }
}
