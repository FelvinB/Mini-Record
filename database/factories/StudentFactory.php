<?php

namespace Database\Factories;

use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'student_number' => strtoupper($this->faker->unique()->bothify('####-????')),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'course' => $this->faker->randomElement([
                'BS Information Technology',
                'BS Computer Science',
                'BS Civil Engineering',
                'BS Accountancy',
                'BS Nursing',
                'BS Education',
            ]),
            'year_level' => $this->faker->numberBetween(1, 4),
        ];
    }
}
