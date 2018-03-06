<?php
defined('TYPO3_MODE') or die();

// Register a new main module "Site Configuration"
if (!isset($GLOBALS['TBE_MODULES']['site'])) {
    // Add the main module after the "file" main module
    $newTbeModules = [];
    foreach ($GLOBALS['TBE_MODULES'] as $k => $v) {
        $newTbeModules[$k] = $v;
        if ($k === 'file') {
            $newTbeModules['site'] = '';
        }
    }
    $GLOBALS['TBE_MODULES'] = $newTbeModules;
    unset($newTbeModules);

    $GLOBALS['TBE_MODULES']['_configuration']['site'] = [
        'access' => 'user,group',
        'name' => 'site',
        'workspaces' => 'online,custom',
        'iconIdentifier' => 'fa-map-signs',
        'labels' => 'LLL:EXT:sites/Resources/Private/Language/locallang_sites.xlf'
    ];
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
    'site',
    'configure',
    'top',
    null,
    [
        'access' => 'user,group',
        'routeTarget' => \Bmack\Sites\Controller\ConfigurationController::class . '::processRequest',
        'name' => 'site_configure',
        'workspaces' => 'online,custom',
        'iconIdentifier' => 'fa-globe',
        'labels' => 'LLL:EXT:sites/Resources/Private/Language/locallang_sitesconfigure.xlf'
    ]
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
    'site',
    'templates',
    null,
    null,
    [
        'access' => 'user,group',
        'routeTarget' => \Bmack\Sites\Controller\ConfigurationController::class . '::processRequest',
        'name' => 'site_templates',
        'workspaces' => 'online,custom',
        'iconIdentifier' => 'fa-code',
        'labels' => 'LLL:EXT:sites/Resources/Private/Language/locallang_templates.xlf'
    ]
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
    'site',
    'contenttypes',
    null,
    null,
    [
        'access' => 'user,group',
        'routeTarget' => \Bmack\Sites\Controller\ConfigurationController::class . '::processRequest',
        'name' => 'site_contenttypes',
        'workspaces' => 'online,custom',
        'iconIdentifier' => 'fa-cubes',
        'labels' => 'LLL:EXT:sites/Resources/Private/Language/locallang_contenttypes.xlf'
    ]
);
