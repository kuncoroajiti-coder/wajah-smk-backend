<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->text('name')->change();
            $table->text('province')->change();
            $table->text('city')->change();
            $table->text('district')->change();
            $table->text('village')->change();
            $table->text('accreditation')->change();
            $table->text('accreditation_sk')->change();
            $table->text('curriculum_name')->change();
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->string('name')->change();
            $table->string('province')->change();
            $table->string('city')->change();
            $table->string('district')->change();
            $table->string('village')->change();
            $table->string('accreditation')->change();
            $table->string('accreditation_sk')->change();
            $table->string('curriculum_name')->change();
        });
    }
};
