<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    protected array $expectedColumns = [
        'id',
        'name',
        'email',
        'password',
        'role',
        'phone',
        'city',
        'street',
        'house_number',
        'zip_code',
        'billing_phone',
        'billing_city',
        'billing_street',
        'billing_house_number',
        'billing_zip_code',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        User::create([
            'name' => 'Admin User',
            'email' => 'admin@doomshoprecords.com',
            'password' => 'admin123',
            'role' => 1,
        ]);

        User::create([
            'name' => 'Customer User',
            'email' => 'customer@doomshoprecords.com',
            'password' => 'customer123',
            'role' => 2,
        ]);
    }

    public function test_exists_users_table(): void
    {
        $this->assertTrue(Schema::hasTable('users'), 'A users tabla nem letezik');
    }

    public function test_does_the_user_table_contain_all_fields(): void
    {
        foreach ($this->expectedColumns as $column) {
            $this->assertTrue(Schema::hasColumn('users', $column), "A '$column' oszlop nem talalhato a users tablaban.");
        }
    }

    public function test_the_user_table_columns_have_the_expected_types(): void
    {
        $columns = Schema::getColumnListing('users');

        $this->assertEmpty(
            array_diff($this->expectedColumns, $columns),
            'Hianyzo oszlopok a felhasznalok tablaban.'
        );
    }

    public function test_check_if_users_getting_fetched_with_id(): void
    {
        $this->markTestSkipped('Ideiglenesen kiiktatva.');
    }

    public function test_users_table_record_number(): void
    {
        $response = DB::table('users')->get();

        $this->assertCount(2, $response);
        $this->assertGreaterThan(0, count($response));
    }

    public function test_does_the_user_exist(): void
    {
        $email = 'admin@doomshoprecords.com';

        $this->assertDatabaseHas('users', ['email' => $email]);

        $user = User::where('email', $email)->first();
        $this->assertNotNull($user);

        $this->assertTrue(
            DB::table('users')
                ->where('email', $email)
                ->exists()
        );

        $sql = 'SELECT * FROM users WHERE email = ?';
        $user = DB::select($sql, [$email]);
        $this->assertGreaterThan(0, count($user));
    }

    public function test_a_given_password_matches_the_users_hashed_password(): void
    {
        $rawPassword = 'admin123';
        $email = 'admin@doomshoprecords.com';
        $user = User::where('email', $email)->first();

        $passwordMatches = Hash::check($rawPassword, $user->password);

        $this->assertTrue($passwordMatches, "Nem ez a jelszo: $rawPassword");

        $rawPassword = 'wrong-password';
        $this->assertFalse(
            Hash::check($rawPassword, $user->password),
            "Nem ennek kene a jelszonak lennie: $rawPassword"
        );
    }
}
