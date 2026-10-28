<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StudentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $search = trim((string) $request->query('search', ''));

        $students = Student::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('student_number', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('course', 'like', "%{$search}%");
                });
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(10)
            ->withQueryString();

        return StudentResource::collection($students);
    }

    public function store(StoreStudentRequest $request): JsonResponse
    {
        $student = Student::create($request->validated());

        return (new StudentResource($student))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Student $student): StudentResource
    {
        return new StudentResource($student);
    }

    public function update(UpdateStudentRequest $request, Student $student): StudentResource
    {
        $student->update($request->validated());

        return new StudentResource($student);
    }

    public function destroy(Student $student): JsonResponse
    {
        $student->delete();

        return response()->json(['message' => 'Student deleted successfully.']);
    }

    public function stats(): JsonResponse
    {
        return response()->json([
            'data' => [
                'total' => Student::count(),
                'by_course' => Student::query()
                    ->selectRaw('course, count(*) as total')
                    ->groupBy('course')
                    ->orderByDesc('total')
                    ->get(),
                'by_year_level' => Student::query()
                    ->selectRaw('year_level, count(*) as total')
                    ->groupBy('year_level')
                    ->orderBy('year_level')
                    ->get(),
                'recent' => StudentResource::collection(
                    Student::query()->latest()->take(5)->get()
                ),
            ],
        ]);
    }
}
