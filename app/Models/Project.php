<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\ContentBrief;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'platform',
        'aspect_ratio',
        'target_duration_seconds',
        'status',
        'current_stage',
    ];

    public function contentBrief(): HasOne
    {
        return $this->hasOne(ContentBrief::class);
    }

    protected function casts(): array
    {
        return [
            'target_duration_seconds' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
