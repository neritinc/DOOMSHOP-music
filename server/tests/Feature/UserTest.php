<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        User::create([
            'name' => 'Admin',
            'email' => 'admin@doomshoprecords.com',
            'password' => 'admin123',
            'role' => 1,
        ]);
    }

    private function login(string $email = 'admin@doomshoprecords.com', string $password = 'admin123')
    {
        return $this
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
            ->postJson('/api/users/login', [
                'email' => $email,
                'password' => $password,
            ]);
    }

    private function logout(string $token)
    {
        return $this
            ->withHeaders([
                'Accept' => 'application/json',
                'Authorization' => "Bearer $token",
            ])
            ->postJson('/api/users/logout');
    }

    public function test_create_delete_user(): void
    {
        $data = [
            'name' => 'New Customer',
            'email' => 'newcustomer@doomshoprecords.com',
            'password' => '12345678',
        ];

        $response = $this
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
            ->postJson('/api/users', $data);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => $data['email']]);

        $id = $response->json('data.id');
        $response = $this->deleteJson("/api/users/$id");
        $response->assertStatus(401);
    }

    public function test_login(): void
    {
        $response = $this->login();
        $response->assertStatus(200);

        $token = $response->json('data.token');
        $this->assertNotNull($token, 'Bejelentkezes sikertelen');

        $response = $this
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => "Bearer $token",
            ])
            ->get('/api/users');

        $response->assertStatus(200);

        $response = $this->logout($token);
        $response->assertStatus(200);
    }
}
