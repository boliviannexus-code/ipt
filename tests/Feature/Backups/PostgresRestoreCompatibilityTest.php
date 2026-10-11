<?php

declare(strict_types=1);

namespace Tests\Feature\Backups;

use App\Services\Backup\PostgresDatabaseBackupManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PDO;
use RuntimeException;
use Tests\TestCase;

final class PostgresRestoreCompatibilityTest extends TestCase
{
    public function test_restore_replaces_newer_local_relations_and_rolls_back_invalid_sql(): void
    {
        $database = 'backup_compatibility_'.bin2hex(random_bytes(6));
        $admin = DB::connection()->getPdo();
        $admin->exec('CREATE DATABASE "'.$database.'"');
        $db = config('database.connections.pgsql');
        $connection = null;

        try {
            $connection = new PDO("pgsql:host={$db['host']};port={$db['port']};dbname={$database}", $db['username'], $db['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            config(['database.connections.pgsql.database' => $database, 'backups.disk' => 'restore-compatibility']);
            Storage::fake('restore-compatibility');
            $manager = new PostgresDatabaseBackupManager;
            foreach (config('backups.required_tables') as $table) {
                $connection->exec('CREATE TABLE "'.$table.'" (id integer PRIMARY KEY)');
            }
            $connection->exec('INSERT INTO migrations VALUES (1); CREATE TABLE program_levels (id integer PRIMARY KEY); INSERT INTO program_levels VALUES (1); CREATE TABLE enrollment_contracts (id integer PRIMARY KEY);');
            $backup = $manager->create();
            $connection->exec('ALTER TABLE enrollment_contracts ADD COLUMN current_program_level_id integer REFERENCES program_levels(id); CREATE TABLE local_only (id integer);');

            $manager->restore($backup['name']);

            $this->assertNull($connection->query("SELECT to_regclass('public.local_only')")->fetchColumn() ?: null);
            $this->assertSame(0, (int) $connection->query("SELECT count(*) FROM information_schema.columns WHERE table_name = 'enrollment_contracts' AND column_name = 'current_program_level_id'")->fetchColumn());
            $this->assertSame(1, (int) $connection->query('SELECT count(*) FROM program_levels')->fetchColumn());

            $incomplete = $manager->import(UploadedFile::fake()->createWithContent('incomplete.sql.gz', gzencode("-- PostgreSQL database dump\nCREATE TABLE partial_restore (id integer);\n")));
            try {
                $manager->restore($incomplete['name']);
                $this->fail('Incomplete schema should fail before commit.');
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString('tablas requeridas', $exception->getMessage());
            }
            $this->assertSame(1, (int) $connection->query('SELECT count(*) FROM program_levels')->fetchColumn());

            $invalid = $manager->import(UploadedFile::fake()->createWithContent('broken.sql.gz', gzencode("-- PostgreSQL database dump\nCREATE TABLE partial_restore (id integer);\nINVALID SQL;\n")));
            try {
                $manager->restore($invalid['name']);
                $this->fail('Invalid SQL should fail.');
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString('No se pudo restaurar', $exception->getMessage());
            }
            $this->assertSame(1, (int) $connection->query('SELECT count(*) FROM program_levels')->fetchColumn());
            $this->assertNull($connection->query("SELECT to_regclass('public.partial_restore')")->fetchColumn() ?: null);
        } finally {
            $connection = null;
            $admin->exec('DROP DATABASE "'.$database.'" WITH (FORCE)');
            Storage::disk('restore-compatibility')->deleteDirectory('');
        }
    }
}
