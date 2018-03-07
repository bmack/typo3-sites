<?php

use TYPO3\CMS\Sites\Controller\SiteConfigurationController;

return [
    // Create new inline element
    'ajax_record_inline_create' => [
        'path' => '/siteconfiguration/inline/create',
        'target' => SiteConfigurationController::class . '::foo'
    ],
];
