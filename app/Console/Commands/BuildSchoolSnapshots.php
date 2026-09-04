<?php

namespace App\Console\Commands;

use App\Models\School;
use App\Models\SchoolSnapshot;
use Illuminate\Console\Command;

class BuildSchoolSnapshots extends Command
{
    protected $signature = 'app:build-school-snapshots';

    protected $description = 'Build school snapshots from imported school program data';

    public function handle(): int
    {
        $this->info('Membangun school snapshots...');

        $count = 0;

        School::query()
            ->with([
                'programs' => function ($query) {
                    $query->select([
                        'id',
                        'school_id',
                        'semester_id',
                    ])->orderBy('semester_id');
                },
            ])
            ->chunkById(500, function ($schools) use (&$count) {
                foreach ($schools as $school) {
                    $semesterIds = $school->programs
                        ->pluck('semester_id')
                        ->filter()
                        ->unique();

                    foreach ($semesterIds as $semesterId) {
                        SchoolSnapshot::updateOrCreate(
                            [
                                'school_id' => $school->id,
                                'semester_id' => $semesterId,
                            ],
                            [
                                'accreditation' => $school->accreditation,
                                'accreditation_sk' => $school->accreditation_sk,
                                'accreditation_date' => $school->accreditation_date,
                                'curriculum_code' => $school->curriculum_code,
                                'curriculum_name' => $school->curriculum_name,
                            ]
                        );

                        $count++;
                    }
                }

                $this->output->write('.');
            });

        $this->newLine();
        $this->info("Selesai. {$count} snapshot diproses.");

        return self::SUCCESS;
    }
}



