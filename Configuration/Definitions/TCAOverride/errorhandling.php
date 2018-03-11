<?php

return [
    'ctrl' => [
        'type' => 'errorHandler',
    ],
    'columns' => [
        'errorHandler' => [
            'config' => [
                'items' => [
                    [' - select an handler type - ', ''],
                    ['Fluid Template', 'Fluid'],
                    ['Show Content from Page', 'ContentFromPid'],
                    ['PHP Function', 'ClassDispatcher'],
                ],
            ],
        ],
    ],
    'types' => [
        '1' => [
            'showitem' => 'site, errorCode, errorHandler',
        ],
        'Fluid' => [
            'showitem' => 'site, errorCode, errorHandler, errorFluidTemplate',
        ],
        'ContentFromPid' => [
            'showitem' => 'site, errorCode, errorHandler, errorContentSource',
        ],
        'ClassDispatcher' => [
            'showitem' => 'site, errorCode, errorHandler, errorPhpClassFQCN',
        ],
    ],
];