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
        if (! Schema::hasColumn('rengiat_entries', 'subdit_id')) {
            Schema::table('rengiat_entries', function (Blueprint $table) {
                $table->foreignId('subdit_id')->nullable()->after('id')->constrained('subdits')->restrictOnDelete();
            });
        }

        if (Schema::hasColumn('units', 'subdit_id')) {
            $unitSubditMap = DB::table('units')
                ->whereNotNull('subdit_id')
                ->pluck('subdit_id', 'id');

            DB::table('rengiat_entries')
                ->select(['id', 'unit_id'])
                ->get()
                ->each(function (object $entry) use ($unitSubditMap): void {
                    $subditId = $unitSubditMap->get($entry->unit_id);

                    if ($subditId === null) {
                        return;
                    }

                    DB::table('rengiat_entries')
                        ->where('id', $entry->id)
                        ->update(['subdit_id' => $subditId]);
                });

            Schema::table('units', function (Blueprint $table) {
                $table->dropConstrainedForeignId('subdit_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('units', 'subdit_id')) {
            Schema::table('units', function (Blueprint $table) {
                $table->foreignId('subdit_id')->nullable()->after('id')->constrained('subdits')->nullOnDelete();
            });
        }

        if (Schema::hasColumn('rengiat_entries', 'subdit_id')) {
            $entrySubditMap = DB::table('rengiat_entries')
                ->whereNotNull('subdit_id')
                ->pluck('subdit_id', 'id');

            $unitIds = DB::table('units')
                ->whereNotNull('subdit_id')
                ->orderBy('order_index')
                ->pluck('id', 'subdit_id');

            DB::table('rengiat_entries')
                ->select(['id'])
                ->get()
                ->each(function (object $entry) use ($entrySubditMap, $unitIds): void {
                    $subditId = $entrySubditMap->get($entry->id);
                    $unitId = $subditId !== null ? $unitIds->get($subditId) : null;

                    if ($unitId === null) {
                        return;
                    }

                    DB::table('rengiat_entries')
                        ->where('id', $entry->id)
                        ->update(['unit_id' => $unitId]);
                });

            Schema::table('rengiat_entries', function (Blueprint $table) {
                $table->dropConstrainedForeignId('subdit_id');
            });
        }
    }
};
