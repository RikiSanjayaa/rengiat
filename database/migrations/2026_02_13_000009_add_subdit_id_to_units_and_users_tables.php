<?php

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
        $now = now();

        collect([
            ['name' => 'Subdit 1 Perempuan', 'order_index' => 1],
            ['name' => 'Subdit 2 Anak', 'order_index' => 2],
            ['name' => 'Subdit 3 TTPO', 'order_index' => 3],
        ])->each(function (array $subdit) use ($now): void {
            DB::table('subdits')->updateOrInsert(
                ['order_index' => $subdit['order_index']],
                [
                    'name' => $subdit['name'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        });

        Schema::table('units', function (Blueprint $table) {
            $table->foreignId('subdit_id')->nullable()->after('id')->constrained('subdits')->nullOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('subdit_id')->nullable()->after('role')->constrained('subdits')->nullOnDelete();
        });

        $subditIds = DB::table('subdits')
            ->orderBy('order_index')
            ->pluck('id')
            ->values();

        if ($subditIds->isNotEmpty()) {
            DB::table('units')
                ->select(['id'])
                ->orderBy('order_index')
                ->orderBy('id')
                ->get()
                ->values()
                ->each(function (object $unit, int $index) use ($subditIds): void {
                    $subditId = $subditIds[$index % $subditIds->count()];

                    DB::table('units')
                        ->where('id', $unit->id)
                        ->update(['subdit_id' => $subditId]);
                });
        }

        $unitSubditMap = DB::table('units')
            ->whereNotNull('subdit_id')
            ->pluck('subdit_id', 'id');

        DB::table('users')
            ->whereNotNull('unit_id')
            ->select(['id', 'unit_id'])
            ->get()
            ->each(function (object $user) use ($unitSubditMap): void {
                $subditId = $unitSubditMap->get($user->unit_id);

                if ($subditId === null) {
                    return;
                }

                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['subdit_id' => $subditId]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subdit_id');
        });

        Schema::table('units', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subdit_id');
        });
    }
};
