<?php

namespace Tests\Unit\Dao;

use App\Dao\AppDao;
use App\Models\App;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppDaoTest extends TestCase
{
    use RefreshDatabase;

    protected AppDao $dao;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dao = new AppDao();
    }

    public function test_increment_refund_money_calls_increment_refund(): void
    {
        $app = App::factory()->create([
            'refund_count' => 0,
            'refund_dollars' => 0,
        ]);

        $money = Money::of('10.99', 'USD');
        $result = $this->dao->incrementRefundMoney($app->id, $money);

        $this->assertEquals(1, $result);
        
        $app->refresh();
        $this->assertEquals(1, $app->refund_count);
        $this->assertEquals(10.99, $app->refund_dollars);
    }

    public function test_increment_transaction_money_calls_increment_transaction(): void
    {
        $app = App::factory()->create([
            'transaction_count' => 0,
            'transaction_dollars' => 0,
        ]);

        $money = Money::of('25.50', 'USD');
        $result = $this->dao->incrementTransactionMoney($app->id, $money);

        $this->assertEquals(1, $result);
        
        $app->refresh();
        $this->assertEquals(1, $app->transaction_count);
        $this->assertEquals(25.50, $app->transaction_dollars);
    }

    public function test_increment_consumption_money_calls_increment_consumption(): void
    {
        $app = App::factory()->create([
            'consumption_count' => 0,
            'consumption_dollars' => 0,
        ]);

        $money = Money::of('15.75', 'USD');
        $result = $this->dao->incrementConsumptionMoney($app->id, $money);

        $this->assertEquals(1, $result);
        
        $app->refresh();
        $this->assertEquals(1, $app->consumption_count);
        $this->assertEquals(15.75, $app->consumption_dollars);
    }

    public function test_money_methods_handle_decimal_precision(): void
    {
        $app = App::factory()->create([
            'refund_count' => 0,
            'refund_dollars' => 0,
        ]);

        // Test with exact 2 decimal places
        $money = Money::of('10.99', 'USD');
        $this->dao->incrementRefundMoney($app->id, $money);

        $app->refresh();
        $this->assertEquals(10.99, $app->refund_dollars);
    }
}

