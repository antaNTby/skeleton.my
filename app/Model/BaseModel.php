<?php

declare ( strict_types = 1 );

namespace App\Models;

use App\Utils\Schema\RelationManager;
use App\Utils\Schema\SchemaManager;
use App\Utils\Schema\TableSchema;
use flight\database\SimplePdo;
use flight\util\Collection;
use RuntimeException;

abstract class BaseModel
{
    protected string $table = '';

    public function __construct(
        protected readonly SimplePdo       $db,
        protected readonly SchemaManager   $schemaManager,
        protected readonly RelationManager $relationManager,
    ) {
    }

    public function schema(): TableSchema
    {
        if ( $this->table === '' ) {
            throw new RuntimeException( 'Table name is not defined in ' . static::class );
        }

        return $this->schemaManager->get( $this->table );
    }

    /**
     * Получить список записей с фильтрацией, поиском, сортировкой и подгрузкой связей
     *
     * @param array<string, mixed> $queryParams Параметры запроса ($_GET: filter, search, sort, dir, page, limit)
     * @param array<string> $with Имена связей для подгрузки (например: ['seller', 'buyer'])
     * @return array{items: array<Collection>, total: int, page: int, limit: int}
     */
    public function paginate(
        array $queryParams = [],
        array $with = []
    ): array {
        $schema = $this->schema();
        $pk     = $schema->primaryKey;

        // Выбираем только разрешённые для показа колонки (обязательно включая PK)
        $selectCols = $schema->displayedFields ?: $schema->fields;
        if ( !in_array( $pk, $selectCols, true ) ) {
            array_unshift( $selectCols, $pk );
        }
        $columnsSql = implode( ', ', array_map( fn( string $col ) => "`{$col}`", $selectCols ) );

        [$whereSql, $params] = $this->buildWhereClause( $schema, $queryParams );
        $orderSql            = $this->buildOrderClause( $schema, $queryParams );

        $page   = max( 1, (int) ( $queryParams['page'] ?? 1 ) );
        $limit  = max( 1, min( 100, (int) ( $queryParams['limit'] ?? 20 ) ) );
        $offset = ( $page - 1 ) * $limit;

        $total = (int) $this->db->fetchField(
            "SELECT COUNT(*) FROM `{$this->table}` {$whereSql}",
            $params
        );

        $rows = $this->db->fetchAll(
            "SELECT {$columnsSql} FROM `{$this->table}` {$whereSql} {$orderSql} LIMIT {$limit} OFFSET {$offset}",
            $params
        );

        $items = $schema->uncastMany( $rows );

        if ( !empty( $with ) && !empty( $items ) ) {
            $items = $this->loadRelations( $items, $with );
        }

        return [
            'items' => $items,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ];
    }

    /**
     * Найти одну запись по ID (все поля таблицы для просмотра/редактирования)
     */
    public function findById(
        int | string $id,
        array        $with = []
    ): ?Collection {
        $schema = $this->schema();
        $pk     = $schema->primaryKey;

        $row = $this->db->fetchRow(
            "SELECT * FROM `{$this->table}` WHERE `{$pk}` = ?",
            [$id]
        );

        if ( $row === null ) {
            return null;
        }

        /** @var Collection $item */
        $item = $schema->uncastRow( $row );

        if ( !empty( $with ) ) {
            $items = $this->loadRelations( [$item], $with );

            return $items[0];
        }

        return $item;
    }

    /**
     * Создать запись (с автоматической фильтрацией лишних полей и кастингом типов)
     */
    public function create( array $rawData ): string
    {
        $cleanData = $this->schema()->prepareData( $rawData );
        unset( $cleanData[$this->schema()->primaryKey] );

        return $this->db->insert( $this->table, $cleanData );
    }

    /**
     * Обновить запись по ID
     */
    public function update(
        int | string $id,
        array        $rawData
    ): int {
        $schema    = $this->schema();
        $pk        = $schema->primaryKey;
        $cleanData = $schema->prepareData( $rawData );
        unset( $cleanData[$pk] );

        if ( empty( $cleanData ) ) {
            return 0;
        }

        return $this->db->update( $this->table, $cleanData, "`{$pk}` = ?", [$id] );
    }

    /**
     * Удалить запись по ID
     */
    public function delete( int | string $id ): int
    {
        $pk = $this->schema()->primaryKey;

        return $this->db->delete( $this->table, "`{$pk}` = ?", [$id] );
    }

