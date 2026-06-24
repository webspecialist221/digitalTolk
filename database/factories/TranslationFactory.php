<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Locale;
use App\Models\Translation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Translation>
 */
class TranslationFactory extends Factory
{
    protected $model = Translation::class;

    public function definition(): array
    {
        return [
            'translation_key' => fake()->unique()->slug(),
            'locale_id' => Locale::factory(),
            'content' => fake()->sentence(),
        ];
    }
}
