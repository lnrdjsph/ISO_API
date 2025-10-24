<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ✅ Static seeders
        $this->call(AdminUserSeeder::class);

        // ✅ Dynamically include all timestamped export seeders
        $seederPath = database_path('seeders');
        $files = File::files($seederPath);

        foreach ($files as $file) {
            $filename = pathinfo($file->getFilename(), PATHINFO_FILENAME);

            // Match pattern like DatabaseSeederExport_20251024_133258
            if (preg_match('/^DatabaseSeederExport_\d{8}_\d{6}$/', $filename)) {
                $class = "Database\\Seeders\\{$filename}";
                if (class_exists($class)) {
                    $this->call($class);
                }
            }
        }

        // ✅ Optionally call other static seeders
        // $this->call(ProductsTableSeeder::class);
    }
}
