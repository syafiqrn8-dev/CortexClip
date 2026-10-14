<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContentBriefController extends Controller
{
    public function store(Request $request, string $project): JsonResponse
    {
        $validated = $request->validate([
            'topic' => ['required', 'string', 'max:240'],
            'category' => ['required', 'string', 'max:80'],
            'content_goal' => ['required', 'string', 'max:120'],
            'target_audience' => ['sometimes', 'nullable', 'string'],
            'tone' => ['sometimes', 'nullable', 'string', 'max:80'],
            'additional_instructions' => ['sometimes', 'nullable', 'string'],
        ]);

        $projectModel = $request->user()
            ->projects()
            ->findOrFail($project);

        if ($projectModel->contentBrief()->exists()) {
            return $this->conflictResponse();
        }

        try {
            $brief = $projectModel->contentBrief()->create($validated);
        } catch (UniqueConstraintViolationException) {
            // A concurrent request created the brief first; the unique index on
            // content_briefs.project_id is the final safeguard against duplicates.
            return $this->conflictResponse();
        }

        return response()->json([
            'message' => 'Content brief created successfully.',
            'data' => $brief,
        ], 201);
    }

    private function conflictResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'This project already has a content brief.',
        ], 409);
    }
}
