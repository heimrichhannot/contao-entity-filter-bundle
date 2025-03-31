<?php

/*
 * Copyright (c) 2023 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\EntityFilterBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class ModifyEntityFilterQueryEvent extends Event
{
    public const NAME = 'huh.entity_filter.event.modify_entity_filter_query_event';

    public function __construct(
        private readonly string $table,
        private readonly string $listTable,
        private readonly string $field,
        private $activeRecord,
        private string $query,
        private readonly string $where,
        private array $values,
        private readonly array $listDca,
    ) {
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getActiveRecord()
    {
        return $this->activeRecord;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function getWhere(): string
    {
        return $this->where;
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function getListDca(): array
    {
        return $this->listDca;
    }

    public function setQuery(string $query): void
    {
        $this->query = $query;
    }

    public function setValues(array $values): void
    {
        $this->values = $values;
    }

    public function getListTable(): string
    {
        return $this->listTable;
    }
}
