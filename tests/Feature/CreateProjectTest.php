<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CreateProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_a_project(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/projects', [
            'title' => 'Konten Pertamaku',
            'platform' => 'tiktok',
            'aspect_ratio' => '9:16',
            'target_duration_seconds' => 30,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.title', 'Konten Pertamaku')
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.current_stage', 'brief');

        $this->assertDatabaseHas('projects', [
            'user_id' => $user->id,
            'title' => 'Konten Pertamaku',
            'platform' => 'tiktok',
        ]);
    }

    public function test_guest_cannot_create_a_project(): void
    {
        $response = $this->postJson('/api/projects', [
            'title' => 'Project Tanpa Login',
            'platform' => 'tiktok',
            'target_duration_seconds' => 30,
        ]);

        $response->assertUnauthorized();

        $this->assertDatabaseCount('projects', 0);
    }

    public function test_invalid_project_data_is_rejected(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/projects', [
            'title' => '',
            'platform' => 'platform-tidak-valid',
            'target_duration_seconds' => 0,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'title',
                'platform',
                'target_duration_seconds',
            ]);

        $this->assertDatabaseCount('projects', 0);
    }
}