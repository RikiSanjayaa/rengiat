<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default(UserRole::Viewer->value)->after('password');
            $table->foreignId('unit_id')->nullable()->after('role')->constrained('units')->nullOnDelete();
        });

        DB::table('users')->whereNull('role')->update(['role' => UserRole::Viewer->value]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('unit_id');
            $table->dropColumn('role');
        });
    }
};
