<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ExportDatabaseSeeder extends Command
{
    protected $signature = 'db:export-seeder {name=DatabaseSeederExport}';
    protected $description = 'Export all current DB data into a seeder file with timestamped name';

    public function handle()
    {
        $tables = DB::select('SHOW TABLES');
        $dbName = DB::getDatabaseName();
        $key = "Tables_in_{$dbName}";

        // Add timestamp suffix
        $timestamp = now()->format('Ymd_His');
        $baseName = $this->argument('name');
        $seederName = "{$baseName}_{$timestamp}";

        $path = database_path("seeders/{$seederName}.php");

        $output = <<<PHP
<?php

namespace Database\\Seeders;

use Illuminate\\Database\\Seeder;
use Illuminate\\Support\\Facades\\DB;

class {$seederName} extends Seeder
{
    public function run()
    {
        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

PHP;

        foreach ($tables as $table) {
            $tableName = $table->$key;

            if ($tableName === 'migrations') continue;

            $data = DB::table($tableName)->get();
            if ($data->isEmpty()) continue;

            $records = var_export(json_decode(json_encode($data), true), true);
            $output .= "        // Table: {$tableName}\n";
            $output .= "        DB::table('{$tableName}')->truncate();\n";
            $output .= "        DB::table('{$tableName}')->insert({$records});\n\n";
        }

        $output .= <<<PHP
        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}

PHP;

        File::put($path, $output);
        $this->info("✅ Seeder created: database/seeders/{$seederName}.php");
    }
}
