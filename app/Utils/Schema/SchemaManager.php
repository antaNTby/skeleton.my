<?php

declare ( strict_types = 1 );

namespace App\Utils\Schema;

use flight\database\SimplePdo;
use RuntimeException;
use Throwable;

class SchemaManager
{
    /** @var array<string, TableSchema> Коллекция загруженных схем в памяти */
    private array $schemas = [];

    private string $cacheDir;

    /**
     * @param SimplePdo $db Экземпляр подключения к БД
     * @param string|null $cacheDir Путь к папке кэша схем
     * @param bool $generateJson Генерировать ли дубликат схемы в JSON
     * @param array<string> $allowedTables Белый список таблиц (если пустой — сканируются все таблицы БД)
     * @param bool $autoSync Автоматически загружать/синхронизировать схемы при создании объекта
     */

    public function __construct(
        private readonly SimplePdo       $db,
        private readonly RelationManager $relationManager,
        ?string                          $cacheDir = null,
        private readonly bool            $generateJson = false,
        private readonly array           $allowedTables = [],
        bool                             $autoSync = true,
    ) {
        $this->cacheDir = rtrim(
            $cacheDir ?? ( CONFIG_DIR . DIRECTORY_SEPARATOR . 'Schema' ),
            DIRECTORY_SEPARATOR
        );

        if ( $autoSync ) {
            $this->syncAllTables( forceRefresh: false );
        }
    }

    /**
     * Получить схему таблицы (из памяти -> из кэша -> из БД)
     */
    public function get(
        string $tableName,
        string $primaryKey = 'id',
        array  $relations = []
    ): TableSchema {
        if ( !empty( $this->allowedTables ) && !in_array( $tableName, $this->allowedTables, true ) ) {
            throw new RuntimeException( "Access to table schema '{$tableName}' is not in the allowed list." );
        }

        if ( isset( $this->schemas[$tableName] ) ) {
            return $this->schemas[$tableName];
        }

        $schema = $this->loadFromCache( $tableName );

        if ( $schema === null ) {
            $schema = $this->syncTable( $tableName, $primaryKey, $relations, preserveCustom: true );
        } elseif ( !empty( $relations ) ) {
            $schema->relations = array_merge( $schema->relations, $relations );
        }

        $this->schemas[$tableName] = $schema;

        return $schema;
    }

