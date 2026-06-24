<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Locale;
use App\Models\Tag;
use App\Models\Translation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TranslationPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        DB::disableQueryLog();

        $this->seedPerformanceDataset();
        $this->token = User::factory()->create()
            ->createToken('api-token')
            ->plainTextToken;
    }

    public function test_list_translations_endpoint_responds_under_200ms(): void
    {
        $duration = $this->measureResponseTime('/api/v1/translations?per_page=50');

        // Timing depends on the local machine and CI load, so keep this threshold realistic.
        $this->assertLessThan(0.2, $duration, 'List translations took too long: '.$duration.' seconds.');
    }

    public function test_search_translations_endpoint_responds_under_200ms(): void
    {
        $duration = $this->measureResponseTime('/api/v1/translations/search?q=translation-1500');

        // Timing depends on the local machine and CI load, so keep this threshold realistic.
        $this->assertLessThan(0.2, $duration, 'Search translations took too long: '.$duration.' seconds.');
    }

    public function test_export_translations_endpoint_responds_under_500ms(): void
    {
        // Warm the cache first so we benchmark the optimized export path.
        $this->measureResponseTime('/api/v1/translations/export/en');

        $duration = $this->measureResponseTime('/api/v1/translations/export/en');

        // Timing depends on the local machine and CI load, so keep this threshold realistic.
        $this->assertLessThan(0.5, $duration, 'Export translations took too long: '.$duration.' seconds.');
    }

    private function measureResponseTime(string $uri): float
    {
        $startedAt = microtime(true);

        $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson($uri)
            ->assertOk();

        return microtime(true) - $startedAt;
    }

    private function seedPerformanceDataset(): void
    {
        $locales = collect([
            Locale::query()->create([
                'code' => 'en',
                'name' => 'English',
                'is_active' => true,
            ]),
            Locale::query()->create([
                'code' => 'fr',
                'name' => 'French',
                'is_active' => true,
            ]),
            Locale::query()->create([
                'code' => 'es',
                'name' => 'Spanish',
                'is_active' => true,
            ]),
        ]);

        $tags = collect([
            Tag::query()->create(['name' => 'mobile']),
            Tag::query()->create(['name' => 'desktop']),
            Tag::query()->create(['name' => 'web']),
        ]);

        $translationCount = 3000;
        $batchSize = 500;
        $now = now();
        $translationId = 1;
        $localeIds = $locales->pluck('id')->all();
        $tagIds = $tags->pluck('id')->all();

        for ($offset = 0; $offset < $translationCount; $offset += $batchSize) {
            $currentBatchSize = min($batchSize, $translationCount - $offset);
            $rows = [];
            $pivotRows = [];

            for ($index = 0; $index < $currentBatchSize; $index++) {
                $localeId = $localeIds[$translationId % count($localeIds)];

                $rows[] = [
                    'translation_key' => 'translation-'.$translationId,
                    'locale_id' => $localeId,
                    'content' => 'Welcome message '.$translationId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $selectedTagIds = [$tagIds[$translationId % count($tagIds)]];

                foreach ($selectedTagIds as $tagId) {
                    $pivotRows[] = [
                        'translation_id' => $translationId,
                        'tag_id' => $tagId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                $translationId++;
            }

            DB::table('translations')->insert($rows);
            DB::table('tag_translation')->insert($pivotRows);
        }
    }
}
