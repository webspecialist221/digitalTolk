<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Locale;
use App\Models\Tag;
use App\Models\Translation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranslationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_translations(): void
    {
        $response = $this->getJson('/api/v1/translations');

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_create_translation(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/translations', [
                'translation_key' => 'welcome',
                'locale' => 'en',
                'content' => 'Welcome',
                'tags' => ['web', 'mobile'],
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.translation_key', 'welcome')
            ->assertJsonPath('data.locale.code', 'en');

        $this->assertDatabaseHas('translations', [
            'translation_key' => 'welcome',
            'content' => 'Welcome',
        ]);

        $this->assertDatabaseHas('locales', [
            'code' => 'en',
            'name' => 'English',
        ]);

        $this->assertDatabaseHas('tags', [
            'name' => 'web',
        ]);

        $this->assertDatabaseHas('tags', [
            'name' => 'mobile',
        ]);
    }

    public function test_authenticated_user_can_view_translation(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;
        $translation = $this->createTranslationFixture();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/v1/translations/{$translation->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $translation->id)
            ->assertJsonPath('data.locale.code', 'en');
    }

    public function test_authenticated_user_can_update_translation(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;
        $translation = $this->createTranslationFixture();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson("/api/v1/translations/{$translation->id}", [
                'translation_key' => 'welcome_updated',
                'content' => 'Welcome updated',
                'tags' => ['desktop'],
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.translation_key', 'welcome_updated')
            ->assertJsonPath('data.content', 'Welcome updated');

        $this->assertDatabaseHas('translations', [
            'id' => $translation->id,
            'translation_key' => 'welcome_updated',
        ]);
    }

    public function test_authenticated_user_can_delete_translation(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;
        $translation = $this->createTranslationFixture();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson("/api/v1/translations/{$translation->id}");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('translations', [
            'id' => $translation->id,
        ]);
    }

    public function test_authenticated_user_can_search_by_locale(): void
    {
        $this->createTranslationFixture();

        $response = $this->authenticatedGet('/api/v1/translations/search?locale=en');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment([
                'translation_key' => 'welcome',
            ]);
    }

    public function test_authenticated_user_can_search_by_tag(): void
    {
        $this->createTranslationFixture();

        $response = $this->authenticatedGet('/api/v1/translations/search?tag=web');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment([
                'translation_key' => 'welcome',
            ]);
    }

    public function test_authenticated_user_can_search_by_key(): void
    {
        $this->createTranslationFixture();

        $response = $this->authenticatedGet('/api/v1/translations/search?key=welcome');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment([
                'translation_key' => 'welcome',
            ]);
    }

    public function test_authenticated_user_can_search_by_content(): void
    {
        $this->createTranslationFixture();

        $response = $this->authenticatedGet('/api/v1/translations/search?content=Welcome');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment([
                'translation_key' => 'welcome',
            ]);
    }

    public function test_authenticated_user_can_export_translations_by_locale(): void
    {
        $this->createTranslationFixture();

        $response = $this->authenticatedGet('/api/v1/translations/export/en');

        $response->assertOk()
            ->assertExactJson([
                'welcome' => 'Welcome',
            ]);
    }

    private function authenticatedGet(string $uri)
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        return $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson($uri);
    }

    private function createTranslationFixture(): Translation
    {
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

        Tag::query()->insert([
            ['name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'mobile', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'desktop', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $tagIds = Tag::query()->whereIn('name', ['web', 'mobile'])->pluck('id')->all();
        $translation->tags()->sync($tagIds);

        return $translation->refresh()->load(['locale', 'tags']);
    }
}
