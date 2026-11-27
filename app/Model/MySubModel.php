<?php

declare ( strict_types = 1 );

namespace App\Model;

use App\Utils\Schema\RelationManager;
use App\Utils\Schema\SchemaManager;
use App\Utils\Schema\TableSchema;
use Flight;
use flight\ActiveRecord;

class MySubModel extends ActiveRecord
{
    protected TableSchema $tableSchema;
    protected SchemaManager $schemaManager;
    protected RelationManager $relationManager;

    public function __construct(
                         $databaseConnection,
        string           $table_name,
        ?SchemaManager   $schemaManager = null,
        ?RelationManager $relationManager = null,
    ) {
        // Если менеджеры не переданы явно — достаём из контейнера Flight
        $this->schemaManager   = $schemaManager ?? Flight::make( SchemaManager::class );
        $this->relationManager = $relationManager ?? Flight::make( RelationManager::class );

        $this->tableSchema = $this->schemaManager->get( $table_name );

        // Конвертируем наши связи из RelationManager в формат flight\ActiveRecord
        $arRelations = $this->buildActiveRecordRelations( $table_name );

        parent::__construct( $databaseConnection, $table_name, [
            'primaryKey' => $this->tableSchema->primaryKey,
            'relations'  => $arRelations,
        ] );
    }

    public function schema(): TableSchema
    {
        return $this->tableSchema;
    }

    /**
     * Динамическое создание экземпляра связанной модели (нужно для работы with() и ленивой загрузки в ActiveRecord)
     */
    public function createRelatedModel( string $targetTable ): self
    {
        return new self(
            $this->databaseConnection,
            $targetTable,
            $this->schemaManager,
            $this->relationManager
        );
    }

    /**
     * Безопасная загрузка данных из $_POST (отсекает лишние поля и приводит типы)
     */
    public function fill( array $rawData ): static
    {
        $cleanData = $this->tableSchema->prepareData( $rawData );
        unset( $cleanData[$this->tableSchema->primaryKey] );

        return $this->copyFrom( $cleanData );
    }

    /**
     * Применить фильтрацию, поиск и сортировку из $_GET по настроенным чекбоксам схемы
     */
    public function applyQuery(
        array $queryParams = [],
        bool  $onlyDisplayed = true
    ): static
    {
        $schema = $this->tableSchema;
        $pk     = $schema->primaryKey;

        // 1. Выбираем только отмеченные в визуализаторе колонки (обязательно оставляя PK)
        if ( $onlyDisplayed && !empty( $schema->displayedFields ) ) {
            $cols = $schema->displayedFields;
            if ( !in_array( $pk, $cols, true ) ) {
                array_unshift( $cols, $pk );
            }
            $this->select( ...$cols );
        }

        // 2. Точные фильтры (?filter[status]=active&filter[seller_id]=5) по filterFields
        $filters = is_array( $queryParams['filter'] ?? null ) ? $queryParams['filter'] : [];
        foreach ( $filters as $field => $value ) {
            if ( $value === '' || $value === null ) {
                continue;
            }
            if ( !in_array( $field, $schema->filterFields, true ) ) {
                continue;
            }
            $this->eq( $field, $value );
        }

        // 3. Поиск (?search=...) по searchFields
        $search = trim( (string) ( $queryParams['search'] ?? '' ) );
        if ( $search !== '' && !empty( $schema->searchFields ) ) {
            $this->startWrap();
            foreach ( $schema->searchFields as $index => $field ) {
                if ( $index === 0 ) {
                    $this->like( $field, "%{$search}%" );
                } else {
                    $this->or()->like( $field, "%{$search}%" );
                }
            }
            $this->endWrap();
        }

        // 4. Безопасная сортировка (?sort=id&dir=ASC) по sortFields
        $sortCol = (string) ( $queryParams['sort'] ?? $pk );
        $dir     = strtoupper( (string) ( $queryParams['dir'] ?? 'ASC' ) ) === 'DESC' ? 'DESC' : 'ASC';

        if ( !in_array( $sortCol, $schema->sortFields, true ) ) {
            $sortCol = $pk;
        }
        $this->orderByColumn( $sortCol, $dir );

        return $this;
    }

    /**
     * Событие ActiveRecord: приводим типы после загрузки одной записи (find)
     */
    protected function afterFind( self $self ): void
    {
        $self->tableSchema->uncastRow( $self );
    }

    /**
     * Событие ActiveRecord: приводим типы после загрузки списка (findAll)
     *
     * @param array<int, self> $results
     */
    protected function afterFindAll( array $results ): void
    {
        foreach ( $results as $record ) {
            $record->tableSchema->uncastRow( $record );
        }
    }

    /**
     * Получить списки [id => display_field] для <select> в формах и фильтрах
     *
     * @return array<string, array<int|string, string>>
     */
    public function getSelectOptions(): array
    {
        $options = [];

        foreach ( $this->relationManager->forTable( $this->table ) as $rel ) {
            if ( ( $rel['type'] ?? '' ) !== 'belongsTo' ) {
                continue;
            }

            $targetTable  = $rel['table'];
            $ownerKey     = $rel['owner_key'] ?? 'id';
            $displayField = $rel['display_field'] ?? $ownerKey;
            $fkColumn     = $rel['foreign_key'];

            $options[$fkColumn] = $this->databaseConnection->fetchPairs(
                "SELECT `{$ownerKey}`, `{$displayField}` FROM `{$targetTable}` ORDER BY `{$displayField}` ASC"
            );
        }

        return $options;
    }

    /**
     * Преобразует конфигурацию из RelationManager в формат flight\ActiveRecord
     */
    private function buildActiveRecordRelations( string $tableName ): array
    {
        $arRelations = [];

        foreach ( $this->relationManager->forTable( $tableName ) as $relName => $rel ) {
            $arType = match ( $rel['type'] ?? '' ) {
                'belongsTo' => self::BELONGS_TO,
                'hasMany'   => self::HAS_MANY,
                'hasOne'    => self::HAS_ONE,
                default     => null,
            };

            if ( $arType === null ) {
                continue;
            }

            // В flight\ActiveRecord второй элемент — имя класса модели, а третий — foreign_key
            $arRelations[$relName] = [
                $arType,
                static::class,
                $rel['foreign_key'],
                ['from' => $rel['table']], // указываем целевую таблицу, так как класс модели общий (MySubModel)
            ];
        }

        return $arRelations;
    }
}
