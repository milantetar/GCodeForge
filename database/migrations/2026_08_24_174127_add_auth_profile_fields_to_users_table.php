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
            $table->string('password')->nullable()->change();
            $table->string('avatar_url', 2048)->nullable()->after('email_verified_at');
            $table->string('preferred_theme', 20)->default('dark')->after('remember_token');
            $table->string('status', 32)->default('active')->index()->after('preferred_theme');
            $table->timestamp('last_login_at')->nullable()->after('status');
            $table->ipAddress('last_login_ip')->nullable()->after('last_login_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'avatar_url',
                'preferred_theme',
                'status',
                'last_login_at',
                'last_login_ip',
            ]);

            $table->string('password')->nullable(false)->change();
        });
    }
};
