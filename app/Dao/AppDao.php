<?php

namespace App\Dao;

use App\Models\App;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class AppDao
{
    /**
     * @param $id
     * @throws \Exception
     * @return Collection
     */
    public function find(int $id) : App
    {
        return App::findOrFail($id);
    }

    public function incrementRefund(int $id, float $dollar) : int
    {
        return App::query()->where('id', $id)->update([
            'refund_count' => DB::raw('refund_count + 1'),
            'refund_dollars' => DB::raw("refund_dollars + {$dollar}"),
        ]);
    }

    public function incrementTransaction(int $id, float $dollar) : int
    {
        return App::query()->where('id', $id)->update([
            'transaction_count' => DB::raw('transaction_count + 1'),
            'transaction_dollars' => DB::raw("transaction_dollars + {$dollar}"),
        ]);
    }

    public function incrementConsumption(int $id, float $dollar) : int
    {
        return App::query()->where('id', $id)->update([
            'consumption_count' => DB::raw('consumption_count + 1'),
            'consumption_dollars' => DB::raw("consumption_dollars + {$dollar}"),
        ]);
    }
}
