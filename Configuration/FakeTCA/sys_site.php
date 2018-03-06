<?php

return [
    'ctrl' => [
        'label' => 'identifier',
        'crdate' => 'createdon',
        'cruser_id' => 'createdby',
        'tstamp' => 'updatedon',
        'default_sortby' => 'identifier',
        'title' => 'Site Configuration',
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
        'identifier' => [
            'label' => 'Site Identifier',
            'config' => [
                'type' => 'input',
                'size' => 35,
                'max' => 255,
                'eval' => 'required,unique,lower,trim',
            ]
        ],
        'rootpageid' => [
            'label' => 'Root Page ID (You must create a page with a site root flag)',
            'config' => [
                'type' => 'select',
                'items' => [
                    ['please choose a page', '']
                ],
                'renderType' => 'selectSingle',
                'foreign_table' => 'pages',
                'foreign_table_where' => ' AND (is_siteroot=1 OR (pid=0 AND doktype IN (1,6,7))) ORDER BY pid, sorting',
                'size' => 1,
                'min' => 1,
                'max' => 1,
            ]
        ],
        'base' => [
            'label' => 'Entry point (can be https://www.mydomain/ or just /, if it is just / you can not rely on TYPO3 creating full URLs)',
            'config' => [
                'type' => 'input',
            ]
        ],
        'defaultLanguage' => [
            'label' => 'Language key (used for XLF files)',
            'config' => [
                'type' => 'input',
                'size' => 4,
                'placeholder' => 'fr',
            ]
        ],
        'defaultLanguageLabel' => [
            'label' => 'Language label (e.g. "French")',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'placeholder' => 'French',
            ]
        ],
        'defaultLanguageLocale' => [
            'label' => 'Locale (e.g. "fr_FR.UTF-8")',
            'config' => [
                'type' => 'input',
                'size' => 8,
                'placeholder' => 'fr_FR.UTF-8',
            ]
        ],
        'defaultLanguageFlag' => $GLOBALS['TCA']['sys_language']['columns']['flag'],
        'availableLanguages' => [
            'label' => 'Available Languages for this site',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'sys_sitelanguage',
                'foreign_table_field' => 'site',
                'foreign_selector' => 'language',
                'foreign_unique' => 'language',
            ]
        ]
    ],
    'types' => [
        '0' => [
            'showitem' => '--div--;General, identifier, rootpageid, base, --div--;Language=0 Definition, --palette--;;language, --div--;Available Languages, available_languages',
        ],
    ],
    'palettes' => [
        'language' => [
            'label' => 'Define Language=0 parameters for this site',
            'showitem' => 'defaultLanguageLabel,defaultLanguage,--linebreak--,default_locale,--linebreak--,default_flag'
        ]
    ]
];