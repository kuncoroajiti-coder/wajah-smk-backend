<?php

namespace App\Console\Commands;

use App\Models\School;
use Illuminate\Console\Command;

class TestGeocode extends Command
{
    protected $signature = 'app:test-geocode';

    protected $description = 'Uji geocoding 5 sekolah tanpa menyimpan hasil';

    public function handle(): int
    {
        $schools = School::whereNull('latitude')
            ->whereNull('longitude')
            ->orderBy('id')
            ->limit(5)
            ->get();

        foreach ($schools as $school) {
            $query = urlencode(
                $school->name . ', ' .
                $school->village . ', ' .
                $school->district . ', ' .
                $school->city . ', ' .
                $school->province . ', Indonesia'
            );

            $url = 'https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&q=' . $query;

            $context = stream_context_create([
                'http' => [
                    'header' => "User-Agent: WajahSMK/1.0 (BBPPMPV BOE)\r\n",
                    'timeout' => 15,
                ],
            ]);

            $result = @file_get_contents($url, false, $context);
            $data = $result ? json_decode($result, true) : [];

            $this->line("ID={$school->id} | {$school->name}");

            if (!empty($data)) {
                $this->line("LAT={$data[0]['lat']} | LON={$data[0]['lon']}");
                $this->line("DISPLAY={$data[0]['display_name']}");
            } else {
                $this->line('HASIL=Tidak ditemukan');
            }

            $this->line(str_repeat('-', 60));

            sleep(1);
        }

        return self::SUCCESS;
    }
}
