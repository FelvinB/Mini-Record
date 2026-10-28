<?php

use App\Models\Student;
use App\Models\User;
use Illuminate\Auth\AuthManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $admin = User::factory()->create(['is_admin' => true]);
    Sanctum::actingAs($admin);
});

function studentPayload(array $overrides = []): array
{
    return array_merge([
        'student_number' => '20260001',
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
        'email' => 'juan.delacruz@example.com',
        'course' => 'BS Information Technology',
        'year_level' => 2,
    ], $overrides);
}

it('lists students with pagination metadata', function () {
    Student::factory()->count(3)->create();

    $this->getJson('/api/students')
        ->assertOk()
        ->assertJsonStructure(['data', 'links', 'meta'])
        ->assertJsonCount(3, 'data');
});

it('creates a student', function () {
    $this->postJson('/api/students', studentPayload())
        ->assertCreated()
        ->assertJsonPath('data.full_name', 'Juan Dela Cruz');

    $this->assertDatabaseHas('students', ['email' => 'juan.delacruz@example.com']);
});

it('rejects invalid data', function () {
    $this->postJson('/api/students', [
        'student_number' => '',
        'email' => 'not-an-email',
        'year_level' => 9,
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['student_number', 'first_name', 'last_name', 'email', 'course', 'year_level']);
});

it('rejects a student number that is not an 8-digit year-to-id format', function () {
    $this->postJson('/api/students', studentPayload(['student_number' => '2024']))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['student_number']);

    $this->postJson('/api/students', studentPayload(['student_number' => 'ABCDEFGH']))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['student_number']);

    $this->postJson('/api/students', studentPayload(['student_number' => '202407655']))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['student_number']);
});

it('prevents duplicate student numbers and emails', function () {
    Student::factory()->create(['student_number' => '20260001', 'email' => 'juan.delacruz@example.com']);

    $this->postJson('/api/students', studentPayload())
        ->assertStatus(422)
        ->assertJsonValidationErrors(['student_number', 'email']);
});

it('updates a student while keeping its own email', function () {
    $student = Student::factory()->create(studentPayload());

    $this->putJson("/api/students/{$student->id}", studentPayload(['first_name' => 'Jose']))
        ->assertOk()
        ->assertJsonPath('data.first_name', 'Jose');
});

it('deletes a student', function () {
    $student = Student::factory()->create();

    $this->deleteJson("/api/students/{$student->id}")->assertOk();
    $this->assertDatabaseMissing('students', ['id' => $student->id]);
});

it('searches across fields', function () {
    Student::factory()->create(['course' => 'BS Nursing', 'last_name' => 'Reyes']);
    Student::factory()->create(['course' => 'BS Accountancy', 'last_name' => 'Santos']);

    $this->getJson('/api/students?search=Nursing')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.last_name', 'Reyes');
});

it('returns dashboard statistics', function () {
    Student::factory()->count(4)->create();

    $this->getJson('/api/students/stats')
        ->assertOk()
        ->assertJsonPath('data.total', 4)
        ->assertJsonStructure(['data' => ['by_course', 'by_year_level', 'recent']]);
});

it('forbids students from managing the registry', function () {
    $student = Student::factory()->create();
    $user = User::factory()->create(['is_admin' => false, 'student_id' => $student->id]);

    Sanctum::actingAs($user);

    $this->getJson('/api/students')->assertStatus(403);
    $this->getJson('/api/students/stats')->assertStatus(403);
    $this->postJson('/api/students', studentPayload())->assertStatus(403);
    $this->deleteJson("/api/students/{$student->id}")->assertStatus(403);
});

it('requires authentication to manage the registry', function () {
    $this->app->make(AuthManager::class)->forgetGuards();

    $this->getJson('/api/students')->assertStatus(401);
});
