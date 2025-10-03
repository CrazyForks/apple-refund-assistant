<?php

namespace App\Dao;

use App\Models\App;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class AppDao
{
    /**
     * @param int $id
     * @return App
     */
    public function find(int $id) : App
    {
        return App::find($id);
    }

    /**
     * 增加退款金额（安全版本）
     */
    public function incrementRefund(int $id, float $dollar): int
    {
        // 确保数值精度并防止精度丢失
        $safeDollar = round($dollar, 2);
        return App::query()->where('id', $id)->update([
            'refund_count' => DB::raw('refund_count + 1'),
            'refund_dollars' => DB::raw("refund_dollars + {$safeDollar}"),
        ]);
    }

    /**
     * 增加退款金额（Money 对象版本）
     */
    public function incrementRefundMoney(int $id, Money $dollarAmount): int
    {
        $dollarFloat = $dollarAmount->getAmount()->toFloat();
        return $this->incrementRefund($id, $dollarFloat);
    }

    /**
     * 增加交易金额（安全版本）
     */
    public function incrementTransaction(int $id, float $dollar): int
    {
        $safeDollar = round($dollar, 2);
        return App::query()->where('id', $id)->update([
            'transaction_count' => DB::raw('transaction_count + 1'),
            'transaction_dollars' => DB::raw("transaction_dollars + {$safeDollar}"),
        ]);
    }

    /**
     * 增加交易金额（Money 对象版本）
     */
    public function incrementTransactionMoney(int $id, Money $dollarAmount): int
    {
        $dollarFloat = $dollarAmount->getAmount()->toFloat();
        return $this->incrementTransaction($id, $dollarFloat);
    }

    /**
     * 增加消费金额（安全版本）
     */
    public function incrementConsumption(int $id, float $dollar): int
    {
        $safeDollar = round($dollar, 2);
        return App::query()->where('id', $id)->update([
            'consumption_count' => DB::raw('consumption_count + 1'),
            'consumption_dollars' => DB::raw("consumption_dollars + {$safeDollar}"),
        ]);
    }

    /**
     * 增加消费金额（Money 对象版本）
     */
    public function incrementConsumptionMoney(int $id, Money $dollarAmount): int
    {
        $dollarFloat = $dollarAmount->getAmount()->toFloat();
        return $this->incrementConsumption($id, $dollarFloat);
    }
}
