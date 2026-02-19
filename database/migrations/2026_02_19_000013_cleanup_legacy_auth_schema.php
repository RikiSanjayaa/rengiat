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
        $hasEmailColumn = Schema::hasColumn('users', 'email');
        $hasEmailVerifiedAtColumn = Schema::hasColumn('users', 'email_verified_at');

        if ($hasEmailColumn || $hasEmailVerifiedAtColumn) {
            Schema::table('users', function (Blueprint $table) use ($hasEmailColumn, $hasEmailVerifiedAtColumn): void {
                if ($hasEmailColumn) {
                    $table->dropColumn('email');
                }

                if ($hasEmailVerifiedAtColumn) {
                    $table->dropColumn('email_verified_at');
                }
            });
        }

        Schema::dropIfExists('password_reset_tokens');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('users', 'email')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('email')->nullable();
            });
        }

        if (! Schema::hasColumn('users', 'email_verified_at')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->timestamp('email_verified_at')->nullable();
            });
        }

        if (! Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table): void {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }
    }
};
