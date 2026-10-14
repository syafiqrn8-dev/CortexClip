<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_belongs_to_its_user(): void
    {
        $user = User::factory()->create();

        $project = Project::create([
            'user_id' => $user->id,
            'title' => 'Test Project',
            'platform' => 'tiktok',
            'aspect_ratio' => '9:16',
            'target_duration_seconds' => 30,
            'status' => 'draft',
            'current_stage' => 'brief',
        ]);

        $this->assertTrue($project->user->is($user));
    }

    public function test_user_only_gets_their_own_projects(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $projectA = Project::create([
            'user_id' => $userA->id,
            'title' => 'Project A',
            'platform' => 'tiktok',
            'aspect_ratio' => '9:16',
            'target_duration_seconds' => 30,
            'status' => 'draft',
            'current_stage' => 'brief',
        ]);

        Project::create([
            'user_id' => $userB->id,
            'title' => 'Project B',
            'platform' => 'youtube',
            'aspect_ratio' => '9:16',
            'target_duration_seconds' => 60,
            'status' => 'draft',
            'current_stage' => 'brief',
        ]);

        $projects = $userA->projects;

        $this->assertCount(1, $projects);
        $this->assertTrue($projects->first()->is($projectA));
    }
}