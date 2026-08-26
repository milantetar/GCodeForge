<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('machine_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 160);
            $table->string('slug', 160);
            $table->text('description')->nullable();
            $table->string('units', 16)->default('mm');
            $table->json('settings')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'slug']);
            $table->index(['user_id', 'is_default']);
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('machine_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 160);
            $table->string('slug', 160);
            $table->text('description')->nullable();
            $table->string('status', 32)->default('active');
            $table->json('settings')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'slug']);
            $table->index(['user_id', 'status', 'updated_at']);
        });

        Schema::create('conversions', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('machine_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 64);
            $table->string('status', 32)->default('pending');
            $table->json('options');
            $table->char('input_hash', 64);
            $table->json('warnings')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('input_lines')->default(0);
            $table->unsignedInteger('output_lines')->default(0);
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['project_id', 'status', 'created_at']);
            $table->index(['user_id', 'type', 'created_at']);
            $table->index(['user_id', 'input_hash']);
        });

        Schema::create('conversion_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversion_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 24);
            $table->string('disk', 64);
            $table->string('path', 2048);
            $table->string('original_name', 255);
            $table->string('mime_type', 100)->default('text/plain');
            $table->unsignedBigInteger('size_bytes');
            $table->char('checksum', 64);
            $table->timestamps();

            $table->unique(['conversion_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversion_files');
        Schema::dropIfExists('conversions');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('machine_profiles');
    }
};
