<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestBase;

class ExampleTest extends TestBase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        User::create([
            'name' => 'DOOMSHOP Admin',
            'email' => 'admin@doomshoprecords.com',
            'password' => 'admin123',
            'role' => 1,
        ]);
    }

    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/api/x');
        $response->assertStatus(200);
        $response->assertSee('API');
    }

    public function test_user_login_logout(): void
    {
        $response = $this->login('admin@doomshoprecords.com', 'admin123');
        $response->assertStatus(200);

        $token = $this->myGetToken($response);

        $response = $this->myGet('/api/users', $token);
        $response->assertStatus(200);

        $response = $this->logout($token);
        $response->assertStatus(200);
    }
}
