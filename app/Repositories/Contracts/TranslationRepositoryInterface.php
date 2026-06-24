<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Translation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface TranslationRepositoryInterface
{
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator;

    public function find(int $id): ?Translation;

    public function create(array $data): Translation;

    public function update(int $id, array $data): ?Translation;

    public function delete(int $id): bool;

    public function exportByLocale(string $localeCode): Collection;

    public function latestUpdatedTimestampForLocale(string $localeCode): ?string;

    public function search(array $filters, int $perPage = 20): LengthAwarePaginator;
}
