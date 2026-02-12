<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\RengiatAttachment;
use App\Models\RengiatEntry;
use App\Models\Unit;
use App\Models\User;
use Carbon\CarbonImmutable;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            RengiatAttachment::query()->delete();
            RengiatEntry::query()->delete();
            AuditLog::query()->delete();

            $units = collect(range(1, 5))
                ->map(fn (int $index) => Unit::query()->updateOrCreate(
                    ['order_index' => $index],
                    [
                        'name' => "UNIT {$index}",
                        'active' => true,
                    ],
                ));

            $password = Hash::make('password');

            $superAdmin = User::query()->updateOrCreate(
                ['email' => 'superadmin@rengiat.test'],
                [
                    'name' => 'Super Admin',
                    'password' => $password,
                    'role' => UserRole::SuperAdmin,
                    'unit_id' => null,
                    'email_verified_at' => now(),
                ],
            );

            User::query()->updateOrCreate(
                ['email' => 'admin@rengiat.test'],
                [
                    'name' => 'Admin',
                    'password' => $password,
                    'role' => UserRole::Admin,
                    'unit_id' => null,
                    'email_verified_at' => now(),
                ],
            );

            $viewer = User::query()->updateOrCreate(
                ['email' => 'viewer@rengiat.test'],
                [
                    'name' => 'Pimpinan',
                    'password' => $password,
                    'role' => UserRole::Viewer,
                    'unit_id' => null,
                    'email_verified_at' => now(),
                ],
            );

            $operators = $units->mapWithKeys(function (Unit $unit) use ($password): array {
                $operator = User::query()->updateOrCreate(
                    ['email' => sprintf('operator%d@rengiat.test', $unit->order_index)],
                    [
                        'name' => sprintf('Operator Unit %d', $unit->order_index),
                        'password' => $password,
                        'role' => UserRole::Operator,
                        'unit_id' => $unit->id,
                        'email_verified_at' => now(),
                    ],
                );

                return [$unit->id => $operator];
            });

            $activityPool = [
                'Koordinasi lintas instansi terkait penanganan laporan.',
                'Pendampingan korban untuk asesmen lanjutan.',
                'Rapat evaluasi harian dan penyusunan tindak lanjut.',
                'Monitoring perkembangan perkara prioritas.',
                'Verifikasi dokumen pendukung laporan masyarakat.',
                'Konsolidasi data dan administrasi berkas perkara.',
                'Penyusunan bahan paparan pimpinan.',
                'Asistensi penyusunan berita acara pemeriksaan lanjutan.',
                'Kunjungan lapangan untuk verifikasi kronologi kejadian.',
                'Pendataan perkembangan perkara pada aplikasi monitoring internal.',
                'Koordinasi dengan penyidik terkait kelengkapan alat bukti.',
                'Pendampingan psikososial korban bersama unit terkait.',
                'Penyusunan ringkasan hasil gelar perkara.',
                'Penjadwalan ulang agenda pemeriksaan saksi.',
                'Validasi administrasi SP2HP dan dokumen pendukung.',
                'Pembaruan matriks tindak lanjut laporan masyarakat.',
            ];

            $baseDate = CarbonImmutable::today()->subDays(20);

            foreach (range(0, 20) as $dayOffset) {
                $entryDateObject = $baseDate->addDays($dayOffset);
                $entryDate = $entryDateObject->toDateString();

                foreach ($units as $unit) {
                    if (! $this->shouldSeedUnitOnDate($entryDateObject, $unit->order_index)) {
                        continue;
                    }

                    $entryCount = $this->resolveEntryCountForDate($entryDateObject);

                    foreach (range(1, $entryCount) as $itemNumber) {
                        $timeStart = random_int(0, 1) === 1
                            ? CarbonImmutable::createFromTime(random_int(8, 17), random_int(0, 1) * 30)->format('H:i')
                            : null;

                        $createdAt = $entryDateObject->setTime(random_int(8, 19), random_int(0, 1) * 30);
                        $updatedAt = $createdAt->addMinutes(random_int(0, 90));

                        RengiatEntry::query()->create([
                            'unit_id' => $unit->id,
                            'entry_date' => $entryDate,
                            'time_start' => $timeStart,
                            'description' => $this->buildDemoDescription(
                                $activityPool[array_rand($activityPool)],
                                $unit->order_index,
                            ),
                            'case_number' => null,
                            'created_by' => $operators[$unit->id]->id,
                            'updated_by' => null,
                            'created_at' => $createdAt,
                            'updated_at' => $updatedAt,
                        ]);
                    }
                }
            }

            User::query()->updateOrCreate(
                ['email' => 'operator@rengiat.test'],
                [
                    'name' => 'Operator Demo',
                    'password' => $password,
                    'role' => UserRole::Operator,
                    'unit_id' => $units->first()?->id,
                    'email_verified_at' => now(),
                ],
            );

            $superAdmin->touch();
            $viewer->touch();
        });
    }

    private function buildDemoDescription(string $baseDescription, int $unitOrder): string
    {
        if (random_int(0, 1) === 0) {
            return $baseDescription;
        }

        return sprintf(
            '%s (No. Kasus: LP/%03d/%02d/%d)',
            $baseDescription,
            random_int(1, 250),
            $unitOrder,
            now()->year,
        );
    }

    private function resolveEntryCountForDate(CarbonImmutable $date): int
    {
        // Weekdays are denser than weekends.
        if ($date->isWeekend()) {
            return random_int(1, 3);
        }

        return random_int(2, 6);
    }

    private function shouldSeedUnitOnDate(CarbonImmutable $date, int $unitOrder): bool
    {
        // Keep small gaps so report preview can display '-' cases.
        $skipThreshold = $date->isWeekend() ? 35 : 15;
        $seedFactor = (($unitOrder * 13) + ((int) $date->day)) % 100;

        return $seedFactor >= $skipThreshold || random_int(1, 100) > $skipThreshold;
    }
}
