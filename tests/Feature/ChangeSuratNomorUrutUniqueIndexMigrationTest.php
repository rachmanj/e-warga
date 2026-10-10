<?php

namespace Tests\Feature;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ChangeSuratNomorUrutUniqueIndexMigrationTest extends TestCase
{
    use RefreshDatabase;

    private const NEW_INDEX_NAME = 'surat_tenant_id_nomor_urut_tahun_unique';

    private const OLD_INDEX_COLUMNS = ['tenant_id', 'surat_jenis_id', 'nomor_urut', 'tahun'];

    public function test_migration_up_can_run_twice_without_error(): void
    {
        $migration = $this->suratNomorUrutIndexMigration();

        $migration->up();
        $migration->up();

        $this->assertTrue($this->indexExistsByName(self::NEW_INDEX_NAME));
    }

    public function test_migration_up_is_safe_when_old_unique_index_is_missing(): void
    {
        $migration = $this->suratNomorUrutIndexMigration();

        $migration->down();

        foreach ($this->uniqueIndexNamesContainingColumns(['surat_jenis_id', 'nomor_urut']) as $indexName) {
            $this->dropIndexByName($indexName);
        }

        $migration->up();

        $this->assertTrue($this->indexExistsByName(self::NEW_INDEX_NAME));
        $this->assertFalse($this->hasUniqueIndexOnColumns(self::OLD_INDEX_COLUMNS));
    }

    private function suratNomorUrutIndexMigration(): Migration
    {
        return require database_path('migrations/2026_10_10_100635_change_surat_nomor_urut_unique_index.php');
    }

    private function indexExistsByName(string $indexName): bool
    {
        foreach (Schema::getIndexes('surat') as $index) {
            if (($index['name'] ?? '') === $indexName) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $columns
     */
    private function hasUniqueIndexOnColumns(array $columns): bool
    {
        foreach (Schema::getIndexes('surat') as $index) {
            if ($index['primary'] ?? false) {
                continue;
            }

            if (! ($index['unique'] ?? false)) {
                continue;
            }

            if (($index['columns'] ?? []) === $columns) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $requiredColumns
     * @return list<string>
     */
    private function uniqueIndexNamesContainingColumns(array $requiredColumns): array
    {
        $names = [];

        foreach (Schema::getIndexes('surat') as $index) {
            if ($index['primary'] ?? false) {
                continue;
            }

            if (! ($index['unique'] ?? false)) {
                continue;
            }

            $indexColumns = $index['columns'] ?? [];
            $hasAll = true;
            foreach ($requiredColumns as $column) {
                if (! in_array($column, $indexColumns, true)) {
                    $hasAll = false;
                    break;
                }
            }

            if ($hasAll) {
                $names[] = $index['name'];
            }
        }

        return array_values(array_unique($names));
    }

    private function dropIndexByName(string $indexName): void
    {
        Schema::table('surat', function ($table) use ($indexName) {
            $table->dropIndex($indexName);
        });
    }
}
