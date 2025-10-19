<?php

namespace Tests\Unit\Repositories;

use App\Models\App;
use App\Repositories\AppRepository;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected AppRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new AppRepository;
    }

    public function test_increment_refund_money(): void
    {
        $app = App::factory()->create([
            'refund_count' => 0,
            'refund_dollars' => 0,
        ]);

        $money = Money::of('5.99', 'USD');
        $result = $this->repository->incrementRefundMoney($app->id, $money);

        $this->assertEquals(1, $result);

        $app->refresh();
        $this->assertEquals(1, $app->refund_count);
        $this->assertEquals(5.99, $app->refund_dollars);
    }

    public function test_increment_transaction_money(): void
    {
        $app = App::factory()->create([
            'transaction_count' => 0,
            'transaction_dollars' => 0,
        ]);

        $money = Money::of('9.99', 'USD');
        $result = $this->repository->incrementTransactionMoney($app->id, $money);

        $this->assertEquals(1, $result);

        $app->refresh();
        $this->assertEquals(1, $app->transaction_count);
        $this->assertEquals(9.99, $app->transaction_dollars);
    }

    public function test_increment_consumption_money(): void
    {
        $app = App::factory()->create([
            'consumption_count' => 0,
            'consumption_dollars' => 0,
        ]);

        $money = Money::of('3.99', 'USD');
        $result = $this->repository->incrementConsumptionMoney($app->id, $money);

        $this->assertEquals(1, $result);

        $app->refresh();
        $this->assertEquals(1, $app->consumption_count);
        $this->assertEquals(3.99, $app->consumption_dollars);
    }
}
