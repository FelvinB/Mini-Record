<?php

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('registers an account when the student number and email match a record', function () {
    $student = Student::factory()->create([
        'student_number' => '20260001',
        'email' => 'juan.delacruz@example.com',
    ]);

    $this->postJson('/api/register', [
        'student_number' => '20260001',
        'email' => 'juan.delacruz@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertCreated()
        ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'student']])
        ->assertJsonPath('user.student.id', $student->id)
        ->assertJsonPath('user.is_admin', false)
        ->assertJsonPath('user.name', $student->full_name);

    $this->assertDatabaseHas('users', [
        'email' => $student->email,
        'student_id' => $student->id,
    ]);
});

it('rejects registration when the student number is unknown', function () {
    $this->postJson('/api/register', [
        'student_number' => '20269999',
        'email' => 'nobody@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['student_number']);
});

it('rejects registration when the email does not match the student record', function () {
    Student::factory()->create([
        'student_number' => '20260001',
        'email' => 'juan.delacruz@example.com',
    ]);

    $this->postJson('/api/register', [
        'student_number' => '20260001',
        'email' => 'impostor@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['student_number']);
});

it('logs in a student account and exposes the linked record', function () {
    $student = Student::factory()->create(['email' => 'juan.delacruz@example.com']);
    $user = User::factory()->for($student)->create([
        'email' => 'juan.delacruz@example.com',
        'password' => 'password123',
    ]);

    $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password123',
    ])->assertOk()
        ->assertJsonStructure(['token', 'user' => ['id', 'email', 'student']])
        ->assertJsonPath('user.student.id', $student->id)
        ->assertJsonPath('user.is_admin', false);
});

it('logs in an admin account', function () {
    $user = User::factory()->create([
        'email' => 'admin@example.com',
        'password' => 'admin12345',
        'is_admin' => true,
    ]);

    $this->postJson('/api/login', [
        'email' => 'admin@example.com',
        'password' => 'admin12345',
    ])->assertOk()
        ->assertJsonPath('user.is_admin', true);
});

it('rejects login with wrong credentials', function () {
    $this->postJson('/api/login', [
        'email' => 'nobody@example.com',
        'password' => 'wrong-password',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('returns the current user with their linked student', function () {
    $student = Student::factory()->create(['email' => 'juan.delacruz@example.com']);
    $user = User::factory()->for($student)->create(['email' => 'juan.delacruz@example.com']);

    Sanctum::actingAs($user);

    $this->getJson('/api/me')
        ->assertOk()
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonPath('user.student.id', $student->id);
});

it('requires authentication for the me endpoint', function () {
    $this->getJson('/api/me')->assertStatus(401);
});

it('lets a student view their own record', function () {
    $student = Student::factory()->create(['email' => 'juan.delacruz@example.com']);
    $user = User::factory()->for($student)->create(['email' => 'juan.delacruz@example.com']);

    Sanctum::actingAs($user);

    $this->getJson('/api/me/student')
        ->assertOk()
        ->assertJsonPath('data.id', $student->id)
        ->assertJsonPath('data.student_number', $student->student_number);
});

it('lets a student update their own record', function () {
    $student = Student::factory()->create(['email' => 'juan.delacruz@example.com']);
    $user = User::factory()->for($student)->create(['email' => 'juan.delacruz@example.com']);

    Sanctum::actingAs($user);

    $this->putJson('/api/me/student', [
        'first_name' => 'Jose',
        'last_name' => 'Reyes',
        'course' => 'BS Computer Science',
        'year_level' => 3,
    ])->assertOk()
        ->assertJsonPath('data.full_name', 'Jose Reyes')
        ->assertJsonPath('data.course', 'BS Computer Science')
        ->assertJsonPath('data.year_level', 3);
});

it('ignores attempts to change the student number or email through the profile', function () {
    $student = Student::factory()->create([
        'student_number' => '20260001',
        'email' => 'juan.delacruz@example.com',
    ]);
    $user = User::factory()->for($student)->create(['email' => 'juan.delacruz@example.com']);

    Sanctum::actingAs($user);

    $this->putJson('/api/me/student', [
        'first_name' => 'Jose',
        'last_name' => 'Reyes',
        'course' => 'BS Computer Science',
        'year_level' => 1,
        'student_number' => '20269999',
        'email' => 'hacked@example.com',
    ])->assertOk();

    expect($student->fresh())
        ->student_number->toBe('20260001')
        ->email->toBe('juan.delacruz@example.com');
});

it('requires authentication to read or update a profile', function () {
    $this->getJson('/api/me/student')->assertStatus(401);
    $this->putJson('/api/me/student', [
        'first_name' => 'Jose',
        'last_name' => 'Reyes',
        'course' => 'BS Computer Science',
        'year_level' => 1,
    ])->assertStatus(401);
});

it('logs out and revokes the current token', function () {
    $student = Student::factory()->create(['email' => 'juan.delacruz@example.com']);
    $user = User::factory()->for($student)->create(['email' => 'juan.delacruz@example.com']);

    $response = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertOk();

    $token = $response->json('token');

    $this->withToken($token)->postJson('/api/logout')->assertOk();
    $this->assertDatabaseCount('personal_access_tokens', 0);
});
