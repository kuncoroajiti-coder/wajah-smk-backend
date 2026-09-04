<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_snapshots', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')
                ->constrained('schools')
                ->cascadeOnDelete();

            $table->string('semester_id', 20);

            $table->string('accreditation', 10)->nullable();
            $table->string('accreditation_sk')->nullable();
            $table->date('accreditation_date')->nullable();

            $table->string('curriculum_code', 50)->nullable();
            $table->string('curriculum_name')->nullable();

            $table->timestamps();

            $table->unique(['school_id', 'semester_id']);
            $table->index('semester_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_snapshots');
    }
};
