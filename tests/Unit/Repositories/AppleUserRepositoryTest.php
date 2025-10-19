<?php

namespace Tests\Unit\Repositories;

use App\Models\App;
use App\Models\AppleUser;
use App\Repositories\AppleUserRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppleUserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected AppleUserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new AppleUserRepository();
    }

    public function test_find_returns_null_when_token_is_empty(): void
    {
        $app = App::factory()->create();
        
        $result = $this->repository->find('', $app->id);
        
        $this->assertNull($result);
    }

    public function test_find_returns_null_when_token_is_null(): void
    {
        $app = App::factory()->create();
        
        $result = $this->repository->find(null, $app->id);
        
        $this->assertNull($result);
    }

    public function test_find_returns_user_when_found(): void
    {
        $app = App::factory()->create();
        $user = AppleUser::factory()->create([
            'app_account_token' => 'test-token',
            'app_id' => $app->id,
        ]);
        
        $result = $this->repository->find('test-token', $app->id);
        
        $this->assertNotNull($result);
        $this->assertEquals($user->id, $result->id);
    }

    public function test_increment_refunded(): void
    {
        $app = App::factory()->create();
        $user = AppleUser::factory()->create([
            'app_id' => $app->id,
            'refunded_dollars' => 0,
        ]);

        $this->repository->incrementRefunded($user->id, 5.99);

        $user->refresh();
        $this->assertEquals(5.99, $user->refunded_dollars);
    }

    public function test_increment_refunded_by_token(): void
    {
        $app = App::factory()->create();
        $user = AppleUser::factory()->create([
            'app_account_token' => 'test-token',
            'app_id' => $app->id,
            'refunded_dollars' => 0,
        ]);

        $result = $this->repository->incrementRefundedByToken('test-token', $app->id, 9.99);

        $this->assertEquals(1, $result);
        
        $user->refresh();
        $this->assertEquals(9.99, $user->refunded_dollars);
    }
}

