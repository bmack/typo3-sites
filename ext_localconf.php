<?php
defined('TYPO3_MODE') or die();
// Register FontAwesome for icon submodule
$iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
$iconRegistry->registerIcon(
    'fa-map-signs',
    \TYPO3\CMS\Core\Imaging\IconProvider\FontawesomeIconProvider::class,
    [ 'name' => 'map-signs' ]
);
$iconRegistry->registerIcon(
    'fa-globe',
    \TYPO3\CMS\Core\Imaging\IconProvider\FontawesomeIconProvider::class,
    [ 'name' => 'globe' ]
);
$iconRegistry->registerIcon(
    'fa-cubes',
    \TYPO3\CMS\Core\Imaging\IconProvider\FontawesomeIconProvider::class,
    [ 'name' => 'cubes' ]
);
$iconRegistry->registerIcon(
    'fa-code',
    \TYPO3\CMS\Core\Imaging\IconProvider\FontawesomeIconProvider::class,
    [ 'name' => 'code' ]
);

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['viewOnClickClass'][] = TYPO3\CMS\Sites\Routing\BackendUriGenerationHook::class;