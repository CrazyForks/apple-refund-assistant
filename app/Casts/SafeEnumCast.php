<?php

declare(strict_types=1);

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class SafeEnumCast implements CastsAttributes
{
    protected string $enumClass;

    public static function cast($class): string
    {
        return self::class . ':' . $class;
    }

    public function __construct(string $enumClass)
    {
        $this->enumClass = $enumClass;
    }

    // Read value from database
    public function get($model, string $key, $value, array $attributes)
    {
        // Handle null values explicitly before calling tryFrom
        if ($value === null) {
            return null;
        }
        
        // tryFrom returns null instead of throwing exception
        return $this->enumClass::tryFrom($value) ?? $value;
    }

    // Store value to database
    public function set($model, string $key, $value, array $attributes)
    {
        if ($value instanceof $this->enumClass) {
            return $value->value;
        }

        return $value;
    }
}
