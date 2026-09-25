<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('google_id')->nullable()->unique()->after('email');
            $table->string('google_avatar_url', 2048)->nullable()->after('google_id');
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        $googleOnly = DB::table('users')->whereNull('password')->exists();

        if ($googleOnly) {
            throw new RuntimeException('Cannot roll back while Google-only users exist.');
        }

        if (Schema::hasColumn('users', 'google_id')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropIndex('users_google_id_unique');
            });
        }

        if (Schema::hasColumn('users', 'google_avatar_url')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('google_avatar_url');
            });
        }

        if (Schema::hasColumn('users', 'google_id')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('google_id');
            });
        }

        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            if (Schema::hasColumn('users', 'password')) {
                Schema::table('users', function (Blueprint $table): void {
                    $table->string('password')->nullable(false)->change();
                });
            }
        }
    }
};
