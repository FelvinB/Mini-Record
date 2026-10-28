<?php

namespace App\Http\Requests;

use App\Rules\StudentNumberFormat;
use Illuminate\Foundation\Http\FormRequest;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'student_number' => ['required', new StudentNumberFormat, 'unique:students,student_number'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:students,email'],
            'course' => ['required', 'string', 'max:100'],
            'year_level' => ['required', 'integer', 'between:1,4'],
        ];
    }
}
