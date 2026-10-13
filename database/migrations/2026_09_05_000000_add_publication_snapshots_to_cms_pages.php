<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cms_pages', function (Blueprint $table): void {
            $table->json('published_content')->nullable();
            $table->string('published_slug', 160)->nullable()->unique();
            $table->unsignedInteger('lock_version')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('cms_pages', function (Blueprint $table): void {
            $table->dropUnique(['published_slug']);
            $table->dropColumn(['published_content', 'published_slug', 'lock_version']);
        });
    }
};
