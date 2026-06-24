<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Tag;
use App\Repositories\Contracts\TagRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EloquentTagRepository implements TagRepositoryInterface
{
    public function findOrCreateMany(array $tags): Collection
    {
        $names = collect($tags)
            ->map(static function (mixed $tag): ?string {
                if (is_string($tag)) {
                    return trim($tag);
                }

                if (is_array($tag) && isset($tag['name']) && is_string($tag['name'])) {
                    return trim($tag['name']);
                }

                return null;
            })
            ->filter()
            ->unique()
            ->values();

        if ($names->isEmpty()) {
            return collect();
        }

        DB::transaction(function () use ($names): void {
            $now = now();
            $rows = $names->map(static function (string $name) use ($now): array {
                return [
                    'name' => $name,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })->all();

            Tag::query()->upsert($rows, ['name'], ['updated_at']);
        });

        return Tag::query()
            ->select(['id', 'name'])
            ->whereIn('name', $names->all())
            ->orderBy('name')
            ->get();
    }
}
