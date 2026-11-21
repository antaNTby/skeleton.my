<?php

namespace App\Database\Schema;

use App\Database\TableSchema;

class SchemaManager
{
    /**
     * @var array<string, Table>
     */
    private array $tables = [];

    /**
     * @var array<string, array<string, string>> $inverseRelations
     */
    private array $inverseRelations = [];

    /**
     * @var array<string, string> $cache
     */
    private array $cache = [];

    /**
     * @var array<string, string> $relationCache
     */
    private array $relationCache = [];

    /**
     * @var array<string, array<string, string>> $relations
     */
    private array $relations = [];

    public function __construct()
    {
        $this->buildInverseRelations();
    }

    /**
     * Синхронизирует все таблицы с БД.
     */
    public function syncAllTables(): void
    {
        $this->loadTablesFromDatabase();
        $this->buildRelations();
        $this->buildInverseRelations();
    }

    /**
     * Загружает таблицы из БД.
     */
    private function loadTablesFromDatabase(): void
    {
        // Здесь должен быть код, который загружает таблицы из БД
        // Пример:
        // $tables = $this->database->getAllTables();
        // foreach ($tables as $name => $definition) {
        //     $this->tables[$name] = new Table($name, $definition);
        // }
    }

    /**
     * Строит отношения на основе схем.
     */
    private function buildRelations(): void
    {
        foreach ( $this->tables as $table ) {
            foreach ( $table->getColumns() as $column ) {
                if ( $column->isForeignKey() ) {
                    $this->relations[$table->getName()][$column->getName()] = $column->getReferencedTable();
                }
            }
        }
    }

    /**
     * Строит обратные отношения (hasMany).
     */
    private function buildInverseRelations(): void
    {
        foreach ( $this->relations as $table => $columns ) {
            foreach ( $columns as $column => $referencedTable ) {
                if ( isset( $this->relations[$referencedTable] ) ) {
                    $this->inverseRelations[$referencedTable][$table] = $column;
                }
            }
        }
    }

    /**
     * Возвращает таблицу по имени.
     */
    public function getTable( string $name ): ?Table
    {
        return $this->tables[$name] ?? null;
    }

    /**
     * Возвращает все таблицы.
     */
    public function getTables(): array
    {
        return $this->tables;
    }

    /**
     * Возвращает отношения.
     */
    public function getRelations(): array
    {
        return $this->relations;
    }

    /**
     * Возвращает обратные отношения.
     */
    public function getInverseRelations(): array
    {
        return $this->inverseRelations;
    }

    /**
     * Возвращает кэш.
     */
    public function getCache(): array
    {
        return $this->cache;
    }

    /**
     * Возвращает кэш отношений.
     */
    public function getRelationCache(): array
    {
        return $this->relationCache;
    }

    /**
     * Устанавливает кэш.
     */
    public function setCache( array $cache ): void
    {
        $this->cache = $cache;
    }

    /**
     * Устанавливает кэш отношений.
     */
    public function setRelationCache( array $relationCache ): void
    {
        $this->relationCache = $relationCache;
    }
}
