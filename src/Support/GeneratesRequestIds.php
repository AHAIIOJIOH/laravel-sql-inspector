<?php

namespace Ahaiiojioh\LaravelSqlInspector\Support;

use Illuminate\Support\Str;

trait GeneratesRequestIds
{
    protected function generateProfileId(): string
    {
        return (string) Str::uuid();
    }
}
