<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->after('name');
        });

        $usedUsernames = [];

        DB::table('users')
            ->select(['id', 'name', 'username'])
            ->orderBy('id')
            ->get()
            ->each(function (object $user) use (&$usedUsernames): void {
                if (! empty($user->username)) {
                    $usedUsernames[] = $user->username;

                    return;
                }

                $baseUsername = Str::of((string) $user->name)
                    ->lower()
                    ->replaceMatches('/[^a-z0-9_]/', '_')
                    ->trim('_')
                    ->value();

                if ($baseUsername === '') {
                    $baseUsername = 'user';
                }

                $username = $baseUsername;
                $suffix = 1;

                while (in_array($username, $usedUsernames, true)) {
                    $username = $baseUsername.$suffix;
                    $suffix++;
                }

                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['username' => $username]);

                $usedUsernames[] = $username;
            });

        Schema::table('users', function (Blueprint $table) {
            $table->unique('username');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_username_unique');
            $table->dropColumn('username');
        });
    }
};
