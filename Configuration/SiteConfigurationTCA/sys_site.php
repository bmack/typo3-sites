<?php

return [
    'ctrl' => [
        'label' => 'identifier',
        'title' => 'Site Configuration',
        'typeicon_classes' => [
            'default' => 'mimetypes-x-content-domain'
        ],
        'rootLevel' => 1,
        'hideTable' => true,
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
        'rootPageId' => [
            'label' => 'Root Page ID (You must create a page with a site root flag)',
            'config' => [
                'type' => 'select',
                'readOnly' => true,
                'renderType' => 'selectSingle',
                'foreign_table' => 'pages',
                'foreign_table_where' => ' AND (is_siteroot=1 OR (pid=0 AND doktype IN (1,6,7))) AND l10n_parent = 0 ORDER BY pid, sorting',
            ]
        ],
        'base' => [
            'label' => 'Entry point (can be https://www.mydomain/ or just /, if it is just / you can not rely on TYPO3 creating full URLs)',
            'config' => [
                'type' => 'input',
            ]
        ],
        'languages' => [
            'label' => 'Available Languages for this site',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'sys_site_language',
                'foreign_selector' => 'language',
                'foreign_unique' => 'language',
                'size' => 4,
            ]
        ],
        'errorHandling' => [
            'label' => 'Error Handling',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'sys_site_errorhandling',
            ]
        ]
    ],
    'types' => [
        '0' => [
            'showitem' => '--div--;General, identifier, rootPageId, base, --div--;Languages, languages, --div--;Error Handling, errorHandling',
        ],
    ],
];