<?php

namespace App\Models\Traits;

trait EnvironmentTrait {

    public function getEnvironment(): string
    {
        return $this->attributes['environment'] ?? '';
    }
}
