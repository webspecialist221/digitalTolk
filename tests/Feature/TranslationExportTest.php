<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Locale;
use App\Models\Translation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranslationExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_exports_translations_as_a_raw_locale_map(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $locale = Locale::query()->create([
            'code' => 'en',
            'name' => 'English',
            'is_active' => true,
        ]);

        Translation::query()->create([
            'translation_key' => 'welcome',
            'locale_id' => $locale->id,
            'content' => 'Welcome',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/translations/export/en');

        $response->assertOk()
            ->assertExactJson([
                'welcome' => 'Welcome',
            ]);
    }
}
