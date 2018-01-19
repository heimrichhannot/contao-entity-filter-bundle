<?php

$GLOBALS['TL_DCA']['tl_entity_filter'] = [
    'fields' => [
        'connective'     => [
            'label'     => &$GLOBALS['TL_LANG']['tl_entity_filter']['connective'],
            'inputType' => 'select',
            'options'   => [
                \HeimrichHannot\UtilsBundle\Database\DatabaseUtil::SQL_CONDITION_OR,
                \HeimrichHannot\UtilsBundle\Database\DatabaseUtil::SQL_CONDITION_AND
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
            'options'   => \HeimrichHannot\UtilsBundle\Database\DatabaseUtil::OPERATORS,
            'reference' => &$GLOBALS['TL_LANG']['MSC']['operators'],
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
    ],
];