<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolSnapshot extends Model
{
    protected $fillable = [
        'school_id',
        'semester_id',
        'accreditation',
        'accreditation_sk',
        'accreditation_date',
        'curriculum_code',
        'curriculum_name',
    ];

    protected $casts = [
        'accreditation_date' => 'date',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
