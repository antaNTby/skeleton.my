<?php

namespace App\Schema;

use DateTimeInterface;
use JsonException;

class Datacaster
{
    protected array $fieldTypes;

    public function __construct( array $fieldTypes )
    {
        $this->fieldTypes = $fieldTypes;
    }

    /**
     * Подготавливает данные, фильтруя по полям и приводя типы
     */
    public function prepareData( array $data ): array
    {
        if ( !empty( $this->fieldTypes ) ) {
            $data = array_intersect_key( $data, array_flip( array_keys( $this->fieldTypes ) ) );
        }

        return $this->castFields( $data );
    }

    /**
     * Приводит значения полей к нужным типам
     */
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
            'json'  => is_string( $value ) ? json_decode( $value, true ) ?? [] : $value,
            default => (string) $value,
        };
    }
}
