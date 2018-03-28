<?php

return [
    'ctrl' => [
        'label' => 'errorCode',
        'crdate' => 'createdon',
        'cruser_id' => 'createdby',
        'tstamp' => 'updatedon',
        'title' => 'Error Handling',
        'type' => 'errorHandler',
        'typeicon_column' => 'errorHandler',
        'typeicon_classes' => [
            'default' => 'default-not-found',
            'Fluid' => 'mimetypes-text-html',
            'ContentFromPid' => 'apps-pagetree-page-content-from-page',
            'ClassDispatcher' => 'mimetypes-text-php'
        ],
        'rootLevel' => 1,
        'hideTable' => true,
    ],
    'interface' => [],
    'columns' => [
        'errorCode' => [
            'label' => 'Error Status Code',
            'config' => [
                'type' => 'input',
                'eval' => 'required, trim, int',
                'range' => [
                    'lower' => 100,
                    'upper' => 599,
                ],
                'default' => 404,
                'valuePicker' => [
                    'mode' => '',
                    'items' => [
                        [ '404 (Page not found)', '404', ],
                        [ '403 (Forbidden)', '403', ],
                        [ '401 (Unauthorized)', '401', ],
                        [ '500 (Internal Server Error)', '500', ],
                        [ '503 (Service Unavailable)', '503', ],
                        [ 'any error not defined otherwise', '0', ],
                    ],
                ],
            ],
        ],
        'errorHandler' => [
            'label' => 'How to handle errors',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [' - select an handler type - ', ''],
                    ['Fluid Template', 'Fluid'],
                    ['Show Content from Page', 'Page'],
                    ['PHP Class (must implement the PageErrorHandlerInterface)', 'PHP'],
                ],
            ]
        ],
        'errorFluidTemplate' => [
            'label' => 'Fluid Template File',
            'config' => [
                'type' => 'input',
                'eval' => 'required',
            ]
        ],
        'errorContentSource' => [
            'label' => 'Show Content From Page',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputLink',
                'eval' => 'required',
            ]
        ],
        'errorPhpClassFQCN' => [
            'label' => 'ErrorHandler Class Target (FQCN)',
            'config' => [
                'type' => 'input',
                'eval' => 'required',
            ]
        ],
    ],
    'types' => [
        '1' => [
            'showitem' => 'errorCode, errorHandler',
        ],
        'Fluid' => [
            'showitem' => 'errorCode, errorHandler, errorFluidTemplate',
        ],
        'Page' => [
            'showitem' => 'errorCode, errorHandler, errorContentSource',
        ],
        'PHP' => [
            'showitem' => 'errorCode, errorHandler, errorPhpClassFQCN',
        ],
    ],
];