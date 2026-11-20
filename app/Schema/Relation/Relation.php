<?php

declare ( strict_types = 1 );

namespace App\Schema\Relation;

final class Relation implements RelationInterface
{
    private string $name;
    private string $type;
    private string $foreignKey;
    private string $targetTable;

    public function __construct(
        string $name,
        string $type,
        string $foreignKey,
        string $targetTable
    ) {
        $this->name        = $name;
        $this->type        = $type;
        $this->foreignKey  = $foreignKey;
        $this->targetTable = $targetTable;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getForeignKey(): string
    {
        return $this->foreignKey;
    }

    public function getTargetTable(): string
    {
        return $this->targetTable;
    }

    public function hasRelations(): bool
    {
        return !empty( $this->relations );
    }
}
