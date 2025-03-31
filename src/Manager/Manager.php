<?php

/*
 * Copyright (c) 2020 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\EntityFilterBundle\Manager;

use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\System;
use HeimrichHannot\EntityFilterBundle\Helper\DatabaseHelper;

class Manager
{
    public function __construct(
        protected ContaoFramework $framework
    )
    {
    }

    public static function addField(string $table): void
    {
        $GLOBALS['TL_DCA'][$table]['config']['onload_callback']['entity_filter'] = static function () use ($table) {
            $GLOBALS['TL_CSS']['entity_filter'] = '/bundles/heimrichhannotcontaoentityfilter/css/entity_filter.css';
        };
    }

    public static function addFilterToDca(string $name, string $parentTable, string $childTable, array $multiColumnEditorOverrides = []): void
    {
        System::loadLanguageFile('tl_entity_filter');
        $dca = &$GLOBALS['TL_DCA'][$parentTable];

        $mceOptions = array_merge(
            [
                'minRowCount' => 0,
                'class' => 'entity-filter',
                'fields' => static::fields(),
                'table' => $childTable,
            ],
            $multiColumnEditorOverrides
        );

        $dca['fields'][$name] = [
            'label' => &$GLOBALS['TL_LANG'][$parentTable][$name],
            'exclude' => true,
            'inputType' => 'multiColumnEditor',
            'eval' => [
                'tl_class' => 'long clr',
                'multiColumnEditor' => $mceOptions,
            ],
            'sql' => 'blob NULL',
        ];
    }

    public function addListToDca(string $name, string $parentTable, string $filterFieldName, string $childTable, array $fields = []): void
    {
        $dca = &$GLOBALS['TL_DCA'][$parentTable];

        $dca['fields'][$name] = [
            'label' => &$GLOBALS['TL_LANG'][$parentTable][$name],
            'exclude' => true,
            'inputType' => 'listWidget',
            'eval' => [
                'listWidget' => [
                    'items_callback' => ['huh.entity_filter.backend.entity_filter', 'getItemsForDca'],
                    'header_fields_callback' => ['huh.entity_filter.backend.entity_filter', 'getHeaderFieldsForDca'],
                    'filterField' => $filterFieldName,
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
    ): void
    {
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

    public static function fields(): array
    {
        return [
            'connective'     => [
                'label'     => &$GLOBALS['TL_LANG']['tl_entity_filter']['connective'],
                'inputType' => 'select',
                'options'   => [
                    DatabaseHelper::SQL_CONDITION_OR,
                    DatabaseHelper::SQL_CONDITION_AND
                ],
                'reference' => &$GLOBALS['TL_LANG']['MSC']['connectives'],
                'eval'      => ['tl_class' => 'w50', 'groupStyle' => 'width: 65px', 'includeBlankOption' => true],
            ],
            'bracketLeft'  => [
                'label'     => &$GLOBALS['TL_LANG']['tl_entity_filter']['bracketLeft'],
                'inputType' => 'checkbox',
                'eval'      => ['tl_class' => 'w50'],
            ],
            'field'        => [
                'label'            => &$GLOBALS['TL_LANG']['tl_entity_filter']['field'],
                'inputType'        => 'select',
                'options_callback' => ['huh.entity_filter.backend.entity_filter', 'getFieldsAsOptions'],
                'eval'             => ['tl_class' => 'w50', 'chosen' => true, 'includeBlankOption' => true, 'mandatory' => true, 'groupStyle' => 'width: 350px'],
            ],
            'operator'     => [
                'label'     => &$GLOBALS['TL_LANG']['tl_entity_filter']['operator'],
                'inputType' => 'select',
                'options'   => DatabaseHelper::OPERATORS,
                'reference' => &$GLOBALS['TL_LANG']['MSC']['databaseOperators'],
                'eval'      => ['tl_class' => 'w50', 'groupStyle' => 'width: 115px'],
            ],
            'value'        => [
                'label'     => &$GLOBALS['TL_LANG']['tl_entity_filter']['value'],
                'inputType' => 'text',
                'eval'      => ['maxlength' => 255, 'tl_class' => 'w50', 'groupStyle' => 'width: 250px'],
                'sql'       => "varchar(255) NOT NULL default ''",
            ],
            'bracketRight' => [
                'label'     => &$GLOBALS['TL_LANG']['tl_entity_filter']['bracketRight'],
                'inputType' => 'checkbox',
                'eval'      => ['tl_class' => 'w50'],
            ],
        ];
    }
}
