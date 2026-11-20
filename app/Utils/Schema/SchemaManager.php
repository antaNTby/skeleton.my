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

    public function __construct(
        private readonly SimplePdo $db,
        ?string                    $cacheDir = null,
        private readonly bool      $generateJson = false,
    ) {
        $this->cacheDir = rtrim(
            $cacheDir ?? ( CONFIG_DIR . DIRECTORY_SEPARATOR . 'Schema' ),
            DIRECTORY_SEPARATOR
        );

    }

    /**
     * Получить схему таблицы (из памяти -> из кэша -> из БД)
     */
    public function get(
        string $tableName,
        string $primaryKey = 'id',
        array  $relations = []
    ): TableSchema {
        if ( isset( $this->schemas[$tableName] ) ) {
            return $this->schemas[$tableName];
        }

        $schema = $this->loadFromCache( $tableName );

        if ( $schema === null ) {
            $schema = $this->syncTable( $tableName, $primaryKey, $relations );
        } elseif ( !empty( $relations ) ) {
            $schema->relations = array_merge( $schema->relations, $relations );
        }

        $this->schemas[$tableName] = $schema;

        return $schema;
    }

    /**
     * Синхронизировать конкретную таблицу с БД и сохранить в кэш
     */
    public function syncTable(
        string $tableName,
        string $primaryKey = 'id',
        array  $relations = []
    ): TableSchema {
        $this->ensureCacheDir();

        // Защита от SQL-инъекций в имени таблицы
        if ( !preg_match( '/^[a-zA-Z0-9_]+$/', $tableName ) ) {
            throw new RuntimeException( "Invalid table name: {$tableName}" );
        }

        // Нативный метод SimplePdo::fetchAll()
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

        $schema = new TableSchema(
            tableName: $tableName,
            primaryKey: $detectedPk,
            fields: $fields,
            displayedFields: $fields,
            filterFields: $fields,
            searchFields: $fields,
            sortFields: $fields,
            fieldTypes: $fieldTypes,
            sqlTypes: $sqlTypes,
            relations: $relations,
        );

        $this->saveToCache( $schema );
        $this->schemas[$tableName] = $schema;
        dd( $this );

        return $schema;
    }

    /**
     * Сканировать всю базу данных и построить коллекцию схем всех таблиц
     *
     * @return array<string, TableSchema>
     */
    public function syncAllTables( bool $forceRefresh = false ): array
    {
        // Нативный метод SimplePdo::fetchAll() (возвращает массив строк вида ['Tables_in_dbname' => 'users'])
        $rows = $this->db->fetchAll( 'SHOW TABLES' );

        foreach ( $rows as $row ) {
            $data      = is_array( $row ) ? $row : $row->getData();
            $tableName = (string) reset( $data );

            if ( $tableName === '' ) {
                continue;
            }

            if ( $forceRefresh ) {
                $this->clearCache( $tableName );
            }

            $this->get( $tableName );
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
}