    /**
     * Синхронизировать конкретную таблицу с БД и сохранить в кэш
     *
     * @param bool $preserveCustom Сохранять ли ручные правки (displayed_fields, filter_fields и др.) из существующего файла
     */
    public function syncTable(
        string $tableName,
        string $primaryKey = 'id',
        array  $relations = [],
        bool   $preserveCustom = true
    ): TableSchema {
        if ( !empty( $this->allowedTables ) && !in_array( $tableName, $this->allowedTables, true ) ) {
            throw new RuntimeException( "Table '{$tableName}' is not allowed for schema sync." );
        }

        $this->ensureCacheDir();

        if ( !preg_match( '/^[a-zA-Z0-9_]+$/', $tableName ) ) {
            throw new RuntimeException( "Invalid table name: {$tableName}" );
        }

        // Читаем старую схему до запроса к БД, чтобы сохранить ручные правки
        $existingSchema = $preserveCustom ? $this->loadFromCache( $tableName ) : null;

        $columns = $this->db->fetchAll( "DESCRIBE `{$tableName}`" );

        if ( empty( $columns ) ) {
            throw new RuntimeException( "Table '{$tableName}' not found or has no columns." );
        }

        $fields     = [];
        $fieldTypes = [];
        $sqlTypes   = [];
        $detectedPk = $primaryKey;

        foreach ( $columns as $column ) {
            $fieldName = (string) $column['Field'];
            $typeRaw   = (string) $column['Type'];

            if ( ( $column['Key'] ?? '' ) === 'PRI' ) {
                $detectedPk = $fieldName;
            }

            $fields[]               = $fieldName;
            $sqlTypes[$fieldName]   = $typeRaw;
            $fieldTypes[$fieldName] = $this->detectPhpType( $typeRaw );
        }

        // Слияние с существующими ручными настройками (удаляем только несуществующие в БД колонки)
        if ( $existingSchema !== null ) {
            $displayedFields = $this->filterExistingFields( $existingSchema->displayedFields, $fields );
            $filterFields    = $this->filterExistingFields( $existingSchema->filterFields, $fields );
            $searchFields    = $this->filterExistingFields( $existingSchema->searchFields, $fields );
            $sortFields      = $this->filterExistingFields( $existingSchema->sortFields, $fields );
            $mergedRelations = array_merge( $existingSchema->relations, $relations );
        } else {
            $displayedFields = $fields;
            $filterFields    = $fields;
            $searchFields    = $fields;
            $sortFields      = $fields;
            $mergedRelations = $relations;
        }

// Внутри syncTable() перед созданием new TableSchema(...):
        $autoRelations = $this->detectDbRelations( $tableName );

        $mergedRelations = array_replace_recursive(
            $autoRelations,
            $existingSchema?->relations ?? [],
            $relations
        );

        // $schema = new TableSchema(
        //     tableName: $tableName,
        //     primaryKey: $detectedPk,
        //     fields: $fields,
        //     displayedFields: $displayedFields,
        //     filterFields: $filterFields,
        //     searchFields: $searchFields,
        //     sortFields: $sortFields,
        //     fieldTypes: $fieldTypes,
        //     sqlTypes: $sqlTypes,
        //     relations: $mergedRelations,
        // );

        $schema = new TableSchema(
            tableName: $tableName,
            primaryKey: $detectedPk,
            fields: $fields,
            displayedFields: $displayedFields,
            filterFields: $filterFields,
            searchFields: $searchFields,
            sortFields: $sortFields,
            fieldTypes: $fieldTypes,
            sqlTypes: $sqlTypes,
            hasRelations: $this->relationManager->hasRelations( $tableName ),
        );

        $this->saveToCache( $schema );
        $this->schemas[$tableName] = $schema;

        return $schema;
    }

    /**
     * Синхронизировать все разрешённые таблицы (или всю БД, если белый список пуст)
     *
     * @param bool $forceRefresh Принудительно перечитать структуру из БД
     * @param bool $preserveCustom Сохранить ли ручные правки в списках полей при пересинхронизации
     * @return array<string, TableSchema>
     */
    public function syncAllTables(
        bool $forceRefresh = false,
        bool $preserveCustom = true
    ): array {
        $tables = !empty( $this->allowedTables )
        ? $this->allowedTables
        : $this->fetchAllDbTableNames();

        foreach ( $tables as $tableName ) {
            $tableName = (string) $tableName;
            if ( $tableName === '' ) {
                continue;
            }

            if ( $forceRefresh ) {
                unset( $this->schemas[$tableName] );
                $this->syncTable( $tableName, preserveCustom: $preserveCustom );
            } else {
                $this->get( $tableName );
            }
        }
// В syncAllTables(bool $forceRefresh = false, bool $preserveCustom = true):
        if ( $forceRefresh ) {
            $this->relationManager->syncAll( $preserveCustom );
        }

        return $this->schemas;
    }

    /**
     * Получить все загруженные в данный момент схемы
     *
     * @return array<string, TableSchema>
     */
    public function all(): array
    {
        return $this->schemas;
    }

    public function has( string $tableName ): bool
    {
        return isset( $this->schemas[$tableName] ) || file_exists( $this->getPhpCachePath( $tableName ) );
    }

