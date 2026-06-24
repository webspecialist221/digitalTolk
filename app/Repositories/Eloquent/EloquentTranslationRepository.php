<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Translation;
use App\Repositories\Contracts\TranslationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EloquentTranslationRepository implements TranslationRepositoryInterface
{
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->baseQuery($filters)
            ->latest('translations.id')
            ->paginate($perPage);
    }

    public function find(int $id): ?Translation
    {
        return $this->baseQuery([])
            ->whereKey($id)
            ->first();
    }

    public function create(array $data): Translation
    {
        $translation = Translation::query()->create($data);

        return $this->loadRelations($translation);
    }

    public function update(int $id, array $data): ?Translation
    {
        $translation = Translation::query()->find($id);

        if ($translation === null) {
            return null;
        }

        $translation->fill($data);
        $translation->save();

        return $this->loadRelations($translation);
    }

    public function delete(int $id): bool
    {
        return (bool) Translation::query()->whereKey($id)->delete();
    }

    public function exportByLocale(string $localeCode): Collection
    {
        return Translation::query()
            ->select([
                'translations.translation_key',
                'translations.content',
            ])
            ->whereHas('locale', static function (Builder $query) use ($localeCode): void {
                $query->where('code', $localeCode);
            })
            ->orderBy('translations.translation_key')
            ->pluck('content', 'translation_key');
    }

    public function latestUpdatedTimestampForLocale(string $localeCode): ?string
    {
        return Translation::query()
            ->whereHas('locale', static function (Builder $query) use ($localeCode): void {
                $query->where('code', $localeCode);
            })
            ->max('translations.updated_at');
    }

    public function search(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->baseQuery($filters, true)
            ->latest('translations.id')
            ->paginate($perPage);
    }

    private function baseQuery(array $filters, bool $searchMode = false): Builder
    {
        $query = Translation::query()
            ->select([
                'translations.id',
                'translations.translation_key',
                'translations.locale_id',
                'translations.content',
                'translations.created_at',
                'translations.updated_at',
            ])
            ->with([
                'locale:id,code,name,is_active',
                'tags:id,name',
            ]);

        if (isset($filters['translation_key']) && is_string($filters['translation_key']) && $filters['translation_key'] !== '') {
            $query->where('translations.translation_key', 'like', '%'.$filters['translation_key'].'%');
        }

        if (isset($filters['locale_id']) && is_numeric($filters['locale_id'])) {
            $query->where('translations.locale_id', (int) $filters['locale_id']);
        }

        if (isset($filters['locale_code']) && is_string($filters['locale_code']) && $filters['locale_code'] !== '') {
            $query->whereHas('locale', static function (Builder $localeQuery) use ($filters): void {
                $localeQuery->where('code', $filters['locale_code']);
            });
        }

        if (isset($filters['tag_ids']) && is_array($filters['tag_ids']) && $filters['tag_ids'] !== []) {
            $tagIds = array_values(array_filter($filters['tag_ids'], 'is_numeric'));

            if ($tagIds !== []) {
                $query->whereHas('tags', static function (Builder $tagQuery) use ($tagIds): void {
                    $tagQuery->whereIn('tags.id', $tagIds);
                });
            }
        }

        if (isset($filters['tag']) && is_string($filters['tag']) && $filters['tag'] !== '') {
            $query->whereHas('tags', static function (Builder $tagQuery) use ($filters): void {
                $tagQuery->where('tags.name', 'like', '%'.$filters['tag'].'%');
            });
        }

        $search = $filters['search'] ?? $filters['content'] ?? null;

        if (is_string($search) && $search !== '') {
            if ($this->supportsFullTextSearch()) {
                $query->whereFullText('translations.content', $search);
            } else {
                $query->where('translations.content', 'like', '%'.$search.'%');
            }
        }

        return $query;
    }

    private function supportsFullTextSearch(): bool
    {
        return in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true);
    }

    private function loadRelations(Translation $translation): Translation
    {
        return $translation->load([
            'locale:id,code,name,is_active',
            'tags:id,name',
        ]);
    }
}
