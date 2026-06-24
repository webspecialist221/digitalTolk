<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Locale;

interface LocaleRepositoryInterface
{
    public function findByCode(string $code): ?Locale;

    public function createIfNotExists(string $code, ?string $name = null): Locale;
}
