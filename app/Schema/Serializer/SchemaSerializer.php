<?php

declare ( strict_types = 1 );

namespace App\Schema\Serializer;

use App\Schema\TableSchema;

final class SchemaSerializer
{

    public function fromArray( array $data ): TableSchema
    {
        $tableName       = $data['table_info']['name'] ?? '';
        $primaryKey      = $data['table_info']['primaryKey'] ?? '';
        $fields          = $data['fields'] ?? [];
        $displayedFields = $data['displayed_fields'] ?? [];
        $filterFields    = $data['filter_fields'] ?? [];
        $searchFields    = $data['search_fields'] ?? [];
        $fieldTypes      = $data['field_types'] ?? [];
        $sqlTypes        = $data['sql_types'] ?? [];
        $relations       = $data['relations'] ?? [];

        return new TableSchema(
            $tableName,
            $primaryKey,
            $fields,
            $displayedFields,
            $filterFields,
            $searchFields,
            $fieldTypes,
            $sqlTypes,
            $relations
        );
    }

    public function toArray( TableSchema $schema ): array
    {
        return [
            'table_info'       => [
                'name'       => $schema->getTableName(),
                'primaryKey' => $schema->getPrimaryKey(),
                'fieldsList' => implode( ',', $schema->getFields() ),
            ],
            'fields'           => $schema->getFields(),
            'displayed_fields' => $schema->getDisplayedFields(),
            'filter_fields'    => $schema->getFilterFields(),
            'search_fields'    => $schema->getSearchFields(),
            'field_types'      => $schema->getFieldTypes(),
            'sql_types'        => $schema->getSqlTypes(),
            'relations'        => $schema->getRelations(),
        ];
    }
}
