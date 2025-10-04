<?php

namespace Tests\Unit\Dao;

use App\Dao\AppleUserDao;
use App\Models\App;
use App\Models\AppleUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppleUserDaoTest extends TestCase
{
    use RefreshDatabase;

    protected AppleUserDao $dao;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dao = new AppleUserDao();
    }

    public function test_find_returns_null_when_token_is_empty(): void
    {
        $app = App::factory()->create();
        
        $result = $this->dao->find('', $app->id);
        
        $this->assertNull($result);
    }

    public function test_find_returns_null_when_token_is_null(): void
    {
        $app = App::factory()->create();
        
        $result = $this->dao->find(null, $app->id);
        
        $this->assertNull($result);
    }

    public function test_find_returns_user_when_found(): void
    {
        $app = App::factory()->create();
        $user = AppleUser::factory()->create([
            'app_id' => $app->id,
            'app_account_token' => 'test-token-123',
        ]);
        
        $result = $this->dao->find('test-token-123', $app->id);
        
        $this->assertNotNull($result);
        $this->assertEquals($user->id, $result->id);
    }

    public function test_find_returns_null_when_user_not_found(): void
    {
        $app = App::factory()->create();
        
        $result = $this->dao->find('non-existent-token', $app->id);
        
        $this->assertNull($result);
    }

    public function test_increment_refunded_updates_user(): void
    {
        $app = App::factory()->create();
        $user = AppleUser::factory()->create([
            'app_id' => $app->id,
            'refunded_dollars' => 0,
        ]);

        $this->dao->incrementRefunded($user->id, 10.50);

        $user->refresh();
        $this->assertEquals(10.50, $user->refunded_dollars);
    }

    public function test_increment_refunded_accumulates_multiple_calls(): void
    {
        $app = App::factory()->create();
        $user = AppleUser::factory()->create([
            'app_id' => $app->id,
            'refunded_dollars' => 5.00,
        ]);

        $this->dao->incrementRefunded($user->id, 3.50);
        $this->dao->incrementRefunded($user->id, 2.00);

        $user->refresh();
        $this->assertEquals(10.50, $user->refunded_dollars);
    }
}

