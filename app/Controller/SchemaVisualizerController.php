<?php

declare ( strict_types = 1 );

namespace App\Controller;

use App\Utils\Schema\RelationManager;
use App\Utils\Schema\SchemaManager;
use Flight;
use Smarty\Smarty;

class SchemaVisualizerController
{
    public function __construct(
        private readonly SchemaManager   $schemaManager,
        private readonly RelationManager $relationManager,
        private readonly Smarty          $smarty,
    ) {
    }

    /**
     * GET /dev/schema-map
     */
    public function index(): void
    {
        $schemas = $this->schemaManager->all();
        $tables  = [];

        foreach ( $schemas as $tableName => $schema ) {
            $relations = $this->relationManager->forTable( $tableName );
            $fkColumns = [];

            foreach ( $relations as $rel ) {
                if ( ( $rel['type'] ?? '' ) === 'belongsTo' && !empty( $rel['foreign_key'] ) ) {
                    $fkColumns[$rel['foreign_key']] = $rel['source'] ?? 'foreign_key';
                }
            }

// Внутри SchemaVisualizerController::index() при формировании $tables[$tableName]:
            $tables[$tableName] = [
                'name'         => $tableName,
                'primaryKey'   => $schema->primaryKey,
                'fields'       => $schema->fields,
                'displayedMap' => array_flip( $schema->displayedFields ),
                'filterMap'    => array_flip( $schema->filterFields ),
                'searchMap'    => array_flip( $schema->searchFields ),
                'sortMap'      => array_flip( $schema->sortFields ),
                'fieldTypes'   => $schema->fieldTypes,
                'sqlTypes'     => $schema->sqlTypes,
                'fkColumns'    => $fkColumns,
                'hasRelations' => $schema->hasRelations,
                'relations'    => $relations,
            ];

        }

        $this->smarty->assign( [
            'pageTitle'   => 'Визуализация схемы БД и связей',
            'mermaidCode' => $this->buildMermaidDiagram( $tables ),
            'tables'      => $tables,
            'totalTables' => count( $tables ),
            'synced'      => isset( $_GET['synced'] ),
        ] );

        $this->smarty->display( 'dev/schema_map.tpl.html' );
    }
    public function diagram(): void
    {
        $schemas = $this->schemaManager->all();
        $tables  = [];

        foreach ( $schemas as $tableName => $schema ) {
            $relations = $this->relationManager->forTable( $tableName );
            $fkColumns = [];

            foreach ( $relations as $rel ) {
                if ( ( $rel['type'] ?? '' ) === 'belongsTo' && !empty( $rel['foreign_key'] ) ) {
                    $fkColumns[$rel['foreign_key']] = $rel['source'] ?? 'foreign_key';
                }
            }

// Внутри SchemaVisualizerController::index() при формировании $tables[$tableName]:
            $tables[$tableName] = [
                'name'         => $tableName,
                'primaryKey'   => $schema->primaryKey,
                'fields'       => $schema->fields,
                'displayedMap' => array_flip( $schema->displayedFields ),
                'filterMap'    => array_flip( $schema->filterFields ),
                'searchMap'    => array_flip( $schema->searchFields ),
                'sortMap'      => array_flip( $schema->sortFields ),
                'fieldTypes'   => $schema->fieldTypes,
                'sqlTypes'     => $schema->sqlTypes,
                'fkColumns'    => $fkColumns,
                'hasRelations' => $schema->hasRelations,
                'relations'    => $relations,
            ];

        }

        $this->smarty->assign( [
            'pageTitle'   => 'Визуализация схемы БД и связей',
            'mermaidCode' => $this->buildMermaidDiagram( $tables ),
            'tables'      => $tables,
            'totalTables' => count( $tables ),
            'synced'      => isset( $_GET['synced'] ),
        ] );

        $this->smarty->display( 'dev/schema_diagram.tpl.html' );
    }

    /**
     * POST /dev/schema-map/sync
     */
    public function sync(): void
    {
        $this->schemaManager->syncAllTables( forceRefresh: true, preserveCustom: true );
        Flight::redirect( '/dev/schema-map?synced=1' );
    }

