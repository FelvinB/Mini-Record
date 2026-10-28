<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use App\Models\User;
use App\Rules\StudentNumberFormat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'student_number' => ['required', new StudentNumberFormat],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $student = Student::query()
            ->where('student_number', $validated['student_number'])
            ->first();

        if ($student === null || strcasecmp((string) $student->email, (string) $validated['email']) !== 0) {
            throw ValidationException::withMessages([
                'student_number' => ['The student number and email do not match a record in the registry.'],
            ]);
        }

        $user = User::create([
            'name' => $student->full_name,
            'email' => $student->email,
            'password' => Hash::make($validated['password']),
            'student_id' => $student->id,
        ]);

        $token = $user->createToken('student-auth')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $this->userData($user),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ])) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        $user = User::where('email', $credentials['email'])->firstOrFail();
        $token = $user->createToken('student-auth')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $this->userData($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->userData($request->user()),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->tokens()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    /**
     * @return array{id: int, name: string, email: string, is_admin: bool, student: StudentResource|null}
     */
    private function userData(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_admin' => (bool) $user->is_admin,
            'student' => $user->student ? new StudentResource($user->student) : null,
        ];
    }
}
