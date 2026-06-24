<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translations', function (Blueprint $table): void {
            $table->id();
            $table->string('translation_key');
            $table->foreignId('locale_id')
                ->constrained('locales')
                ->cascadeOnDelete();
            $table->longText('content');
            $table->timestamps();

            $table->unique(['translation_key', 'locale_id']);
            $table->index('translation_key');
            $table->index('locale_id');

            if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
                $table->fullText('content');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
