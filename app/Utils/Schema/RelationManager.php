<?php

declare ( strict_types = 1 );

namespace App\Utils\Schema;

use flight\database\SimplePdo;
use RuntimeException;
use Throwable;

class RelationManager
{
    /** @var array<string, array<string, array<string, string>>> Карта связей: [table => [relName => config]] */
    private array $relationsMap = [];

    private string $cacheDir;

    public function __construct(
        private readonly SimplePdo $db,
        ?string                    $cacheDir = null,
        private readonly bool      $generateJson = false,
        private readonly array     $allowedTables = [],
        bool                       $autoSync = true,
    ) {
        $this->cacheDir = rtrim(
            $cacheDir ?? ( PATH_CONFIG . DIRECTORY_SEPARATOR . 'Schema' ),
            DIRECTORY_SEPARATOR
        );

        if ( $autoSync ) {
            $this->loadOrSync();
        }
    }

    /**
     * Загрузить из кэша, а если файла нет — собрать из БД
     */
    public function loadOrSync(): array
    {
        $cached = $this->loadFromCache();
        if ( $cached !== null ) {
            $this->relationsMap = $cached;

            return $this->relationsMap;
        }

        return $this->syncAll();
    }

    /**
     * Собрать все связи из БД и объединить с ручными настройками из существующего файла
     */
    public function syncAll( bool $preserveCustom = true ): array
    {
        $this->ensureCacheDir();

        $existingMap = $preserveCustom ? ( $this->loadFromCache() ?? [] ) : [];
        $autoMap     = $this->detectAllDbRelations();

        // Сливаем авто-связи с ручными (ручные имеют приоритет и не затираются)
        $merged = array_replace_recursive( $autoMap, $existingMap );

        // Фильтруем по белому списку, если он задан
        if ( !empty( $this->allowedTables ) ) {
            $merged = array_intersect_key( $merged, array_flip( $this->allowedTables ) );
        }

        $this->relationsMap = $merged;
        $this->saveToCache( $this->relationsMap );

        return $this->relationsMap;
    }

    /**
     * Получить все связи для конкретной таблицы
     *
     * @return array<string, array<string, string>>
     */
    public function forTable( string $tableName ): array
    {
        return $this->relationsMap[$tableName] ?? [];
    }

    /**
     * Проверить, есть ли у таблицы хотя бы одна связь
     */
    public function hasRelations( string $tableName ): bool
    {
        return !empty( $this->relationsMap[$tableName] );
    }

    /**
     * Получить конкретную связь таблицы по имени (например: get('orders', 'seller'))
     */
    public function get(
        string $tableName,
        string $relationName
    ): ?array {
        return $this->relationsMap[$tableName][$relationName] ?? null;
    }

    /**
     * Найти связь belongsTo по имени колонки внешнего ключа (например: 'seller_id')
     */
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
     * Полная карта связей всех таблиц
     */
    public function all(): array
    {
        return $this->relationsMap;
    }

    /**
     * Один запрос к information_schema на всю БД + генерация обратных hasMany
     */
    private function detectAllDbRelations(): array
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

        // 1. Формируем прямые связи (belongsTo)
        foreach ( $rows as $row ) {
            $childTable  = (string) $row['child_table'];
            $fk          = (string) $row['foreign_key'];
            $parentTable = (string) $row['parent_table'];
            $ownerKey    = (string) $row['owner_key'];

            $relName = str_ends_with( $fk, '_id' ) ? substr( $fk, 0, -3 ) : $parentTable;

            $map[$childTable][$relName] = [
                'type'          => 'belongsTo',
                'table'         => $parentTable,
                'foreign_key'   => $fk,
                'owner_key'     => $ownerKey,
                'display_field' => $this->guessDisplayField( $parentTable ),
            ];
        }

        // 2. Формируем обратные связи (hasMany)
        foreach ( $map as $childTable => $relations ) {
            foreach ( $relations as $relName => $config ) {
                if ( ( $config['type'] ?? '' ) !== 'belongsTo' ) {
                    continue;
                }

                $parentTable = $config['table'];
                $fk          = $config['foreign_key'];
                $ownerKey    = $config['owner_key'];

                $singularParent = match ( $parentTable ) {
                    'companies' => 'company_',
                    'users'     => 'user_',
                    default     => rtrim( $parentTable, 's' ) . '_',
                };

                $inverseName = str_starts_with( $childTable, $singularParent )
                ? substr( $childTable, strlen( $singularParent ) )
                : $childTable;

                // Если на одну таблицу ссылаются несколько ключей (seller_id, buyer_id)
                if ( isset( $map[$parentTable][$inverseName] )
                    && ( $map[$parentTable][$inverseName]['foreign_key'] ?? '' ) !== $fk
                ) {
                    $inverseName = $childTable . '_as_' . $relName;
                }

                if ( !isset( $map[$parentTable][$inverseName] ) ) {
                    $map[$parentTable][$inverseName] = [
                        'type'        => 'hasMany',
                        'table'       => $childTable,
                        'foreign_key' => $fk,
                        'owner_key'   => $ownerKey,
                    ];
                }
            }
        }

        return $map;
    }

    private function guessDisplayField( string $refTable ): string
    {
        return match ( $refTable ) {
            '__dict_opf'   => 'short_name',
            '__dict_banks' => 'bank_name',
            'users'        => 'username',
            'invoices'     => 'number',
            'orders'       => 'id',
            default        => 'name',
        };
    }

    private function loadFromCache(): ?array
    {
        $file = $this->getPhpCachePath();
        if ( !file_exists( $file ) ) {
            return null;
        }

        $data = @include $file;

        return is_array( $data ) ? $data : null;
    }

    private function saveToCache( array $data ): void
    {
        $content = "<?php\n// Global database relations map\nreturn " . var_export( $data, true ) . ";\n";
        $this->atomicWrite( $this->getPhpCachePath(), $content );

        if ( $this->generateJson ) {
            $json = json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
            if ( $json !== false ) {
                $this->atomicWrite( $this->getJsonCachePath(), $json );
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

    private function getPhpCachePath(): string
    {
        return $this->cacheDir . DIRECTORY_SEPARATOR . 'relations_map.php';
    }

    private function getJsonCachePath(): string
    {
        return $this->cacheDir . DIRECTORY_SEPARATOR . 'relations_map.json';
    }

    private function ensureCacheDir(): void
    {
        if ( !is_dir( $this->cacheDir ) && !@mkdir( $this->cacheDir, 0777, true ) && !is_dir( $this->cacheDir ) ) {
            throw new RuntimeException( "Failed to create schema cache directory: {$this->cacheDir}" );
        }
    }
}
