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
            ]
        ],
        'errorHandler' => [
            'label' => 'How to handle errors',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [' - select an handler type - ', ''],
                    ['Fluid Template', 'Fluid'],
                    ['Show Content from Page','ContentFromPid'],
                    ['PHP Function',   'ClassDispatcher'],
                ],
            ]
        ],
        'errorFluidTemplate' => [
            'label' => 'Fluid Template File (use SITES:syntax if you like)',
            'config' => [
                'type' => 'input',
            ]
        ],
        'errorContentSource' => [
            'label' => 'Show Content From Page',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputLink'
            ]
        ],
        'errorPhpClassFQCN' => [
            'label' => 'ErrorHandler Class Target (FQCN)',
            'config' => [
                'type' => 'input',
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
        'ContentFromPid' => [
            'showitem' => 'errorCode, errorHandler, errorContentSource',
        ],
        'ClassDispatcher' => [
            'showitem' => 'errorCode, errorHandler, errorPhpClassFQCN',
        ],
    ],
];