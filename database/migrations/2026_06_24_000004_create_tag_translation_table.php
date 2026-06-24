<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tag_translation', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('translation_id')
                ->constrained('translations')
                ->cascadeOnDelete();
            $table->foreignId('tag_id')
                ->constrained('tags')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['translation_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tag_translation');
    }
};
