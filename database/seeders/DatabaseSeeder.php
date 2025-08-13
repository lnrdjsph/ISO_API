<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Call other seeders here, e.g.:
        $this->call(AdminUserSeeder::class);
        // $this->call(ProductsTableSeeder::class);

        // For now, this is empty by default.
    }
}
