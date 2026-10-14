<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProjectRetrievalTest extends TestCase
{
    use RefreshDatabase;

    private function makeProjectFor(User $user, array $attributes = []): Project
    {
        return $user->projects()->create([
            'title' => 'Video Pertamaku',
            'platform' => 'tiktok',
            'aspect_ratio' => '9:16',
            'target_duration_seconds' => 30,
            'status' => 'draft',
            'current_stage' => 'brief',
            ...$attributes,
        ]);
    }

    public function test_authenticated_user_can_list_their_own_projects(): void
    {
        $user = User::factory()->create();
        $project = $this->makeProjectFor($user, ['title' => 'Project Milikku']);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/projects');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Projects retrieved successfully.')
            ->assertJsonPath('data.0.id', $project->id)
            ->assertJsonPath('data.0.title', 'Project Milikku')
            ->assertJsonPath('data.0.platform', 'tiktok')
            ->assertJsonPath('data.0.aspect_ratio', '9:16')
            ->assertJsonPath('data.0.target_duration_seconds', 30)
            ->assertJsonPath('data.0.status', 'draft')
            ->assertJsonPath('data.0.current_stage', 'brief');

        $this->assertCount(1, $response->json('data'));
    }

    public function test_list_endpoint_does_not_expose_another_users_projects(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $mine = $this->makeProjectFor($user, ['title' => 'Punyaku']);
        $this->makeProjectFor($other, ['title' => 'Punya Orang Lain']);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/projects');

        $response->assertOk();

        $titles = array_column($response->json('data'), 'title');

        $this->assertContains('Punyaku', $titles);
        $this->assertNotContains('Punya Orang Lain', $titles);
        $this->assertSame([$mine->id], array_column($response->json('data'), 'id'));
    }

    public function test_list_endpoint_does_not_leak_internal_fields(): void
    {
        $user = User::factory()->create();
        $this->makeProjectFor($user);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/projects');

        $response
            ->assertOk()
            ->assertJsonMissingPath('data.0.user_id')
            ->assertJsonMissingPath('data.0.deleted_at');
    }

    public function test_list_endpoint_excludes_soft_deleted_projects(): void
    {
        $user = User::factory()->create();
        $visible = $this->makeProjectFor($user, ['title' => 'Masih Ada']);
        $deleted = $this->makeProjectFor($user, ['title' => 'Sudah Dihapus']);

        $deleted->delete();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/projects');

        $response->assertOk();

        $ids = array_column($response->json('data'), 'id');

        $this->assertContains($visible->id, $ids);
        $this->assertNotContains($deleted->id, $ids);
        $this->assertCount(1, $ids);
    }

    public function test_list_endpoint_paginates_with_sensible_metadata(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        foreach (range(1, 20) as $i) {
            $this->makeProjectFor($user, ['title' => "Project {$i}"]);
        }

        $this->makeProjectFor($other, ['title' => 'Project Orang Lain']);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/projects?per_page=5');

        $response
            ->assertOk()
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonPath('meta.total', 20)
            ->assertJsonPath('meta.last_page', 4)
            ->assertJsonStructure(['data', 'meta', 'links' => ['first', 'last', 'prev', 'next']]);

        $this->assertCount(5, $response->json('data'));

        // Second page returns the next slice and reports the page number.
        $this->getJson('/api/projects?per_page=5&page=2')
            ->assertOk()
            ->assertJsonPath('meta.current_page', 2);
    }

    public function test_list_endpoint_rejects_a_page_size_above_the_maximum(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->getJson('/api/projects?per_page=500')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['per_page']);
    }

    public function test_guest_cannot_list_projects(): void
    {
        $user = User::factory()->create();
        $this->makeProjectFor($user);

        $response = $this->getJson('/api/projects');

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_retrieve_their_own_project(): void
    {
        $user = User::factory()->create();
        $project = $this->makeProjectFor($user, ['title' => 'Project Detail']);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/projects/{$project->id}");

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Project retrieved successfully.')
            ->assertJsonPath('data.id', $project->id)
            ->assertJsonPath('data.title', 'Project Detail')
            ->assertJsonPath('data.platform', 'tiktok')
            ->assertJsonPath('data.aspect_ratio', '9:16')
            ->assertJsonPath('data.target_duration_seconds', 30)
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.current_stage', 'brief')
            ->assertJsonMissingPath('data.user_id')
            ->assertJsonMissingPath('data.deleted_at');
    }

    public function test_detail_endpoint_includes_content_brief_when_it_exists(): void
    {
        $user = User::factory()->create();
        $project = $this->makeProjectFor($user);

        $project->contentBrief()->create([
            'topic' => 'Tips produktivitas',
            'category' => 'edukasi',
            'content_goal' => 'Mengedukasi penonton',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/projects/{$project->id}");

        $response
            ->assertOk()
            ->assertJsonPath('data.content_brief.topic', 'Tips produktivitas')
            ->assertJsonPath('data.content_brief.category', 'edukasi')
            ->assertJsonPath('data.content_brief.content_goal', 'Mengedukasi penonton')
            ->assertJsonPath('data.content_brief.project_id', $project->id);
    }

    public function test_detail_endpoint_returns_null_content_brief_when_none_exists(): void
    {
        $user = User::factory()->create();
        $project = $this->makeProjectFor($user);

        Sanctum::actingAs($user);

        $this->getJson("/api/projects/{$project->id}")
            ->assertOk()
            ->assertJsonPath('data.content_brief', null);
    }

    public function test_user_cannot_retrieve_another_users_project(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $project = $this->makeProjectFor($owner);

        Sanctum::actingAs($intruder);

        $this->getJson("/api/projects/{$project->id}")->assertNotFound();
    }

    public function test_unknown_project_returns_not_found(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->getJson('/api/projects/999999')->assertNotFound();
    }

    public function test_guest_cannot_retrieve_a_project(): void
    {
        $user = User::factory()->create();
        $project = $this->makeProjectFor($user);

        $this->getJson("/api/projects/{$project->id}")->assertUnauthorized();
    }
}
