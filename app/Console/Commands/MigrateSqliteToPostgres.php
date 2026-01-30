<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MigrateSqliteToPostgres extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-sqlite-to-postgres';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate data from SQLite to Postgres';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $source = 'sqlite';
        $destination = 'pgsql';

        // Ensure sqlite connection points to the correct file
        config([
            'database.connections.sqlite.url' => null,
            'database.connections.sqlite.database' => database_path('database.sqlite')
        ]);

        // Set pgsql credentials explicitly to bypass .env loading issues
        config([
            'database.connections.pgsql.host' => 'db.urjquzycytreqwdytpok.supabase.co',
            'database.connections.pgsql.port' => '5432',
            'database.connections.pgsql.database' => 'postgres',
            'database.connections.pgsql.username' => 'postgres',
            'database.connections.pgsql.password' => 'chris-marketplac',
        ]);

        $this->info("Starting migration from $source to $destination...");

        $tables = \DB::connection($source)->select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%';");

        // Disable foreign keys on destination
        \DB::connection($destination)->statement('SET CONSTRAINTS ALL DEFERRED');

        foreach ($tables as $table) {
            $tableName = $table->name;

            if ($tableName === 'migrations') {
                $this->line("Skipping $tableName table.");
                continue;
            }

            $this->info("Migrating table: $tableName");

            // Clear destination table
            \DB::connection($destination)->table($tableName)->truncate();

            // Transfer data in chunks
            \DB::connection($source)->table($tableName)->orderByRaw('1')->chunk(100, function ($rows) use ($destination, $tableName) {
                $data = array_map(function ($row) {
                    return (array) $row;
                }, $rows->toArray());

                if (!empty($data)) {
                    \DB::connection($destination)->table($tableName)->insert($data);
                }
            });
        }

        $this->info("Resetting Postgres sequences...");
        $this->resetSequences($destination);

        $this->info("Migration completed successfully!");
    }

    protected function resetSequences($connection)
    {
        $tables = \DB::connection($connection)->select("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'");

        foreach ($tables as $table) {
            $tableName = $table->table_name;
            $columns = \DB::connection($connection)->select("SELECT column_name, column_default FROM information_schema.columns WHERE table_name = ? AND column_default LIKE 'nextval%'", [$tableName]);

            foreach ($columns as $column) {
                \DB::connection($connection)->statement("SELECT setval(pg_get_serial_sequence(?, ?), (SELECT MAX(" . $column->column_name . ") FROM " . $tableName . "))", [$tableName, $column->column_name]);
            }
        }
    }
}