    /**
     * Жадная загрузка связей (belongsTo и hasMany) за 1 дополнительный запрос на каждую связь
     *
     * @param array<Collection> $items
     * @param array<string> $relationNames
     * @return array<Collection>
     */
    public function loadRelations(
        array $items,
        array $relationNames
    ): array {
        if ( empty( $items ) || empty( $relationNames ) ) {
            return $items;
        }

        foreach ( $relationNames as $relName ) {
            $relConfig = $this->relationManager->get( $this->table, $relName );
            if ( $relConfig === null ) {
                continue;
            }

            $type       = $relConfig['type'] ?? '';
            $targetTbl  = $relConfig['table'];
            $foreignKey = $relConfig['foreign_key'];
            $ownerKey   = $relConfig['owner_key'] ?? 'id';

            if ( $type === 'belongsTo' ) {
                // Собираем уникальные ID из дочерних записей (например, seller_id)
                $ids = [];
                foreach ( $items as $item ) {
                    $val = $item[$foreignKey] ?? null;
                    if ( $val !== null && $val !== '' ) {
                        $ids[$val] = $val;
                    }
                }

                if ( empty( $ids ) ) {
                    foreach ( $items as $item ) {
                        $item[$relName] = null;
                    }
                    continue;
                }

                $relatedRows = $this->db->fetchAll(
                    "SELECT * FROM `{$targetTbl}` WHERE `{$ownerKey}` IN (?)",
                    [array_values( $ids )]
                );

                $targetSchema = $this->schemaManager->get( $targetTbl );
                $relatedRows  = $targetSchema->uncastMany( $relatedRows );

                $indexed = [];
                foreach ( $relatedRows as $rRow ) {
                    $indexed[$rRow[$ownerKey]] = $rRow;
                }

                foreach ( $items as $item ) {
                    $fkVal          = $item[$foreignKey] ?? null;
                    $item[$relName] = ( $fkVal !== null && isset( $indexed[$fkVal] ) ) ? $indexed[$fkVal] : null;
                }
            } elseif ( $type === 'hasMany' ) {
                // Собираем уникальные PK родительских записей
                $ownerIds = [];
                foreach ( $items as $item ) {
                    $val = $item[$ownerKey] ?? null;
                    if ( $val !== null && $val !== '' ) {
                        $ownerIds[$val] = $val;
                    }
                }

                if ( empty( $ownerIds ) ) {
                    foreach ( $items as $item ) {
                        $item[$relName] = [];
                    }
                    continue;
                }

                $childRows = $this->db->fetchAll(
                    "SELECT * FROM `{$targetTbl}` WHERE `{$foreignKey}` IN (?)",
                    [array_values( $ownerIds )]
                );

                $targetSchema = $this->schemaManager->get( $targetTbl );
                $childRows    = $targetSchema->uncastMany( $childRows );

                $grouped = [];
                foreach ( $childRows as $cRow ) {
                    $grouped[$cRow[$foreignKey]][] = $cRow;
                }

                foreach ( $items as $item ) {
                    $pkVal          = $item[$ownerKey] ?? null;
                    $item[$relName] = ( $pkVal !== null && isset( $grouped[$pkVal] ) ) ? $grouped[$pkVal] : [];
                }
            }
        }

        return $items;
    }

    /**
     * Получить пары [id => display_field] для всех belongsTo связей таблицы (для <select> в формах и фильтрах)
     *
     * @return array<string, array<int|string, string>>
     */
    public function getFormSelectOptions(): array
    {
        $options = [];

        foreach ( $this->relationManager->forTable( $this->table ) as $relName => $rel ) {
            if ( ( $rel['type'] ?? '' ) !== 'belongsTo' ) {
                continue;
            }

            $targetTable  = $rel['table'];
            $ownerKey     = $rel['owner_key'] ?? 'id';
            $displayField = $rel['display_field'] ?? $ownerKey;
            $fkColumn     = $rel['foreign_key'];

            $options[$fkColumn] = $this->db->fetchPairs(
                "SELECT `{$ownerKey}`, `{$displayField}` FROM `{$targetTable}` ORDER BY `{$displayField}` ASC"
            );
        }

        return $options;
    }

    /**
     * Построение WHERE на основе белых списков filterFields и searchFields
     *
     * @return array{0: string, 1: array<int, mixed>}
     */
    private function buildWhereClause(
        TableSchema $schema,
        array       $queryParams
    ): array {
        $conditions = [];
        $params     = [];

        // 1. Точные фильтры (?filter[status]=active&filter[seller_id]=5)
        $filters = is_array( $queryParams['filter'] ?? null ) ? $queryParams['filter'] : [];
        foreach ( $filters as $field => $value ) {
            if ( $value === '' || $value === null ) {
                continue;
            }
            // Строгая проверка по настроенным чекбоксам filter_fields
            if ( !in_array( $field, $schema->filterFields, true ) ) {
                continue;
            }

            $conditions[] = "`{$field}` = ?";
            $params[]     = $value;
        }

        // 2. Полнотекстовый поиск по колонкам из search_fields (?search=ООО Ромашка)
        $search = trim( (string) ( $queryParams['search'] ?? '' ) );
        if ( $search !== '' && !empty( $schema->searchFields ) ) {
            $searchParts = [];
            foreach ( $schema->searchFields as $field ) {
                $searchParts[] = "`{$field}` LIKE ?";
                $params[]      = "%{$search}%";
            }
            $conditions[] = '(' . implode( ' OR ', $searchParts ) . ')';
        }
        $whereSql = !empty( $conditions ) ? 'WHERE ' . implode( ' AND ', $conditions ) : '';

        return [$whereSql, $params];
    }
/**
 * Построение ORDER BY на основе белого списка sortFields
 */
    private function buildOrderClause(
        TableSchema $schema,
        array       $queryParams
    ): string {
        $sortCol = (string) ( $queryParams['sort'] ?? $schema->primaryKey );
        $dir     = strtoupper( (string) ( $queryParams['dir'] ?? 'DESC' ) ) === 'ASC' ? 'ASC' : 'DESC';
// Если колонка не разрешена для сортировки — откатываемся на primaryKey
        if ( !in_array( $sortCol, $schema->sortFields, true ) ) {
            $sortCol = $schema->primaryKey;
        }

        return "ORDER BY {$sortCol} {$dir}";
    }
}
