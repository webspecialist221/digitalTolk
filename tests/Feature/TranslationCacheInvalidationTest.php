<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Locale;
use App\Models\Translation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TranslationCacheInvalidationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_updating_translation_clears_the_previous_export_cache_key(): void
    {
        Carbon::setTestNow('2026-06-24 10:00:00');

        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $locale = Locale::query()->create([
            'code' => 'en',
            'name' => 'English',
            'is_active' => true,
        ]);

        $translation = Translation::query()->create([
            'translation_key' => 'welcome',
            'locale_id' => $locale->id,
            'content' => 'Welcome',
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/translations/export/en')
            ->assertOk();

        $oldCacheKey = 'translations_export_en_'.$translation->fresh()->updated_at->format('Y-m-d H:i:s');

        Carbon::setTestNow('2026-06-24 10:00:01');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson("/api/v1/translations/{$translation->id}", [
                'content' => 'Welcome updated',
            ])
            ->assertOk();

        $this->assertFalse(Cache::has($oldCacheKey));

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/translations/export/en');

        $response->assertOk()
            ->assertExactJson([
                'welcome' => 'Welcome updated',
            ]);
    }

    public function test_export_refreshes_when_a_translation_changes_outside_the_api(): void
    {
        Carbon::setTestNow('2026-06-24 10:00:00');

        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $locale = Locale::query()->create([
            'code' => 'en',
            'name' => 'English',
            'is_active' => true,
        ]);

        $translation = Translation::query()->create([
            'translation_key' => 'welcome',
            'locale_id' => $locale->id,
            'content' => 'Welcome',
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/translations/export/en')
            ->assertOk();

        Carbon::setTestNow('2026-06-24 10:00:02');

        DB::table('translations')
            ->where('id', $translation->id)
            ->update([
                'content' => 'Welcome from direct DB change',
                'updated_at' => now(),
            ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/translations/export/en');

        $response->assertOk()
            ->assertExactJson([
                'welcome' => 'Welcome from direct DB change',
            ]);
    }
}
