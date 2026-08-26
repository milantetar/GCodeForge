<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Personal and professional profile.
            $table->string('first_name', 100)->nullable()->after('name');
            $table->string('last_name', 100)->nullable()->after('first_name');
            $table->string('headline', 160)->nullable()->after('last_name');
            $table->text('bio')->nullable()->after('headline');
            $table->text('phone_number')->nullable()->after('bio');

            // Public portfolio identity. A unique column already has an index.
            $table->string('portfolio_slug', 160)->nullable()->unique()->after('phone_number');

            // Location and external presence.
            $table->string('country', 100)->nullable()->after('portfolio_slug');
            $table->string('state', 100)->nullable()->after('country');
            $table->string('city', 100)->nullable()->after('state');
            $table->string('zip_code', 20)->nullable()->after('city');
            $table->text('address')->nullable()->after('zip_code');
            $table->string('website_url', 2048)->nullable()->after('address');
            $table->json('social_links')->nullable()->after('website_url');

            // Visibility. Account availability is represented by the existing status column.
            $table->boolean('is_public')->default(true)->after('social_links');
            $table->softDeletes();

            $table->index(['status', 'is_public'], 'users_status_visibility_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_status_visibility_index');
            $table->dropUnique(['portfolio_slug']);
            $table->dropColumn([
                'first_name',
                'last_name',
                'headline',
                'bio',
                'phone_number',
                'portfolio_slug',
                'country',
                'state',
                'city',
                'zip_code',
                'address',
                'website_url',
                'social_links',
                'is_public',
                'deleted_at',
            ]);
        });
    }
};