    /**
     * Генерирует синтаксис ER-диаграммы для Mermaid.js
     *
     * @param array<string, array<string, mixed>> $tables
     */
    private function buildMermaidDiagram( array $tables ): string
    {
        $lines = ['erDiagram'];

        // 1. Сущности и колонки
        foreach ( $tables as $tableName => $table ) {
            $lines[] = "    {$tableName} {";

            foreach ( $table['fields'] as $field ) {
                $phpType = $table['fieldTypes'][$field] ?? 'mixed';
                $marker  = '';

                if ( $field === $table['primaryKey'] ) {
                    $marker = ' PK';
                } elseif ( isset( $table['fkColumns'][$field] ) ) {
                    $marker = ' FK';
                }

                $lines[] = "        {$phpType} {$field}{$marker}";
            }

            $lines[] = '    }';
        }

        // 2. Связи (только belongsTo, чтобы линии не дублировались с hasMany)
        foreach ( $tables as $childTable => $table ) {
            foreach ( $table['relations'] as $relName => $rel ) {
                if ( ( $rel['type'] ?? '' ) !== 'belongsTo' ) {
                    continue;
                }

                $parentTable = $rel['table'] ?? '';
                if ( !isset( $tables[$parentTable] ) ) {
                    continue;
                }

                $fk        = $rel['foreign_key'] ?? '';
                $source    = $rel['source'] ?? 'foreign_key';
                $connector = ( $source === 'foreign_key' ) ? '||--o{' : '||..o{';

                $lines[] = "    {$parentTable} {$connector} {$childTable} : \"{$relName} ({$fk})\"";
            }
        }

        return implode( "\n", $lines );
    }

    /**
     * POST /dev/schema-map/update
     */
    public function update_OLD(): void
    {
        $tableName = (string) ( $_POST['table'] ?? '' );

        if ( $tableName !== '' && $this->schemaManager->has( $tableName ) ) {
            $this->schemaManager->updateFieldSettings(
                tableName: $tableName,
                displayedFields: (array) ( $_POST['displayed_fields'] ?? [] ),
                filterFields: (array) ( $_POST['filter_fields'] ?? [] ),
                searchFields: (array) ( $_POST['search_fields'] ?? [] ),
                sortFields: (array) ( $_POST['sort_fields'] ?? [] ),
            );
        }

        Flight::redirect( '/dev/schema-map?synced=1' );
    }

/**
 * POST /dev/schema-map/update
 */
    public function update(): void
    {
        $tableName = (string) ( $_POST['table'] ?? '' );
        $isAjax    = ( ( $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '' ) === 'XMLHttpRequest' )
        || str_contains( $_SERVER['HTTP_ACCEPT'] ?? '', 'application/json' );

        if ( $tableName === '' || !$this->schemaManager->has( $tableName ) ) {
            if ( $isAjax ) {
                Flight::json( ['success' => false, 'message' => 'Таблица не найдена'], 404 );

                return;
            }
            Flight::redirect( '/dev/schema-map' );

            return;
        }

        try {
            $this->schemaManager->updateFieldSettings(
                tableName: $tableName,
                displayedFields: (array) ( $_POST['displayed_fields'] ?? [] ),
                filterFields: (array) ( $_POST['filter_fields'] ?? [] ),
                searchFields: (array) ( $_POST['search_fields'] ?? [] ),
                sortFields: (array) ( $_POST['sort_fields'] ?? [] ),
            );

            if ( $isAjax ) {
                Flight::json( [
                    'success' => true,
                    'table'   => $tableName,
                    'message' => 'Настройки сохранены',
                ] );

                return;
            }

            Flight::redirect( '/dev/schema-map?synced=1' );
        } catch ( \Throwable $e ) {
            if ( $isAjax ) {
                Flight::json( ['success' => false, 'message' => $e->getMessage()], 500 );

                return;
            }
            throw $e;
        }
    }

    /**
     * POST /dev/schema-map/sync-table
     */
    public function syncTable(): void
    {
        $tableName = (string) ( $_POST['table'] ?? '' );
        $isAjax    = ( ( $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '' ) === 'XMLHttpRequest' )
        || str_contains( $_SERVER['HTTP_ACCEPT'] ?? '', 'application/json' );

        if ( $tableName === '' || !$this->schemaManager->has( $tableName ) ) {
            if ( $isAjax ) {
                Flight::json( ['success' => false, 'message' => 'Таблица не найдена'], 404 );

                return;
            }
            Flight::redirect( '/dev/schema-map' );

            return;
        }

        try {
            // Обновляем связи и структуру конкретной таблицы с сохранением ручных настроек
            $this->relationManager->syncAll( preserveCustom: true );
            $this->schemaManager->syncTable( $tableName, preserveCustom: true );

            if ( $isAjax ) {
                Flight::json( [
                    'success' => true,
                    'table'   => $tableName,
                    'message' => "Таблица {$tableName} синхронизирована",
                ] );

                return;
            }

            Flight::redirect( '/dev/schema-map?synced=1' );
        } catch ( \Throwable $e ) {
            if ( $isAjax ) {
                Flight::json( ['success' => false, 'message' => $e->getMessage()], 500 );

                return;
            }
            throw $e;
        }
    }

}
