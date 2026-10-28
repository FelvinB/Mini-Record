<?php

namespace App\Http\Requests;

use App\Rules\StudentNumberFormat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $studentId = $this->route('student')?->id;

        return [
            'student_number' => [
                'required',
                new StudentNumberFormat,
                Rule::unique('students', 'student_number')->ignore($studentId),
            ],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('students', 'email')->ignore($studentId),
            ],
            'course' => ['required', 'string', 'max:100'],
            'year_level' => ['required', 'integer', 'between:1,4'],
        ];
    }
}