    /**
     * Полностью удалить файлы кэша с диска (без сохранения ручных настроек)
     */
    public function clearCache( ?string $tableName = null ): bool
    {
        if ( $tableName !== null ) {
            unset( $this->schemas[$tableName] );
            $phpFile  = $this->getPhpCachePath( $tableName );
            $jsonFile = $this->getJsonCachePath( $tableName );

            $deleted = file_exists( $phpFile ) ? @unlink( $phpFile ) : false;
            if ( file_exists( $jsonFile ) ) {
                @unlink( $jsonFile );
            }

            return $deleted;
        }

        $this->schemas = [];
        $files         = glob( $this->cacheDir . DIRECTORY_SEPARATOR . '*_schema.{php,json}', GLOB_BRACE ) ?: [];
        foreach ( $files as $file ) {
            @unlink( $file );
        }

        return true;
    }

    /**
     * Оставляет только те поля из пользовательского списка, которые всё ещё существуют в БД.
     * Если пользователь намеренно оставил список пустым ([]), пустой массив сохранится.
     *
     * @param array<string> $customFields
     * @param array<string> $actualDbFields
     * @return array<string>
     */
    private function filterExistingFields(
        array $customFields,
        array $actualDbFields
    ): array {
        return array_values( array_intersect( $customFields, $actualDbFields ) );
    }

    /**
     * @return array<string>
     */
    private function fetchAllDbTableNames(): array
    {
        $rows   = $this->db->fetchAll( 'SHOW TABLES' );
        $tables = [];

        foreach ( $rows as $row ) {
            $data = is_array( $row ) ? $row : $row->getData();
            $name = (string) reset( $data );
            if ( $name !== '' ) {
                $tables[] = $name;
            }
        }

        return $tables;
    }

    private function loadFromCache( string $tableName ): ?TableSchema
    {
        $file = $this->getPhpCachePath( $tableName );

        if ( !file_exists( $file ) ) {
            return null;
        }

        $data = @include $file;

        return is_array( $data ) ? TableSchema::fromArray( $data ) : null;
    }

    private function saveToCache( TableSchema $schema ): void
    {
        $phpFile = $this->getPhpCachePath( $schema->tableName );
        $data    = $schema->toArray();

        $content = "<?php\n// Cache for table '{$schema->tableName}'\nreturn " . var_export( $data, true ) . ";\n";
        $this->atomicWrite( $phpFile, $content );

        if ( $this->generateJson ) {
            $jsonFile    = $this->getJsonCachePath( $schema->tableName );
            $jsonContent = json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
            if ( $jsonContent !== false ) {
                $this->atomicWrite( $jsonFile, $jsonContent );
            }
        }
    }

    private function atomicWrite(
        string $targetFile,
        string $content
    ): void {
        $tempFile = $this->cacheDir . DIRECTORY_SEPARATOR . 'schema_tmp_' . uniqid( '', true ) . '.tmp';

        if ( @file_put_contents( $tempFile, $content, LOCK_EX ) === false ) {
            throw new RuntimeException( "Failed to write temp schema file: {$tempFile}" );
        }

        try {
            if ( PHP_OS_FAMILY === 'Windows' && file_exists( $targetFile ) ) {
                @unlink( $targetFile );
            }

            if ( !@rename( $tempFile, $targetFile ) ) {
                throw new RuntimeException( "Failed to rename temp file to: {$targetFile}" );
            }
        } catch ( Throwable $e ) {
            @unlink( $tempFile );
            throw $e;
        }
    }
    private function detectPhpType( string $sqlType ): string
    {
        $sqlType = strtolower( $sqlType );
        if ( str_contains( $sqlType, 'tinyint(1)' ) || str_contains( $sqlType, 'bool' ) ) {
            return 'bool';
        }
        if ( str_contains( $sqlType, 'int' ) ) {
            return 'int';
        }
        if ( str_contains( $sqlType, 'decimal' ) || str_contains( $sqlType, 'float' ) || str_contains( $sqlType, 'double' ) ) {
            return 'float';
        }
        if ( str_contains( $sqlType, 'json' ) ) {
            return 'json';
        }

        return 'string';
    }
    private function getPhpCachePath( string $tableName ): string
    {
        return $this->cacheDir . DIRECTORY_SEPARATOR . $tableName . '_schema.php';
    }
    private function getJsonCachePath( string $tableName ): string
    {
        return $this->cacheDir . DIRECTORY_SEPARATOR . $tableName . '_schema.json';
    }
    private function ensureCacheDir(): void
    {
        if ( !is_dir( $this->cacheDir ) && !@mkdir( $this->cacheDir, 0777, true ) && !is_dir( $this->cacheDir ) ) {
            throw new RuntimeException( "Failed to create schema cache directory: {$this->cacheDir}" );
        }
    }

##########RELATIONS

