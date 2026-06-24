<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Locale;
use App\Models\Tag;
use App\Models\Translation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TranslationSeeder extends Seeder
{
    private const TRANSLATION_COUNT = 100000;
    private const BATCH_SIZE = 1000;

    public function run(): void
    {
        Schema::disableForeignKeyConstraints();

        Translation::query()->truncate();
        DB::table('tag_translation')->truncate();
        Tag::query()->truncate();
        Locale::query()->truncate();

        Schema::enableForeignKeyConstraints();

        $locales = collect([
            Locale::query()->create(['code' => 'en', 'name' => 'English', 'is_active' => true]),
            Locale::query()->create(['code' => 'fr', 'name' => 'French', 'is_active' => true]),
            Locale::query()->create(['code' => 'es', 'name' => 'Spanish', 'is_active' => true]),
        ]);

        $tags = collect([
            Tag::query()->create(['name' => 'mobile']),
            Tag::query()->create(['name' => 'desktop']),
            Tag::query()->create(['name' => 'web']),
        ]);

        $localeIds = $locales->pluck('id')->all();
        $tagIds = $tags->pluck('id')->all();
        $now = now();
        $counter = 1;

        for ($offset = 0; $offset < self::TRANSLATION_COUNT; $offset += self::BATCH_SIZE) {
            $currentBatchSize = min(self::BATCH_SIZE, self::TRANSLATION_COUNT - $offset);
            $rows = [];

            for ($index = 0; $index < $currentBatchSize; $index++) {
                $localeId = Arr::random($localeIds);

                $rows[] = [
                    'translation_key' => 'translation_'.$counter,
                    'locale_id' => $localeId,
                    'content' => fake()->sentence(6),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $counter++;
            }

            DB::table('translations')->insert($rows);

            $translationIds = Translation::query()
                ->select(['id', 'translation_key', 'locale_id'])
                ->whereIn('translation_key', array_column($rows, 'translation_key'))
                ->get();

            $pivotRows = [];

            foreach ($translationIds as $translation) {
                $selectedTagCount = random_int(1, count($tagIds));
                $selectedTagIds = Arr::random($tagIds, $selectedTagCount);

                foreach ((array) $selectedTagIds as $tagId) {
                    $pivotRows[] = [
                        'translation_id' => $translation->id,
                        'tag_id' => $tagId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            if ($pivotRows !== []) {
                DB::table('tag_translation')->insert($pivotRows);
            }
        }
    }
}
