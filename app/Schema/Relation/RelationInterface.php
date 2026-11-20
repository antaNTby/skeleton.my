<?php

declare ( strict_types = 1 );

namespace App\Schema\Relation;

interface RelationInterface
{
    public function getName(): string;

    public function getType(): string;

    public function getForeignKey(): string;

    public function getTargetTable(): string;
}
