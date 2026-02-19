<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\RengiatAttachment;
use App\Models\RengiatEntry;
use App\Models\Subdit;
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

            $subditBlueprints = [
                [
                    'name' => 'Subdit 1 Perempuan',
                    'order_index' => 1,
                ],
                [
                    'name' => 'Subdit 2 Anak',
                    'order_index' => 2,
                ],
                [
                    'name' => 'Subdit 3 TTPO',
                    'order_index' => 3,
                ],
            ];

            $subdits = collect($subditBlueprints)
                ->mapWithKeys(function (array $blueprint): array {
                    $subdit = Subdit::query()->updateOrCreate(
                        ['order_index' => $blueprint['order_index']],
                        ['name' => $blueprint['name']],
                    );

                    return [$blueprint['order_index'] => $subdit];
                });

            $globalUnitNumbers = range(1, 5);

            Unit::query()
                ->whereNotIn('order_index', $globalUnitNumbers)
                ->delete();

            $units = collect($globalUnitNumbers)
                ->map(function (int $unitNumber): Unit {
                    return Unit::query()->updateOrCreate(
                        ['order_index' => $unitNumber],
                        [
                            'name' => sprintf('Unit %d', $unitNumber),
                            'active' => true,
                        ],
                    );
                });

            $password = Hash::make('password');

            $superAdmin = User::query()->updateOrCreate(
                ['username' => 'superadmin'],
                [
                    'name' => 'Super Admin',
                    'password' => $password,
                    'role' => UserRole::SuperAdmin,
                    'subdit_id' => null,
                    'unit_id' => null,
                ],
            );

            User::query()->updateOrCreate(
                ['username' => 'admin'],
                [
                    'name' => 'Admin',
                    'password' => $password,
                    'role' => UserRole::Admin,
                    'subdit_id' => null,
                    'unit_id' => null,
                ],
            );

            $viewer = User::query()->updateOrCreate(
                ['username' => 'viewer'],
                [
                    'name' => 'Pimpinan',
                    'password' => $password,
                    'role' => UserRole::Viewer,
                    'subdit_id' => null,
                    'unit_id' => null,
                ],
            );

            $operatorUsernames = collect($subditBlueprints)
                ->map(fn (array $blueprint): string => sprintf('operator_subdit%d', $blueprint['order_index']))
                ->all();

            User::query()
                ->where('role', UserRole::Operator->value)
                ->whereNotIn('username', $operatorUsernames)
                ->delete();

            $operatorsBySubdit = collect($subditBlueprints)
                ->mapWithKeys(function (array $blueprint) use ($password, $subdits): array {
                    /** @var Subdit $subdit */
                    $subdit = $subdits[$blueprint['order_index']];
                    $username = sprintf('operator_subdit%d', $blueprint['order_index']);

                    $operator = User::query()->updateOrCreate(
                        ['username' => $username],
                        [
                            'name' => sprintf('Operator %s', $subdit->name),
                            'password' => $password,
                            'role' => UserRole::Operator,
                            'subdit_id' => $subdit->id,
                            'unit_id' => null,
                        ],
                    );

                    return [$subdit->id => $operator];
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

                foreach ($subdits as $subdit) {
                    foreach ($units as $unit) {
                        if (! $this->shouldSeedSubditUnitOnDate($entryDateObject, $subdit->order_index, $unit->order_index)) {
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
                                'subdit_id' => $subdit->id,
                                'unit_id' => $unit->id,
                                'entry_date' => $entryDate,
                                'time_start' => $timeStart,
                                'description' => $this->buildDemoDescription(
                                    $activityPool[array_rand($activityPool)],
                                    $unit->order_index,
                                ),
                                'case_number' => null,
                                'created_by' => $operatorsBySubdit[$subdit->id]->id,
                                'updated_by' => null,
                                'created_at' => $createdAt,
                                'updated_at' => $updatedAt,
                            ]);
                        }
                    }
                }
            }

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

    private function shouldSeedSubditUnitOnDate(CarbonImmutable $date, int $subditOrder, int $unitOrder): bool
    {
        // Keep small gaps so report preview can display '-' cases.
        $skipThreshold = $date->isWeekend() ? 35 : 15;
        $seedFactor = (($subditOrder * 29) + ($unitOrder * 13) + ((int) $date->day)) % 100;

        return $seedFactor >= $skipThreshold || random_int(1, 100) > $skipThreshold;
    }
}
