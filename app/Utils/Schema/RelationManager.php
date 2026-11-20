<?php

declare ( strict_types = 1 );

namespace App\Utils\Schema;

use flight\database\SimplePdo;
use RuntimeException;
use Throwable;

class RelationManager
{
    /** @var array<string, array<string, array<string, string>>> Жёсткие связи из FOREIGN KEY */
    private array $relationsMap = [];

    /** @var array<string, array<string, array<string, string>>> Неявные связи по суффиксу *_id */
    private array $implicitRelationsMap = [];

    private string $cacheDir;

    /** Универсальный приоритет колонок для отображения в списках/формах */
    private const DISPLAY_FIELD_CANDIDATES = [
        'name',
        'short_name',
        'title',
        'full_name',
        'bank_name',
        'username',
        'number',
        'email',
        'label',
        'code',
    ];

    /**
     * @param SimplePdo $db Подключение к БД
     * @param string|null $cacheDir Директория для хранения файлов кэша
     * @param bool $generateJson Генерировать ли дубликаты в формате JSON
     * @param array<string> $allowedTables Белый список таблиц (если пуст — используются все таблицы БД)
     * @param array<string, string> $columnAliases Карта алиасов префиксов колонок (например: ['seller' => 'companies', 'buyer' => 'companies'])
     * @param bool $autoSync Загружать/синхронизировать связи при создании объекта
     */
    public function __construct(
        private readonly SimplePdo $db,
        ?string                    $cacheDir = null,
        private readonly bool      $generateJson = false,
        private readonly array     $allowedTables = [],
        private readonly array     $columnAliases = [],
        bool                       $autoSync = true,
    ) {
        $this->cacheDir = rtrim(
            $cacheDir ?? ( CONFIG_DIR . DIRECTORY_SEPARATOR . 'Schema' ),
            DIRECTORY_SEPARATOR
        );

        if ( $autoSync ) {
            $this->loadOrSync();
        }
    }

    /**
     * Загрузить оба файла из кэша, а если хотя бы одного нет — собрать из БД
     */
    public function loadOrSync(): array
    {
        $cachedStrict   = $this->loadFromCache( $this->getStrictPhpPath() );
        $cachedImplicit = $this->loadFromCache( $this->getImplicitPhpPath() );

        if ( $cachedStrict !== null && $cachedImplicit !== null ) {
            $this->relationsMap         = $cachedStrict;
            $this->implicitRelationsMap = $cachedImplicit;

            return $this->all();
        }

        return $this->syncAll();
    }

    /**
     * Собрать жёсткие FK (relations_map.php) и неявные *_id (relations_id.php)
     */
    public function syncAll( bool $preserveCustom = true ): array
    {
        $this->ensureCacheDir();

        // Получаем карту всех таблиц и их колонок одним запросом
        $dbColumnsMap = $this->fetchAllDbColumnsMap();

        // 1. Жёсткие связи (FOREIGN KEY -> relations_map.php)
        $existingStrict = $preserveCustom ? ( $this->loadFromCache( $this->getStrictPhpPath() ) ?? [] ) : [];
        $autoStrict     = $this->detectStrictDbRelations( $dbColumnsMap );
        $mergedStrict   = array_replace_recursive( $autoStrict, $existingStrict );

        if ( !empty( $this->allowedTables ) ) {
            $mergedStrict = array_intersect_key( $mergedStrict, array_flip( $this->allowedTables ) );
        }

        $this->relationsMap = $mergedStrict;
        $this->saveToCache( $this->relationsMap, 'relations_map', 'Strict database relations (FOREIGN KEY)' );

        // 2. Неявные связи (*_id без FK -> relations_id.php)
        $existingImplicit = $preserveCustom ? ( $this->loadFromCache( $this->getImplicitPhpPath() ) ?? [] ) : [];
        $autoImplicit     = $this->detectImplicitIdRelations( $dbColumnsMap, $this->relationsMap );
        $mergedImplicit   = array_replace_recursive( $autoImplicit, $existingImplicit );

        if ( !empty( $this->allowedTables ) ) {
            $mergedImplicit = array_intersect_key( $mergedImplicit, array_flip( $this->allowedTables ) );
        }

        $this->implicitRelationsMap = $mergedImplicit;
        $this->saveToCache( $this->implicitRelationsMap, 'relations_id', 'Implicit database relations (*_id convention)' );

        return $this->all();
    }

