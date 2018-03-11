<?php
return [
    'language' =>
        [
            'sys_language' =>
                [
                    'label' => 'Language',
                    'config' =>
                        [
                            'type' => 'select',
                            'foreign_table' => 'sys_language',
                            'renderType' => 'selectSingle',
                            'size' => 1,
                            'min' => 1,
                            'max' => 1,
                        ],
                ],
            'entryPoint' =>
                [
                    'label' => 'EntryPoint',
                    'config' =>
                        [
                            'renderType' => 'inputLink',
                            'type' => 'input',
                            'placeholder' => '/',
                            'size' => 20,
                        ],
                ],
            'fallbackType' =>
                [
                    'label' => 'Fallback Type',
                    'config' =>
                        [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'items' =>
                                [
                                    0 =>
                                        [
                                            0 => 'strict',
                                        ],
                                    1 =>
                                        [
                                            0 => 'fallback',
                                        ],
                                ],
                            'default' => 'strict',
                        ],
                ],
            'fallbacks' =>
                [
                    'label' => 'Fallbacks',
                    'config' =>
                        [
                            'type' => 'select',
                            'foreign_table' => 'language',
                            'renderType' => 'selectMultipleSideBySide',
                        ],
                ],
        ],
    'errorHandling' =>
        [
            'errorCode' =>
                [
                    'label' => 'Error Code',
                    'config' =>
                        [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'items' =>
                                [
                                    0 =>
                                        [
                                            0 => '4xx',
                                        ],
                                    1 =>
                                        [
                                            0 => '404',
                                        ],
                                ],
                        ],
                ],
            'errorHandler' =>
                [
                    'label' => '',
                    'config' =>
                        [
                            'renderType' => 'selectSingle',
                            'size' => 1,
                            'min' => 1,
                            'max' => 1,
                        ],
                ],
        ],
    'site' =>
        [
            'identifier' =>
                [
                    'label' => 'Identifier',
                    'config' =>
                        [
                            'type' => 'input',
                            'eval' => 'nospace,lower,trim',
                        ],
                ],
            'rootPage' =>
                [
                    'label' => 'Root Page Id',
                ],
            'entryPoint' =>
                [
                    'label' => 'EntryPoint',
                    'config' =>
                        [
                            'renderType' => 'inputLink',
                            'type' => 'input',
                            'placeholder' => '/',
                            'size' => 20,
                        ],
                ],
            'defaultLanguage' =>
                [
                    'label' => 'Definition of default language',
                ],
            'defaultLanguage_iso2LetterCode' =>
                [
                    'label' => '',
                    'config' =>
                        [
                            'type' => 'input',
                            'eval' => 'lower',
                        ],
                ],
            'defaultLanguage_label' =>
                [
                    'label' => 'Label',
                    'config' =>
                        [
                            'type' => 'input',
                        ],
                ],
            'defaultLanguage_locale' =>
                [
                    'label' => 'Locale',
                    'config' =>
                        [
                            'type' => 'input',
                        ],
                ],
            'defaultLanguage_flag' =>
                [
                    'label' => 'Flag',
                    'config' =>
                        [
                            'type' => 'input',
                            'eval' => 'lower',
                        ],
                ],
            'availableLanguages' =>
                [
                    'label' => 'Languages / Translations',
                    'config' =>
                        [
                            'type' => 'inline',
                            'foreign_table' => 'language',
                            'foreign_table_field' => 'language',
                        ],
                ],
            'errorHandling' =>
                [
                    'label' => 'Definition of error Handling',
                    'config' =>
                        [
                            'type' => 'inline',
                            'foreign_table' => 'errorhandling',
                            'foreign_table_field' => 'error_handling',
                        ],
                ],
        ],
];