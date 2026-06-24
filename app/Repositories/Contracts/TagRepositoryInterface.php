<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface TagRepositoryInterface
{
    public function findOrCreateMany(array $tags): Collection;
}