    /**
     * Получить все связи для таблицы (жёсткие + неявные, приоритет у жёстких)
     *
     * @return array<string, array<string, string>>
     */
    public function forTable( string $tableName ): array
    {
        return array_merge(
            $this->implicitRelationsMap[$tableName] ?? [],
            $this->relationsMap[$tableName] ?? []
        );
    }

    /**
     * Получить только жёсткие связи таблицы (из relations_map.php)
     */
    public function strictForTable( string $tableName ): array
    {
        return $this->relationsMap[$tableName] ?? [];
    }

    /**
     * Получить только неявные *_id связи таблицы (из relations_id.php)
     */
    public function implicitForTable( string $tableName ): array
    {
        return $this->implicitRelationsMap[$tableName] ?? [];
    }

    public function hasRelations( string $tableName ): bool
    {
        return !empty( $this->relationsMap[$tableName] ) || !empty( $this->implicitRelationsMap[$tableName] );
    }

    public function get(
        string $tableName,
        string $relationName
    ): ?array {
        return $this->forTable( $tableName )[$relationName] ?? null;
    }

    public function findByForeignKey(
        string $tableName,
        string $columnName
    ): ?array {
        foreach ( $this->forTable( $tableName ) as $name => $config ) {
            if ( ( $config['type'] ?? '' ) === 'belongsTo' && ( $config['foreign_key'] ?? '' ) === $columnName ) {
                return ['name' => $name, ...$config];
            }
        }

        return null;
    }

    /**
     * Полная объединённая карта связей всех таблиц
     */
    public function all(): array
    {
        return array_replace_recursive( $this->implicitRelationsMap, $this->relationsMap );
    }

    /**
     * 1. Поиск реальных FOREIGN KEY из information_schema.KEY_COLUMN_USAGE
     *
     * @param array<string, array<string>> $dbColumnsMap
     */
    private function detectStrictDbRelations( array $dbColumnsMap ): array
    {
        $sql = '
            SELECT
                TABLE_NAME AS child_table,
                COLUMN_NAME AS foreign_key,
                REFERENCED_TABLE_NAME AS parent_table,
                REFERENCED_COLUMN_NAME AS owner_key
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND REFERENCED_TABLE_NAME IS NOT NULL
            ORDER BY TABLE_NAME, COLUMN_NAME
        ';

        $rows = $this->db->fetchAll( $sql );
        $map  = [];

        foreach ( $rows as $row ) {
            $childTable  = (string) $row['child_table'];
            $fk          = (string) $row['foreign_key'];
            $parentTable = (string) $row['parent_table'];
            $ownerKey    = (string) $row['owner_key'];

            $relName       = str_ends_with( $fk, '_id' ) ? substr( $fk, 0, -3 ) : $parentTable;
            $parentColumns = $dbColumnsMap[$parentTable] ?? [];

            $map[$childTable][$relName] = [
                'type'          => 'belongsTo',
                'source'        => 'foreign_key',
                'table'         => $parentTable,
                'foreign_key'   => $fk,
                'owner_key'     => $ownerKey,
                'display_field' => $this->detectDisplayField( $parentColumns, $ownerKey ),
            ];
        }

        return $this->appendInverseHasMany( $map, 'foreign_key' );
    }

