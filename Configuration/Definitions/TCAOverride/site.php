<?php

return [
    'columns' => [
        'rootPageId' => [
            'config' => [
                'foreign_table_where' => ' AND (is_siteroot=1 OR (pid=0 AND doktype IN (1,6,7))) AND l10n_parent = 0 ORDER BY pid, sorting',
            ]
        ],
        'availableLanguages' => [
            'config' => [
                'foreign_selector' => 'language',
                'foreign_unique' => 'language',
            ]
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => '--div--;General, identifier, rootPageId, base, --div--;Language=0 Definition, --palette--;;language, --div--;Available Languages, availableLanguages, --div--;Error Handling, errorHandling',
        ],
    ],
    'palettes' => [
        'language' => [
            'label' => 'Define Language=0 parameters for this site',
            'showitem' => 'defaultLanguageLabel,defaultLanguage,--linebreak--,defaultLocale,--linebreak--,defaultFlag'
        ]
    ]
];