<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_programs', function (Blueprint $table) {
            $table->id();

            // Relasi ke sekolah
            $table->foreignId('school_id')
                ->constrained('schools')
                ->cascadeOnDelete();

            // Periode data
            $table->string('semester_id', 20)->nullable();

            // Bidang keahlian
            $table->string('bidang_jurusan_id', 50)->nullable();
            $table->string('bidang_nama_jurusan')->nullable();

            // Program keahlian
            $table->string('prog_jurusan_id', 50)->nullable();
            $table->string('prog_nama_jurusan')->nullable();

            // Kompetensi keahlian
            $table->string('komp_jurusan_id', 50)->nullable();
            $table->string('komp_nama_jurusan')->nullable();

            // Peserta didik
            $table->unsignedInteger('students_grade_10')->default(0);
            $table->unsignedInteger('students_grade_11')->default(0);
            $table->unsignedInteger('students_grade_12')->default(0);
            $table->unsignedInteger('students_grade_13')->default(0);

            $table->timestamps();

            $table->index('semester_id');
            $table->index('bidang_jurusan_id');
            $table->index('prog_jurusan_id');
            $table->index('komp_jurusan_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_programs');
    }
};
