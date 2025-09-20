<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Kra8\Snowflake\HasShortflakePrimary;

class App extends Model
{
    use HasShortflakePrimary;

    protected $guarded = [];
    protected function p8Key(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => empty($value) ? '' : Crypt::decryptString($value),
            set: fn (?string $value) => empty($value) ? null : Crypt::encryptString($value),
        );
    }

}
