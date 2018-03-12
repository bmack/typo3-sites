<?php

use TYPO3\CMS\Sites\Controller\SiteInlineAjaxController;

return [
    // Site configuration inline create route
    'site_configuration_inline_create' => [
        'path' => '/siteconfiguration/inline/create',
        'target' => SiteInlineAjaxController::class . '::newInlineChildAction'
    ],
    // Site configuration inline open existing "record" route
    'site_configuration_inline_details' => [
        'path' => '/siteconfiguration/inline/details',
        'target' => SiteInlineAjaxController::class . '::openInlineChildAction'
    ],
];
