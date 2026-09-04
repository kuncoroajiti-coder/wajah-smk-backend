<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolProgram extends Model
{
    protected $fillable = [
        'school_id',
        'semester_id',
        'bidang_jurusan_id',
        'bidang_nama_jurusan',
        'prog_jurusan_id',
        'prog_nama_jurusan',
        'komp_jurusan_id',
        'komp_nama_jurusan',
        'students_grade_10',
        'students_grade_11',
        'students_grade_12',
        'students_grade_13',
    ];

    protected $casts = [
        'students_grade_10' => 'integer',
        'students_grade_11' => 'integer',
        'students_grade_12' => 'integer',
        'students_grade_13' => 'integer',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
