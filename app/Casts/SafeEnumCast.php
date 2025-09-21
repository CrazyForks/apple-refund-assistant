<?php
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

    // 读取数据库值
    public function get($model, string $key, $value, array $attributes)
    {
        // tryFrom 会返回 null 而不是抛异常
        return $this->enumClass::tryFrom($value) ?? $value;
    }

    // 存入数据库
    public function set($model, string $key, $value, array $attributes)
    {
        if ($value instanceof $this->enumClass) {
            return $value->value;
        }

        return $value;
    }
}
