<?php

namespace App\Imports;

use App\Models\School;
use App\Models\SchoolProgram;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Row;

class SchoolsImport implements OnEachRow, WithHeadingRow, WithChunkReading
{
    public function onRow(Row $row): void
    {
        $data = $row->toArray();

        $npsn = trim((string) ($data['npsn'] ?? ''));

        if ($npsn === '') {
            return;
        }

        $school = School::updateOrCreate(
            ['npsn' => $npsn],
            [
                'name' => $data['satuan_pendidikan'] ?? 'Tidak diketahui',
                'education_type' => $data['bentukpendidikan'] ?? 'SMK',
                'status' => $data['status_sekolah_nama'] ?? null,
                'region_code' => $data['kode_wilayah'] ?? null,
                'province' => $data['provinsi'] ?? null,
                'city' => $data['kabupaten_kota'] ?? null,
                'district' => $data['kecamatan'] ?? null,
                'village' => $data['kelurahan'] ?? null,
                'accreditation' => $data['akreditasi'] ?? null,
                'accreditation_sk' => $data['akreditasi_sp_sk'] ?? null,
                'accreditation_date' => !empty($data['akreditasi_sp_tmt']) && is_numeric($data['akreditasi_sp_tmt'])
                    ? Date::excelToDateTimeObject($data['akreditasi_sp_tmt'])->format('Y-m-d')
                    : ($data['akreditasi_sp_tmt'] ?? null),
                'curriculum_code' => $data['kurikulum'] ?? null,
                'curriculum_name' => $data['kur_nama_kurikulum'] ?? null,
                'is_active' => true,
            ]
        );

        SchoolProgram::updateOrCreate(
            [
                'school_id' => $school->id,
                'semester_id' => $data['semester_id'] ?? null,
                'bidang_jurusan_id' => $data['bidang_jurusan_id'] ?? null,
                'prog_jurusan_id' => $data['prog_jurusan_id'] ?? null,
                'komp_jurusan_id' => $data['komp_jurusan_id'] ?? null,
            ],
            [
                'bidang_nama_jurusan' => $data['bidang_nama_jurusan'] ?? null,
                'prog_nama_jurusan' => $data['prog_nama_jurusan'] ?? null,
                'komp_nama_jurusan' => $data['komp_nama_jurusan'] ?? null,
                'students_grade_10' => (int) ($data['kls_10'] ?? 0),
                'students_grade_11' => (int) ($data['kls_11'] ?? 0),
                'students_grade_12' => (int) ($data['kls_12'] ?? 0),
                'students_grade_13' => (int) ($data['kls_13'] ?? 0),
            ]
        );
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
