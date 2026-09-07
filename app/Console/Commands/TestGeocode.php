<?php

namespace App\Console\Commands;

use App\Models\School;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestGeocode extends Command
{
    protected $signature = 'app:test-geocode';

    protected $description = 'Uji pengambilan koordinat sekolah dari Data Pendidikan Kemendikdasmen';

    public function handle(): int
    {
        $schools = School::whereNull('latitude')
            ->whereNull('longitude')
            ->orderBy('id')
            ->limit(5)
            ->get();

        foreach ($schools as $school) {
            $url = 'https://referensi.data.kemendikdasmen.go.id/pendidikan/npsn/' . $school->npsn;

            $this->line("ID={$school->id} | NPSN={$school->npsn} | {$school->name}");

            try {
                $response = Http::timeout(20)
                    ->withHeaders([
                        'User-Agent' => 'WajahSMK/1.0 (BBPPMPV BOE)',
                    ])
                    ->get($url);

                if (!$response->successful()) {
                    $this->line("STATUS HTTP={$response->status()}");
                    $this->line('HASIL=Gagal mengambil data');
                    $this->line(str_repeat('-', 60));
                    continue;
                }

                $html = $response->body();

                $latitude = null;
                $longitude = null;

                if (preg_match('/Lintang:\s*([-0-9.]+)/i', $html, $latMatch)) {
                    $latitude = $latMatch[1];
                }

                if (preg_match('/Bujur:\s*([-0-9.]+)/i', $html, $lonMatch)) {
                    $longitude = $lonMatch[1];
                }

                if ($latitude !== null && $longitude !== null) {
                    $this->line("LAT={$latitude} | LON={$longitude}");
                    $this->line('HASIL=Koordinat ditemukan');
                } else {
                    $this->line('HASIL=Koordinat tidak ditemukan');
                }
            } catch (\Throwable $e) {
                $this->line('HASIL=Error: ' . $e->getMessage());
            }

            $this->line(str_repeat('-', 60));

            sleep(1);
        }

        return self::SUCCESS;
    }
}
