<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

#[Signature('app:import-employees {file=storage/app/imports/Pegawai BOE 2026(1).xlsx}')]
#[Description('Import whitelist pegawai BOE dari file Excel')]
class ImportEmployees extends Command
{
    public function handle(): int
    {
        $file = base_path($this->argument('file'));

        if (!is_file($file)) {
            $this->error("File tidak ditemukan: {$file}");
            return self::FAILURE;
        }

        $this->info("Membaca file: {$file}");

        try {
            $spreadsheet = IOFactory::load($file);
            $worksheet = $spreadsheet->getActiveSheet();
        } catch (\Throwable $e) {
            $this->error('Gagal membaca file Excel: ' . $e->getMessage());
            return self::FAILURE;
        }

        $rows = $worksheet->toArray(null, true, true, true);

        if (count($rows) < 2) {
            $this->error('File Excel tidak memiliki data pegawai.');
            return self::FAILURE;
        }

        $headers = array_map(
            fn ($value) => strtoupper(trim((string) $value)),
            $rows[1]
        );

        $headerMap = array_flip($headers);

        foreach (['NAMA', 'NIP', 'ROLE'] as $requiredHeader) {
            if (!array_key_exists($requiredHeader, $headerMap)) {
                $this->error("Kolom {$requiredHeader} tidak ditemukan.");
                return self::FAILURE;
            }
        }

        $nameColumn = $headerMap['NAMA'];
        $nipColumn = $headerMap['NIP'];
        $roleColumn = $headerMap['ROLE'];

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($rows as $rowNumber => $row) {
            if ($rowNumber === 1) {
                continue;
            }

            $name = trim((string) ($row[$nameColumn] ?? ''));
            $nip = trim((string) ($row[$nipColumn] ?? ''));
            $roleSource = trim((string) ($row[$roleColumn] ?? ''));

            if ($name === '' || $nip === '') {
                $skipped++;

                $this->warn(
                    "Baris {$rowNumber}: NAMA atau NIP kosong, dilewati."
                );

                continue;
            }

            $nip = preg_replace('/\D+/', '', $nip);

            if ($nip === '') {
                $skipped++;

                $this->warn(
                    "Baris {$rowNumber}: NIP tidak valid, dilewati."
                );

                continue;
            }

            $role = match (strtolower($roleSource)) {
                'manajemen' => 'manajemen',
                'pegawai boe' => 'pegawai_boe',
                'super admin' => 'super_admin',
                default => null,
            };

            if ($role === null) {
                $skipped++;

                $this->warn(
                    "Baris {$rowNumber}: Role '{$roleSource}' tidak dikenali, dilewati."
                );

                continue;
            }

            $user = User::where('nip', $nip)->first();

            if ($user) {
                $user->name = $name;
                $user->role = $role;
                $user->status = 'aktif';
                $user->save();

                $updated++;

                continue;
            }

            /*
             * Kolom email pada database lama masih wajib diisi.
             * Untuk akun internal baru, gunakan email teknis berbasis NIP.
             * Email ini tidak digunakan sebagai username.
             */
            $email = "{$nip}@pegawai.wajah-smk.local";

            $existingEmail = User::where('email', $email)->first();

            if ($existingEmail) {
                throw new RuntimeException(
                    "Email teknis {$email} sudah digunakan oleh user lain."
                );
            }

            User::create([
                'name' => $name,
                'nip' => $nip,
                'email' => $email,
                'password' => '12345',
                'role' => $role,
                'status' => 'aktif',
                'must_change_password' => true,
            ]);

            $created++;
        }

        $this->newLine();
        $this->info('Import pegawai selesai.');
        $this->line("Dibuat     : {$created}");
        $this->line("Diperbarui : {$updated}");
        $this->line("Dilewati   : {$skipped}");

        return self::SUCCESS;
    }
}