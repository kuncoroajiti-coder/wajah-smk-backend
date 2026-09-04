<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->text('accreditation')->nullable()->change();
            $table->text('accreditation_sk')->nullable()->change();
            $table->date('accreditation_date')->nullable()->change();
            $table->string('curriculum_code')->nullable()->change();
            $table->text('curriculum_name')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->text('accreditation')->nullable(false)->change();
            $table->text('accreditation_sk')->nullable(false)->change();
            $table->date('accreditation_date')->nullable(false)->change();
            $table->string('curriculum_code')->nullable(false)->change();
            $table->text('curriculum_name')->nullable(false)->change();
        });
    }
};
