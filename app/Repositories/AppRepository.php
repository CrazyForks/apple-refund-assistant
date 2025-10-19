<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\App;
use Brick\Money\Money;
use Illuminate\Support\Facades\DB;

class AppRepository
{
    public function find(int $id): App
    {
        return App::find($id);
    }

    /**
     * Increment refund amount (safe version)
     */
    public function incrementRefund(int $id, float $dollar): int
    {
        // Ensure numerical precision and prevent precision loss
        $safeDollar = round($dollar, 2);
        return App::query()->where('id', $id)->update([
            'refund_count' => DB::raw('refund_count + 1'),
            'refund_dollars' => DB::raw("refund_dollars + {$safeDollar}"),
        ]);
    }

    /**
     * Increment refund amount (Money object version)
     */
    public function incrementRefundMoney(int $id, Money $dollarAmount): int
    {
        $dollarFloat = $dollarAmount->getAmount()->toFloat();
        return $this->incrementRefund($id, $dollarFloat);
    }

    /**
     * Increment transaction amount (safe version)
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
     * Increment transaction amount (Money object version)
     */
    public function incrementTransactionMoney(int $id, Money $dollarAmount): int
    {
        $dollarFloat = $dollarAmount->getAmount()->toFloat();
        return $this->incrementTransaction($id, $dollarFloat);
    }

    /**
     * Increment consumption amount (safe version)
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
     * Increment consumption amount (Money object version)
     */
    public function incrementConsumptionMoney(int $id, Money $dollarAmount): int
    {
        $dollarFloat = $dollarAmount->getAmount()->toFloat();
        return $this->incrementConsumption($id, $dollarFloat);
    }
}

