<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ScriptGenerationTest extends TestCase
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

    private function makeBriefFor(Project $project, array $attributes = []): void
    {
        $project->contentBrief()->create([
            'topic' => 'Tips produktivitas',
            'category' => 'edukasi',
            'content_goal' => 'Mengedukasi penonton',
            ...$attributes,
        ]);
    }

    private function configureGeminiProvider(?string $apiKey = 'test-gemini-key'): void
    {
        config([
            'services.ai.provider' => 'gemini',
            'services.ai.gemini.api_key' => $apiKey,
            'services.ai.gemini.model' => 'gemini-2.0-flash',
            'services.ai.gemini.base_url' => 'https://generativelanguage.googleapis.com/v1beta',
        ]);
    }

    /**
     * @param  array<string, mixed>  $script
     * @return array<string, mixed>
     */
    private function geminiResponse(array $script, string $finishReason = 'STOP'): array
    {
        return [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [['text' => json_encode($script)]],
                    ],
                    'finishReason' => $finishReason,
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validScript(int $sceneCount = 2): array
    {
        $scenes = [];
        for ($i = 1; $i <= $sceneCount; $i++) {
            $scenes[] = [
                'scene_number' => $i,
                'narration' => "Narration {$i}",
                'visual_direction' => "Visual {$i}",
                'estimated_duration_seconds' => 5,
            ];
        }

        return [
            'title' => 'Gemini Title',
            'hook' => 'Gemini Hook',
            'closing_cta' => 'Gemini CTA',
            'scenes' => $scenes,
        ];
    }

    public function test_authenticated_user_can_generate_script_for_their_project_with_brief(): void
    {
        Http::fake();

        $user = User::factory()->create();
        $project = $this->makeProjectFor($user);
        $this->makeBriefFor($project, [
            'target_audience' => 'Mahasiswa',
            'tone' => 'Santai',
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/projects/{$project->id}/script-generations", [
            'max_scenes' => 3,
            'include_closing' => true,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Script generated successfully.')
            ->assertJsonPath('data.provider', 'mock')
            ->assertJsonPath('data.title', 'Tips produktivitas')
            ->assertJsonPath('data.hook', 'Hook: Tips produktivitas')
            ->assertJsonPath('data.closing_cta', 'Follow for more!')
            ->assertJsonPath('data.scenes.0.scene_number', 1)
            ->assertJsonPath('data.scenes.0.narration', 'Scene 1: Tips produktivitas - engaging hook/narration.')
            ->assertJsonPath('data.scenes.0.visual_direction', 'Show relevant visuals for Tips produktivitas.')
            ->assertJsonPath('data.scenes.0.estimated_duration_seconds', 10)
            ->assertJsonPath('data.scenes.1.scene_number', 2)
            ->assertJsonPath('data.scenes.2.scene_number', 3)
            ->assertJsonPath('data.scenes.2.estimated_duration_seconds', 10);

        $sceneDurations = array_column($response->json('data.scenes'), 'estimated_duration_seconds');
        $this->assertCount(3, $response->json('data.scenes'));
        $this->assertSame(30, array_sum($sceneDurations));

        $this->assertDatabaseHas('ai_generations', [
            'project_id' => $project->id,
            'task_type' => 'script_generation',
            'provider' => 'mock',
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('script_versions', [
            'project_id' => $project->id,
            'version_number' => 1,
            'title' => 'Tips produktivitas',
            'created_by' => $user->id,
        ]);

        $this->assertDatabaseHas('script_drafts', [
            'project_id' => $project->id,
            'title' => 'Tips produktivitas',
            'updated_by' => $user->id,
        ]);

        $this->assertDatabaseCount('script_versions', 1);
        $this->assertDatabaseCount('script_drafts', 1);
    }

    public function test_guest_cannot_generate_script(): void
    {
        Http::fake();

        $user = User::factory()->create();
        $project = $this->makeProjectFor($user);
        $this->makeBriefFor($project);

        $response = $this->postJson("/api/projects/{$project->id}/script-generations");

        $response->assertUnauthorized();

        $this->assertDatabaseCount('ai_generations', 0);
        $this->assertDatabaseCount('script_versions', 0);
        $this->assertDatabaseCount('script_drafts', 0);
    }

    public function test_user_cannot_generate_script_for_another_users_project(): void
    {
        Http::fake();

        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $project = $this->makeProjectFor($owner);
        $this->makeBriefFor($project);

        Sanctum::actingAs($intruder);

        $response = $this->postJson("/api/projects/{$project->id}/script-generations");

        $response->assertNotFound();

        $this->assertDatabaseCount('ai_generations', 0);
        $this->assertDatabaseCount('script_versions', 0);
        $this->assertDatabaseCount('script_drafts', 0);
    }

    public function test_project_without_content_brief_is_rejected(): void
    {
        Http::fake();

        $user = User::factory()->create();
        $project = $this->makeProjectFor($user);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/projects/{$project->id}/script-generations");

        $response->assertUnprocessable();

        $this->assertDatabaseCount('ai_generations', 0);
        $this->assertDatabaseCount('script_versions', 0);
        $this->assertDatabaseCount('script_drafts', 0);
    }

    public function test_invalid_optional_controls_are_rejected(): void
    {
        Http::fake();

        $user = User::factory()->create();
        $project = $this->makeProjectFor($user);
        $this->makeBriefFor($project);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/projects/{$project->id}/script-generations", [
            'max_scenes' => 20,
            'include_closing' => 'yes',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['max_scenes', 'include_closing']);

        $this->assertDatabaseCount('ai_generations', 0);
        $this->assertDatabaseCount('script_versions', 0);
        $this->assertDatabaseCount('script_drafts', 0);
    }

    public function test_include_closing_false_omits_closing_cta(): void
    {
        Http::fake();

        $user = User::factory()->create();
        $project = $this->makeProjectFor($user);
        $this->makeBriefFor($project);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/projects/{$project->id}/script-generations", [
            'include_closing' => false,
        ]);

        $response->assertCreated()->assertJsonPath('data.closing_cta', null);

        $this->assertStringNotContainsString('Closing CTA', $response->json('data.content'));
    }

    public function test_second_generation_increments_version_number(): void
    {
        Http::fake();

        $user = User::factory()->create();
        $project = $this->makeProjectFor($user);
        $this->makeBriefFor($project);

        Sanctum::actingAs($user);

        $this->postJson("/api/projects/{$project->id}/script-generations")->assertCreated();
        $this->postJson("/api/projects/{$project->id}/script-generations")->assertCreated();

        $this->assertDatabaseHas('script_versions', [
            'project_id' => $project->id,
            'version_number' => 1,
        ]);
        $this->assertDatabaseHas('script_versions', [
            'project_id' => $project->id,
            'version_number' => 2,
        ]);

        $this->assertDatabaseCount('script_versions', 2);
        $this->assertDatabaseCount('script_drafts', 1);
    }

    public function test_project_and_brief_data_remain_unchanged_after_generation(): void
    {
        Http::fake();

        $user = User::factory()->create();
        $project = $this->makeProjectFor($user, [
            'title' => 'Original Title',
            'platform' => 'youtube',
            'aspect_ratio' => '16:9',
            'target_duration_seconds' => 60,
        ]);

        $brief = $project->contentBrief()->create([
            'topic' => 'Original Topic',
            'category' => 'edukasi',
            'content_goal' => 'Original Goal',
            'target_audience' => 'Original Audience',
            'tone' => 'Original Tone',
            'additional_instructions' => 'Original Instructions',
        ]);

        $projectBefore = $project->fresh()->toArray();
        $briefBefore = $brief->fresh()->toArray();

        Sanctum::actingAs($user);

        $this->postJson("/api/projects/{$project->id}/script-generations")->assertCreated();

        $this->assertEquals($projectBefore, $project->fresh()->toArray());
        $this->assertEquals($briefBefore, $brief->fresh()->toArray());
    }

    public function test_unknown_project_returns_not_found(): void
    {
        Http::fake();

        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->postJson('/api/projects/999999/script-generations')->assertNotFound();
    }

    public function test_gemini_provider_generates_script_from_structured_response(): void
    {
        $this->configureGeminiProvider();

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(
                $this->geminiResponse($this->validScript(2)),
                200,
            ),
        ]);

        $user = User::factory()->create();
        $project = $this->makeProjectFor($user);
        $this->makeBriefFor($project);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/projects/{$project->id}/script-generations");

        $response
            ->assertCreated()
            ->assertJsonPath('data.provider', 'gemini')
            ->assertJsonPath('data.title', 'Gemini Title')
            ->assertJsonPath('data.hook', 'Gemini Hook')
            ->assertJsonPath('data.closing_cta', 'Gemini CTA')
            ->assertJsonPath('data.scenes.0.narration', 'Narration 1')
            ->assertJsonPath('data.scenes.1.scene_number', 2);

        $this->assertCount(2, $response->json('data.scenes'));

        $this->assertDatabaseHas('ai_generations', [
            'project_id' => $project->id,
            'provider' => 'gemini',
            'model' => 'gemini-2.0-flash',
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('script_versions', [
            'project_id' => $project->id,
            'version_number' => 1,
            'title' => 'Gemini Title',
        ]);
    }

    public function test_gemini_request_uses_expected_endpoint_headers_and_schema(): void
    {
        $this->configureGeminiProvider();

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(
                $this->geminiResponse($this->validScript()),
                200,
            ),
        ]);

        $user = User::factory()->create();
        $project = $this->makeProjectFor($user);
        $this->makeBriefFor($project);

        Sanctum::actingAs($user);

        $this->postJson("/api/projects/{$project->id}/script-generations")->assertCreated();

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/models/gemini-2.0-flash:generateContent')
                && $request->hasHeader('x-goog-api-key', 'test-gemini-key')
                && $request['generationConfig']['responseMimeType'] === 'application/json'
                && $request['generationConfig']['responseSchema']['type'] === 'OBJECT'
                && $request['contents'][0]['role'] === 'user'
                && $request['contents'][0]['parts'][0]['text'] !== '';
        });
    }

    public function test_gemini_missing_api_key_returns_configuration_error(): void
    {
        $this->configureGeminiProvider(null);

        Http::fake();

        $user = User::factory()->create();
        $project = $this->makeProjectFor($user);
        $this->makeBriefFor($project);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/projects/{$project->id}/script-generations");

        $response->assertStatus(500);

        $this->assertDatabaseHas('ai_generations', [
            'project_id' => $project->id,
            'status' => 'failed',
            'error_code' => 'provider_configuration_error',
        ]);

        $this->assertDatabaseCount('script_versions', 0);
        $this->assertDatabaseCount('script_drafts', 0);
    }

    public function test_unknown_provider_is_rejected(): void
    {
        config(['services.ai.provider' => 'does-not-exist']);

        Http::fake();

        $user = User::factory()->create();
        $project = $this->makeProjectFor($user);
        $this->makeBriefFor($project);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/projects/{$project->id}/script-generations");

        $response->assertStatus(500);

        $this->assertDatabaseHas('ai_generations', [
            'project_id' => $project->id,
            'status' => 'failed',
            'error_code' => 'provider_configuration_error',
        ]);

        $this->assertDatabaseCount('script_versions', 0);
        $this->assertDatabaseCount('script_drafts', 0);
    }

    public function test_gemini_provider_failure_is_handled_safely(): void
    {
        $this->configureGeminiProvider();

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => ['code' => 500]], 500),
        ]);

        $user = User::factory()->create();
        $project = $this->makeProjectFor($user);
        $this->makeBriefFor($project);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/projects/{$project->id}/script-generations");

        $response->assertStatus(502)->assertJsonPath('error', 'Unable to generate script at this time.');

        $this->assertDatabaseHas('ai_generations', [
            'project_id' => $project->id,
            'status' => 'failed',
            'error_code' => 'provider_error',
        ]);

        $this->assertDatabaseCount('script_versions', 0);
        $this->assertDatabaseCount('script_drafts', 0);
    }

    public function test_gemini_rate_limit_is_handled_safely(): void
    {
        $this->configureGeminiProvider();

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'error' => ['code' => 429, 'status' => 'RESOURCE_EXHAUSTED'],
            ], 429),
        ]);

        $user = User::factory()->create();
        $project = $this->makeProjectFor($user);
        $this->makeBriefFor($project);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/projects/{$project->id}/script-generations");

        $response->assertStatus(502)->assertJsonPath('error', 'Unable to generate script at this time.');

        $this->assertDatabaseHas('ai_generations', [
            'project_id' => $project->id,
            'status' => 'failed',
            'error_code' => 'provider_error',
        ]);
    }

    public function test_gemini_connection_failure_is_handled_safely(): void
    {
        $this->configureGeminiProvider();

        Http::fake([
            'generativelanguage.googleapis.com/*' => function () {
                throw new ConnectionException('Connection timed out.');
            },
        ]);

        $user = User::factory()->create();
        $project = $this->makeProjectFor($user);
        $this->makeBriefFor($project);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/projects/{$project->id}/script-generations");

        $response->assertStatus(502)->assertJsonPath('error', 'Unable to generate script at this time.');

        $this->assertDatabaseHas('ai_generations', [
            'project_id' => $project->id,
            'status' => 'failed',
            'error_code' => 'provider_error',
        ]);
    }

    public function test_gemini_truncated_response_is_rejected(): void
    {
        $this->configureGeminiProvider();

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(
                $this->geminiResponse($this->validScript(), 'MAX_TOKENS'),
                200,
            ),
        ]);

        $user = User::factory()->create();
        $project = $this->makeProjectFor($user);
        $this->makeBriefFor($project);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/projects/{$project->id}/script-generations");

        $response->assertStatus(502)->assertJsonPath('error', 'Unable to generate script at this time.');

        $this->assertDatabaseHas('ai_generations', [
            'project_id' => $project->id,
            'status' => 'failed',
            'error_code' => 'provider_error',
        ]);

        $this->assertDatabaseCount('script_versions', 0);
    }

    public function test_gemini_blocked_prompt_is_rejected(): void
    {
        $this->configureGeminiProvider();

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'promptFeedback' => ['blockReason' => 'SAFETY'],
            ], 200),
        ]);

        $user = User::factory()->create();
        $project = $this->makeProjectFor($user);
        $this->makeBriefFor($project);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/projects/{$project->id}/script-generations");

        $response->assertStatus(502)->assertJsonPath('error', 'Unable to generate script at this time.');

        $this->assertDatabaseHas('ai_generations', [
            'project_id' => $project->id,
            'status' => 'failed',
            'error_code' => 'provider_error',
        ]);
    }

    public function test_gemini_malformed_json_is_rejected(): void
    {
        $this->configureGeminiProvider();

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => 'not-json']]], 'finishReason' => 'STOP'],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();
        $project = $this->makeProjectFor($user);
        $this->makeBriefFor($project);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/projects/{$project->id}/script-generations");

        $response->assertStatus(502)->assertJsonPath('error', 'Unable to generate script at this time.');

        $this->assertDatabaseHas('ai_generations', [
            'project_id' => $project->id,
            'status' => 'failed',
            'error_code' => 'provider_error',
        ]);
    }

    public function test_max_scenes_truncates_provider_scenes(): void
    {
        $this->configureGeminiProvider();

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(
                $this->geminiResponse($this->validScript(5)),
                200,
            ),
        ]);

        $user = User::factory()->create();
        $project = $this->makeProjectFor($user);
        $this->makeBriefFor($project);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/projects/{$project->id}/script-generations", [
            'max_scenes' => 2,
        ]);

        $response->assertCreated();

        $this->assertCount(2, $response->json('data.scenes'));
        $this->assertSame(2, $response->json('data.scenes.1.scene_number'));
    }

    public function test_unknown_project_returns_not_found_for_gemini(): void
    {
        $this->configureGeminiProvider();

        Http::fake();

        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->postJson('/api/projects/999999/script-generations')->assertNotFound();
    }
}
