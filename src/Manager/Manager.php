<?php

/*
 * Copyright (c) 2020 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\EntityFilterBundle\Manager;

use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\System;

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

    public static function addFilterToDca(string $name, string $parentTable, string $childTable, array $multiColumnEditorOverrides = [])
    {
        Controller::loadDataContainer($parentTable);
        Controller::loadDataContainer('tl_entity_filter');
        System::loadLanguageFile('tl_entity_filter');
        $dca = &$GLOBALS['TL_DCA'][$parentTable];

        $mceOptions = array_merge(
            [
                'class' => 'entity-filter',
                'fields' => $GLOBALS['TL_DCA']['tl_entity_filter']['fields'],
                'table' => $childTable,
            ],
            $multiColumnEditorOverrides
        );

        $dca['fields'][$name] = [
            'label' => &$GLOBALS['TL_LANG'][$parentTable][$name],
            'exclude' => true,
            'inputType' => 'multiColumnEditor',
            'eval' => [
                'multiColumnEditor' => $mceOptions,
            ],
            'sql' => 'blob NULL',
        ];
    }

    public function addListToDca(string $name, string $parentTable, string $filterFieldname, string $childTable, array $fields = [])
    {
        Controller::loadDataContainer($parentTable);
        $dca = &$GLOBALS['TL_DCA'][$parentTable];

        $dca['fields'][$name] = [
            'label' => &$GLOBALS['TL_LANG'][$parentTable][$name],
            'exclude' => true,
            'inputType' => 'listWidget',
            'eval' => [
                'listWidget' => [
                    'items_callback' => ['huh.entity_filter.backend.entity_filter', 'getItemsForDca'],
                    'header_fields_callback' => ['huh.entity_filter.backend.entity_filter', 'getHeaderFieldsForDca'],
                    'filterField' => $filterFieldname,
                    'fields' => $fields,
                    'table' => $childTable,
                ],
            ],
        ];
    }

    public function addFilterCopierToDca(
        string $name,
        string $parentTable,
        string $fieldTable,
        string $fieldname,
        array $optionsCallback = ['huh.field_value_copier.util.field_value_copier_util', 'getOptions'],
        array $config = []
    ) {
        Controller::loadDataContainer($parentTable);
        $dca = &$GLOBALS['TL_DCA'][$parentTable];

        $dca['fields'][$name] = [
            'exclude' => true,
            'inputType' => 'fieldValueCopier',
            'eval' => [
                'fieldValueCopier' => [
                    'table' => $fieldTable,
                    'field' => $fieldname,
                    'options_callback' => $optionsCallback,
                    'config' => $config,
                ],
            ],
        ];
    }
}
