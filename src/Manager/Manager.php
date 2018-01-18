<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0+
 */

namespace HeimrichHannot\EntityFilterBundle\Manager;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;

class Manager
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

    public static function addFilterToDca($strName, $strParentTable, $strChildTable)
    {
        \Controller::loadDataContainer($strParentTable);
        \Controller::loadDataContainer('tl_entity_filter');
        \System::loadLanguageFile('tl_entity_filter');
        $arrDca = &$GLOBALS['TL_DCA'][$strParentTable];

        $arrDca['fields'][$strName] = [
            'label' => &$GLOBALS['TL_LANG'][$strParentTable][$strName],
            'exclude' => true,
            'inputType' => 'multiColumnEditor',
            'eval' => [
                'multiColumnEditor' => [
                    'class' => 'entity-filter',
                    'fields' => $GLOBALS['TL_DCA']['tl_entity_filter']['fields'],
                    'table' => $strChildTable,
                ],
            ],
            'sql' => 'blob NULL',
        ];
    }

    public static function addListToDca($strName, $strParentTable, $strFilterFieldname, $strChildTable, $arrFields = [])
    {
        \Controller::loadDataContainer($strParentTable);
        $arrDca = &$GLOBALS['TL_DCA'][$strParentTable];

        $arrDca['fields'][$strName] = [
            'label' => &$GLOBALS['TL_LANG'][$strParentTable][$strName],
            'exclude' => true,
            'inputType' => 'listWidget',
            'eval' => [
                'listWidget' => [
                    'items_callback' => ['huh.entity_filter.backend.entity_filter', 'getItemsForDca'],
                    'header_fields_callback' => ['huh.entity_filter.backend.entity_filter', 'getHeaderFieldsForDca'],
                    'filterField' => $strFilterFieldname,
                    'fields' => $arrFields,
                    'table' => $strChildTable,
                ],
            ],
        ];
    }

    public static function addFilterCopierToDca(
        $strName,
        $strParentTable,
        $strFieldTable,
        $strFieldname,
        $arrOptionsCallback = ['HeimrichHannot\FieldValueCopier\Backend\FieldValueCopier', 'getOptions']
    ) {
        \Controller::loadDataContainer($strParentTable);
        $arrDca = &$GLOBALS['TL_DCA'][$strParentTable];

        $arrDca['fields'][$strName] = [
            'exclude' => true,
            'inputType' => 'fieldValueCopier',
            'eval' => [
                'fieldValueCopier' => [
                    'table' => $strFieldTable,
                    'field' => $strFieldname,
                    'options_callback' => $arrOptionsCallback,
                ],
            ],
        ];
    }
}
