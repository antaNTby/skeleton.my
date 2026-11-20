<?php

declare ( strict_types = 1 );

namespace App\Utils\Schema;

use ArrayAccess;
use DateTimeInterface;

final class TableSchema
{
    public function __construct(
        public readonly string $tableName,
        public readonly string $primaryKey = 'id',
        public readonly array  $fields = [],
        public array           $displayedFields = [],
        public array           $filterFields = [],
        public array           $searchFields = [],
        public array           $sortFields = [],
        public readonly array  $fieldTypes = [],
        public readonly array  $sqlTypes = [],
        public array           $relations = [],
    ) {
    }

    public static function fromArray( array $data ): self
    {
        $info   = $data['table_info'] ?? [];
        $fields = $data['fields'] ?? [];

        return new self(
            tableName: (string) ( $info['name'] ?? '' ),
            primaryKey: (string) ( $info['primaryKey'] ?? 'id' ),
            fields: $fields,
            displayedFields: array_values( $data['displayed_fields'] ?? $fields ),
            filterFields: array_values( $data['filter_fields'] ?? $fields ),
            searchFields: array_values( $data['search_fields'] ?? $fields ),
            sortFields: array_values( $data['sort_fields'] ?? $fields ),
            fieldTypes: $data['field_types'] ?? [],
            sqlTypes: $data['sql_types'] ?? [],
            relations: $data['relations'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'table_info'       => [
                'name'       => $this->tableName,
                'primaryKey' => $this->primaryKey,
                'fieldsList' => implode( ',', $this->fields ),
            ],
            'fields'           => $this->fields,
            'displayed_fields' => $this->displayedFields,
            'filter_fields'    => $this->filterFields,
            'search_fields'    => $this->searchFields,
            'sort_fields'      => $this->sortFields,
            'field_types'      => $this->fieldTypes,
            'sql_types'        => $this->sqlTypes,
            'relations'        => $this->relations,
        ];
    }

    public function prepareData( array $data ): array
    {
        if ( !empty( $this->fields ) ) {
            $data = array_intersect_key( $data, array_flip( $this->fields ) );
        }

        return $this->castFields( $data );
    }

    public function castFields( array $data ): array
    {
        foreach ( $data as $key => $value ) {
            if ( !isset( $this->fieldTypes[$key] ) || $value === null ) {
                continue;
            }

            if ( $value instanceof DateTimeInterface ) {
                $data[$key] = $value->format( 'Y-m-d H:i:s' );
                continue;
            }

            $data[$key] = match ( $this->fieldTypes[$key] ) {
                'int'   => (int) $value,
                'float' => (float) $value,
                'bool'  => (bool) $value,
                'json'  => is_array( $value ) || is_object( $value )
                ? json_encode( $value, JSON_UNESCAPED_UNICODE )
                : (string) $value,
                default => is_array( $value ) ? (string) reset( $value ) : (string) $value,
            };
        }

        return $data;
    }

    /**
     * Приведение типов для строки из SimplePdo (Collection, stdClass или array)
     */
    public function uncastRow( object | array | null $item ): object | array | null
    {
        if ( $item === null || empty( $this->fieldTypes ) ) {
            return $item;
        }

        $isArrayLike = is_array( $item ) || $item instanceof ArrayAccess;

        foreach ( $this->fieldTypes as $key => $targetType ) {
            if ( $isArrayLike ) {
                if ( !isset( $item[$key] ) || $item[$key] === null ) {
                    continue;
                }
                $item[$key] = $this->castValueToPhp( $item[$key], $targetType );
            } else {
                if ( !property_exists( $item, $key ) || $item->$key === null ) {
                    continue;
                }
                $item->$key = $this->castValueToPhp( $item->$key, $targetType );
            }
        }

        return $item;
    }

    public function uncastMany( array $items ): array
    {
        foreach ( $items as &$item ) {
            if ( is_object( $item ) || is_array( $item ) ) {
                $item = $this->uncastRow( $item );
            }
        }
        unset( $item );

        return $items;
    }

    private function castValueToPhp(
        mixed  $value,
        string $targetType
    ): mixed {
        return match ( $targetType ) {
            'int'   => (int) $value,
            'float' => (float) $value,
            'bool'  => (bool) $value,
            'json'  => is_string( $value ) ? ( json_decode( $value, true ) ?? [] ) : $value,
            default => (string) $value,
        };
    }

################## RELATIONS

    // Внутри class TableSchema:

    public function hasRelation( string $name ): bool
    {
        return isset( $this->relations[$name] );
    }

    public function getRelation( string $name ): ?array
    {
        return $this->relations[$name] ?? null;
    }

/**
 * Найти связь по имени колонки внешнего ключа (например, для 'company_id' вернёт конфиг связи 'company')
 */
    public function getRelationByForeignKey( string $columnName ): ?array
    {
        foreach ( $this->relations as $name => $config ) {
            if ( ( $config['type'] ?? '' ) === 'belongsTo' && ( $config['foreign_key'] ?? '' ) === $columnName ) {
                return ['name' => $name, ...$config];
            }
        }

        return null;
    }

}
