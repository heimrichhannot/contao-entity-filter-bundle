<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\EntityFilterBundle\Backend;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\System;

class EntityFilter extends \Backend
{
    /** @var ContaoFrameworkInterface */
    protected $framework;

    /**
     * Constructor.
     *
     * @param ContaoFrameworkInterface $framework
     */
    public function __construct(ContaoFrameworkInterface $framework)
    {
        $this->framework = $framework;
    }

    public function getHeaderFieldsForDca($arrConfig, $objContext, $objDca)
    {
        return $this->getHeaderFields($objDca, $objContext);
    }

    public function getItemsForDca($arrConfig, $objContext, $objDca)
    {
        return $this->getItems($objDca->table, $objDca->field, $objDca->activeRecord);
    }

    public function getItems($strTable, $strField, $objActiveRecord)
    {
        if (!$strTable || !$strField) {
            return [];
        }

        \Controller::loadDataContainer($strTable);
        \System::loadLanguageFile($strTable);

        $arrListDca = $GLOBALS['TL_DCA'][$strTable]['fields'][$strField];

        if (isset($arrListDca['eval']['listWidget']['table'])) {
            // build query
            $strFilter = $arrListDca['eval']['listWidget']['filterField'];

            if (is_array($arrListDca['eval']['listWidget']['fields']) && !empty($arrListDca['eval']['listWidget']['fields'])) {
                $strFields = implode(',', $arrListDca['eval']['listWidget']['fields']);
            } else {
                $strFields = '*';
            }

            $strQuery = 'SELECT '.$strFields.' FROM '.$arrListDca['eval']['listWidget']['table'];
            list($strWhere, $arrValues) = $this->computeSqlCondition(
                deserialize($objActiveRecord->{$strFilter}, true),
                $arrListDca['eval']['listWidget']['table']
            );

            // get items
            $arrItems = [];

            try {
                $strQuery = $strQuery.($strWhere ? ' WHERE '.$strWhere : '');

                $objItems = \Database::getInstance()->prepare($strQuery)->execute($arrValues);
                if ($objItems->numRows > 0) {
                    while ($objItems->next()) {
                        $arrItems[] = $objItems->row();
                    }
                }
            } catch (\Exception $objException) {
                \Message::addError(sprintf($GLOBALS['TL_LANG']['MSC']['tl_entity_filter']['invalidSqlQuery'], $strQuery,
                                           $objException->getMessage()));
            }

            return $arrItems;
        }

        throw new \Exception("No 'table' set in $strTable.$strField's eval array.");
    }

    public function countItems($strTable, $strField, $objActiveRecord)
    {
        if (!$strTable || !$strField) {
            return false;
        }

        \Controller::loadDataContainer($strTable);
        \System::loadLanguageFile($strTable);

        $arrListDca = $GLOBALS['TL_DCA'][$strTable]['fields'][$strField];

        if (isset($arrListDca['eval']['listWidget']['table'])) {
            // build query
            $strFilter = $arrListDca['eval']['listWidget']['filterField'];

            $strQuery = 'SELECT COUNT(*) AS count FROM '.$arrListDca['eval']['listWidget']['table'];
            list($strWhere, $arrValues) = $this->computeSqlCondition(
                deserialize($objActiveRecord->{$strFilter}, true),
                $arrListDca['eval']['listWidget']['table']
            );

            // get items
            $objItems = \Database::getInstance()->prepare($strQuery.($strWhere ? ' WHERE '.$strWhere : ''))->execute($arrValues);

            return $objItems->count;
        }

        throw new \Exception("No 'table' set in $strTable.$strField's eval array.");
    }

    public function getHeaderFields(\DataContainer $objDc, $objWidget)
    {
        if (!($strTable = $objDc->table) || !($strField = $objDc->field)) {
            return [];
        }

        \Controller::loadDataContainer($strTable);

        $arrDca = $GLOBALS['TL_DCA'][$strTable]['fields'][$objDc->field]['eval']['listWidget'];
        $arrChildDca = $GLOBALS['TL_DCA'][$arrDca['table']];

        if (!isset($arrDca['fields']) || empty($arrDca['fields'])) {
            throw new \Exception("No 'fields' set in $objDc->table.$objDc->field's eval array.");
        }

        // add field labels
        return array_combine(
            $arrDca['fields'],
            array_map(
                function ($val) use ($arrChildDca) {
                    return $arrChildDca['fields'][$val]['label'][0] ?: $val;
                },
                $arrDca['fields']
            )
        );
    }

    public function getFieldsAsOptions(\DataContainer $objDc)
    {
        if (!($strTable = $objDc->table)) {
            return [];
        }

        \Controller::loadDataContainer($strTable);

        if (isset($GLOBALS['TL_DCA'][$strTable]['fields'][$objDc->field]['eval']['multiColumnEditor']['table'])) {
            $strChildTable = $GLOBALS['TL_DCA'][$strTable]['fields'][$objDc->field]['eval']['multiColumnEditor']['table'];

            if (!$strChildTable) {
                return [];
            }

            $arrFields = System::getContainer()->get('huh.utils.dca')->getFields($strChildTable);

            // add table to field values
            return array_combine(
                array_map(
                    function ($val) use ($strChildTable) {
                        return $strChildTable.'.'.$val;
                    },
                    array_keys($arrFields)
                ),
                array_values($arrFields)
            );
        } else {
            throw new \Exception("No 'table' set in $objDc->table.$objDc->field's eval array.");
        }
    }

    /**
     * @param array $arrConditions The array containing arrays of the form ['field' => 'name', 'operator' => '=', 'value' => 'value']
     *
     * @return array Returns array($strCondition, $arrValues)
     */
    public function computeSqlCondition(array $arrConditions, $strTable)
    {
        $strCondition = '';
        $arrValues = [];

        // a condition can't start with a logical connective!
        if (isset($arrCondition[0]['connective'])) {
            $arrCondition[0]['connective'] = '';
        }

        foreach ($arrConditions as $arrCondition) {
            list($strClause, $arrClauseValues) = QueryHelper::computeCondition($arrCondition['field'], $arrCondition['operator'], $arrCondition['value'], $strTable);
            $strCondition .= ' '.$arrCondition['connective'].' '.($arrCondition['bracketLeft'] ? '(' : '').$strClause.($arrCondition['bracketRight'] ? ')' : '');
            $arrValues = array_merge($arrValues, $arrClauseValues);
        }

        return [trim($strCondition), $arrValues];
    }
}
