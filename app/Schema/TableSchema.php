<?php

declare ( strict_types = 1 );

namespace App\Schema;

use App\Schema\Relation\RelationInterface;
use App\Schema\Relation\RelationType;
use App\Schema\Serializer\SchemaSerializer;

final class TableSchema
{
    private string $tableName;
    private string $primaryKey;
    private array $fields;
    private array $displayedFields;
    private array $filterFields;
    private array $searchFields;
    private array $fieldTypes;
    private array $sqlTypes;
    private array $relations;

    public function __construct(
        string $tableName,
        string $primaryKey,
        array  $fields,
        array  $displayedFields,
        array  $filterFields,
        array  $searchFields,
        array  $fieldTypes,
        array  $sqlTypes,
        array  $relations
    ) {
        $this->tableName       = $tableName;
        $this->primaryKey      = $primaryKey;
        $this->fields          = $fields;
        $this->displayedFields = $displayedFields;
        $this->filterFields    = $filterFields;
        $this->searchFields    = $searchFields;
        $this->fieldTypes      = $fieldTypes;
        $this->sqlTypes        = $sqlTypes;
        $this->relations       = $relations;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function getDisplayedFields(): array
    {
        return $this->displayedFields;
    }

    public function getFilterFields(): array
    {
        return $this->filterFields;
    }

    public function getSearchFields(): array
    {
        return $this->searchFields;
    }

    public function getFieldTypes(): array
    {
        return $this->fieldTypes;
    }

    public function getSqlTypes(): array
    {
        return $this->sqlTypes;
    }

    public function getRelations(): array
    {
        return $this->relations;
    }

    public function hasRelation( string $name ): bool
    {
        return isset( $this->relations[$name] );
    }

    public function getRelation( string $name ): ?RelationInterface
    {
        return $this->relations[$name] ?? null;
    }

    public function getRelationByForeignKey( string $columnName ): ?array
    {
        foreach ( $this->relations as $name => $relation ) {
            if ( $relation->getType() === RelationType::BELONGS_TO && $relation->getForeignKey() === $columnName ) {
                return ['name' => $name, ...$relation->toArray()];
            }
        }

        return null;
    }
}
