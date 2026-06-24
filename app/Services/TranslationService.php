<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\TranslationNotFoundException;
use App\Models\Locale;
use App\Models\Translation;
use App\Repositories\Contracts\LocaleRepositoryInterface;
use App\Repositories\Contracts\TagRepositoryInterface;
use App\Repositories\Contracts\TranslationRepositoryInterface;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TranslationService
{
    private const EXPORT_CACHE_PREFIX = 'translations_export_';
    private const EXPORT_CACHE_INDEX_PREFIX = 'translations.export.index.';

    public function __construct(
        private readonly TranslationRepositoryInterface $translationRepository,
        private readonly LocaleRepositoryInterface $localeRepository,
        private readonly TagRepositoryInterface $tagRepository,
        private readonly CacheRepository $cache
    ) {
    }

    public function create(array $data): Translation
    {
        $localeCodes = [];

        $translation = DB::transaction(function () use ($data, &$localeCodes): Translation {
            $locale = $this->resolveLocale($data['locale']);
            $localeCodes[] = $locale->code;
            $tags = $this->normalizeTags($data['tags'] ?? []);

            $translation = $this->translationRepository->create([
                'translation_key' => $data['translation_key'],
                'locale_id' => $locale->id,
                'content' => $data['content'],
            ]);

            if ($tags->isNotEmpty()) {
                $this->syncTags($translation, $tags);
            }

            return $this->view($translation->id);
        });

        $this->clearLocaleExportCaches($localeCodes);

        return $translation;
    }

    public function update(int $id, array $data): ?Translation
    {
        $localeCodes = [];

        $translation = DB::transaction(function () use ($id, $data, &$localeCodes): ?Translation {
            $existingTranslation = $this->view($id);

            if ($existingTranslation === null) {
                return null;
            }

            $originalLocaleCode = $existingTranslation->locale?->code;
            $locale = $existingTranslation->locale;

            if (isset($data['locale']) && is_string($data['locale']) && $data['locale'] !== '') {
                $locale = $this->resolveLocale($data['locale']);
                $localeCodes[] = $locale->code;
            }

            $payload = array_filter([
                'translation_key' => $data['translation_key'] ?? null,
                'locale_id' => $locale?->id,
                'content' => $data['content'] ?? null,
            ], static fn (mixed $value): bool => $value !== null);

            $translation = $this->translationRepository->update($id, $payload);

            if ($translation === null) {
                return null;
            }

            if (array_key_exists('tags', $data)) {
                $tags = $this->normalizeTags($data['tags'] ?? []);
                $this->syncTags($translation, $tags);
            }

            if ($originalLocaleCode !== null) {
                $localeCodes[] = $originalLocaleCode;
            }

            return $this->view($translation->id);
        });

        if ($translation === null) {
            return null;
        }

        $this->clearLocaleExportCaches($localeCodes);

        return $translation;
    }

    public function delete(int $id): bool
    {
        $localeCodes = [];

        $deleted = DB::transaction(function () use ($id, &$localeCodes): bool {
            $translation = $this->view($id);

            if ($translation === null) {
                return false;
            }

            $localeCode = $translation->locale?->code;
            if ($localeCode !== null) {
                $localeCodes[] = $localeCode;
            }

            $deleted = $this->translationRepository->delete($id);

            return $deleted;
        });

        if ($deleted) {
            $this->clearLocaleExportCaches($localeCodes);
        }

        return $deleted;
    }

    public function view(int $id): ?Translation
    {
        return $this->translationRepository->find($id);
    }

    public function findOrFail(int $id): Translation
    {
        $translation = $this->view($id);

        if ($translation === null) {
            throw new TranslationNotFoundException($id);
        }

        return $translation;
    }

    public function updateOrFail(int $id, array $data): Translation
    {
        $updatedTranslation = $this->update($id, $data);

        if ($updatedTranslation === null) {
            throw new TranslationNotFoundException($id);
        }

        return $updatedTranslation;
    }

    public function deleteOrFail(int $id): void
    {
        if (! $this->delete($id)) {
            throw new TranslationNotFoundException($id);
        }
    }

    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->translationRepository->paginate(
            $this->normalizeListFilters($filters),
            $perPage
        );
    }

    public function search(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->translationRepository->search(
            $this->normalizeSearchFilters($filters),
            $perPage
        );
    }

    public function exportByLocale(string $localeCode): Collection
    {
        $latestUpdatedTimestamp = $this->translationRepository->latestUpdatedTimestampForLocale($localeCode);
        $cacheKey = $this->exportCacheKey($localeCode, $latestUpdatedTimestamp);
        $previousCacheKey = $this->cache->get($this->exportIndexKey($localeCode));

        if (is_string($previousCacheKey) && $previousCacheKey !== $cacheKey) {
            $this->cache->forget($previousCacheKey);
        }

        $this->cache->put(
            $this->exportIndexKey($localeCode),
            $cacheKey,
            now()->addDay()
        );

        return $this->cache->remember(
            $cacheKey,
            now()->addHour(),
            fn (): Collection => $this->translationRepository->exportByLocale($localeCode)
        );
    }

    private function resolveLocale(string $code): Locale
    {
        return $this->localeRepository->createIfNotExists($code);
    }

    private function normalizeTags(array $tags): Collection
    {
        return $this->tagRepository->findOrCreateMany($tags);
    }

    private function syncTags(Translation $translation, Collection $tags): void
    {
        $translation->tags()->sync($tags->pluck('id')->all());
    }

    private function forgetLocaleExportCache(string $localeCode): void
    {
        $cacheKey = $this->cache->get($this->exportIndexKey($localeCode));

        if (is_string($cacheKey)) {
            $this->cache->forget($cacheKey);
        }

        $this->cache->forget($this->exportIndexKey($localeCode));
    }

    private function clearLocaleExportCaches(array $localeCodes): void
    {
        foreach (array_unique(array_filter($localeCodes)) as $localeCode) {
            $this->forgetLocaleExportCache((string) $localeCode);
        }
    }

    private function exportCacheKey(string $localeCode, ?string $latestUpdatedTimestamp): string
    {
        return self::EXPORT_CACHE_PREFIX.$localeCode.'_'.($latestUpdatedTimestamp ?? 'none');
    }

    private function exportIndexKey(string $localeCode): string
    {
        return self::EXPORT_CACHE_INDEX_PREFIX.$localeCode;
    }

    private function normalizeListFilters(array $filters): array
    {
        $normalized = [];

        if (isset($filters['key']) && is_string($filters['key']) && $filters['key'] !== '') {
            $normalized['translation_key'] = $filters['key'];
        }

        if (isset($filters['locale']) && is_string($filters['locale']) && $filters['locale'] !== '') {
            $normalized['locale_code'] = $filters['locale'];
        }

        if (isset($filters['tag']) && is_string($filters['tag']) && $filters['tag'] !== '') {
            $normalized['tag'] = $filters['tag'];
        }

        if (isset($filters['content']) && is_string($filters['content']) && $filters['content'] !== '') {
            $normalized['search'] = $filters['content'];
        }

        if (isset($filters['q']) && is_string($filters['q']) && $filters['q'] !== '') {
            $normalized['search'] = $filters['q'];
        }

        return $normalized;
    }

    private function normalizeSearchFilters(array $filters): array
    {
        $normalized = [];

        if (isset($filters['key']) && is_string($filters['key']) && $filters['key'] !== '') {
            $normalized['translation_key'] = $filters['key'];
        }

        if (isset($filters['locale']) && is_string($filters['locale']) && $filters['locale'] !== '') {
            $normalized['locale_code'] = $filters['locale'];
        }

        if (isset($filters['tag']) && is_string($filters['tag']) && $filters['tag'] !== '') {
            $normalized['tag'] = $filters['tag'];
        }

        if (isset($filters['content']) && is_string($filters['content']) && $filters['content'] !== '') {
            $normalized['search'] = $filters['content'];
        }

        if (isset($filters['q']) && is_string($filters['q']) && $filters['q'] !== '') {
            $normalized['search'] = $filters['q'];
        }

        return $normalized;
    }
}
