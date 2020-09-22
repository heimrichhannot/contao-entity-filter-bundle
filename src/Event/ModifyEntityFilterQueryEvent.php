<?php

/*
 * Copyright (c) 2020 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\EntityFilterBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class ModifyEntityFilterQueryEvent extends Event
{
    const NAME = 'huh.entity_filter.event.modify_entity_filter_query_event';
    /**
     * @var string
     */
    private $table;
    /**
     * @var string
     */
    private $field;
    private $activeRecord;
    /**
     * @var string
     */
    private $query;
    /**
     * @var string
     */
    private $where;
    /**
     * @var array
     */
    private $values;
    /**
     * @var array
     */
    private $listDca;
    /**
     * @var string
     */
    private $listTable;

    public function __construct(string $table, string $listTable, string $field, $activeRecord, string $query, string $where, array $values, array $listDca)
    {
        $this->table = $table;
        $this->field = $field;
        $this->activeRecord = $activeRecord;
        $this->query = $query;
        $this->where = $where;
        $this->values = $values;
        $this->listDca = $listDca;
        $this->listTable = $listTable;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getField(): string
    {
        return $this->field;
    }

    /**
     * @return mixed
     */
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
