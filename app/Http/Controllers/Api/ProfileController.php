<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\StudentResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $student = $request->user()?->student;

        if ($student === null) {
            return response()->json(['message' => 'No linked student record found.'], 404);
        }

        return response()->json([
            'data' => new StudentResource($student),
        ]);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $student = $request->user()?->student;

        if ($student === null) {
            return response()->json(['message' => 'No linked student record found.'], 404);
        }

        $student->update($request->validated());

        return response()->json([
            'data' => new StudentResource($student),
        ]);
    }
}
