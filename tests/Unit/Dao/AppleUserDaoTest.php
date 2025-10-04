<?php

namespace Tests\Unit\Dao;

use App\Dao\AppleUserDao;
use App\Models\AppleUser;
use App\Models\App;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppleUserDaoTest extends TestCase
{
    use RefreshDatabase;

    protected AppleUserDao $dao;
    protected App $app;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dao = new AppleUserDao();
        
        // Create a test app
        $this->app = App::create([
            'name' => 'Test App',
            'bundle_id' => 'com.test.app',
            'status' => 1,
        ]);
    }

    public function test_find_returns_null_when_token_is_empty(): void
    {
        $result = $this->dao->find('', $this->app->id);
        $this->assertNull($result);

        $result = $this->dao->find(null, $this->app->id);
        $this->assertNull($result);
    }

    public function test_find_returns_null_when_user_does_not_exist(): void
    {
        $result = $this->dao->find('non-existent-token', $this->app->id);
        $this->assertNull($result);
    }

    public function test_find_returns_user_when_exists(): void
    {
        $user = AppleUser::create([
            'app_account_token' => 'test-token',
            'app_id' => $this->app->id,
            'purchased_dollars' => 10.0,
            'refunded_dollars' => 0.0,
            'play_seconds' => 3600,
            'register_at' => Carbon::now(),
        ]);

        $result = $this->dao->find('test-token', $this->app->id);
        
        $this->assertNotNull($result);
        $this->assertEquals($user->id, $result->id);
        $this->assertEquals('test-token', $result->app_account_token);
    }

    public function test_first_or_create_creates_new_user_with_timestamp(): void
    {
        $registerTimestamp = 1700000000;
        
        $user = $this->dao->firstOrCreate('new-token', $this->app->id, $registerTimestamp);
        
        $this->assertNotNull($user);
        $this->assertTrue($user->exists);
        $this->assertEquals('new-token', $user->app_account_token);
        $this->assertEquals($this->app->id, $user->app_id);
        $this->assertEquals(0, $user->purchased_dollars);
        $this->assertEquals(0, $user->refunded_dollars);
        $this->assertEquals(0, $user->play_seconds);
        $this->assertInstanceOf(Carbon::class, $user->register_at);
        /** @var Carbon $registerAt */
        $registerAt = $user->register_at;
        $this->assertEquals(
            Carbon::createFromTimestamp($registerTimestamp)->format('Y-m-d H:i:s'),
            $registerAt->format('Y-m-d H:i:s')
        );
    }

    public function test_first_or_create_creates_new_user_with_current_time_when_no_timestamp(): void
    {
        Carbon::setTestNow('2024-01-01 12:00:00');
        
        $user = $this->dao->firstOrCreate('new-token', $this->app->id, null);
        
        $this->assertNotNull($user);
        $this->assertEquals('new-token', $user->app_account_token);
        $this->assertInstanceOf(Carbon::class, $user->register_at);
        /** @var Carbon $registerAt */
        $registerAt = $user->register_at;
        $this->assertEquals(
            Carbon::now()->format('Y-m-d H:i:s'),
            $registerAt->format('Y-m-d H:i:s')
        );
        
        Carbon::setTestNow();
    }

    public function test_first_or_create_returns_existing_user(): void
    {
        $existingUser = AppleUser::create([
            'app_account_token' => 'existing-token',
            'app_id' => $this->app->id,
            'purchased_dollars' => 50.0,
            'refunded_dollars' => 5.0,
            'play_seconds' => 7200,
            'register_at' => Carbon::parse('2023-01-01 10:00:00'),
        ]);

        $user = $this->dao->firstOrCreate('existing-token', $this->app->id, 1700000000);
        
        $this->assertEquals($existingUser->id, $user->id);
        $this->assertEquals(50.0, $user->purchased_dollars);
        $this->assertEquals(5.0, $user->refunded_dollars);
        // Register time should not change
        $this->assertInstanceOf(Carbon::class, $user->register_at);
        /** @var Carbon $registerAt */
        $registerAt = $user->register_at;
        $this->assertEquals('2023-01-01 10:00:00', $registerAt->format('Y-m-d H:i:s'));
    }

    public function test_first_or_create_handles_race_condition(): void
    {
        // Create user in "parallel" to simulate race condition
        AppleUser::create([
            'app_account_token' => 'race-token',
            'app_id' => $this->app->id,
            'purchased_dollars' => 0,
            'refunded_dollars' => 0,
            'play_seconds' => 0,
            'register_at' => Carbon::now(),
        ]);

        // Should return existing user without error
        $user = $this->dao->firstOrCreate('race-token', $this->app->id, 1700000000);
        
        $this->assertNotNull($user);
        $this->assertEquals('race-token', $user->app_account_token);
        
        // Should only have one user
        $this->assertEquals(1, AppleUser::where('app_account_token', 'race-token')->count());
    }

    public function test_increment_purchased_increases_amount(): void
    {
        $user = AppleUser::create([
            'app_account_token' => 'test-token',
            'app_id' => $this->app->id,
            'purchased_dollars' => 10.0,
            'refunded_dollars' => 0.0,
            'play_seconds' => 0,
            'register_at' => Carbon::now(),
        ]);

        $this->dao->incrementPurchased($user->id, 5.99);
        
        $user->refresh();
        $this->assertEquals(15.99, $user->purchased_dollars);
    }

    public function test_increment_purchased_multiple_times(): void
    {
        $user = AppleUser::create([
            'app_account_token' => 'test-token',
            'app_id' => $this->app->id,
            'purchased_dollars' => 0.0,
            'refunded_dollars' => 0.0,
            'play_seconds' => 0,
            'register_at' => Carbon::now(),
        ]);

        $this->dao->incrementPurchased($user->id, 1.99);
        $this->dao->incrementPurchased($user->id, 2.99);
        $this->dao->incrementPurchased($user->id, 3.99);
        
        $user->refresh();
        $this->assertEquals(8.97, $user->purchased_dollars);
    }

    public function test_increment_refunded_increases_amount(): void
    {
        $user = AppleUser::create([
            'app_account_token' => 'test-token',
            'app_id' => $this->app->id,
            'purchased_dollars' => 100.0,
            'refunded_dollars' => 0.0,
            'play_seconds' => 0,
            'register_at' => Carbon::now(),
        ]);

        $this->dao->incrementRefunded($user->id, 9.99);
        
        $user->refresh();
        $this->assertEquals(9.99, $user->refunded_dollars);
    }

    public function test_increment_refunded_by_token_updates_user(): void
    {
        $user = AppleUser::create([
            'app_account_token' => 'test-token',
            'app_id' => $this->app->id,
            'purchased_dollars' => 100.0,
            'refunded_dollars' => 0.0,
            'play_seconds' => 0,
            'register_at' => Carbon::now(),
        ]);

        $affected = $this->dao->incrementRefundedByToken('test-token', $this->app->id, 19.99);
        
        $this->assertEquals(1, $affected);
        
        $user->refresh();
        $this->assertEquals(19.99, $user->refunded_dollars);
    }

    public function test_increment_refunded_by_token_returns_zero_when_user_not_exists(): void
    {
        $affected = $this->dao->incrementRefundedByToken('non-existent', $this->app->id, 10.0);
        
        $this->assertEquals(0, $affected);
    }

    public function test_increment_refunded_by_token_only_updates_correct_user(): void
    {
        $user1 = AppleUser::create([
            'app_account_token' => 'token-1',
            'app_id' => $this->app->id,
            'purchased_dollars' => 50.0,
            'refunded_dollars' => 0.0,
            'play_seconds' => 0,
            'register_at' => Carbon::now(),
        ]);

        $user2 = AppleUser::create([
            'app_account_token' => 'token-2',
            'app_id' => $this->app->id,
            'purchased_dollars' => 30.0,
            'refunded_dollars' => 0.0,
            'play_seconds' => 0,
            'register_at' => Carbon::now(),
        ]);

        $this->dao->incrementRefundedByToken('token-1', $this->app->id, 5.0);
        
        $user1->refresh();
        $user2->refresh();
        
        $this->assertEquals(5.0, $user1->refunded_dollars);
        $this->assertEquals(0.0, $user2->refunded_dollars);
    }

    public function test_unique_constraint_on_token_and_app(): void
    {
        AppleUser::create([
            'app_account_token' => 'unique-token',
            'app_id' => $this->app->id,
            'purchased_dollars' => 0,
            'refunded_dollars' => 0,
            'play_seconds' => 0,
            'register_at' => Carbon::now(),
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        
        AppleUser::create([
            'app_account_token' => 'unique-token',
            'app_id' => $this->app->id,
            'purchased_dollars' => 0,
            'refunded_dollars' => 0,
            'play_seconds' => 0,
            'register_at' => Carbon::now(),
        ]);
    }
}

