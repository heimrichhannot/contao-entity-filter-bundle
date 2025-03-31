<?php

/*
 * Copyright (c) 2023 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\EntityFilterBundle\Backend;

use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Database;
use Contao\DataContainer;
use Contao\Message;
use Contao\StringUtil;
use Contao\System;
use Doctrine\DBAL\Query\QueryBuilder;
use HeimrichHannot\EntityFilterBundle\Event\ModifyEntityFilterQueryEvent;
use HeimrichHannot\EntityFilterBundle\Helper\DatabaseHelper;
use HeimrichHannot\UtilsBundle\Util\Utils;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EntityFilter
{

    public function __construct(
        protected ContaoFramework $framework,
        private EventDispatcherInterface $eventDispatcher,
        private readonly Utils $utils,
        private readonly DatabaseHelper $databaseHelper,
    )
    {
    }

    public function getHeaderFieldsForDca(array $config, $context, DataContainer $dc)
    {
        return $this->getHeaderFields($dc, $context);
    }

    public function getItemsForDca(array $config, $context, DataContainer $dc)
    {
        return $this->getItems($dc->table, $dc->field, $dc->activeRecord);
    }

    public function getItems(string $table, string $field, $activeRecord)
    {
        if (!$table || !$field) {
            return [];
        }

        Controller::loadDataContainer($table);
        System::loadLanguageFile($table);

        $listDca = $GLOBALS['TL_DCA'][$table]['fields'][$field];

        if (isset($listDca['eval']['listWidget']['table'])) {
            // build query
            $filter = $listDca['eval']['listWidget']['filterField'];

            $database = Database::getInstance();

            if (\is_array($listDca['eval']['listWidget']['fields']) && !empty($listDca['eval']['listWidget']['fields'])) {
                $existingFields = [];

                foreach ($listDca['eval']['listWidget']['fields'] as $field) {
                    if ($database->fieldExists($field, $listDca['eval']['listWidget']['table'])) {
                        $existingFields[] = $field;
                    }
                }
                $fields = implode(',', $existingFields);
            } else {
                $fields = '*';
            }

            $query = 'SELECT '.$fields.' FROM '.$listDca['eval']['listWidget']['table'];

            [$where, $values] = $this->computeSqlCondition(
                StringUtil::deserialize($activeRecord->{$filter}, true),
                $listDca['eval']['listWidget']['table']
            );

            // get items
            $items = [];

            try {
                $query = $query.($where ? ' WHERE '.$where : '');

                $event = $this->eventDispatcher->dispatch(
                    new ModifyEntityFilterQueryEvent(
                        $table,
                        $listDca['eval']['listWidget']['table'],
                        $field,
                        $activeRecord,
                        $query,
                        $where,
                        $values,
                        $listDca
                    ),
                    ModifyEntityFilterQueryEvent::NAME
                );

                $query = $event->getQuery();
                $values = $event->getValues();

                $itemObjects = Database::getInstance()->prepare($query)->execute($values);

                if ($itemObjects->numRows > 0) {
                    while ($itemObjects->next()) {
                        $items[] = $itemObjects->row();
                    }
                }
            } catch (\Exception $exception) {
                Message::addError(
                    sprintf(
                        $GLOBALS['TL_LANG']['MSC']['tl_entity_filter']['invalidSqlQuery'],
                        $query,
                        $exception->getMessage()
                    )
                );
            }

            return $items;
        }

        throw new \Exception("No 'table' set in $table.$field's eval array.");
    }

    public function countItems(string $table, string $field, $activeRecord)
    {
        if (!$table || !$field) {
            return false;
        }

        Controller::loadDataContainer($table);
        System::loadLanguageFile($table);

        $listDca = $GLOBALS['TL_DCA'][$table]['fields'][$field];

        if (isset($listDca['eval']['listWidget']['table'])) {
            // build query
            $filter = $listDca['eval']['listWidget']['filterField'];

            $query = 'SELECT COUNT(*) AS count FROM '.$listDca['eval']['listWidget']['table'];

            [$where, $values] = $this->computeSqlCondition(
                StringUtil::deserialize($activeRecord->{$filter}, true),
                $listDca['eval']['listWidget']['table']
            );

            // get items
            $items = Database::getInstance()->prepare($query.($where ? ' WHERE '.$where : ''))->execute($values);

            return $items->count;
        }

        throw new \Exception("No 'table' set in $table.$field's eval array.");
    }

    public function getHeaderFields(DataContainer $dc, $widget)
    {
        if (!($table = $dc->table) || !($strField = $dc->field)) {
            return [];
        }

        Controller::loadDataContainer($table);
        System::loadLanguageFile($table);

        $dca = $GLOBALS['TL_DCA'][$table]['fields'][$dc->field]['eval']['listWidget'];

        Controller::loadDataContainer($dca['table']);
        System::loadLanguageFile($dca['table']);

        $childDca = $GLOBALS['TL_DCA'][$dca['table']];

        if (!isset($dca['fields']) || empty($dca['fields'])) {
            throw new \Exception("No 'fields' set in $dc->table.$dc->field's eval array.");
        }

        // add field labels
        return array_combine(
            $dca['fields'],
            array_map(
                function ($val) use ($childDca) {
                    return $childDca['fields'][$val]['label'][0] ?: $val;
                },
                $dca['fields']
            )
        );
    }

    public function getFieldsAsOptions(DataContainer $dc)
    {
        if (!($table = $dc->table)) {
            return [];
        }

        Controller::loadDataContainer($table);

        if (isset($GLOBALS['TL_DCA'][$table]['fields'][$dc->field]['eval']['multiColumnEditor']['table'])) {
            $childTable = $GLOBALS['TL_DCA'][$table]['fields'][$dc->field]['eval']['multiColumnEditor']['table'];

            if (!$childTable) {
                return [];
            }

            $fields = $this->utils->dca()->getDcaFields($childTable);

            // add table to field values
            return array_combine(
                array_map(
                    function ($val) use ($childTable) {
                        return $childTable.'.'.$val;
                    },
                    array_keys($fields)
                ),
                array_values($fields)
            );
        } else {
            throw new \Exception("No 'table' set in $dc->table.$dc->field's eval array.");
        }
    }

    /**
     * @param array        $conditions   The array containing arrays of the form ['field' => 'name', 'operator' => '=', 'value' => 'value']
     * @param QueryBuilder $queryBuilder
     *
     * @return array Returns array($strCondition, $arrValues)
     */
    public function computeSqlCondition(array $conditions, string $table)
    {
        $condition = '';
        $values = [];

        // a condition can't start with a logical connective!
        if (isset($conditions[0]['connective'])) {
            $conditions[0]['connective'] = '';
        }



        foreach ($conditions as $conditionArray) {
            [$clause, $clauseValues] = $this->databaseHelper->computeCondition(
                $conditionArray['field'],
                $conditionArray['operator'],
                $conditionArray['value'],
                $table
            );

            $condition .= ' '.$conditionArray['connective'].' '.($conditionArray['bracketLeft'] ? '(' : '').$clause
                .($conditionArray['bracketRight'] ? ')' : '');

            $values = array_merge($values, $clauseValues);
        }

        return [trim($condition), $values];
    }

    /**
     * Compute conditions using doctrine QueryBuilder.
     *
     * @param array $conditions The array containing arrays of the form ['field' => 'name', 'operator' => '=', 'value' => 'value']
     *
     * @return QueryBuilder
     */
    public function computeQueryBuilderCondition(QueryBuilder $queryBuilder, array $conditions, string $table)
    {
        $condition = '';

        // a condition can't start with a logical connective!
        if (isset($conditions[0]['connective'])) {
            $conditions[0]['connective'] = '';
        }

        foreach ($conditions as $conditionArray) {
            $field = str_replace($table.'.', '', $conditionArray['field']);
            $dca = $GLOBALS['TL_DCA'][$table]['fields'][$field] ?? null;

            $where = $this->databaseHelper->composeWhereForQueryBuilder(
                $queryBuilder,
                $field,
                $conditionArray['operator'],
                $dca,
                $conditionArray['value']
            );

            $condition .= ' '.$conditionArray['connective'].' '.($conditionArray['bracketLeft'] ? '(' : '').$where
                .($conditionArray['bracketRight'] ? ')' : '');
        }

        if (!empty($condition)) {
            $queryBuilder->andWhere($condition);
        }

        return $queryBuilder;
    }
}
