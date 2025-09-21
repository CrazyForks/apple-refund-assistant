<?php

namespace App\Dao;

use App\Models\App;
use Illuminate\Database\Eloquent\Collection;

class AppDao
{
    /**
     * @param $id
     * @throws \Exception
     * @return Collection
     */
    public function find($id) : App
    {
        return App::findOrFail($id);
    }
}
