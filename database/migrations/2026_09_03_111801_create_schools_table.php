<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table) {
            $table->id();

            // Identitas sekolah
            $table->string('npsn', 20)->unique();
            $table->string('name');
            $table->string('education_type', 50)->default('SMK');
            $table->string('status', 30)->nullable();

            // Wilayah
            $table->string('region_code', 30)->nullable();
            $table->string('province')->nullable();
            $table->string('city')->nullable();
            $table->string('district')->nullable();
            $table->string('village')->nullable();

            // Akreditasi
            $table->string('accreditation', 10)->nullable();
            $table->string('accreditation_sk')->nullable();
            $table->date('accreditation_date')->nullable();

            // Kurikulum
            $table->string('curriculum_code', 50)->nullable();
            $table->string('curriculum_name')->nullable();

            // Lokasi geografis
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Foto utama
            $table->string('cover_photo')->nullable();

            // Status aplikasi
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('province');
            $table->index('city');
            $table->index('district');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schools');
    }
};
