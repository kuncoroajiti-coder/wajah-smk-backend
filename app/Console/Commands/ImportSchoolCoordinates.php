<?php

namespace App\Console\Commands;

use App\Models\School;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ImportSchoolCoordinates extends Command
{
    protected $signature = 'app:import-school-coordinates
                            {--limit=10 : Jumlah sekolah yang diproses dalam satu batch}
                            {--delay=1 : Jeda antar-request dalam detik}';

    protected $description = 'Mengambil koordinat sekolah berdasarkan NPSN dari Data Pendidikan Kemendikdasmen';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $delay = max(0, (int) $this->option('delay'));

        $schools = School::where(function ($query) {
                $query->whereNull('latitude')
                    ->orWhereNull('longitude');
            })
            ->whereNotNull('npsn')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($schools->isEmpty()) {
            $this->info('Tidak ada sekolah yang membutuhkan koordinat.');
            return self::SUCCESS;
        }

        $this->info("Memproses {$schools->count()} sekolah.");

        $success = 0;
        $failed = 0;

        foreach ($schools as $school) {
            $this->line(
                "ID={$school->id} | NPSN={$school->npsn} | {$school->name}"
            );

            try {
                $url = 'https://referensi.data.kemendikdasmen.go.id/pendidikan/npsn/' . $school->npsn;

                $response = Http::timeout(20)
                    ->retry(2, 1000)
                    ->withHeaders([
                        'User-Agent' => 'WajahSMK/1.0 (BBPPMPV BOE)',
                    ])
                    ->get($url);

                if (!$response->successful()) {
                    $reason = "HTTP {$response->status()}";

                    $this->error("  GAGAL: {$reason}");

                    Log::warning('Import koordinat sekolah gagal', [
                        'school_id' => $school->id,
                        'npsn' => $school->npsn,
                        'name' => $school->name,
                        'reason' => $reason,
                    ]);

                    $failed++;
                    continue;
                }

                $html = $response->body();

                $latitude = null;
                $longitude = null;

                if (preg_match('/Lintang:\s*([-0-9.]+)/i', $html, $match)) {
                    $latitude = $match[1];
                }

                if (preg_match('/Bujur:\s*([-0-9.]+)/i', $html, $match)) {
                    $longitude = $match[1];
                }

                if ($latitude === null || $longitude === null) {
                    $reason = 'Koordinat tidak ditemukan';

                    $this->warn("  GAGAL: {$reason}");

                    Log::warning('Import koordinat sekolah gagal', [
                        'school_id' => $school->id,
                        'npsn' => $school->npsn,
                        'name' => $school->name,
                        'reason' => $reason,
                    ]);

                    $failed++;
                    continue;
                }

                $latitudeValue = (float) $latitude;
                $longitudeValue = (float) $longitude;

                if (
                    $latitudeValue < -90 ||
                    $latitudeValue > 90 ||
                    $longitudeValue < -180 ||
                    $longitudeValue > 180
                ) {
                    $reason = "Koordinat tidak valid: {$latitudeValue}, {$longitudeValue}";

                    $this->error("  GAGAL: {$reason}");

                    Log::warning('Koordinat sekolah di luar rentang valid', [
                        'school_id' => $school->id,
                        'npsn' => $school->npsn,
                        'name' => $school->name,
                        'latitude' => $latitudeValue,
                        'longitude' => $longitudeValue,
                        'reason' => $reason,
                    ]);

                    $failed++;
                    continue;
                }

                $school->latitude = $latitudeValue;
                $school->longitude = $longitudeValue;
                $school->save();

                $this->info(
                    "  BERHASIL: {$latitudeValue}, {$longitudeValue}"
                );

                $success++;
            } catch (\Throwable $e) {
                $reason = $e->getMessage();

                $this->error("  ERROR: {$reason}");

                Log::error('Import koordinat sekolah exception', [
                    'school_id' => $school->id,
                    'npsn' => $school->npsn,
                    'name' => $school->name,
                    'reason' => $reason,
                ]);

                $failed++;
            }

            if ($delay > 0) {
                sleep($delay);
            }
        }

        $this->newLine();
        $this->info('=== HASIL BATCH ===');
        $this->line("Berhasil : {$success}");
        $this->line("Gagal    : {$failed}");
        $this->line("Diproses : " . ($success + $failed));

        return self::SUCCESS;
    }
}
