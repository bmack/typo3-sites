<?php

use TYPO3\CMS\Sites\Controller\FormInlineAjaxController;

return [
    // Site configuration inline create route
    'site_configuration_inline_create' => [
        'path' => '/siteconfiguration/inline/create',
        'target' => FormInlineAjaxController::class . '::newInlineChildAction'
    ],
];
