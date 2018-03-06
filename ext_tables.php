<?php
defined('TYPO3_MODE') or die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
    'site',
    'configuration',
    'top',
    '',
    [
        'routeTarget' => \TYPO3\CMS\Sites\Controller\SiteConfigurationController::class . '::handleRequest',
        'access' => 'admin',
        'name' => 'site_configuration',
        'iconIdentifier' => 'fa-map-signs',
        'labels' => 'LLL:EXT:sites/Resources/Private/Language/locallang_module_siteconfiguration.xlf'
    ]
);