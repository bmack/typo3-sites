<?php

use TYPO3\CMS\Sites\Controller\SiteConfigurationController;

return [
    // Site configuration inline create route
    'site_configuration_inline_create' => [
        'path' => '/siteconfiguration/inline/create',
        'target' => SiteConfigurationController::class . '::newInlineChildAction'
    ],
];
