<?php

namespace Database\Seeders;

use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $student = Student::query()
            ->where('email', 'felvinbags@gmail.com')
            ->first();

        User::updateOrCreate(
            ['email' => 'felvinbags@gmail.com'],
            [
                'name' => $student?->full_name ?? 'Administrator',
                'password' => Hash::make('admin12345'),
                'is_admin' => true,
                'student_id' => $student?->id,
            ],
        );
    }
}
