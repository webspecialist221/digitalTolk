<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Locale;
use App\Repositories\Contracts\LocaleRepositoryInterface;
use Illuminate\Support\Facades\DB;

class EloquentLocaleRepository implements LocaleRepositoryInterface
{
    public function findByCode(string $code): ?Locale
    {
        return Locale::query()
            ->select(['id', 'code', 'name', 'is_active'])
            ->where('code', $code)
            ->first();
    }

    public function createIfNotExists(string $code, ?string $name = null): Locale
    {
        return DB::transaction(function () use ($code, $name): Locale {
            return Locale::query()->firstOrCreate(
                ['code' => $code],
                [
                    'name' => $name ?? $this->resolveLocaleName($code),
                    'is_active' => true,
                ]
            );
        });
    }

    private function resolveLocaleName(string $code): string
    {
        if (class_exists(\Locale::class)) {
            $displayName = \Locale::getDisplayLanguage($code, 'en');

            if (is_string($displayName) && $displayName !== '') {
                return $displayName;
            }
        }

        return $code;
    }
}
