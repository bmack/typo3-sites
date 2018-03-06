<?php

return [
    'ctrl' => [
        'label' => 'language',
        'crdate' => 'createdon',
        'cruser_id' => 'createdby',
        'tstamp' => 'updatedon',
        'default_sortby' => 'identifier',
        'title' => 'Language Configuration for a Site',
        'typeicon_classes' => [
            'default' => 'mimetypes-x-content-domain'
        ],
        'rootLevel' => 1,
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
        'fallbacktype' => [
            'label' => 'Fallback Type',
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
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'items' => [
                    ['Default Language', 0]
                ],
                'foreign_table' => 'sys_language',
                'size' => 5,
                'min' => 0,
                'max' => 50,
            ]
        ]
    ],
    'types' => [
        '1' => [
            'showitem' => 'site, language, base, fallbacktype, fallbacks',
        ],
    ],
];