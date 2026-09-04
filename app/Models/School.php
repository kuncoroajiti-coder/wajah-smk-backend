<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    protected $fillable = [
        'npsn',
        'name',
        'education_type',
        'status',
        'region_code',
        'province',
        'city',
        'district',
        'village',
        'accreditation',
        'accreditation_sk',
        'accreditation_date',
        'curriculum_code',
        'curriculum_name',
        'latitude',
        'longitude',
        'cover_photo',
        'is_active',
    ];

    protected $casts = [
        'accreditation_date' => 'date',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'is_active' => 'boolean',
    ];

    public function programs(): HasMany
    {
        return $this->hasMany(SchoolProgram::class);
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(SchoolSnapshot::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
