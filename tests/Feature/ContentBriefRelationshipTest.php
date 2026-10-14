<?php

namespace Tests\Feature;

use App\Models\ContentBrief;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentBriefRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_can_have_a_content_brief(): void
    {
        $user = User::factory()->create();

        $project = $user->projects()->create([
            'title' => 'Video Pertamaku',
            'platform' => 'tiktok',
            'aspect_ratio' => '9:16',
            'target_duration_seconds' => 30,
            'status' => 'draft',
            'current_stage' => 'brief',
        ]);

        $brief = $project->contentBrief()->create([
            'topic' => 'Tips produktivitas',
            'category' => 'edukasi',
            'content_goal' => 'Mengedukasi penonton',
        ]);

        $this->assertInstanceOf(ContentBrief::class, $brief);

        $this->assertTrue($brief->project->is($project));

        $this->assertTrue($project->contentBrief->is($brief));

        $this->assertDatabaseHas('content_briefs', [
            'project_id' => $project->id,
            'topic' => 'Tips produktivitas',
            'category' => 'edukasi',
            'content_goal' => 'Mengedukasi penonton',
        ]);
    }
}
