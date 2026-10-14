<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentBrief extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'topic',
        'category',
        'target_audience',
        'content_goal',
        'tone',
        'additional_instructions',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
