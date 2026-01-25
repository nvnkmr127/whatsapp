<?php

namespace App\Models;

use App\Enums\EmailUseCase;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'name',
        'slug',
        'subject',
        'content_html',
        'content_text',
        'variable_schema',
        'is_locked',
        'description',
        'is_active',
    ];

    protected $casts = [
        'variable_schema' => 'array',
        'is_locked' => 'boolean',
        'is_active' => 'boolean',
        'type' => EmailUseCase::class,
    ];
}
