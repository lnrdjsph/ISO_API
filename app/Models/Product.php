<?php
namespace App\Models;

use Illuminate\Support\Facades\DB;

class Product
{
    protected static $connection = 'oracle_local';
    protected static $table = 'PRODUCTS';

    public static function all()
    {
        return DB::connection(static::$connection)
            ->table(static::$table)
            ->select('SKU', 'NAME')
            ->get();
    }

    public static function search($query)
    {
        return DB::connection(static::$connection)
            ->table(static::$table)
            ->select('SKU', 'NAME')
            ->where(function ($q) use ($query) {
                $q->where('NAME', 'like', "%{$query}%")
                  ->orWhere('SKU', 'like', "%{$query}%");
            })
            ->get();
    }
}
