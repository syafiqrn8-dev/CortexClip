<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ContentBriefCreationTest extends TestCase
{
    use RefreshDatabase;

    private function makeProjectFor(User $user): Project
    {
        return $user->projects()->create([
            'title' => 'Video Pertamaku',
            'platform' => 'tiktok',
            'aspect_ratio' => '9:16',
            'target_duration_seconds' => 30,
            'status' => 'draft',
            'current_stage' => 'brief',
        ]);
    }

    public function test_authenticated_user_can_create_a_content_brief_for_their_project(): void
    {
        $user = User::factory()->create();
        $project = $this->makeProjectFor($user);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/projects/{$project->id}/content-brief", [
            'topic' => 'Tips produktivitas',
            'category' => 'edukasi',
            'content_goal' => 'Mengedukasi penonton',
            'target_audience' => 'Mahasiswa',
            'tone' => 'Santai',
            'additional_instructions' => 'Gunakan bahasa sehari-hari',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.topic', 'Tips produktivitas')
            ->assertJsonPath('data.category', 'edukasi')
            ->assertJsonPath('data.content_goal', 'Mengedukasi penonton')
            ->assertJsonPath('data.target_audience', 'Mahasiswa')
            ->assertJsonPath('data.tone', 'Santai')
            ->assertJsonPath('data.additional_instructions', 'Gunakan bahasa sehari-hari')
            ->assertJsonPath('data.project_id', $project->id);

        $this->assertDatabaseHas('content_briefs', [
            'project_id' => $project->id,
            'topic' => 'Tips produktivitas',
            'category' => 'edukasi',
            'content_goal' => 'Mengedukasi penonton',
        ]);

        $this->assertDatabaseCount('content_briefs', 1);

        $this->assertTrue($project->fresh()->contentBrief->topic === 'Tips produktivitas');
    }

    public function test_guest_cannot_create_a_content_brief(): void
    {
        $user = User::factory()->create();
        $project = $this->makeProjectFor($user);

        $response = $this->postJson("/api/projects/{$project->id}/content-brief", [
            'topic' => 'Tips produktivitas',
            'category' => 'edukasi',
            'content_goal' => 'Mengedukasi penonton',
        ]);

        $response->assertUnauthorized();

        $this->assertDatabaseCount('content_briefs', 0);
    }

    public function test_missing_required_fields_are_rejected(): void
    {
        $user = User::factory()->create();
        $project = $this->makeProjectFor($user);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/projects/{$project->id}/content-brief", [
            'topic' => '',
            'category' => '',
            'content_goal' => '',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'topic',
                'category',
                'content_goal',
            ]);

        $this->assertDatabaseCount('content_briefs', 0);
    }

    public function test_optional_fields_are_accepted_when_provided(): void
    {
        $user = User::factory()->create();
        $project = $this->makeProjectFor($user);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/projects/{$project->id}/content-brief", [
            'topic' => 'Resep cepat',
            'category' => 'kuliner',
            'content_goal' => 'Menambah interaksi',
            'target_audience' => 'Ibu rumah tangga',
            'tone' => 'Hangat',
            'additional_instructions' => 'Sertakan bahan dan langkah singkat',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('content_briefs', [
            'project_id' => $project->id,
            'target_audience' => 'Ibu rumah tangga',
            'tone' => 'Hangat',
            'additional_instructions' => 'Sertakan bahan dan langkah singkat',
        ]);
    }

    public function test_optional_fields_may_be_omitted(): void
    {
        $user = User::factory()->create();
        $project = $this->makeProjectFor($user);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/projects/{$project->id}/content-brief", [
            'topic' => 'Resep cepat',
            'category' => 'kuliner',
            'content_goal' => 'Menambah interaksi',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('content_briefs', [
            'project_id' => $project->id,
            'topic' => 'Resep cepat',
            'category' => 'kuliner',
            'content_goal' => 'Menambah interaksi',
            'target_audience' => null,
            'tone' => null,
            'additional_instructions' => null,
        ]);
    }

    public function test_user_cannot_create_a_brief_for_another_users_project(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $project = $this->makeProjectFor($owner);

        Sanctum::actingAs($intruder);

        $response = $this->postJson("/api/projects/{$project->id}/content-brief", [
            'topic' => 'Tips produktivitas',
            'category' => 'edukasi',
            'content_goal' => 'Mengedukasi penonton',
        ]);

        $response->assertNotFound();

        $this->assertDatabaseCount('content_briefs', 0);
    }

    public function test_second_brief_for_the_same_project_returns_conflict(): void
    {
        $user = User::factory()->create();
        $project = $this->makeProjectFor($user);

        Sanctum::actingAs($user);

        $this->postJson("/api/projects/{$project->id}/content-brief", [
            'topic' => 'Brief pertama',
            'category' => 'edukasi',
            'content_goal' => 'Tujuan pertama',
        ])->assertCreated();

        $original = $project->fresh()->contentBrief;

        $response = $this->postJson("/api/projects/{$project->id}/content-brief", [
            'topic' => 'Brief kedua',
            'category' => 'hiburan',
            'content_goal' => 'Tujuan kedua',
        ]);

        $response->assertStatus(409);

        $this->assertDatabaseCount('content_briefs', 1);

        $this->assertDatabaseHas('content_briefs', [
            'project_id' => $project->id,
            'topic' => 'Brief pertama',
            'category' => 'edukasi',
            'content_goal' => 'Tujuan pertama',
        ]);

        $this->assertTrue($project->fresh()->contentBrief->is($original));
    }
}
