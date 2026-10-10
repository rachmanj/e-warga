<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'surat';

    private const NEW_INDEX_NAME = 'surat_tenant_id_nomor_urut_tahun_unique';

    private const OLD_INDEX_COLUMNS = ['tenant_id', 'surat_jenis_id', 'nomor_urut', 'tahun'];

    public function up(): void
    {
        foreach ($this->findUniqueIndexNamesContainingColumns(self::TABLE, ['surat_jenis_id', 'nomor_urut']) as $indexName) {
            $this->dropIndexIfExists(self::TABLE, $indexName);
        }

        if (! $this->indexExistsByName(self::TABLE, self::NEW_INDEX_NAME)) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->unique(['tenant_id', 'nomor_urut', 'tahun'], self::NEW_INDEX_NAME);
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExistsByName(self::TABLE, self::NEW_INDEX_NAME)) {
            $this->dropIndexIfExists(self::TABLE, self::NEW_INDEX_NAME);
        }

        if (! $this->hasUniqueIndexOnColumns(self::TABLE, self::OLD_INDEX_COLUMNS)) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->unique(self::OLD_INDEX_COLUMNS);
            });
        }
    }

    /**
     * @return list<string>
     */
    private function findUniqueIndexNamesContainingColumns(string $table, array $requiredColumns): array
    {
        if ($this->driver() === 'mysql') {
            return $this->findUniqueIndexNamesContainingColumnsMysql($table, $requiredColumns);
        }

        return $this->findUniqueIndexNamesContainingColumnsViaSchema($table, $requiredColumns);
    }

    /**
     * @param  list<string>  $requiredColumns
     * @return list<string>
     */
    private function findUniqueIndexNamesContainingColumnsMysql(string $table, array $requiredColumns): array
    {
        if ($requiredColumns === []) {
            return [];
        }

        $having = implode(' AND ', array_map(
            fn (string $column) => 'SUM(CASE WHEN COLUMN_NAME = ? THEN 1 ELSE 0 END) > 0',
            $requiredColumns
        ));

        $bindings = [
            Schema::getConnection()->getDatabaseName(),
            $table,
            ...$requiredColumns,
        ];

        $rows = DB::select(
            "SELECT INDEX_NAME
            FROM information_schema.statistics
            WHERE TABLE_SCHEMA = ?
              AND TABLE_NAME = ?
              AND NON_UNIQUE = 0
              AND INDEX_NAME <> 'PRIMARY'
            GROUP BY INDEX_NAME
            HAVING {$having}",
            $bindings
        );

        return array_values(array_unique(array_map(fn ($row) => $row->INDEX_NAME, $rows)));
    }

    /**
     * @param  list<string>  $requiredColumns
     * @return list<string>
     */
    private function findUniqueIndexNamesContainingColumnsViaSchema(string $table, array $requiredColumns): array
    {
        $names = [];

        foreach ($this->listUniqueIndexes($table) as $index) {
            if ($index['primary'] ?? false) {
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

    private function indexExistsByName(string $table, string $indexName): bool
    {
        if ($this->driver() === 'mysql') {
            return DB::table('information_schema.statistics')
                ->where('TABLE_SCHEMA', Schema::getConnection()->getDatabaseName())
                ->where('TABLE_NAME', $table)
                ->where('INDEX_NAME', $indexName)
                ->exists();
        }

        foreach ($this->listUniqueIndexes($table) as $index) {
            if (($index['name'] ?? '') === $indexName) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $columns
     */
    private function hasUniqueIndexOnColumns(string $table, array $columns): bool
    {
        foreach ($this->listUniqueIndexes($table) as $index) {
            if ($index['primary'] ?? false) {
                continue;
            }

            if (($index['columns'] ?? []) === $columns) {
                return true;
            }
        }

        return false;
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if ($indexName === '' || $indexName === 'PRIMARY') {
            return;
        }

        if (! $this->indexExistsByName($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($indexName) {
            $blueprint->dropIndex($indexName);
        });
    }

    /**
     * @return list<array{name: string, columns: list<string>, unique: bool, primary: bool}>
     */
    private function listUniqueIndexes(string $table): array
    {
        return Schema::getConnection()->getSchemaBuilder()->getIndexes($table);
    }

    private function driver(): string
    {
        return Schema::getConnection()->getDriverName();
    }
};
