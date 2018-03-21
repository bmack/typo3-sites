<?php

return [
    'ctrl' => [
        'label' => 'language',
        'crdate' => 'createdon',
        'cruser_id' => 'createdby',
        'tstamp' => 'updatedon',
        'title' => 'Language Configuration for a Site',
        'typeicon_classes' => [
            'default' => 'mimetypes-x-content-domain'
        ],
        'rootLevel' => 1,
        'hideTable' => true,
        'searchFields' => 'identifier'
    ],
    'interface' => [
        'showRecordFieldList' => 'identifier'
    ],
    'columns' => [
        'site' => [
            'label' => 'Belongs to site',
            'config' => [
                'type' => 'passthrough',
            ]
        ],
        'language' => [
            'label' => 'Language',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['Default Language', 0]
                ],
                'foreign_table' => 'sys_language',
                'size' => 1,
                'min' => 1,
                'max' => 1,
            ]
        ],
        'base' => [
            'label' => 'Entrypoint (either https://www.mydomain.fr/ or /fr/)',
            'config' => [
                'type' => 'input'
            ]
        ],
        'locale' => [
            'label' => 'Language Locale',
            'config' => [
                'type' => 'input'
            ]
        ],
        'typo3Language' => [
            'label' => 'Language Key for XLF files',
            'config' => [
                'type' => 'input'
            ]
        ],
        'fallbackType' => [
            'label' => 'Fallback Type',
            'onChange' => 'reload',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['No Fallback (strict)', 'strict'],
                    ['Fallback to other language', 'fallback'],
                ]
            ]
        ],
        'fallbacks' => [
            'label' => 'Fallback to other language(s) - order is important!',
            'displayCond' => 'FIELD:fallbackType:=:fallback',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'items' => [
                    ['Default Language', 0],
                ],
                'foreign_table' => 'sys_language',
                'size' => 5,
                'min' => 0,
                'max' => 50,
            ]
        ],
    ],
    'types' => [
        '1' => [
            'showitem' => 'site, language, base, locale, typo3Language, fallbackType, fallbacks',
        ],
    ],
];