    /**
     * 2. Поиск неявных связей по колонкам *_id, для которых не задан FOREIGN KEY
     *
     * @param array<string, array<string>> $dbColumnsMap
     */
    private function detectImplicitIdRelations(
        array $dbColumnsMap,
        array $strictMap
    ): array {
        $implicitMap = [];

        foreach ( $dbColumnsMap as $childTable => $columns ) {
            if ( !empty( $this->allowedTables ) && !in_array( $childTable, $this->allowedTables, true ) ) {
                continue;
            }

            foreach ( $columns as $columnName ) {
                if ( $columnName === 'id' || !str_ends_with( $columnName, '_id' ) ) {
                    continue;
                }

                // Пропускаем, если для этой колонки уже есть реальный FOREIGN KEY
                if ( $this->hasStrictColumn( $strictMap, $childTable, $columnName ) ) {
                    continue;
                }

                $prefix      = substr( $columnName, 0, -3 );
                $targetTable = $this->resolveTargetTable( $prefix, $dbColumnsMap );

                if ( $targetTable === null ) {
                    continue;
                }

                if ( !empty( $this->allowedTables ) && !in_array( $targetTable, $this->allowedTables, true ) ) {
                    continue;
                }

                $targetColumns = $dbColumnsMap[$targetTable] ?? [];

                $implicitMap[$childTable][$prefix] = [
                    'type'          => 'belongsTo',
                    'source'        => 'implicit_id',
                    'table'         => $targetTable,
                    'foreign_key'   => $columnName,
                    'owner_key'     => 'id',
                    'display_field' => $this->detectDisplayField( $targetColumns, 'id' ),
                ];
            }
        }

        return $this->appendInverseHasMany( $implicitMap, 'implicit_id' );
    }

