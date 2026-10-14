<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    private const DEFAULT_PER_PAGE = 15;

    private const MAX_PER_PAGE = 100;

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:'.self::MAX_PER_PAGE],
        ]);

        $projects = $request->user()
            ->projects()
            ->latest()
            ->paginate($validated['per_page'] ?? self::DEFAULT_PER_PAGE);

        return response()->json([
            'message' => 'Projects retrieved successfully.',
            'data' => array_map(
                fn (Project $project): array => $this->projectPayload($project),
                $projects->items(),
            ),
            'meta' => [
                'current_page' => $projects->currentPage(),
                'per_page' => $projects->perPage(),
                'total' => $projects->total(),
                'last_page' => $projects->lastPage(),
                'from' => $projects->firstItem(),
                'to' => $projects->lastItem(),
            ],
            'links' => [
                'first' => $projects->url(1),
                'last' => $projects->url($projects->lastPage()),
                'prev' => $projects->previousPageUrl(),
                'next' => $projects->nextPageUrl(),
            ],
        ]);
    }

    public function show(Request $request, string $project): JsonResponse
    {
        $projectModel = $request->user()
            ->projects()
            ->with('contentBrief')
            ->findOrFail($project);

        return response()->json([
            'message' => 'Project retrieved successfully.',
            'data' => [
                ...$this->projectPayload($projectModel),
                'content_brief' => $projectModel->contentBrief,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'platform' => [
                'required',
                'string',
                Rule::in(['tiktok', 'instagram', 'youtube']),
            ],
            'aspect_ratio' => [
                'sometimes',
                'string',
                Rule::in(['9:16', '16:9', '1:1']),
            ],
            'target_duration_seconds' => [
                'required',
                'integer',
                'min:1',
                'max:600',
            ],
        ]);

        $project = $request->user()->projects()->create([
            ...$validated,
            'aspect_ratio' => $validated['aspect_ratio'] ?? '9:16',
            'status' => 'draft',
            'current_stage' => 'brief',
        ]);

        return response()->json([
            'message' => 'Project created successfully.',
            'data' => $project,
        ], 201);
    }

    /**
     * Shape a project for API output, exposing only the public fields.
     *
     * @return array<string, mixed>
     */
    private function projectPayload(Project $project): array
    {
        return [
            'id' => $project->id,
            'title' => $project->title,
            'platform' => $project->platform,
            'aspect_ratio' => $project->aspect_ratio,
            'target_duration_seconds' => $project->target_duration_seconds,
            'status' => $project->status,
            'current_stage' => $project->current_stage,
            'created_at' => $project->created_at,
            'updated_at' => $project->updated_at,
        ];
    }
}