    /**
     * Автоматически извлекает связи belongsTo из внешних ключей MySQL
     *
     * @return array<string, array<string, string>>
     */
    private function detectDbRelations( string $tableName ): array
    {
        $sql = '
        SELECT
            COLUMN_NAME AS foreign_key,
            REFERENCED_TABLE_NAME AS ref_table,
            REFERENCED_COLUMN_NAME AS owner_key
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND REFERENCED_TABLE_NAME IS NOT NULL
    ';

        $rows      = $this->db->fetchAll( $sql, [$tableName] );
        $relations = [];

        foreach ( $rows as $row ) {
            $fk       = (string) $row['foreign_key'];
            $refTable = (string) $row['ref_table'];
            $ownerKey = (string) $row['owner_key'];

            // Генерируем имя связи из foreign_key (например, 'company_id' -> 'company')
            $relationName = str_ends_with( $fk, '_id' )
            ? substr( $fk, 0, -3 )
            : $refTable;

            $relations[$relationName] = [
                'type'          => 'belongsTo',
                'table'         => $refTable,
                'foreign_key'   => $fk,
                'owner_key'     => $ownerKey,
                'display_field' => 'name', // значение по умолчанию для выпадающих списков
            ];
        }

        return $relations;
    }

/**
 * Вызывается в конце syncAllTables() после загрузки всех схем
 */
    private function buildInverseRelations(): void
    {
        foreach ( $this->schemas as $childTable => $childSchema ) {
            foreach ( $childSchema->relations as $relName => $relConfig ) {
                if ( ( $relConfig['type'] ?? '' ) !== 'belongsTo' ) {
                    continue;
                }

                $parentTable = $relConfig['table'] ?? '';
                $fk          = $relConfig['foreign_key'] ?? '';
                $ownerKey    = $relConfig['owner_key'] ?? 'id';

                if ( !isset( $this->schemas[$parentTable] ) ) {
                    continue;
                }

                $parentSchema = $this->schemas[$parentTable];

                                                                  // Убираем префикс родительской таблицы: 'company_contacts' -> 'contacts'
                $prefix         = rtrim( $parentTable, 's' ) . '_'; // 'companie_' -> лучше проверять явно:
                $singularParent = match ( $parentTable ) {
                    'companies' => 'company_',
                    'users'     => 'user_',
                    default     => rtrim( $parentTable, 's' ) . '_',
                };

                $inverseName = str_starts_with( $childTable, $singularParent )
                ? substr( $childTable, strlen( $singularParent ) )
                : $childTable;

                // Если на одну таблицу ссылаются несколько ключей (seller_id, buyer_id -> orders)
                if ( isset( $parentSchema->relations[$inverseName] )
                    && ( $parentSchema->relations[$inverseName]['foreign_key'] ?? '' ) !== $fk
                ) {
                    $inverseName = $childTable . '_as_' . $relName; // 'orders_as_seller', 'orders_as_buyer'
                }

                // Добавляем только если не переопределено вручную
                if ( !isset( $parentSchema->relations[$inverseName] ) ) {
                    $parentSchema->relations[$inverseName] = [
                        'type'        => 'hasMany',
                        'table'       => $childTable,
                        'foreign_key' => $fk,
                        'owner_key'   => $ownerKey,
                    ];
                }
            }
        }
    }

}