    /**
     * Добавляет обратные связи hasMany в переданную карту
     */
    private function appendInverseHasMany(
        array  $map,
        string $source
    ): array {
        foreach ( $map as $childTable => $relations ) {
            foreach ( $relations as $relName => $config ) {
                if ( ( $config['type'] ?? '' ) !== 'belongsTo' ) {
                    continue;
                }

                $parentTable = $config['table'];
                $fk          = $config['foreign_key'];
                $ownerKey    = $config['owner_key'];

                $inverseName = $this->buildInverseRelationName( $childTable, $parentTable );

                if ( isset( $map[$parentTable][$inverseName] )
                    && ( $map[$parentTable][$inverseName]['foreign_key'] ?? '' ) !== $fk
                ) {
                    $inverseName = $childTable . 'as' . $relName;
                }
                if ( !isset( $map[$parentTable][$inverseName] ) ) {
                    $map[$parentTable][$inverseName] = [
                        'type'        => 'hasMany',
                        'source'      => $source,
                        'table'       => $childTable,
                        'foreign_key' => $fk,
                        'owner_key'   => $ownerKey,
                    ];
                }
            }
        }

        return $map;
    }
/**
 * Формирует имя обратной связи (например: 'company_contacts' + 'companies' -> 'contacts')
 */
    private function buildInverseRelationName(
        string $childTable,
        string $parentTable
    ): string {
// Убираем префикс справочника, если есть

// Стало:
        $cleanParent = preg_replace( '/^_+dict_/', '', $parentTable ) ?? $parentTable;

// Преобразуем множественное число родителя в префикс единственного числа (companies -> company_, users -> user_)
        $singularPrefix = str_ends_with( $cleanParent, 'ies' )
        ? substr( $cleanParent, 0, -3 ) . 'y_'
        : rtrim( $cleanParent, 's' ) . '_';

        return str_starts_with( $childTable, $singularPrefix )
        ? substr( $childTable, strlen( $singularPrefix ) )
        : $childTable;
    }
/**
 * Пытается сопоставить префикс колонки ('order', 'seller', 'opf') с реальной таблицей БД
 *
 * @param array<string, array> $dbColumnsMap
 */
    private function resolveTargetTable(
        string $prefix,
        array  $dbColumnsMap
    ): ?string {
// 1. Проверка по переданным в конструктор алиасам (например: seller -> companies)
        if ( isset( $this->columnAliases[$prefix] ) ) {
            $aliased = $this->columnAliases[$prefix];

            return isset( $dbColumnsMap[$aliased] ) ? $aliased : null;
        }
// 2. Кандидаты по стандартным соглашениям именования таблиц
        $candidates = [
            $prefix . 's',           // order -> orders, user -> users
            $prefix . 'es',          // status -> statuses
            $prefix,                 // точное совпадение
            '_dict' . $prefix,       // справочники в единственном числе (__dict_opf)
            '_dict' . $prefix . 's', // справочники во множественном числе (__dict_banks)
            'dict' . $prefix,
            'dict' . $prefix . 's',
        ];
        if ( str_ends_with( $prefix, 'y' ) ) {
            $candidates[] = substr( $prefix, 0, -1 ) . 'ies'; // company -> companies
        }
        foreach ( $candidates as $candidate ) {
            if ( isset( $dbColumnsMap[$candidate] ) ) {
                return $candidate;
            }
        }

        return null;
    }
/**
 * Подбирает отображаемое поле на основе реальных колонок целевой таблицы
 *
 * @param array $columns
 */
    private function detectDisplayField(
        array  $columns,
        string $fallback = 'id'
    ): string {
        foreach ( self::DISPLAY_FIELD_CANDIDATES as $candidate ) {
            if ( in_array( $candidate, $columns, true ) ) {
                return $candidate;
            }
        }

        return $fallback;
    }
/**
 * Загружает карту [table_name => ['col1', 'col2', ...]] для всей текущей БД одним запросом
 *
 * @return array<string, array>
 */
    private function fetchAllDbColumnsMap(): array
    {
        $sql = '
SELECT TABLE_NAME, COLUMN_NAME
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
ORDER BY TABLE_NAME, ORDINAL_POSITION
';
        $rows = $this->db->fetchAll( $sql );
        $map  = [];
        foreach ( $rows as $row ) {
            $table         = (string) $row['TABLE_NAME'];
            $col           = (string) $row['COLUMN_NAME'];
            $map[$table][] = $col;
        }

        return $map;
    }
    private function hasStrictColumn(
        array  $strictMap,
        string $tableName,
        string $columnName
    ): bool {
        foreach ( $strictMap[$tableName] ?? [] as $config ) {
            if ( ( $config['type'] ?? '' ) === 'belongsTo' && ( $config['foreign_key'] ?? '' ) === $columnName ) {
                return true;
            }
        }

        return false;
    }
    private function loadFromCache( string $file ): ?array
    {
        if ( !file_exists( $file ) ) {
            return null;
        }
        $data = @include $file;

        return is_array( $data ) ? $data : null;
    }
    private function saveToCache(
        array  $data,
        string $baseName,
        string $comment
    ): void {
        $phpFile = $this->cacheDir . DIRECTORY_SEPARATOR . $baseName . '.php';
        $content = "<?php\n// {$comment}\nreturn " . var_export( $data, true ) . ";\n";
        $this->atomicWrite( $phpFile, $content );
        if ( $this->generateJson ) {
            $jsonFile = $this->cacheDir . DIRECTORY_SEPARATOR . $baseName . '.json';
            $json     = json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
            if ( $json !== false ) {
                $this->atomicWrite( $jsonFile, $json );
            }
        }
    }
    private function atomicWrite(
        string $targetFile,
        string $content
    ): void {
        $tempFile = $this->cacheDir . DIRECTORY_SEPARATOR . 'rel_tmp_' . uniqid( '', true ) . '.tmp';
        if ( @file_put_contents( $tempFile, $content, LOCK_EX ) === false ) {
            throw new RuntimeException( "Failed to write temp relations file: {$tempFile}" );
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
    private function getStrictPhpPath(): string
    {
        return $this->cacheDir . DIRECTORY_SEPARATOR . 'relations_map.php';
    }
    private function getImplicitPhpPath(): string
    {
        return $this->cacheDir . DIRECTORY_SEPARATOR . 'relations_id.php';
    }
    private function ensureCacheDir(): void
    {
        if ( !is_dir( $this->cacheDir ) && !@mkdir( $this->cacheDir, 0777, true ) && !is_dir( $this->cacheDir ) ) {
            throw new RuntimeException( "Failed to create schema cache directory: {$this->cacheDir}" );
        }
    }
}
